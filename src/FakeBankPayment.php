<?php

declare(strict_types=1);

namespace Pohoda\Tools;

/**
 * Create a synthetic bank receipt (BV) in Pohoda via mServer.
 *
 * Used to exercise pohoda-changes-api pollers (mServer lastChanges / MSSQL DatSave).
 */
class FakeBankPayment extends \Ease\Sand
{
    /**
     * @param array<string, mixed> $overrides optional bank record fields
     *
     * @return array{ok: bool, id: ?int, details: mixed, record: array<string, mixed>}
     */
    public function create(array $overrides = []): array
    {
        $now = time();
        $amount = (string) ($overrides['amount']
            ?? \Ease\Shared::cfg('POHODA_BANK_AMOUNT', sprintf('%.2f', random_int(100, 9999) / 100)));
        $account = (string) ($overrides['account']
            ?? \Ease\Shared::cfg('POHODA_BANK_ACCOUNT', 'KB'));
        $symVar = (string) ($overrides['symVar'] ?? (string) $now);
        $text = (string) ($overrides['text']
            ?? \Ease\Shared::cfg('POHODA_BANK_TEXT', 'Fake payment from pohoda-tools '.$now));

        $record = [
            'account' => $account,
            'bankType' => $overrides['bankType'] ?? 'receipt',
            'datePayment' => $overrides['datePayment'] ?? date('Y-m-d'),
            'dateStatement' => $overrides['dateStatement'] ?? date('Y-m-d'),
            'intNote' => $overrides['intNote'] ?? 'pohoda-tools fake-bank',
            'note' => $overrides['note'] ?? 'Generated for changes-api poller test',
            'statementNumber' => [
                'statementNumber' => (string) ($overrides['statementNumber'] ?? substr((string) $now, -8)),
            ],
            'symVar' => $symVar,
            'symSpec' => (string) ($overrides['symSpec'] ?? '23'),
            'text' => $text,
            'homeCurrency' => [
                'priceNone' => $amount,
            ],
            'paymentAccount' => $overrides['paymentAccount'] ?? [
                'accountNo' => (string) ($overrides['accountNo'] ?? '123456789'),
                'bankCode' => (string) ($overrides['bankCode'] ?? '0300'),
            ],
        ];

        if (isset($overrides['partnerIdentity']) && is_array($overrides['partnerIdentity'])) {
            $record['partnerIdentity'] = $overrides['partnerIdentity'];
        }

        $opts = [
            'url' => (string) \Ease\Shared::cfg('POHODA_URL'),
            'user' => (string) \Ease\Shared::cfg('POHODA_USERNAME'),
            'password' => (string) \Ease\Shared::cfg('POHODA_PASSWORD'),
            'debug' => (bool) \Ease\Shared::cfg('APP_DEBUG', false),
        ];
        $ico = (string) \Ease\Shared::cfg('POHODA_ICO', '');

        if ($ico !== '') {
            $opts['ico'] = $ico;
        }

        $banker = new \mServer\Bank($record, $opts);

        try {
            $banker->addToPohoda();
            $ok = $banker->commit();
        } catch (\Throwable $e) {
            $this->addStatusMessage('Bank create exception: '.$e->getMessage(), 'error');

            return [
                'ok' => false,
                'id' => null,
                'details' => null,
                'record' => $record,
                'error' => $e->getMessage(),
            ];
        }

        $details = $banker->response->producedDetails ?? null;
        $id = null;

        if (is_array($details)) {
            $id = isset($details['id']) ? (int) $details['id'] : null;

            if ($id === null && isset($details[0]['id'])) {
                $id = (int) $details[0]['id'];
            }
        }

        if ($ok) {
            $this->addStatusMessage(
                sprintf('Created bank %s #%s amount=%s symVar=%s', $record['bankType'], $id ?? '?', $amount, $symVar),
                'success',
            );
        } else {
            $this->addStatusMessage('Bank create failed: '.json_encode($banker->response ?? null), 'error');
        }

        return [
            'ok' => $ok,
            'id' => $id,
            'details' => $details,
            'record' => $record,
        ];
    }
}
