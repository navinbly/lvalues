# Phase 1 Hardening Operations

Set `CI_ENV` to `development`, `testing`, `staging`, or `production`. Each environment selects a separate default database. Staging and production must provide `LVALUES_DB_USER`, `LVALUES_DB_PASSWORD`, and `LVALUES_ENCRYPTION_KEY`; override database names with `LVALUES_DB_NAME`.

## Scheduled commands

Run these from the project root with the PHP CLI:

```text
php index.php ops backup
php index.php ops restore_drill
php index.php ops monitor
```

Recommended schedule:

- Backup every 6 hours.
- Restore drill daily in staging and weekly in production.
- Monitor every 5 minutes.

Backups are stored outside the public upload tree under `storage/backups` by default. Each SQL backup has a SHA-256 manifest. Restore drills create an isolated temporary database, import the backup, verify that tables exist, and remove the temporary database.

## Health checks

- `/health/live` is a lightweight uptime probe.
- `/health/ready` verifies database connectivity, writable runtime directories, and hardening tables.
- `/health/providers` checks configured SMTP, WhatsApp, and payment provider endpoints. Protect it with `LVALUES_HEALTH_TOKEN` and the `X-Health-Token` request header.

Configure provider URLs with `LVALUES_SMTP_HEALTH_URL`, `LVALUES_WHATSAPP_HEALTH_URL`, and `LVALUES_PAYMENT_HEALTH_URL`. Configure alert delivery with `LVALUES_ALERT_EMAIL`.

## Upload scanning

Set `LVALUES_MALWARE_SCANNER` to a scanner command such as `clamscan --no-summary`. Staging and production reject uploads when a scanner is not configured. Development performs built-in signature and executable-content checks.
