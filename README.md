# School Management System (SchoolMS)

A robust, feature-rich PHP & MySQL School Management System.

## 🚀 Features Implemented So Far

### 1. Advanced Role-Based Access Control (RBAC)
*   **Secure Routing:** Restricts directory and file access based on 4 distinct roles: **Admin, Teacher, Staff, and Parent**.
*   **Dynamic UI:** Sidebar menus and features dynamically adapt depending on the active user session.
*   **Demo Mode:** Pre-seeded demo credentials provided straight on the login page for rapid role-testing.

### 2. Modern UI/UX Overhaul
*   **Frontend Technologies:** Upgraded global interface to **Bootstrap 5** and **Google Fonts (Nunito)**.
*   **SaaS-style Dashboards:** Clean, modern card layouts featuring float animations and gradient overlays.
*   **Live Charts (Chart.js):** Integrated interactive data telemetry right on the main dashboard.
*   **DataTables Pipeline:** Integrated **jQuery DataTables** for all list views (Students, Classes, Staff), establishing instant asynchronous search, pagination, and multi-format document exporting (CSV/Excel/PDF).

### 3. System Settings Module
*   **Database Integrated Vars:** Avoids hardcoding configurations into PHP files.
*   **Admin Control Panel:** Allows Admins to directly update the **School Name, Logo, Address, Phone, and Academic Year** from the UI. Changes immediately broadcast to the navigation, page headers, cutouts, and browser tabs.

### 4. Noticeboard / Broadcasting Portal
*   **Notice Management:** Allows administration to draft system-wide announcements, circulars, and rules.
*   **Dashboard Alerts:** Pushes color-coded, severity-tiered alerts (Info, Warning, Danger, Success) directly to the top of the main Dashboard where every logged-in user is forced to see them.

### 5. Classes Management (Tamil Nadu Curriculum Tailored)
*   **Curriculum Mapping:** Restructured classroom taxonomy strictly mapping to the TN State Board/Samacheer structure.
*   **Preloaded Base:** Automatically configured with **35 unique classrooms**, starting from Pre-KG, traversing through secondary grades, and containing specialized branches for +1 and +2 (e.g., `Bio-Maths`, `CS-Maths`, `Commerce`, `Vocational`). Includes `Tamil Medium` classifications.
*   **Class Teachers:** Native relationship fields linking existing employee accounts as the designated "Class Teacher".

## 🛠️ Technology Stack
*   **Backend:** PHP 8+ (Core)
*   **Database:** MySQL / MariaDB
*   **Frontend:** HTML5, CSS3, JavaScript
*   **Libraries:** Bootstrap 5, FontAwesome 6, Chart.js, DataTables 1.13