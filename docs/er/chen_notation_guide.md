# SchoolMS Chen Notation Guide

Last updated: 2026-04-19

This guide explains how to model the current SchoolMS schema with Chen notation.

## Chen notation refresher

- Entity: rectangle
- Relationship: diamond
- Attribute: oval
- Primary key: underlined attribute
- Multivalued attribute: double oval
- Derived attribute: dashed oval

## Model the current schema (database/schema.sql)

Primary entities to draw:

- USER
- ACCOUNT_REQUEST
- CLASS
- STUDENT
- PARENT_STUDENT_LINK
- STAFF
- ATTENDANCE
- FEE
- EXAM
- RESULT
- TRANSPORT
- TRANSPORT_ASSIGNMENT
- HOSTEL_ROOM
- HOSTEL_ASSIGNMENT
- NOTICE
- SYSTEM_SETTING

## Suggested relationships

- USER reviews ACCOUNT_REQUEST (1:N)
- USER maps to STUDENT via student.user_id (1:0..1)
- USER maps to STAFF via staff.user_id (1:0..1)
- USER can manage CLASS via class_teacher_id (1:N)
- USER publishes NOTICE (1:N)
- CLASS has STUDENT (1:N)
- CLASS has ATTENDANCE rows (1:N)
- CLASS has EXAM rows (1:N)
- STUDENT has ATTENDANCE rows (1:N)
- STUDENT has FEE rows (1:N)
- STUDENT has RESULT rows (1:N)
- EXAM has RESULT rows (1:N)
- STUDENT participates in TRANSPORT_ASSIGNMENT (1:N)
- TRANSPORT participates in TRANSPORT_ASSIGNMENT (1:N)
- STUDENT participates in HOSTEL_ASSIGNMENT (1:N)
- HOSTEL_ROOM participates in HOSTEL_ASSIGNMENT (1:N)
- USER(parent) and STUDENT are linked through PARENT_STUDENT_LINK (M:N via bridge)

## Practical modeling notes

- Represent PARENT_STUDENT_LINK as a relationship entity (bridge) with its own attributes (relationship, status).
- Include SYSTEM_SETTING as a standalone configuration entity keyed by setting_key.
- For role modeling, USER.role supports: admin, teacher, staff, parent, student.

## Prompt template for diagram generation tools

```text
Create a Chen-notation ER model for SchoolMS using these entities:
USER, ACCOUNT_REQUEST, CLASS, STUDENT, PARENT_STUDENT_LINK, STAFF,
ATTENDANCE, FEE, EXAM, RESULT, TRANSPORT, TRANSPORT_ASSIGNMENT,
HOSTEL_ROOM, HOSTEL_ASSIGNMENT, NOTICE, SYSTEM_SETTING.

Show primary keys as underlined attributes.
Use 1:1, 1:N, and M:N cardinalities based on foreign-key behavior in a MySQL schema.
Model PARENT_STUDENT_LINK as a bridge between USER (parent role) and STUDENT.
```

## Validation checklist

- Entity list matches database/schema.sql.
- Role set includes student role.
- Bridge tables (parent_student_links, transport_assignments, hostel_assignments) are represented.
- system_settings is present in the model.