<?php
require_once '../header.php';

if (!in_array($role, ['admin', 'staff'])) {
    header('Location: ' . BASE_URL . 'dashboard.php?error=Access Denied');
    exit();
}


$error = '';
$success = '';
$students = [];
$rooms = [];

// Fetch all active students not already assigned to hostel
$stmt = $conn->prepare("
    SELECT s.id, s.admission_no, s.full_name, s.class_id, c.class_name
    FROM students s
    JOIN classes c ON s.class_id = c.id
    WHERE s.status = 'active'
    AND s.id NOT IN (SELECT student_id FROM hostel_assignments WHERE status = 'active')
    ORDER BY s.full_name
");
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch all hostel rooms with availability
$stmt = $conn->prepare("
    SELECT r.id, r.room_no, r.floor, r.capacity, r.room_type, r.fee_per_month,
           COUNT(a.id) as occupancy
    FROM hostel_rooms r
    LEFT JOIN hostel_assignments a ON r.id = a.room_id AND a.status = 'active'
    GROUP BY r.id
    HAVING occupancy < r.capacity
    ORDER BY r.floor, r.room_no
");
$stmt->execute();
$rooms = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'assign') {
        $student_id = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
        $room_id = isset($_POST['room_id']) ? (int)$_POST['room_id'] : 0;
        $join_date = isset($_POST['join_date']) ? trim($_POST['join_date']) : '';

        if ($student_id <= 0 || $room_id <= 0 || empty($join_date)) {
            $error = 'All fields are required!';
        } else {
            // Verify student exists and is not already assigned
            $stmt = $conn->prepare("SELECT id FROM students WHERE id = ? AND status = 'active'");
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            $student_check = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$student_check) {
                $error = 'Invalid student selected!';
            } else {
                // Check if student is already assigned to hostel
                $stmt = $conn->prepare("SELECT id FROM hostel_assignments WHERE student_id = ? AND status = 'active'");
                $stmt->bind_param("i", $student_id);
                $stmt->execute();
                $existing_assign = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($existing_assign) {
                    $error = 'Student is already assigned to a hostel room!';
                } else {
                    // Verify room exists
                    $stmt = $conn->prepare("SELECT id, capacity FROM hostel_rooms WHERE id = ?");
                    $stmt->bind_param("i", $room_id);
                    $stmt->execute();
                    $room_check = $stmt->get_result()->fetch_assoc();
                    $stmt->close();

                    if (!$room_check) {
                        $error = 'Invalid room selected!';
                    } else {
                        // Check room capacity
                        $stmt = $conn->prepare("
                            SELECT COUNT(*) as current_occupancy FROM hostel_assignments
                            WHERE room_id = ? AND status = 'active'
                        ");
                        $stmt->bind_param("i", $room_id);
                        $stmt->execute();
                        $capacity_check = $stmt->get_result()->fetch_assoc();
                        $stmt->close();

                        if ($capacity_check['current_occupancy'] >= $room_check['capacity']) {
                            $error = 'Room is at full capacity!';
                        } else {
                            $stmt = $conn->prepare("
                                INSERT INTO hostel_assignments (student_id, room_id, join_date, status)
                                VALUES (?, ?, ?, 'active')
                            ");
                            $stmt->bind_param("iis", $student_id, $room_id, $join_date);

                            if ($stmt->execute()) {
                                $success = 'Student assigned to hostel room successfully!';
                                header("Location: assign.php");
                                exit();
                            } else {
                                $error = 'Error assigning student: ' . $stmt->error;
                            }
                            $stmt->close();
                        }
                    }
                }
            }
        }
    }
}
?>

<h1 class="page-title"><i class="fas fa-tasks"></i> Assign Students to Hostel</h1>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="action" value="assign">
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="student_id" class="form-label">Select Student *</label>
                    <select class="form-control" id="student_id" name="student_id" required>
                        <option value="">-- Choose Student --</option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?php echo $student['id']; ?>">
                                <?php echo htmlspecialchars($student['admission_no'] . ' - ' . $student['full_name'] . ' (' . $student['class_name'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="room_id" class="form-label">Select Room *</label>
                    <select class="form-control" id="room_id" name="room_id" required>
                        <option value="">-- Choose Room --</option>
                        <?php foreach ($rooms as $room): ?>
                            <option value="<?php echo $room['id']; ?>">
                                <?php echo htmlspecialchars('Room ' . $room['room_no'] . ' - Floor ' . $room['floor'] . ' (' . $room['room_type'] . ', ' . $room['occupancy'] . '/' . $room['capacity'] . ' occupied)'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="join_date" class="form-label">Join Date *</label>
                    <input type="date" class="form-control" id="join_date" name="join_date" required value="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>

            <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Assign Student</button>
            <a href="list.php" class="btn btn-secondary"><i class="fas fa-list"></i> View Assignments</a>
        </form>
    </div>
</div>

<?php require_once '../footer.php'; ?>
