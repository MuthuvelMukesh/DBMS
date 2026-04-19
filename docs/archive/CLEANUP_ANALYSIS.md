# SchoolMS Cleanup Analysis (Archive)

Status: completed and archived
Last reviewed: 2026-04-19

## Purpose of this archive note

This document preserves historical cleanup context from the reorganization phase.
The cleanup actions referenced in earlier versions were already executed.

Do not use this file as a live runbook for delete/move operations.

## Cleanup outcome (completed)

The reorganization goals were completed:

- Legacy root helper files were consolidated into structured folders.
- Database patch scripts were standardized in database/patches.
- Shared auth/layout includes were centralized in includes.
- Utility scripts were centralized in src/utilities.
- Test cookies are under tests/cookies.
- ER docs are under docs/er.

## Current source of truth

Use active docs for present-day operations:

- README.md
- QUICK_REFERENCE.md
- docs/SETUP.md
- docs/PROJECT_STRUCTURE.md
- tests/README.md

## Current key paths

- config/app.php
- config/database.php
- includes/header.php
- includes/footer.php
- includes/sidebar.php
- database/schema.sql
- database/patches/001_account_requests.sql
- database/patches/002_add_deleted_status.sql
- database/patches/003_add_student_role.sql
- database/patches/004_fees_paid_amount.sql
- database/patches/005_parent_student_links.sql
- src/utilities/hash.php
- src/utilities/check_roles.php

## Safety note

Any old archive command that deletes root duplicates or moves db_patch_*.sql / chen_notation_*.md is historical and not applicable to the current repository state.