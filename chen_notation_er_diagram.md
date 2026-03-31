# School Management System - ER Diagram (Chen Notation)

## 📄 Abstract
This document outlines the complete Entity-Relationship (ER) architecture for the School Management System (SchoolMS) using Chen Notation. To accurately represent the entire application scope, the schema incorporates fundamental Role-Based Access Control (RBAC)—spanning Admins, Teachers, Staff, and Parents—alongside all critical operational modules: **Students, Staff, Classes, Attendance, Fees, Exams, Results, Transport, Hostel, Notices, and Settings**. 

Because rendering a 12+ entity Chen ER diagram in a single AI generation can cause visual artifacting and overlapping lines, this guide provides the prompt text for the **Full Comprehensive System**, but recommends drawing or generating it utilizing a modular strategy if visual clarity is needed.

---

This document provides the exact structural details and prompts required to generate a correct **Chen Notation** Entity-Relationship (ER) diagram for the SchoolMS project using generative AI (like Gemini) or a diagramming tool.

## 🤖 Prompt for Gemini / AI Image Generator (Full System incl. All Modules)

*Copy and paste the exact prompt below into your AI tool to generate the visual diagram containing ALL current application modules and RBAC operations. The prompt strictly enforces Chen Notation principles and a standard white background.*

```text
Generate a high-quality, professional Entity-Relationship Diagram (ERD) using STRICT CHEN NOTATION for a complete School Management System. 

Visual Requirements:
- Use a completely STANDARD WHITE BACKGROUND (#FFFFFF).
- All lines and text should be clearly legible (black or dark blue).
- Do not use Crow's Foot notation; strictly use Chen's Notation rules.
- Space out the entities to prevent overlapping lines.

Chen Notation Rules to Apply:
1. Strong Entities must be in Rectangles.
2. Relationships must be in Diamonds.
3. Attributes must be in Ovals/Ellipses connected to their Entities.
4. Primary Keys must be placed in Ovals with the text UNDERLINED.
5. Provide Cardinality letters (1, M, N) next to the linking lines between Entities and Relationships.

Entities and their Attributes to draw:
- USER: ID (Underlined), Username, Password, Role (Admin/Teacher/Staff/Parent).
- STUDENT: ID (Underlined), AdmissionNo, Name.
- STAFF: ID (Underlined), EmployeeID, Designation.
- CLASS: ID (Underlined), ClassName, Section.
- ATTENDANCE: ID (Underlined), Date, Status.
- FEE: ID (Underlined), Amount, PaymentStatus.
- EXAM: ID (Underlined), Subject, Date.
- RESULT: ID (Underlined), Marks, Grade.
- TRANSPORT: ID (Underlined), RouteName, VehicleNo.
- HOSTEL: ID (Underlined), RoomNo, BedType.
- NOTICE: ID (Underlined), Title, Message.

Relationships to draw:
- USER -> "Acts As" (Diamond) -> STUDENT (1 to 1) 
- USER -> "Acts As" (Diamond) -> STAFF (1 to 1)
- USER (Parent) -> "Wards" (Diamond) -> STUDENT (1 to N)
- STUDENT -> "Enrolled In" (Diamond) -> CLASS (N to 1)
- STAFF (Teacher) -> "Manages" (Diamond) -> CLASS (1 to 1)
- STUDENT -> "Pays" (Diamond) -> FEE (1 to N)
- STUDENT -> "Records" (Diamond) -> ATTENDANCE (1 to N)
- CLASS -> "Conducts" (Diamond) -> EXAM (1 to N)
- EXAM -> "Yields" (Diamond) -> RESULT (1 to N)
- STUDENT -> "Achieves" (Diamond) -> RESULT (1 to N)
- STUDENT -> "Uses" (Diamond) -> TRANSPORT (N to 1)
- STUDENT -> "Resides In" (Diamond) -> HOSTEL (N to 1)
- USER (Admin) -> "Publishes" (Diamond) -> NOTICE (1 to N)
```

---

## 📐 Chen Notation Key Principles Explained

If you are using a drag-and-drop tool (like draw.io, Lucidchart, or Visio) instead of an AI generator, follow these visual rules:

### 1. Entities (Rectangles)
Represent the tables in your database (e.g., `Users`, `Students`, `Fees`, `Exams`).

### 2. Attributes (Ellipses / Ovals)
Represent the columns of your tables. Connect these with a solid straight line to their parent Entity.
*   **Primary Keys (Underlined Text):** E.g., `id` (Always underline the ID).
*   **Standard Attributes:** E.g., `amount`, `class_name`, `status`.

### 3. Relationships (Diamonds)
Represent how entities connect via Foreign Keys in the database operations. E.g., The foreign key `student_id` in the `fees` table creates the "Pays" diamond relationship.

### 4. Cardinality (Lines with Numbers/Letters)
Written on the lines connecting Entities to Relationships.
*   **N to 1**: Many Students use One Transport Route.
*   **1 to N**: One Exam yields Many Results (one for each student).
*   **1 to 1**: One User Login maps strictly to One Staff Employee profile.

---

## 🗄️ Detailed Database Mapping for Chen Notation (All Modules)

If you want to map the entire database out manually in Chen Notation, here is the full comprehensive map of operations:

| Entity | Primary Key (Underline) | Normal Attributes (Ovals) | Relationships (Diamonds) |
| :--- | :--- | :--- | :--- |
| **USER** | `id` | `username`, `password`, `role` (Admin/Teacher/Staff/Parent) | `Acts_As` (1:1 with Staff/Student), `Wards` (1:N with Student), `Publishes` (1:N with Notice) |
| **STUDENT** | `id` | `admission_no`, `first_name`, `dob` | `Enrolled_In` (N:1 with Class), `Pays` (1:N with Fee), `Records` (1:N with Attendance), `Achieves` (1:N with Result) |
| **STAFF** | `id` | `employee_id`, `first_name`, `department` | `Manages` (1:1 with Class) |
| **CLASS** | `id` | `class_name`, `section`, `status` | `Contains` (1:N with Student), `Conducts` (1:N with Exam) |
| **ATTENDANCE** | `id` | `date`, `status`, `remarks` | *(Connected via Student Records)* |
| **FEE** | `id` | `fee_type`, `amount`, `payment_status`| *(Connected via Student Pays)* |
| **EXAM** | `id` | `exam_name`, `subject`, `exam_date`| `Yields` (1:N with Result) |
| **RESULT** | `id` | `marks_obtained`, `total_marks`, `grade`| *(Intersection of Exam & Student)* |
| **TRANSPORT** | `id` | `route_name`, `vehicle_no`, `driver_name`| `Uses` (1:N mapped to Students) |
| **HOSTEL** | `id` | `room_no`, `capacity`, `bed_type`| `Resides_In` (1:N mapped to Students) |
| **NOTICE** | `id` | `title`, `message`, `type` | `Published_By` (N:1 with User) |