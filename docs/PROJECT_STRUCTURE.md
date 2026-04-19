# SchoolMS Project Structure

This document reflects the current repository organization and file responsibilities.

## Repository Tree (Current)

```text
SchoolMS/
|-- .env.example
|-- .gitignore
|-- README.md
|-- QUICK_REFERENCE.md
|-- dashboard.php
|-- login.php
|-- logout.php
|-- profile.php
|-- request_account.php
|-- patch_endpoints.php
|
|-- attendance/
|   |-- mark.php
|   |-- report.php
|   `-- student_report.php
|
|-- classes/
|   `-- list.php
|
|-- config/
|   |-- app.php
|   `-- database.php
|
|-- database/
|   |-- schema.sql
|   `-- patches/
|       |-- 001_account_requests.sql
|       |-- 002_add_deleted_status.sql
|       |-- 003_add_student_role.sql
|       |-- 004_fees_paid_amount.sql
|       `-- 005_parent_student_links.sql
|
|-- docs/
|   |-- PROJECT_STRUCTURE.md
|   |-- SETUP.md
|   |-- archive/
|   `-- er/
|
|-- exams/
|   |-- add.php
|   `-- list.php
|
|-- fees/
|   |-- add.php
|   |-- collect.php
|   |-- list.php
|   `-- receipt.php
|
|-- hostel/
|   |-- assign.php
|   |-- list.php
|   |-- rooms.php
|   `-- vacate.php
|
|-- includes/
|   |-- footer.php
|   |-- header.php
|   `-- sidebar.php
|
|-- logs/
|-- notices/
|   `-- index.php
|-- public/
|   `-- css/app.css
|
|-- results/
|   |-- add.php
|   |-- marksheet.php
|   `-- report.php
|
|-- settings/
|   |-- account_requests.php
|   |-- index.php
|   `-- parent_links.php
|
|-- src/
|   |-- utilities/
|   |   |-- check_roles.php
|   |   `-- hash.php
|   `-- views/
|
|-- staff/
|   |-- add.php
|   |-- delete.php
|   |-- edit.php
|   `-- list.php
|
|-- students/
|   |-- add.php
|   |-- delete.php
|   |-- edit.php
|   |-- list.php
|   `-- view.php
|
|-- tests/
|   |-- README.md
|   `-- cookies/
|
|-- transport/
|   |-- assign.php
|   |-- get_stops.php
|   |-- list.php
|   `-- routes.php
|
`-- uploads/
```

## Important Architectural Notes

- Entry-point pages are currently in repository root (`login.php`, `dashboard.php`, etc.), not inside `public/`.
- Feature modules are also root-level directories (for example `students/`, `fees/`, `transport/`).
- Shared auth, CSRF, and role permission checks are centralized through `includes/header.php`.
- Navigation is role-aware and rendered from `includes/sidebar.php`.
- Global constants and error logging are configured in `config/app.php`.

## File Responsibilities

Core entry files:

- `login.php`: Authentication flow and CSRF validation.
- `request_account.php`: Public account request submission.
- `dashboard.php`: Role-sensitive dashboard cards and quick actions.
- `profile.php`: User profile page.

Shared layer:

- `includes/header.php`: Session check, CSRF, RBAC guard, settings preload.
- `includes/sidebar.php`: Role-scoped module navigation.
- `includes/footer.php`: Script includes and page closure.

Admin settings area:

- `settings/index.php`: System settings (name/logo/address/phone/year).
- `settings/account_requests.php`: Approve/reject account requests and assign role.
- `settings/parent_links.php`: Link parent users to one or more students.

Utility scripts:

- `src/utilities/hash.php`: Generate password hashes from CLI.
- `src/utilities/check_roles.php`: Scan PHP files for role-check patterns.
- `patch_endpoints.php`: CLI-only helper to inject role checks in selected endpoints.

## Database Structure and Patches

- Authoritative schema: `database/schema.sql`
- Patches: `database/patches/*.sql`
- Current schema includes account request workflow, parent-student link mapping, and partial fee payment tracking.

## Security Conventions

- Only authenticated users can access module pages that include `includes/header.php`.
- All POST requests in authenticated context require valid `_csrf_token`.
- Role permissions are checked against the current path (root page or module directory).
- Sensitive operations use prepared statements.

## Update Checklist for New Modules

1. Create a root module directory (for example `library/`).
2. Include `includes/header.php` and `includes/footer.php` in each page.
3. Add role gating for module pages.
4. Add sidebar entries in `includes/sidebar.php` where appropriate.
5. Add schema or patch files in `database/`.
6. Update `README.md` and `QUICK_REFERENCE.md` with new routes.

## Last Updated

2026-04-19
