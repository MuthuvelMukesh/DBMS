# SchoolMS

SchoolMS is a PHP and MySQL school management system with role-based access, academic workflows, fee tracking, transport and hostel operations, and admin-managed account onboarding.

This documentation is aligned with the current codebase as of 2026-04-19.

## Core Features

- Authentication with session-based access control.
- RBAC for five roles: admin, teacher, staff, parent, student.
- CSRF protection for POST requests across authenticated pages.
- Student, staff, class, attendance, exam, and result modules.
- Fee ledger with pending, paid, and partial payment tracking.
- Fee receipt generation and payment collection workflow.
- Transport routes and transport assignments.
- Hostel rooms and hostel allocation management.
- Notice publishing with dashboard alerts.
- Public account request flow with admin approval and role assignment.
- Parent-to-student linking for parent-scoped visibility in attendance/results/fees/transport/hostel views.
- Admin system settings for school profile (name, logo, address, phone, academic year).

## Role Access Snapshot

| Role | Main Access |
|------|-------------|
| admin | Full access to all modules, settings, notices, user onboarding |
| teacher | students, classes, attendance, exams, results |
| staff | transport, hostel |
| parent | fees, attendance, results, transport, hostel (linked students only) |
| student | fees, attendance, results, exams, transport, hostel (own data) |

## Current Project Layout

```text
SchoolMS/
|-- dashboard.php
|-- login.php
|-- logout.php
|-- profile.php
|-- request_account.php
|-- patch_endpoints.php
|-- attendance/
|-- classes/
|-- config/
|-- database/
|   |-- schema.sql
|   `-- patches/
|-- docs/
|-- exams/
|-- fees/
|-- hostel/
|-- includes/
|-- notices/
|-- public/
|   `-- css/
|-- results/
|-- settings/
|-- src/
|   |-- utilities/
|   `-- views/
|-- staff/
|-- students/
|-- tests/
|-- transport/
|-- uploads/
|-- .env.example
`-- QUICK_REFERENCE.md
```

## Database and Migration Notes

- Main schema: `database/schema.sql`
- Primary tables: users, account_requests, classes, students, parent_student_links, staff, attendance, fees, exams, results, transport, transport_assignments, hostel_rooms, hostel_assignments, notices, system_settings.
- Incremental patches in `database/patches/`:
	- `001_account_requests.sql`
	- `002_add_deleted_status.sql`
	- `003_add_student_role.sql`
	- `004_fees_paid_amount.sql`
	- `005_parent_student_links.sql`

## Quick Start

1. Create database and import schema.
2. Configure runtime environment variables (`DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`, `BASE_URL`).
3. Ensure writable directories: `logs/` and `uploads/`.
4. Open `login.php` in your browser.
5. Sign in with default admin credentials and rotate password immediately.

Default admin credentials:

- Username: `admin`
- Password: `admin123`

## Important Routes

- Login: `/SchoolMS/login.php`
- Dashboard: `/SchoolMS/dashboard.php`
- Public account request: `/SchoolMS/request_account.php`
- Settings: `/SchoolMS/settings/index.php`
- Account requests: `/SchoolMS/settings/account_requests.php`
- Parent links: `/SchoolMS/settings/parent_links.php`

## Documentation Index

- Setup guide: `docs/SETUP.md`
- Project structure: `docs/PROJECT_STRUCTURE.md`
- Quick reference: `QUICK_REFERENCE.md`
- Testing notes: `tests/README.md`
- ER diagram: `docs/er/chen_notation_er_diagram.md`

## Security Baseline

- Session and RBAC checks are enforced in shared include flow.
- CSRF token generation and POST validation are enabled.
- SQL queries use prepared statements in module pages.
- Passwords are hashed with PHP password APIs.

## Notes

- `patch_endpoints.php` is a CLI helper used to inject role checks into endpoint files and is not a web endpoint.
- The login screen does not allow self-registration; account creation is admin-controlled via request approval.