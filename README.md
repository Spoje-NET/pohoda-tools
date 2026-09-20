# Pohoda Tools

CLI helpers for Stormware Pohoda (mServer), analogous to [AbraFlexi-Tools](https://github.com/VitexSoftware/AbraFlexi-Tools).

Primary use today: generate **fake bank receipts** so `pohoda-changes-api` pollers (mServer `lastChanges` or MSSQL `DatSave`) can pick them up and cache documents.

## Quick start

```bash
cp .env.example .env
# edit POHODA_URL / POHODA_ICO / credentials / POHODA_BANK_ACCOUNT
composer install
bin/pohoda-fake-bank --amount=42.00 --json
```

Then on the changes-api host:

```bash
pohoda-changes-poller
# expect a new bank row in changes_cache / record_cache
```

## Commands

| Command | Purpose |
|---------|---------|
| `bin/pohoda-fake-bank` | Insert one bank receipt (`bankType=receipt`) via mServer |

Options: `--amount=`, `--account=KB`, `--sym-var=`, `--text=`, `--bank-type=receipt|expense`, `--json`

## Environment

| Variable | Meaning |
|----------|---------|
| `POHODA_URL` | mServer base URL (e.g. `http://172.18.100.4:40000`) |
| `POHODA_ICO` | Accounting unit IČO |
| `POHODA_USERNAME` / `POHODA_PASSWORD` | mServer login |
| `POHODA_BANK_ACCOUNT` | Pohoda bank account `ids` (default `KB` on demo unit) |

On the demo mServer (`172.18.100.4:40000` / IČO `12345678`), prefer **`KB`** — `CSOB` may reject receipts with currency mismatch (error 111).

## License

MIT
