# SchoolMS Cleanup Commands (Archive)

Status: archived reference
Last reviewed: 2026-04-19

## Context

Earlier versions of this file contained migration-era move/delete commands.
Those commands are obsolete for the current repository layout.

Use this file only as historical context.

## Safe verification commands (current)

Windows PowerShell examples:

```powershell
# Verify markdown docs
Get-ChildItem docs -Recurse -Filter *.md

# Verify migration patch files
Get-ChildItem database/patches -Filter *.sql | Sort-Object Name

# PHP syntax check
Get-ChildItem . -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }

# Role-gate scan utility
php src/utilities/check_roles.php
```

## Maintenance principle

- Prefer read-only verification and documentation updates.
- If future cleanup is required, perform a fresh audit first.
- Avoid destructive commands unless a new plan is reviewed and approved.