# SchoolMS Setup Guide

This guide is aligned with the current SchoolMS codebase.

## Prerequisites

- PHP 8.0+
- MySQL 5.7+ or MariaDB 10.3+
- Apache or Nginx

Optional:

- Git
- Command line access for patching and lint checks

## 1. Clone or Copy Project

Place the project in your web root (example: `c:/xampp/htdocs/SchoolMS`).

## 2. Create and Seed Database

```bash
mysql -u root -p < database/schema.sql
```

The schema creates database `schoolms`, all required tables, and the default admin account.

## 3. Configure Environment Variables

The application reads runtime values using `getenv(...)`.

Required values:

```text
DB_HOST=localhost
DB_USER=root
DB_PASS=1234
DB_NAME=schoolms
BASE_URL=/SchoolMS/
```

You can use `.env.example` as a template, but ensure values are available to PHP runtime through your server environment strategy.

Examples:

- Apache (vhost): `SetEnv DB_HOST localhost`
- Nginx + PHP-FPM: `fastcgi_param DB_HOST localhost;`

## 4. Writable Directories

Ensure these directories are writable by the web server user:

- `logs/`
- `uploads/`

Linux/macOS:

```bash
chmod 755 logs/
chmod 755 uploads/
```

Windows:

- Grant Modify permission on both folders to the web server account.

## 5. Open Application

- Login page: `/SchoolMS/login.php`
- Default admin:
  - Username: `admin`
  - Password: `admin123`

Change the default password immediately after first login.

## Existing Database Upgrade (Patch Flow)

If your database was created from an older version, run patches in order:

```bash
mysql -u root -p schoolms < database/patches/001_account_requests.sql
mysql -u root -p schoolms < database/patches/002_add_deleted_status.sql
mysql -u root -p schoolms < database/patches/003_add_student_role.sql
mysql -u root -p schoolms < database/patches/004_fees_paid_amount.sql
mysql -u root -p schoolms < database/patches/005_parent_student_links.sql
```

Patch summary:

- `001`: account request workflow.
- `002`: deleted status support for students/staff.
- `003`: add student role in users role enum.
- `004`: partial fee payment support via `paid_amount`.
- `005`: parent-to-student mapping table.

## First-Time Admin Configuration

After login:

1. Open `Settings -> System Settings`.
2. Configure school name, phone, address, academic year, logo.
3. Review `Settings -> Account Requests` for pending onboarding.
4. Use `Settings -> Parent Links` to map parent users to students.

## Account Onboarding Workflow

1. User submits request at `/SchoolMS/request_account.php`.
2. Admin reviews at `/SchoolMS/settings/account_requests.php`.
3. Admin approves and assigns one role: teacher, staff, parent, or student.
4. For parent role, admin links parent to student records in `/SchoolMS/settings/parent_links.php`.

## Smoke Validation Checklist

- [ ] Login works with admin credentials
- [ ] Dashboard loads without errors
- [ ] Students list page opens
- [ ] Attendance mark/report pages open
- [ ] Fees list and collect flow works
- [ ] Results report works
- [ ] Transport and hostel lists load
- [ ] Account request approve/reject works
- [ ] Parent link create/remove works

## Utility Commands

Generate password hash:

```bash
php src/utilities/hash.php "new_password"
```

Check role gates in files:

```bash
php src/utilities/check_roles.php
```

Inject role checks in selected endpoints (CLI only):

```bash
php patch_endpoints.php
```

## Troubleshooting

Database connection failed:

- Verify MySQL/MariaDB is running.
- Verify `DB_*` variables are visible to PHP runtime.
- Verify schema has been imported.

Invalid request token:

- Refresh and retry.
- Clear browser cookies/session and login again.

Fee module warns about legacy schema:

- Run `database/patches/004_fees_paid_amount.sql`.

Parent sees no records:

- Confirm user role is `parent` and status is active.
- Confirm active links exist in `parent_student_links`.

## Deployment Security Baseline

- [ ] Change default admin password
- [ ] Use HTTPS
- [ ] Keep `display_errors` disabled in production
- [ ] Restrict filesystem write permissions
- [ ] Backup database regularly
- [ ] Restrict direct web access to sensitive directories (`config`, `includes`, `src`)

## Last Updated

2026-04-19
