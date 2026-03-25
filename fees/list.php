<?php
require_once '../header.php';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$limit = 10;
$offset = ($page - 1) * $limit;

// Build query
$query = "
    SELECT f.id, f.student_id, f.fee_type, f.amount, f.due_date, f.paid_date, 
           f.payment_status, f.receipt_no, s.admission_no, s.full_name
    FROM fees f
    JOIN students s ON f.student_id = s.id
    WHERE 1=1
";
$params = [];
$types = "";

if (!empty($search)) {
    $query .= " AND (s.admission_no LIKE ? OR s.full_name LIKE ? OR f.receipt_no LIKE ?)";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param];
    $types = "sss";
}

if (!empty($status_filter)) {
    $query .= " AND f.payment_status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$query .= " ORDER BY f.created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$fees = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get total count
$count_query = "SELECT COUNT(*) as total FROM fees WHERE 1=1";
if (!empty($search)) {
    $count_query .= " AND (SELECT admission_no FROM students WHERE students.id = fees.student_id) LIKE ? 
                        OR (SELECT full_name FROM students WHERE students.id = fees.student_id) LIKE ? 
                        OR receipt_no LIKE ?";
} elseif (!empty($status_filter)) {
    $count_query .= " AND payment_status = ?";
}

$stmt = $conn->prepare($count_query);
if (!empty($search)) {
    $search_param = "%$search%";
    $stmt->bind_param("sss", $search_param, $search_param, $search_param);
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

<h1 class="page-title"><i class="fas fa-money-bill-wave"></i> Fee Management</h1>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-light border-bottom d-flex justify-content-between align-items-center">
        <h5 class="mb-0">All Fees</h5>
        <a href="add.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Fee</a>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-8">
                <form method="GET" action="" class="d-flex gap-2">
                    <input type="text" name="search" class="form-control" placeholder="Search by admission no, name, or receipt no..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-outline-primary"><i class="fas fa-search"></i> Search</button>
                </form>
            </div>
            <div class="col-md-4">
                <form method="GET" action="">
                    <select name="status" class="form-select" onchange="this.form.submit();">
                        <option value="">All Status</option>
                        <option value="Paid" <?php echo ($status_filter == 'Paid') ? 'selected' : ''; ?>>Paid</option>
                        <option value="Pending" <?php echo ($status_filter == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="Partial" <?php echo ($status_filter == 'Partial') ? 'selected' : ''; ?>>Partial</option>
                    </select>
                </form>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Receipt No</th>
                        <th>Admission No</th>
                        <th>Student Name</th>
                        <th>Fee Type</th>
                        <th>Amount</th>
                        <th>Due Date</th>
                        <th>Payment Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($fees)): ?>
                        <?php foreach ($fees as $fee): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($fee['receipt_no'] ?? 'N/A'); ?></strong></td>
                                <td><?php echo htmlspecialchars($fee['admission_no']); ?></td>
                                <td><?php echo htmlspecialchars($fee['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($fee['fee_type']); ?></td>
                                <td>₹<?php echo number_format($fee['amount'], 2); ?></td>
                                <td><?php echo date('d-m-Y', strtotime($fee['due_date'])); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        if ($fee['payment_status'] == 'Paid') echo 'success';
                                        elseif ($fee['payment_status'] == 'Pending') echo 'danger';
                                        else echo 'warning';
                                    ?>">
                                        <?php echo htmlspecialchars($fee['payment_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($fee['payment_status'] != 'Paid'): ?>
                                        <a href="collect.php?id=<?php echo $fee['id']; ?>" class="btn btn-sm btn-success" title="Collect Payment"><i class="fas fa-money-bill"></i></a>
                                    <?php endif; ?>
                                    <?php if (!empty($fee['receipt_no'])): ?>
                                        <a href="receipt.php?id=<?php echo $fee['id']; ?>" class="btn btn-sm btn-info" target="_blank" title="View Receipt"><i class="fas fa-file-pdf"></i></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No fee records found</td>
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
    </div>
</div>

<?php require_once '../footer.php'; ?>
