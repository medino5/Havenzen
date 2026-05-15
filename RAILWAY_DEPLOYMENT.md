# Railway Deployment

This repo is prepared for a Railway demo deployment using Docker, PHP 8.3, and Railway MySQL.

## Railway Services

Create one Railway project with two services:

1. **MySQL** service from Railway's MySQL template.
2. **Havenzen Web** service from the GitHub repo.

Railway should detect the root `Dockerfile` and build the PHP web service from it.

## Web Service Variables

Set these variables on the Havenzen Web service:

```text
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_USER=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}
DB_NAME=${{MySQL.MYSQLDATABASE}}
GOOGLE_MAPS_API_KEY=your-google-maps-key
GPS_TRACKING_API_KEY=make-a-long-random-secret
THERMAL_PRINTER_NAME=Xprinter XP-58IIH
```

If your MySQL service has a different name in Railway, replace `MySQL` in the reference variables with that service name.

## Database Setup

Import the schema into the Railway MySQL service:

```text
database/schema.sql
```

For a demo with existing local data, export a sanitized local SQL dump and import it into Railway after the schema. Do not commit production/customer dumps to Git.

Create the first admin account after import. You can generate a password hash locally with:

```powershell
& 'C:\xampp\php\php.exe' admin\generate_hash.php "temporary-password"
```

Then insert a user row with role `admin` and a matching row in `admins`.

## Health Check

Railway uses:

```text
/health.php
```

This endpoint intentionally does not connect to MySQL, so deployments can boot and report app-level health before database troubleshooting.

## Demo Limits

- The Docker image uses PHP's built-in web server. This is fine for a Railway demo, but move to Apache/Nginx or a managed PHP host for production.
- Uploaded files are not persistent in this demo container unless you add Railway storage or move uploads to object storage.
- Direct thermal printing remains local-office functionality. A hosted Railway app cannot directly access the USB printer attached to the office laptop.
- Phone GPS tracking every 5 seconds will consume database writes. Monitor Railway credits during testing.
