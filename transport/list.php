<?php
require_once '../header.php';

$error = '';
$success = '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Fetch all transport assignments
$stmt = $conn->prepare("
    SELECT ta.id, ta.student_id, ta.transport_id, ta.pickup_stop, ta.join_date, ta.status,
           s.admission_no, s.full_name, c.class_name,
           t.route_name, t.vehicle_no, t.driver_name
    FROM transport_assignments ta
    JOIN students s ON ta.student_id = s.id
    JOIN classes c ON s.class_id = c.id
    JOIN transport t ON ta.transport_id = t.id
    WHERE ta.status = 'active'
    ORDER BY t.route_name, s.full_name
    LIMIT ? OFFSET ?
");
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$assignments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get total count
$count_result = $conn->query("SELECT COUNT(*) as total FROM transport_assignments WHERE status = 'active'");
$count_row = $count_result->fetch_assoc();
$total_records = $count_row['total'];
$total_pages = ceil($total_records / $limit);

// Handle removal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove') {
    $assignment_id = isset($_POST['assignment_id']) ? (int)$_POST['assignment_id'] : 0;

    if ($assignment_id <= 0) {
        $error = 'Invalid assignment!';
    } else {
        // Check assignment exists
        $stmt = $conn->prepare("SELECT id FROM transport_assignments WHERE id = ? AND status = 'active'");
        $stmt->bind_param("i", $assignment_id);
        $stmt->execute();
        $check = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$check) {
            $error = 'Assignment not found!';
        } else {
            $stmt = $conn->prepare("UPDATE transport_assignments SET status = 'inactive' WHERE id = ?");
            $stmt->bind_param("i", $assignment_id);

            if ($stmt->execute()) {
                $success = 'Student removed from route successfully!';
                header("Location: list.php");
                exit();
            } else {
                $error = 'Error removing assignment: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
}
?>

<h1 class="page-title"><i class="fas fa-list"></i> Transport Assignments</h1>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-light border-bottom d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Active Student Assignments</h5>
        <a href="assign.php" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> Assign Student
        </a>
    </div>
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

        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Route Name</th>
                        <th>Student (Admission)</th>
                        <th>Class</th>
                        <th>Vehicle No</th>
                        <th>Pickup Stop</th>
                        <th>Join Date</th>
                        <th>Driver</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($assignments)): ?>
                        <?php foreach ($assignments as $assignment): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($assignment['route_name']); ?></strong></td>
                                <td>
                                    <?php echo htmlspecialchars($assignment['admission_no'] . ' - ' . $assignment['full_name']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($assignment['class_name']); ?></td>
                                <td><?php echo htmlspecialchars($assignment['vehicle_no']); ?></td>
                                <td><?php echo htmlspecialchars($assignment['pickup_stop']); ?></td>
                                <td><?php echo date('d M Y', strtotime($assignment['join_date'])); ?></td>
                                <td><?php echo htmlspecialchars($assignment['driver_name']); ?></td>
                                <td>
                                    <span class="badge bg-success">
                                        <?php echo ucfirst($assignment['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="assignment_id" value="<?php echo $assignment['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to remove this student from the route?');">
                                            <i class="fas fa-trash"></i> Remove
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">No active assignments found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
            <nav class="d-flex justify-content-center mt-4">
                <ul class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../footer.php'; ?>
