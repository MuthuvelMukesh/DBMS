<?php
require_once '../header.php';

$fees = [];
$error = '';
$schema_notice = '';

$fees_columns = [];
$columns_result = $conn->query("SHOW COLUMNS FROM fees");
if ($columns_result instanceof mysqli_result) {
    while ($column = $columns_result->fetch_assoc()) {
        $fees_columns[$column['Field']] = true;
    }
    $columns_result->free();
} else {
    $error = 'Unable to inspect fees table schema: ' . $conn->error;
}

$has_paid_amount = isset($fees_columns['paid_amount']);
$has_receipt_no = isset($fees_columns['receipt_no']);
$has_created_at = isset($fees_columns['created_at']);

if (!$has_paid_amount) {
    $schema_notice = 'Legacy fees schema detected. Run db_patch_fees_paid_amount.sql to enable partial payment tracking.';
}

$paid_amount_select = $has_paid_amount
    ? 'f.paid_amount'
    : "CASE WHEN f.payment_status = 'Paid' THEN f.amount ELSE 0 END AS paid_amount";
$receipt_no_select = $has_receipt_no
    ? 'f.receipt_no'
    : "NULL AS receipt_no";
$order_by = $has_created_at ? 'f.created_at DESC' : 'f.id DESC';

// Adjust based on RBAC
if ($error === '') {
    if ($role === 'student') {
        $stmt = $conn->prepare("
            SELECT f.id, f.student_id, f.fee_type, f.amount, {$paid_amount_select}, f.due_date, f.paid_date,
                   f.payment_status, {$receipt_no_select}, s.admission_no, s.full_name
            FROM fees f
            JOIN students s ON f.student_id = s.id
            WHERE s.user_id = ?
            ORDER BY {$order_by}
        ");

        if ($stmt) {
            $stmt->bind_param("i", $_SESSION['user_id']);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                if ($result instanceof mysqli_result) {
                    $fees = $result->fetch_all(MYSQLI_ASSOC);
                } else {
                    $error = 'Unable to read fee records for this user.';
                }
            } else {
                $error = 'Unable to load fee records: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $error = 'Unable to prepare fee query: ' . $conn->error;
        }
    } elseif ($role === 'parent') {
        if (!ensure_parent_student_links_table($conn)) {
            $error = 'Unable to load parent-student links.';
        } else {
            $stmt = $conn->prepare("
                SELECT f.id, f.student_id, f.fee_type, f.amount, {$paid_amount_select}, f.due_date, f.paid_date,
                       f.payment_status, {$receipt_no_select}, s.admission_no, s.full_name
                FROM fees f
                JOIN students s ON f.student_id = s.id
                JOIN parent_student_links psl ON psl.student_id = s.id
                WHERE psl.parent_user_id = ?
                  AND psl.status = 'active'
                ORDER BY {$order_by}
            ");

            if ($stmt) {
                $stmt->bind_param("i", $_SESSION['user_id']);
                if ($stmt->execute()) {
                    $result = $stmt->get_result();
                    if ($result instanceof mysqli_result) {
                        $fees = $result->fetch_all(MYSQLI_ASSOC);
                    } else {
                        $error = 'Unable to read fee records for linked students.';
                    }
                } else {
                    $error = 'Unable to load fee records: ' . $stmt->error;
                }
                $stmt->close();
            } else {
                $error = 'Unable to prepare fee query: ' . $conn->error;
            }
        }
    } else {
        $query = "
            SELECT f.id, f.student_id, f.fee_type, f.amount, {$paid_amount_select}, f.due_date, f.paid_date,
                   f.payment_status, {$receipt_no_select}, s.admission_no, s.full_name
            FROM fees f
            JOIN students s ON f.student_id = s.id
            ORDER BY {$order_by}
        ";
        $result = $conn->query($query);
        if ($result instanceof mysqli_result) {
            $fees = $result->fetch_all(MYSQLI_ASSOC);
        } else {
            $error = 'Unable to load fee records: ' . $conn->error;
        }
    }
}
?>

<h1 class="page-title"><i class="fas fa-money-bill-wave"></i> Fee Management</h1>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-light border-bottom d-flex justify-content-between align-items-center">
        <h5 class="mb-0">All Fees</h5>
        <?php if (in_array($role, ['admin'])): ?>
            <a href="add.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Fee</a>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($schema_notice)): ?>
            <div class="alert alert-warning" role="alert">
                <i class="fas fa-triangle-exclamation"></i> <?php echo htmlspecialchars($schema_notice); ?>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-hover datatable align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Receipt No</th>
                        <th>Admission No</th>
                        <th>Student Name</th>
                        <th>Fee Type</th>
                        <th>Amount</th>
                        <th>Paid / Due</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
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
                                <td><strong>₹<?php echo number_format($fee['amount'], 2); ?></strong></td>
                                <td>
                                    <?php
                                        $paid_amount = (float) ($fee['paid_amount'] ?? 0);
                                        $remaining_amount = max(0, (float) $fee['amount'] - $paid_amount);
                                    ?>
                                    <span class="text-success">₹<?php echo number_format($paid_amount, 2); ?></span>
                                    /
                                    <span class="text-danger">₹<?php echo number_format($remaining_amount, 2); ?></span>
                                </td>
                                <td><?php echo date('d-m-Y', strtotime($fee['due_date'])); ?></td>
                                <td>
                                    <?php if ($fee['payment_status'] == 'Paid'): ?>
                                        <span class="badge bg-success">Paid</span>
                                    <?php elseif ($fee['payment_status'] == 'Partial'): ?>
                                        <span class="badge bg-warning text-dark">Partial</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <?php if ($fee['payment_status'] != 'Paid' && in_array($role, ['admin'])): ?>
                                        <a href="collect.php?id=<?php echo $fee['id']; ?>" class="btn btn-outline-success" title="Collect Payment"><i class="fas fa-money-bill-wave"></i></a>
                                        <?php endif; ?>
                                        <a href="receipt.php?id=<?php echo $fee['id']; ?>" class="btn btn-outline-info" title="View Receipt"><i class="fas fa-file-invoice"></i></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="9" class="text-center py-4">No records found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../footer.php'; ?>