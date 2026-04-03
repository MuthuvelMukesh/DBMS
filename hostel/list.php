<?php
require_once '../header.php';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Fetch all active hostel assignments
if ($role === 'student') {
    $stmt = $conn->prepare("
        SELECT ha.id, ha.student_id, ha.room_id, ha.join_date, ha.status,
               s.admission_no, s.full_name, c.class_name,
               r.room_no, r.floor, r.room_type, r.capacity
        FROM hostel_assignments ha
        JOIN students s ON ha.student_id = s.id
        JOIN classes c ON s.class_id = c.id
        JOIN hostel_rooms r ON ha.room_id = r.id
        WHERE ha.status = 'active' AND s.user_id = ?
        ORDER BY r.floor, r.room_no, s.full_name
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param("iii", $_SESSION['user_id'], $limit, $offset);
} else {
    $stmt = $conn->prepare("
        SELECT ha.id, ha.student_id, ha.room_id, ha.join_date, ha.status,
               s.admission_no, s.full_name, c.class_name,
               r.room_no, r.floor, r.room_type, r.capacity
        FROM hostel_assignments ha
        JOIN students s ON ha.student_id = s.id
        JOIN classes c ON s.class_id = c.id
        JOIN hostel_rooms r ON ha.room_id = r.id
        WHERE ha.status = 'active'
        ORDER BY r.floor, r.room_no, s.full_name
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param("ii", $limit, $offset);
}
$stmt->execute();
$assignments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get total count
if ($role === 'student') {
    $stmt_c = $conn->prepare("SELECT COUNT(*) as total FROM hostel_assignments ha JOIN students s ON ha.student_id = s.id WHERE ha.status = 'active' AND s.user_id = ?");
    $stmt_c->bind_param("i", $_SESSION['user_id']);
    $stmt_c->execute();
    $count_row = $stmt_c->get_result()->fetch_assoc();
    $stmt_c->close();
} else {
    $count_result = $conn->query("SELECT COUNT(*) as total FROM hostel_assignments WHERE status = 'active'");
    $count_row = $count_result->fetch_assoc();
}
$total_records = $count_row['total'];
$total_pages = ceil($total_records / $limit);
?>

<h1 class="page-title"><i class="fas fa-list"></i> Hostel Assignments</h1>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-light border-bottom d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Active Student Assignments</h5>
        <?php if ($role !== 'student'): ?>
        <a href="assign.php" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> Assign Student
        </a>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Room</th>
                        <th>Floor</th>
                        <th>Room Type</th>
                        <th>Student (Admission)</th>
                        <th>Class</th>
                        <th>Join Date</th>
                        <th>Status</th>
                        <?php if ($role !== 'student'): ?>
                        <th>Action</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($assignments)): ?>
                        <?php foreach ($assignments as $assignment): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($assignment['room_no']); ?></strong></td>
                                <td><?php echo $assignment['floor']; ?></td>
                                <td><?php echo htmlspecialchars($assignment['room_type']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($assignment['admission_no'] . ' - ' . $assignment['full_name']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($assignment['class_name']); ?></td>
                                <td><?php echo date('d M Y', strtotime($assignment['join_date'])); ?></td>
                                <td>
                                    <span class="badge bg-success">
                                        <?php echo ucfirst($assignment['status']); ?>
                                    </span>
                                </td>
                                <?php if ($role !== 'student'): ?>
                                <td>
                                    <a href="vacate.php?assignment_id=<?php echo $assignment['id']; ?>" class="btn btn-warning btn-sm">
                                        <i class="fas fa-sign-out-alt"></i> Vacate
                                    </a>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?php echo ($role === 'student' ? '7' : '8'); ?>" class="text-center text-muted py-4">No active assignments found</td>
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
