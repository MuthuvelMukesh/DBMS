<?php
require 'dbconfig.php';
$conn->query("UPDATE classes SET class_name='X Std', section='A' WHERE id=1;");
$conn->query("UPDATE classes SET class_name='X Std', section='B' WHERE id=2;");
$conn->query("UPDATE classes SET class_name='XI Std', section='A (Bio-Maths)' WHERE id=3;");

$sql = "INSERT INTO classes (class_name, section) VALUES 
('Pre-KG', 'A'),
('LKG', 'A'), ('LKG', 'B'),
('UKG', 'A'), ('UKG', 'B'),
('I Std', 'A'), ('I Std', 'B'),
('II Std', 'A'), ('II Std', 'B'),
('III Std', 'A'), ('III Std', 'B'),
('IV Std', 'A'), ('IV Std', 'B'),
('V Std', 'A'), ('V Std', 'B'),
('VI Std', 'A'), ('VI Std', 'B'), ('VI Std', 'C (Tamil Med)'),
('VII Std', 'A'), ('VII Std', 'B'), ('VII Std', 'C (Tamil Med)'),
('VIII Std', 'A'), ('VIII Std', 'B'), ('VIII Std', 'C (Tamil Med)'),
('IX Std', 'A'), ('IX Std', 'B'), ('IX Std', 'C (Tamil Med)'),
('X Std', 'C (Tamil Med)'),
('XI Std', 'B (CS-Maths)'), ('XI Std', 'C (Commerce)'), ('XI Std', 'D (Vocational)'),
('XII Std', 'A (Bio-Maths)'), ('XII Std', 'B (CS-Maths)'), ('XII Std', 'C (Commerce)'), ('XII Std', 'D (Vocational)')
ON DUPLICATE KEY UPDATE id=id;";

$conn->query($sql);
echo "Classes successfully seeded for TN model.";
?>