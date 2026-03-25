<?php
require_once '../header.php';

if ($role != 'admin') {
    echo '<div class="alert alert-danger">You do not have permission to access this page.</div>';
    require_once '../footer.php';
    exit();
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$department_filter = isset($_GET['department']) ? trim($_GET['department']) : '';
$limit = 10;
$offset = ($page - 1) * $limit;

// Build query
$query = "SELECT id, staff_id, full_name, designation, department, contact, email, salary, join_date, status 
          FROM staff WHERE status != 'deleted'";
$params = [];
$types = "";

if (!empty($search)) {
    $query .= " AND (staff_id LIKE ? OR full_name LIKE ? OR email LIKE ?)";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param];
    $types = "sss";
}

if (!empty($department_filter)) {
    $query .= " AND department = ?";
    $params[] = $department_filter;
    $types .= "s";
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
$staff = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get total count
$count_query = "SELECT COUNT(*) as total FROM staff WHERE status != 'deleted'";
if (!empty($search)) {
    $count_query .= " AND (staff_id LIKE ? OR full_name LIKE ? OR email LIKE ?)";
    $stmt = $conn->prepare($count_query);
    $search_param = "%$search%";
    $stmt->bind_param("sss", $search_param, $search_param, $search_param);
} else if (!empty($department_filter)) {
    $count_query .= " AND department = ?";
    $stmt = $conn->prepare($count_query);
    $stmt->bind_param("s", $department_filter);
} else {
    $stmt = $conn->prepare($count_query);
}
$stmt->execute();
$count_result = $stmt->get_result();
$count_row = $count_result->fetch_assoc();
$total_records = $count_row['total'];
$total_pages = ceil($total_records / $limit);
$stmt->close();

// Get unique departments
$depts_result = $conn->query("SELECT DISTINCT department FROM staff WHERE status != 'deleted' ORDER BY department");
$departments = $depts_result->fetch_all(MYSQLI_ASSOC);
?>

<h1 class="page-title"><i class="fas fa-briefcase"></i> Staff Management</h1>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-light border-bottom d-flex justify-content-between align-items-center">
        <h5 class="mb-0">All Staff Members</h5>
        <a href="add.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add New Staff</a>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-8">
                <form method="GET" action="" class="d-flex gap-2">
                    <input type="text" name="search" class="form-control" placeholder="Search by staff ID, name, or email..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-outline-primary"><i class="fas fa-search"></i> Search</button>
                    <?php if (!empty($search) || !empty($department_filter)): ?>
                        <a href="list.php" class="btn btn-outline-secondary"><i class="fas fa-times"></i> Clear</a>
                    <?php endif; ?>
                </form>
            </div>
            <div class="col-md-4">
                <form method="GET" action="">
                    <select name="department" class="form-select" onchange="this.form.submit();">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo htmlspecialchars($dept['department']); ?>" <?php echo ($dept['department'] == $department_filter) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['department']); ?>
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
                        <th>Staff ID</th>
                        <th>Full Name</th>
                        <th>Designation</th>
                        <th>Department</th>
                        <th>Contact</th>
                        <th>Salary</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($staff)): ?>
                        <?php foreach ($staff as $member): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($member['staff_id']); ?></strong></td>
                                <td><?php echo htmlspecialchars($member['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($member['designation']); ?></td>
                                <td><?php echo htmlspecialchars($member['department']); ?></td>
                                <td><?php echo htmlspecialchars($member['contact']); ?></td>
                                <td>₹<?php echo number_format($member['salary'], 2); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo ($member['status'] == 'active') ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($member['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="edit.php?id=<?php echo $member['id']; ?>" class="btn btn-sm btn-warning" title="Edit"><i class="fas fa-edit"></i></a>
                                    <a href="delete.php?id=<?php echo $member['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?');" title="Delete"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No staff members found</td>
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
                            <a class="page-link" href="?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">First</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Previous</a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Next</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $total_pages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Last</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>

        <p class="text-muted small mt-3">Total Records: <?php echo $total_records; ?></p>
    </div>
</div>

<?php require_once '../footer.php'; ?>
