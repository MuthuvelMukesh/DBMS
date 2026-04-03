-- Apply this once on existing SchoolMS databases to align roles with application RBAC.
ALTER TABLE users
MODIFY COLUMN role ENUM('admin', 'teacher', 'staff', 'parent', 'student') NOT NULL DEFAULT 'staff';
