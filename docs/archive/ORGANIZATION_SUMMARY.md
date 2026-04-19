# SchoolMS Organization Summary (Archive)

Status: archived snapshot
Last reviewed: 2026-04-19

## Summary

SchoolMS runs with root entry pages, root module folders, and centralized config/includes/database/utilities.

## Current structure highlights

- Root entry pages:
  - login.php
  - dashboard.php
  - logout.php
  - profile.php
  - request_account.php
- Root module directories:
  - students, staff, classes, attendance, fees, exams, results, transport, hostel, notices, settings
- Shared shell:
  - includes/header.php
  - includes/footer.php
  - includes/sidebar.php
- Config:
  - config/app.php
  - config/database.php
- Database:
  - database/schema.sql
  - database/patches/*.sql
- Utilities:
  - src/utilities/hash.php
  - src/utilities/check_roles.php

## Operational notes

- Role checks and CSRF enforcement are centralized via includes/header.php.
- Parent visibility is controlled through parent_student_links and settings/parent_links.php.
- Onboarding flow is request_account.php -> settings/account_requests.php.

## Canonical docs

Use these as source of truth:

- README.md
- QUICK_REFERENCE.md
- docs/SETUP.md
- docs/PROJECT_STRUCTURE.md
- tests/README.md

## Archive intent

This file is retained as a historical record of the organization phase.