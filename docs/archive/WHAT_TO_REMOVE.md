# SchoolMS What-To-Remove Guide (Archive)

Status: archived
Last reviewed: 2026-04-19

## Archive notice

Earlier versions of this file listed migration-era removal candidates.
Those lists are no longer valid for the current repository state.

Do not run old archive delete lists without a fresh audit.

## Current safe cleanup targets (general)

Only remove generated/runtime artifacts as needed:

- log files under logs/
- temporary backup files (*.bak, *.tmp)
- obsolete local cookie artifacts under tests/cookies/

## Do not remove (core paths)

- config/
- includes/
- database/schema.sql
- database/patches/
- src/utilities/
- module directories (students, staff, classes, attendance, fees, exams, results, transport, hostel, notices, settings)

## Use live docs

For current guidance:

- README.md
- QUICK_REFERENCE.md
- docs/SETUP.md
- docs/PROJECT_STRUCTURE.md
- tests/README.md