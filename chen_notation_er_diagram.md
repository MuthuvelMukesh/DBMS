# School Management System - ER Diagram (Chen Notation)

## 📄 Abstract
The Entity-Relationship (ER) diagram for the School Management System (SchoolMS) illustrates the foundational database architecture required to manage the digital operations of an educational institution. The system revolves around five primary entities: **Users**, **Students**, **Staff**, **Classes**, and **Notices**. At the core is a Role-Based Access Control (RBAC) credential system where `Users` securely map to distinct `Students` or `Staff` profiles. The schema captures critical academic relationships—such as students being enrolled in specific `Classes` and staff members serving as class teachers—while accommodating administrative communications via system-wide `Notices`. Modeled utilizing strict Chen Notation, this blueprint clearly defines the entities, their key attributes, and their correlational cardinalities (1:1, 1:N, N:1), functioning as a robust, normalized, and scalable data foundation for the application design.

---

This document provides the exact structural details and the prompt required to generate a correct **Chen Notation** Entity-Relationship (ER) diagram for the SchoolMS project using generative AI (like Gemini) or a diagramming tool.

## 🤖 Prompt for Gemini / AI Image Generator
*Copy and paste the exact prompt below into your AI tool to generate the visual diagram. The prompt strictly enforces Chen Notation principles and a standard white background.*

```text
Generate a high-quality, professional Entity-Relationship Diagram (ERD) using STRICT CHEN NOTATION for a School Management System. 

Visual Requirements:
- Use a completely STANDARD WHITE BACKGROUND (#FFFFFF).
- All lines and text should be clearly legible (black or dark blue).
- Do not use Crow's Foot notation; strictly use Chen's Notation rules.

Chen Notation Rules to Apply:
1. Strong Entities must be in Rectangles.
2. Relationships must be in Diamonds.
3. Attributes must be in Ovals/Ellipses connected to their Entities.
4. Primary Keys must be placed in Ovals with the text UNDERLINED.
5. Provide Cardinality numbers (1, M, N) next to the linking lines between Entities and Relationships.

Entities and their Attributes to draw:
- USER: ID (Underlined), Username, Password, Role.
- STUDENT: ID (Underlined), AdmissionNo, FirstName, LastName, DOB.
- STAFF: ID (Underlined), EmployeeID, Designation, Department.
- CLASS: ID (Underlined), ClassName, Section.
- NOTICE: ID (Underlined), Title, Message, Date.

Relationships to draw:
- USER -> "Is A" (Triangle/Diamond) -> STUDENT (1 to 1)
- USER -> "Is A" (Triangle/Diamond) -> STAFF (1 to 1)
- STUDENT -> "Enrolled In" (Diamond) -> CLASS (N to 1)
- STAFF -> "Manages" (Diamond) -> CLASS (1 to 1)
- USER (Admin) -> "Publishes" (Diamond) -> NOTICE (1 to N)
```

---

## 📐 Chen Notation Key Principles Explained

If you are using a drag-and-drop tool (like draw.io, Lucidchart, or Visio) instead of an AI generator, follow these visual rules:

### 1. Entities (Rectangles)
Represent the core tables in your database.
*   **Users** (Base Entity)
*   **Students**
*   **Staff**
*   **Classes**
*   **Notices**

### 2. Attributes (Ellipses / Ovals)
Represent the columns of your tables. Connect these with a solid straight line to their parent Entity.
*   **Primary Keys (Underlined Text):** E.g., `id` for Users, `id` for Students.
*   **Standard Attributes:** E.g., `first_name`, `class_name`, `title`.

### 3. Relationships (Diamonds)
Represent how entities connect (Foreign Keys).
*   **"Enrolled_In"**: Connects `Student` to `Class`.
*   **"Teaches"**: Connects `Staff` to `Class`.
*   **"Belongs_To"**: Connects `Student` to `User` (for login credentials).

### 4. Cardinality (Lines with Numbers)
Written on the lines connecting Entities to Relationships.
*   **Staff-to-Class (1 : M)**: One staff member can manage multiple classes (or sections).
*   **Class-to-Student (1 : N)**: One class contains many students.
*   **User-to-Student (1 : 1)**: One login account belongs to exactly one student profile.

---

## 🗄️ Detailed Database Mapping for Chen Notation

If you want to map the entire database out manually in Chen Notation, here is the full map:

| Entity | Primary Key (Underline) | Normal Attributes (Ovals) | Relationships (Diamonds) |
| :--- | :--- | :--- | :--- |
| **USER** | `id` | `username`, `password`, `role`, `status` | `Has_Profile` (1:1 with Staff/Student), `Creates` (1:M with Notice) |
| **STUDENT** | `id` | `admission_no`, `first_name`, `gender`, `dob` | `Enrolled_In` (N:1 with Class) |
| **STAFF** | `id` | `employee_id`, `first_name`, `department` | `Class_Teacher_Of` (1:N with Class) |
| **CLASS** | `id` | `class_name`, `section`, `status` | `Contains` (1:N with Student), `Managed_By` (1:1 with Staff) |
| **NOTICE** | `id` | `title`, `message`, `type`, `is_active` | `Published_By` (N:1 with User/Admin) |
| **SETTINGS**| `setting_key` | `setting_value` | *(Independent Entity / Global)* |