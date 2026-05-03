# Havenzen Deployment Plan

This plan keeps the current XAMPP workflow working while giving the project a clean path to a hosted deployment later.

## Current Local Baseline

- PHP app currently runs from `C:\xampp\htdocs\havenzen`.
- MySQL database name is `havenzen_db`.
- Runtime config lives in `config.php`, which should stay local and should not be committed.
- Public setup should start from `config.example.php`.
- GPS tracker posts to `api/gps_tracking.php` with `GPS_TRACKING_API_KEY`.
- Thermal printing depends on a Windows printer queue matching `THERMAL_PRINTER_NAME`.

## Phase 1: Stabilize The Repo

1. Keep `config.php`, SQL dumps, runtime folders, and local ESP32 credential sketches out of Git.
2. Commit source files, migrations, public assets, and documentation only.
3. Store deployment-sensitive values as environment variables:
   - `DB_HOST`
   - `DB_USER`
   - `DB_PASSWORD`
   - `DB_NAME`
   - `GOOGLE_MAPS_API_KEY`
   - `GPS_TRACKING_API_KEY`
   - `THERMAL_PRINTER_NAME`
4. Keep database changes in `migrations/` so production can be updated repeatably.

## Phase 2: Prepare A Production Server

Recommended starting stack:

- Ubuntu VPS or managed PHP hosting
- Nginx or Apache
- PHP 8.2+
- MySQL 8+ or MariaDB 10.6+
- HTTPS certificate through Let's Encrypt

Server setup tasks:

1. Create a production database and database user with limited permissions.
2. Upload the app into the web root or deploy through Git.
3. Copy `config.example.php` to `config.php` on the server.
4. Set production credentials and API keys.
5. Import the baseline schema, then run pending migrations.
6. Lock down writable folders so only required upload/runtime directories are writable.

## Phase 3: GPS Tracker Deployment

For production GPS tracking, update the ESP32 sketch to post to the hosted domain:

```text
https://your-domain.example/api/gps_tracking.php
```

Tracker requirements:

- ESP32 must have internet access through Wi-Fi or hotspot.
- `API_KEY` in the sketch must match `GPS_TRACKING_API_KEY` on the server.
- The server must allow HTTPS POST requests to `api/gps_tracking.php`.

## Phase 4: Printing Strategy

Direct thermal printing is local-device dependent. For deployment, choose one:

1. Keep printing on the office laptop where the printer driver and queue are installed.
2. Add a small local print bridge that receives jobs from the hosted app and sends them to the Windows printer.
3. Generate printable browser receipts and let the operator print from the browser.

The current direct print path works best for a local XAMPP setup.

## Phase 5: Launch Checklist

- Confirm all admin accounts use strong passwords.
- Rotate the GPS API key before going live.
- Restrict Google Maps API key by domain.
- Confirm duplicate vehicle plate migration has run.
- Test booking creation, baggage fee totals, receipt preview, direct print, GPS tracking, login, and admin search.
- Set up regular database backups.
- Add basic monitoring for PHP errors and failed GPS posts.

## Future Improvements

- Move all secrets to `.env` with a small config loader.
- Add deployment scripts for migrations.
- Add role-based audit logging for critical admin actions.
- Add a production-safe local print bridge.
- Add CI checks for PHP syntax before every push.
