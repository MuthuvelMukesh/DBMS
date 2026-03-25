<?php
require_once '../header.php';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$class_filter = isset($_GET['class']) ? (int)$_GET['class'] : '';
$limit = 10;
$offset = ($page - 1) * $limit;

// Build query
$query = "SELECT id, admission_no, full_name, dob, gender, class_id, section, parent_name, contact, status 
          FROM students WHERE status != 'deleted'";
$params = [];
$types = "";

if (!empty($search)) {
    $query .= " AND (admission_no LIKE ? OR full_name LIKE ? OR parent_name LIKE ?)";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param];
    $types = "sss";
}

if (!empty($class_filter)) {
    $query .= " AND class_id = ?";
    $params[] = $class_filter;
    $types .= "i";
}

$query .= " ORDER BY full_name ASC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$students = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get total count
$count_query = "SELECT COUNT(*) as total FROM students WHERE status != 'deleted'";
if (!empty($search)) {
    $count_query .= " AND (admission_no LIKE ? OR full_name LIKE ? OR parent_name LIKE ?)";
    $stmt = $conn->prepare($count_query);
    $search_param = "%$search%";
    $stmt->bind_param("sss", $search_param, $search_param, $search_param);
} else if (!empty($class_filter)) {
    $count_query .= " AND class_id = ?";
    $stmt = $conn->prepare($count_query);
    $stmt->bind_param("i", $class_filter);
} else {
    $stmt = $conn->prepare($count_query);
}
$stmt->execute();
$count_result = $stmt->get_result();
$count_row = $count_result->fetch_assoc();
$total_records = $count_row['total'];
$total_pages = ceil($total_records / $limit);
$stmt->close();

// Get classes for dropdown
$classes_query = "SELECT DISTINCT id, CONCAT(class_name, ' - ', section) as class_name FROM classes WHERE status = 'active' ORDER BY class_name";
$classes_result = $conn->query($classes_query);
$classes = $classes_result->fetch_all(MYSQLI_ASSOC);
?>

<h1 class="page-title"><i class="fas fa-users"></i> Student Management</h1>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-light border-bottom d-flex justify-content-between align-items-center">
        <h5 class="mb-0">All Students</h5>
        <a href="add.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add New Student</a>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-8">
                <form method="GET" action="" class="d-flex gap-2">
                    <input type="text" name="search" class="form-control" placeholder="Search by admission no, name, or parent name..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-outline-primary"><i class="fas fa-search"></i> Search</button>
                    <?php if (!empty($search) || !empty($class_filter)): ?>
                        <a href="list.php" class="btn btn-outline-secondary"><i class="fas fa-times"></i> Clear</a>
                    <?php endif; ?>
                </form>
            </div>
            <div class="col-md-4">
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
        </div>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Admission No</th>
                        <th>Full Name</th>
                        <th>Class</th>
                        <th>Gender</th>
                        <th>Parent Name</th>
                        <th>Contact</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($students)): ?>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($student['admission_no']); ?></strong></td>
                                <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['class_id']); ?> - <?php echo htmlspecialchars($student['section']); ?></td>
                                <td><?php echo htmlspecialchars($student['gender']); ?></td>
                                <td><?php echo htmlspecialchars($student['parent_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['contact']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo ($student['status'] == 'active') ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($student['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="view.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-info" title="View"><i class="fas fa-eye"></i></a>
                                    <a href="edit.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-warning" title="Edit"><i class="fas fa-edit"></i></a>
                                    <a href="delete.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?');" title="Delete"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No students found</td>
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
                            <a class="page-link" href="?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($class_filter) ? '&class=' . $class_filter : ''; ?>">First</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($class_filter) ? '&class=' . $class_filter : ''; ?>">Previous</a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($class_filter) ? '&class=' . $class_filter : ''; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($class_filter) ? '&class=' . $class_filter : ''; ?>">Next</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $total_pages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($class_filter) ? '&class=' . $class_filter : ''; ?>">Last</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>

        <p class="text-muted small mt-3">Total Records: <?php echo $total_records; ?></p>
    </div>
</div>

<?php require_once '../footer.php'; ?>
