# School Management System - ER Diagram (Chen Notation)

## Abstract
This document outlines the complete Entity-Relationship (ER) architecture for the School Management System (SchoolMS) using Chen Notation. To accurately represent the entire application scope, the schema incorporates fundamental Role-Based Access Control (RBAC) spanning Admins, Teachers, Staff, and Parents, alongside all critical operational modules: **Students, Staff, Classes, Attendance, Fees, Exams, Results, Transport, Hostel, Notices, and Settings**.

## Complete System Chen ER Diagram
Below is the full Chen Notation ER diagram generated dynamically using Mermaid.js.

```mermaid
graph TD
    %% Styling for Chen Notation
    classDef entity fill:#3b82f6,stroke:#1e40af,stroke-width:2px,color:#fff,shape:rect;
    classDef relationship fill:#10b981,stroke:#047857,stroke-width:2px,color:#fff,shape:diamond;
    classDef attribute fill:#f8fafc,stroke:#94a3b8,stroke-width:1px,color:#0f172a,shape:circle;
    classDef pk fill:#f1f5f9,stroke:#0f172a,stroke-width:2px,color:#0f172a,shape:circle;

    %% Entities
    E_User["USER"]:::entity
    E_Student["STUDENT"]:::entity
    E_Staff["STAFF"]:::entity
    E_Class["CLASS"]:::entity
    E_Attendance["ATTENDANCE"]:::entity
    E_Fee["FEE"]:::entity
    E_Exam["EXAM"]:::entity
    E_Result["RESULT"]:::entity
    E_Transport["TRANSPORT"]:::entity
    E_Hostel["HOSTEL"]:::entity
    E_Notice["NOTICE"]:::entity

    %% Primary Keys
    PK_User(("<u>id</u>")):::pk
    PK_Student(("<u>id</u>")):::pk
    PK_Staff(("<u>id</u>")):::pk
    PK_Class(("<u>id</u>")):::pk
    PK_Attendance(("<u>id</u>")):::pk
    PK_Fee(("<u>id</u></u>")):::pk
    PK_Exam(("<u>id</u>")):::pk
    PK_Result(("<u>id</u>")):::pk
    PK_Transport(("<u>id</u>")):::pk
    PK_Hostel(("<u>id</u>")):::pk
    PK_Notice(("<u>id</u>")):::pk

    %% Normal Attributes
    A_User1(("username")):::attribute
    A_User2(("role")):::attribute
    A_Student1(("admission_no")):::attribute
    A_Student2(("name")):::attribute
    A_Staff1(("employee_id")):::attribute
    A_Staff2(("designation")):::attribute
    A_Class1(("class_name")):::attribute
    A_Class2(("section")):::attribute
    A_Att1(("date")):::attribute
    A_Att2(("status")):::attribute
    A_Fee1(("amount")):::attribute
    A_Fee2(("payment_status")):::attribute
    A_Exam1(("subject")):::attribute
    A_Exam2(("date")):::attribute
    A_Result1(("marks")):::attribute
    A_Result2(("grade")):::attribute
    A_Trans1(("route_name")):::attribute
    A_Host1(("room_no")):::attribute
    A_Not1(("title")):::attribute

    %% Link PKs to Entities
    PK_User --- E_User
    PK_Student --- E_Student
    PK_Staff --- E_Staff
    PK_Class --- E_Class
    PK_Attendance --- E_Attendance
    PK_Fee --- E_Fee
    PK_Exam --- E_Exam
    PK_Result --- E_Result
    PK_Transport --- E_Transport
    PK_Hostel --- E_Hostel
    PK_Notice --- E_Notice

    %% Link Attributes to Entities
    A_User1 --- E_User
    A_User2 --- E_User
    A_Student1 --- E_Student
    A_Student2 --- E_Student
    A_Staff1 --- E_Staff
    A_Staff2 --- E_Staff
    A_Class1 --- E_Class
    A_Class2 --- E_Class
    A_Att1 --- E_Attendance
    A_Att2 --- E_Attendance
    A_Fee1 --- E_Fee
    A_Fee2 --- E_Fee
    A_Exam1 --- E_Exam
    A_Exam2 --- E_Exam
    A_Result1 --- E_Result
    A_Result2 --- E_Result
    A_Trans1 --- E_Transport
    A_Host1 --- E_Hostel
    A_Not1 --- E_Notice

    %% Relationships
    R_UserStudent{"Acts As"}:::relationship
    R_UserStaff{"Acts As"}:::relationship
    R_StudentClass{"Enrolled In"}:::relationship
    R_StaffClass{"Manages"}:::relationship
    R_StudentAtt{"Records"}:::relationship
    R_ClassAtt{"Associated"}:::relationship
    R_StudentFee{"Pays"}:::relationship
    R_ClassExam{"Conducts"}:::relationship
    R_ExamResult{"Yields"}:::relationship
    R_StudentResult{"Achieves"}:::relationship
    R_StudentTrans{"Uses"}:::relationship
    R_StudentHostel{"Resides In"}:::relationship
    R_UserNotice{"Publishes"}:::relationship

    %% Map Relationships with Cardinality
    E_User ---|1| R_UserStudent ---|1| E_Student
    E_User ---|1| R_UserStaff ---|1| E_Staff
    E_Student ---|N| R_StudentClass ---|1| E_Class
    E_Staff ---|1| R_StaffClass ---|1| E_Class
    
    E_Student ---|1| R_StudentAtt ---|N| E_Attendance
    E_Class ---|1| R_ClassAtt ---|N| E_Attendance

    E_Student ---|1| R_StudentFee ---|N| E_Fee
    
    E_Class ---|1| R_ClassExam ---|N| E_Exam
    E_Exam ---|1| R_ExamResult ---|N| E_Result
    E_Student ---|1| R_StudentResult ---|N| E_Result

    E_Student ---|N| R_StudentTrans ---|1| E_Transport
    E_Student ---|N| R_StudentHostel ---|1| E_Hostel
    
    E_User ---|1| R_UserNotice ---|N| E_Notice
```

