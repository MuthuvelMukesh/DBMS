-- Patch: allow logical delete status for students and staff
-- Run once against existing SchoolMS databases.

ALTER TABLE students
    MODIFY COLUMN status ENUM('active', 'inactive', 'passed_out', 'deleted') NOT NULL DEFAULT 'active';

ALTER TABLE staff
    MODIFY COLUMN status ENUM('active', 'inactive', 'retired', 'deleted') NOT NULL DEFAULT 'active';
