<?php
require_once '../header.php';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$class_filter = isset($_GET['class']) ? (int)$_GET['class'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$limit = 10;
$offset = ($page - 1) * $limit;

// Get classes
$classes_result = $conn->query("SELECT id, CONCAT(class_name, ' - ', section) as class_name FROM classes WHERE status = 'active' ORDER BY class_name");
$classes = $classes_result->fetch_all(MYSQLI_ASSOC);

// Build query
$query = "SELECT id, exam_name, class_id, subject, exam_date, max_marks, pass_marks, status 
          FROM exams WHERE 1=1";
$params = [];
$types = "";

if (!empty($class_filter)) {
    $query .= " AND class_id = ?";
    $params[] = $class_filter;
    $types = "i";
}

if (!empty($status_filter)) {
    $query .= " AND status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$query .= " ORDER BY exam_date DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$exams = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get total count
$count_query = "SELECT COUNT(*) as total FROM exams WHERE 1=1";
if (!empty($class_filter)) {
    $count_query .= " AND class_id = ?";
} elseif (!empty($status_filter)) {
    $count_query .= " AND status = ?";
}

$stmt = $conn->prepare($count_query);
if (!empty($class_filter)) {
    $stmt->bind_param("i", $class_filter);
} elseif (!empty($status_filter)) {
    $stmt->bind_param("s", $status_filter);
}
$stmt->execute();
$count_result = $stmt->get_result();
$count_row = $count_result->fetch_assoc();
$total_records = $count_row['total'];
$total_pages = ceil($total_records / $limit);
$stmt->close();
?>

<h1 class="page-title"><i class="fas fa-pen-square"></i> Exam Management</h1>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-light border-bottom d-flex justify-content-between align-items-center">
        <h5 class="mb-0">All Exams</h5>
        <a href="add.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Exam</a>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-6">
                <form method="GET" action="">
                    <select name="class" class="form-select" onchange="this.form.submit();">
                        <option value="">All Classes</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>" <?php echo ($class['id'] == $class_filter) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($class['class_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            <div class="col-md-6">
                <form method="GET" action="">
                    <select name="status" class="form-select" onchange="this.form.submit();">
                        <option value="">All Status</option>
                        <option value="scheduled" <?php echo ($status_filter == 'scheduled') ? 'selected' : ''; ?>>Scheduled</option>
                        <option value="completed" <?php echo ($status_filter == 'completed') ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo ($status_filter == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </form>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Exam Name</th>
                        <th>Class</th>
                        <th>Subject</th>
                        <th>Date</th>
                        <th>Marks</th>
                        <th>Pass Marks</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($exams)): ?>
                        <?php foreach ($exams as $exam): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($exam['exam_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($exam['class_id']); ?></td>
                                <td><?php echo htmlspecialchars($exam['subject']); ?></td>
                                <td><?php echo date('d-m-Y', strtotime($exam['exam_date'])); ?></td>
                                <td><?php echo $exam['max_marks']; ?></td>
                                <td><?php echo $exam['pass_marks']; ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        if ($exam['status'] == 'completed') echo 'success';
                                        elseif ($exam['status'] == 'scheduled') echo 'primary';
                                        else echo 'danger';
                                    ?>">
                                        <?php echo ucfirst($exam['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No exams found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav>
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=1">First</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $total_pages; ?>">Last</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../footer.php'; ?>