## Chen Notation Key Principles Explained

If you are using a drag-and-drop tool (like draw.io, Lucidchart, or Visio) instead of an AI generator, follow these visual rules:

### 1. Entities (Rectangles / Blue)
Represent the tables in your database.

### 2. Attributes (Ellipses / Ovals / White)
Represent the columns of your tables. Connect these with a solid straight line to their parent Entity.
*   **Primary Keys (Underlined Text):** E.g., `id` (Always underline the ID).
*   **Standard Attributes:** E.g., `amount`, `class_name`, `status`.

### 3. Relationships (Diamonds / Green)
Represent how entities connect via Foreign Keys in the database operations. E.g., The foreign key `student_id` in the `fees` table creates the "Pays" diamond relationship.

### 4. Cardinality (Lines with Numbers/Letters)
Written on the lines connecting Entities to Relationships.
*   **N to 1**: Many Students use One Transport Route.
*   **1 to N**: One Exam yields Many Results (one for each student).
*   **1 to 1**: One User Login maps strictly to One Staff Employee profile.

## AI Generator Prompt (Gemini Nana Banana)

You can copy and paste the following prompt directly into Gemini (or any advanced LLM) to generate or refine a full, professional ER Diagram using strict Chen Notation.

```text
Act as an expert Database Architect. I need you to create a full, professional Entity-Relationship (ER) Diagram for a comprehensive School Management System. 

You must strictly adhere to all Chen Notation principles:
1. Entities must be represented by Rectangles.
2. Weak Entities must be represented by Double Rectangles.
3. Relationships must be represented by Diamonds.
4. Attributes must be represented by Ovals.
5. Multivalued Attributes must be represented by Double Ovals.
6. Derived Attributes must be represented by Dashed Ovals.
7. Primary Keys must be represented by Ovals with Underlined text.
8. Define the full Cardinality (1:1, 1:N, M:N) and Participation constraints (Total/Partial) clearly on the relationship lines.

The system includes the following core modules: Users (RBAC: Admin, Teacher, Staff, Parent), Students, Staff, Classes, Attendance, Fees, Exams, Results, Transport, Hostel, and Notices. 

Please provide the output as a fully renderable syntax (such as Mermaid.js flowchart with explicit shape declarations matching Chen notation, or PlantUML) OR provide a highly detailed textual blueprint of the diagram that can be directly imported or drawn into a tool like draw.io, Visio, or Lucidchart. Make the architecture production-ready, highly detailed, and professional.
```
