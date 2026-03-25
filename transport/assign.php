<?php
require_once '../header.php';

$error = '';
$success = '';
$students = [];
$transports = [];
$selected_transport = null;

// Fetch all active transports
$stmt = $conn->prepare("SELECT id, route_name, stops FROM transport WHERE status = 'active' ORDER BY route_name");
$stmt->execute();
$transports = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch all students not already assigned to transport
$stmt = $conn->prepare("
    SELECT s.id, s.admission_no, s.full_name, s.class_id, c.class_name
    FROM students s
    JOIN classes c ON s.class_id = c.id
    WHERE s.status = 'active'
    AND s.id NOT IN (SELECT student_id FROM transport_assignments WHERE status = 'active')
    ORDER BY s.full_name
");
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'assign') {
        $student_id = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
        $transport_id = isset($_POST['transport_id']) ? (int)$_POST['transport_id'] : 0;
        $pickup_stop = isset($_POST['pickup_stop']) ? trim($_POST['pickup_stop']) : '';
        $join_date = isset($_POST['join_date']) ? trim($_POST['join_date']) : '';

        if ($student_id <= 0 || $transport_id <= 0 || empty($pickup_stop) || empty($join_date)) {
            $error = 'All fields are required!';
        } else {
            // Verify student and route exist and student is not already assigned
            $stmt = $conn->prepare("SELECT id FROM students WHERE id = ? AND status = 'active'");
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            $student_check = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$student_check) {
                $error = 'Invalid student selected!';
            } else {
                $stmt = $conn->prepare("SELECT id, capacity FROM transport WHERE id = ? AND status = 'active'");
                $stmt->bind_param("i", $transport_id);
                $stmt->execute();
                $transport_check = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$transport_check) {
                    $error = 'Invalid route selected!';
                } else {
                    // Check if student is already assigned to this route
                    $stmt = $conn->prepare("SELECT id FROM transport_assignments WHERE student_id = ? AND transport_id = ? AND status = 'active'");
                    $stmt->bind_param("ii", $student_id, $transport_id);
                    $stmt->execute();
                    $existing = $stmt->get_result()->fetch_assoc();
                    $stmt->close();

                    if ($existing) {
                        $error = 'Student is already assigned to this route!';
                    } else {
                        // Check route capacity
                        $stmt = $conn->prepare("
                            SELECT COUNT(*) as assigned_count FROM transport_assignments
                            WHERE transport_id = ? AND status = 'active'
                        ");
                        $stmt->bind_param("i", $transport_id);
                        $stmt->execute();
                        $capacity_check = $stmt->get_result()->fetch_assoc();
                        $stmt->close();

                        if ($capacity_check['assigned_count'] >= $transport_check['capacity']) {
                            $error = 'Route is at full capacity!';
                        } else {
                            $stmt = $conn->prepare("
                                INSERT INTO transport_assignments (student_id, transport_id, pickup_stop, join_date, status)
                                VALUES (?, ?, ?, ?, 'active')
                            ");
                            $stmt->bind_param("iiss", $student_id, $transport_id, $pickup_stop, $join_date);

                            if ($stmt->execute()) {
                                $success = 'Student assigned to route successfully!';
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

// Get stops for selected transport via GET (for dropdown population)
$selected_stops = [];
if (isset($_GET['transport_id'])) {
    $tid = (int)$_GET['transport_id'];
    $stmt = $conn->prepare("SELECT stops FROM transport WHERE id = ?");
    $stmt->bind_param("i", $tid);
    $stmt->execute();
    $transport_row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($transport_row) {
        $selected_stops = array_map('trim', explode(',', $transport_row['stops']));
    }
}
?>

<h1 class="page-title"><i class="fas fa-tasks"></i> Assign Students to Routes</h1>

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

        <form method="POST" id="assignForm">
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
                    <label for="transport_id" class="form-label">Select Route *</label>
                    <select class="form-control" id="transport_id" name="transport_id" required onchange="loadStops(this.value)">
                        <option value="">-- Choose Route --</option>
                        <?php foreach ($transports as $transport): ?>
                            <option value="<?php echo $transport['id']; ?>">
                                <?php echo htmlspecialchars($transport['route_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="pickup_stop" class="form-label">Pickup Stop *</label>
                    <select class="form-control" id="pickup_stop" name="pickup_stop" required>
                        <option value="">-- Select Stop --</option>
                        <?php foreach ($selected_stops as $stop): ?>
                            <option value="<?php echo htmlspecialchars($stop); ?>">
                                <?php echo htmlspecialchars($stop); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

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

<script>
function loadStops(transportId) {
    if (transportId === '') {
        document.getElementById('pickup_stop').innerHTML = '<option value="">-- Select Stop --</option>';
        return;
    }

    fetch('get_stops.php?transport_id=' + transportId)
        .then(response => response.json())
        .then(data => {
            let html = '<option value="">-- Select Stop --</option>';
            data.forEach(stop => {
                html += '<option value="' + stop.trim() + '">' + stop.trim() + '</option>';
            });
            document.getElementById('pickup_stop').innerHTML = html;
        });
}
</script>

<?php require_once '../footer.php'; ?>
