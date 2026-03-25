<?php
require_once 'dbconfig.php';

// Turn off foreign key checks temporarily if needed, though inserts should be ordered
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

$conn->query("TRUNCATE TABLE classes");
$conn->query("TRUNCATE TABLE students");
$conn->query("TRUNCATE TABLE staff");
$conn->query("TRUNCATE TABLE hostel_rooms");
$conn->query("TRUNCATE TABLE transport");

echo "Tables truncated...\n";

// Seed Staff (Teachers)
$staff_queries = [
    "INSERT INTO staff (staff_id, full_name, designation, department, contact, email, salary, join_date, status) VALUES 
    ('EMP001', 'John Doe', 'Teacher', 'Mathematics', '9876543210', 'john@school.com', 50000.00, '2020-01-15', 'active')",
    "INSERT INTO staff (staff_id, full_name, designation, department, contact, email, salary, join_date, status) VALUES 
    ('EMP002', 'Jane Smith', 'Teacher', 'Science', '8765432109', 'jane@school.com', 48000.00, '2021-03-10', 'active')",
    "INSERT INTO staff (staff_id, full_name, designation, department, contact, email, salary, join_date, status) VALUES 
    ('EMP003', 'Robert Brown', 'Teacher', 'English', '7654321098', 'robert@school.com', 45000.00, '2022-06-20', 'active')",
    "INSERT INTO staff (staff_id, full_name, designation, department, contact, email, salary, join_date, status) VALUES 
    ('EMP004', 'Emily White', 'Driver', 'Transport', '6543210987', 'emily@school.com', 25000.00, '2019-08-01', 'active')"
];

foreach ($staff_queries as $q) {
    if(!$conn->query($q)) echo "Staff error: " . $conn->error . "\n";
}
echo "Staff seeded...\n";

// Seed Classes
$class_queries = [
    "INSERT INTO classes (id, class_name, section, class_teacher_id, status) VALUES (1, '10th Grade', 'A', 1, 'active')",
    "INSERT INTO classes (id, class_name, section, class_teacher_id, status) VALUES (2, '10th Grade', 'B', 2, 'active')",
    "INSERT INTO classes (id, class_name, section, class_teacher_id, status) VALUES (3, '11th Grade', 'A', 3, 'active')"
];

foreach ($class_queries as $q) {
     if(!$conn->query($q)) echo "Class error: " . $conn->error . "\n";
}
echo "Classes seeded...\n";

// Seed Students
$student_queries = [
    "INSERT INTO students (admission_no, full_name, dob, gender, class_id, section, parent_name, contact, address, status) VALUES 
    ('ADM1001', 'Alice Johnson', '2010-05-14', 'Female', 1, 'A', 'Michael Johnson', '9988776655', '123 Elm St, City', 'active')",
    "INSERT INTO students (admission_no, full_name, dob, gender, class_id, section, parent_name, contact, address, status) VALUES 
    ('ADM1002', 'Bobby Fisher', '2010-09-21', 'Male', 1, 'A', 'David Fisher', '8877665544', '456 Oak St, City', 'active')",
    "INSERT INTO students (admission_no, full_name, dob, gender, class_id, section, parent_name, contact, address, status) VALUES 
    ('ADM1003', 'Charlie Davis', '2011-01-10', 'Male', 2, 'B', 'Sarah Davis', '7766554433', '789 Pine St, City', 'active')",
    "INSERT INTO students (admission_no, full_name, dob, gender, class_id, section, parent_name, contact, address, status) VALUES 
    ('ADM1004', 'Diana Miller', '2009-11-05', 'Female', 3, 'A', 'James Miller', '6655443322', '321 Maple St, City', 'active')"
];

foreach ($student_queries as $q) {
     if(!$conn->query($q)) echo "Student error: " . $conn->error . "\n";
}
echo "Students seeded...\n";

// Seed Hostel Rooms
$hostel_queries = [
    "INSERT INTO hostel_rooms (room_no, floor, capacity, room_type, fee_per_month, status) VALUES 
    ('R101', 1, 2, 'Double', 5000.00, 'active')",
    "INSERT INTO hostel_rooms (room_no, floor, capacity, room_type, fee_per_month, status) VALUES 
    ('R102', 1, 4, 'Quad', 3000.00, 'active')",
    "INSERT INTO hostel_rooms (room_no, floor, capacity, room_type, fee_per_month, status) VALUES 
    ('R201', 2, 1, 'Single', 8000.00, 'active')"
];

foreach ($hostel_queries as $q) {
     if(!$conn->query($q)) echo "Hostel error: " . $conn->error . "\n";
}
echo "Hostels seeded...\n";

// Seed Transport
$transport_queries = [
    "INSERT INTO transport (route_name, vehicle_no, driver_name, driver_contact, stops, capacity, monthly_fee, status) VALUES 
    ('North Route', 'BUS-01', 'Emily White', '6543210987', '[\"Elm St\", \"Oak St\", \"Pine St\"]', 40, 1500.00, 'active')",
    "INSERT INTO transport (route_name, vehicle_no, driver_name, driver_contact, stops, capacity, monthly_fee, status) VALUES 
    ('South Route', 'VAN-02', 'Tom Wilson', '5432109876', '[\"Maple St\", \"Cedar St\"]', 20, 1000.00, 'active')"
];

foreach ($transport_queries as $q) {
     if(!$conn->query($q)) echo "Transport error: " . $conn->error . "\n";
}
echo "Transport seeded...\n";

$conn->query("SET FOREIGN_KEY_CHECKS = 1");
echo "Data seeding complete!!\n";
?>