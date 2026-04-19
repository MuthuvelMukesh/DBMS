# SchoolMS Quick Reference

This file is a fast lookup for day-to-day development and operations.

## Documentation

- Project structure: `docs/PROJECT_STRUCTURE.md`
- Setup and configuration: `docs/SETUP.md`
- Database schema: `database/schema.sql`
- ER diagram: `docs/er/chen_notation_er_diagram.md`
- Tests and smoke guidance: `tests/README.md`

## Runtime Configuration

Main config files:

```text
config/app.php
config/database.php
```

Required environment variables:

```text
DB_HOST
DB_USER
DB_PASS
DB_NAME
BASE_URL
```

## Current Structure at a Glance

```text
dashboard.php, login.php, logout.php, profile.php, request_account.php
attendance/ classes/ exams/ fees/ hostel/ notices/ results/ settings/
students/ staff/ transport/
includes/ config/ database/ docs/ public/ src/ tests/ uploads/ logs/
```

## Default Admin

```text
Username: admin
Password: admin123
```

Change this immediately after first login.

## Role Matrix

| Role | Effective Access |
|------|------------------|
| admin | Full system, notices, and settings |
| teacher | students, classes, attendance, exams, results |
| staff | transport, hostel |
| parent | fees, attendance, results, transport, hostel (linked students) |
| student | fees, attendance, results, exams, transport, hostel (own records) |

## Database Tables (16)

```text
users
account_requests
classes
students
parent_student_links
staff
attendance
fees
exams
results
transport
transport_assignments
hostel_rooms
hostel_assignments
notices
system_settings
```

## Database Patches

Apply in order for existing deployments:

```text
database/patches/001_account_requests.sql
database/patches/002_add_deleted_status.sql
database/patches/003_add_student_role.sql
database/patches/004_fees_paid_amount.sql
database/patches/005_parent_student_links.sql
```

## Useful Commands

Generate password hash:

```bash
php src/utilities/hash.php "new_password"
```

Verify endpoint role checks:

```bash
php src/utilities/check_roles.php
```

Patch role checks in selected files (CLI only):

```bash
php patch_endpoints.php
```

## Key URLs

```text
/SchoolMS/login.php
/SchoolMS/dashboard.php
/SchoolMS/request_account.php
/SchoolMS/students/list.php
/SchoolMS/attendance/mark.php
/SchoolMS/fees/list.php
/SchoolMS/fees/collect.php
/SchoolMS/results/report.php
/SchoolMS/transport/list.php
/SchoolMS/hostel/list.php
/SchoolMS/settings/index.php
/SchoolMS/settings/account_requests.php
/SchoolMS/settings/parent_links.php
/SchoolMS/notices/index.php
```

## Troubleshooting

Database connection failed:

- Confirm MySQL/MariaDB is running.
- Confirm environment variables are available to PHP.
- Confirm `DB_NAME` exists and schema is imported.

Missing `paid_amount` behavior in fees module:

- Run `database/patches/004_fees_paid_amount.sql`.

Parent data not visible in parent role pages:

- Ensure parent account is approved.
- Ensure mappings exist in `settings/parent_links.php`.

CSRF token errors:

- Refresh page and retry.
- Clear cookies/session and sign in again.

## Security Checklist

- [ ] Default admin password changed
- [ ] HTTPS enabled in deployment
- [ ] Writable permissions set only where needed (`logs/`, `uploads/`)
- [ ] `display_errors` disabled in production
- [ ] Regular database backup scheduled

## Last Updated

2026-04-19
