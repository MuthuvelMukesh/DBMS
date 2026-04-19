# SchoolMS ER Diagram (Chen Notation)

Last updated: 2026-04-19
Schema source: database/schema.sql

## Scope

This ER reference aligns with the current SchoolMS schema, including onboarding, parent-student links, transport and hostel assignments, and system settings.

## Entities in current schema

- users
- account_requests
- classes
- students
- parent_student_links
- staff
- attendance
- fees
- exams
- results
- transport
- transport_assignments
- hostel_rooms
- hostel_assignments
- notices
- system_settings

## Conceptual Chen-style view (Mermaid)

```mermaid
graph TD
    USER[USER]
    ACCOUNT_REQUEST[ACCOUNT_REQUEST]
    CLASS[CLASS]
    STUDENT[STUDENT]
    PARENT_LINK[PARENT_STUDENT_LINK]
    STAFF[STAFF]
    ATTENDANCE[ATTENDANCE]
    FEE[FEE]
    EXAM[EXAM]
    RESULT[RESULT]
    TRANSPORT[TRANSPORT]
    TRANSPORT_ASSIGN[TRANSPORT_ASSIGNMENT]
    HOSTEL_ROOM[HOSTEL_ROOM]
    HOSTEL_ASSIGN[HOSTEL_ASSIGNMENT]
    NOTICE[NOTICE]
    SETTINGS[SYSTEM_SETTING]

    USER -->|reviews| ACCOUNT_REQUEST
    USER -->|maps to| STUDENT
    USER -->|maps to| STAFF
    USER -->|can be class_teacher_id| CLASS
    USER -->|publishes| NOTICE

    CLASS -->|enrolls| STUDENT
    CLASS -->|tracks| ATTENDANCE
    CLASS -->|schedules| EXAM

    STUDENT -->|records| ATTENDANCE
    STUDENT -->|has| FEE
    STUDENT -->|has| RESULT
    STUDENT -->|assigned| TRANSPORT_ASSIGN
    STUDENT -->|assigned| HOSTEL_ASSIGN

    EXAM -->|produces| RESULT

    TRANSPORT -->|contains| TRANSPORT_ASSIGN
    HOSTEL_ROOM -->|contains| HOSTEL_ASSIGN

    USER -->|parent role links via| PARENT_LINK
    STUDENT -->|linked via| PARENT_LINK

    SETTINGS -->|global config| USER
```

## Key relationship notes

- Parent visibility is mediated by parent_student_links (not by a single parent column on users).
- Fees support cumulative payments through paid_amount and payment_status.
- Transport and hostel are modeled with separate assignment tables.
- system_settings stores application-wide configuration key-value pairs.

## Role note (users.role)

Current role enum:

- admin
- teacher
- staff
- parent
- student