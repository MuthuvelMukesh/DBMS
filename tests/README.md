# SchoolMS Testing Notes

This folder stores test helpers and role cookie snapshots used for manual QA.

## Cookie Files Present

The current `tests/cookies/` directory contains:

- `parent_test.cookie`
- `qa_admin.cookie`
- `qa_parent.cookie`
- `qa_staff.cookie`
- `qa_teacher.cookie`
- `student_smoke.cookie`
- `test_admin.cookie`
- `test_parent.cookie`
- `test_staff.cookie`
- `test_teacher.cookie`

## Baseline Test Account

- Username: `admin`
- Password: `admin123`

Change this password in real environments.

## Manual Smoke Suite

1. Login and dashboard
2. Students module (`students/list.php`, `students/add.php`)
3. Classes module (`classes/list.php`)
4. Attendance module (`attendance/mark.php`, `attendance/report.php`, `attendance/student_report.php`)
5. Fees module (`fees/list.php`, `fees/collect.php`, `fees/receipt.php`)
6. Exams and results (`exams/list.php`, `results/add.php`, `results/report.php`)
7. Transport and hostel (`transport/list.php`, `hostel/list.php`)
8. Settings flows (`settings/index.php`, `settings/account_requests.php`, `settings/parent_links.php`)
9. Public onboarding (`request_account.php`)

## Role Validation Checklist

Admin:

- Verify access to all modules.
- Verify notices and settings are visible.

Teacher:

- Verify access to students, classes, attendance, exams, results.
- Verify no access to settings/notices/staff management.

Staff:

- Verify access to transport and hostel pages.
- Verify no access to academic and settings pages.

Parent:

- Verify access to fees, attendance, results, transport, hostel.
- Verify records are visible only after active parent-student links exist.

Student:

- Verify visibility is limited to own records.
- Verify no create/edit operations for restricted modules.

## CLI Validation Helpers

Role check scan:

```bash
php ../src/utilities/check_roles.php
```

PHP syntax lint (PowerShell example):

```powershell
Get-ChildItem .. -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }
```

## Database Validation Queries

```sql
SHOW TABLES;
DESCRIBE users;
DESCRIBE account_requests;
DESCRIBE parent_student_links;
DESCRIBE fees;
```

## Security Regression Checks

1. Verify POST forms reject invalid/missing CSRF token.
2. Verify role-gated URLs redirect unauthorized users.
3. Verify parent pages do not expose unlinked student data.
4. Verify fee collection supports partial payments when `paid_amount` exists.

## Last Updated

2026-04-19
