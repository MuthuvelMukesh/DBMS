<?php
require_once '../header.php';

// Adjust based on RBAC
if (in_array($role, ['student', 'parent'], true)) {
    // Only fetch fees for this student
    $stmt = $conn->prepare("
        SELECT f.id, f.student_id, f.fee_type, f.amount, f.due_date, f.paid_date,
               f.payment_status, f.receipt_no, s.admission_no, s.full_name
        FROM fees f
        JOIN students s ON f.student_id = s.id
        WHERE s.user_id = ?
        ORDER BY f.created_at DESC
    ");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $fees = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $query = "
        SELECT f.id, f.student_id, f.fee_type, f.amount, f.due_date, f.paid_date,   
               f.payment_status, f.receipt_no, s.admission_no, s.full_name
        FROM fees f
        JOIN students s ON f.student_id = s.id
        ORDER BY f.created_at DESC
    ";
    $result = $conn->query($query);
    $fees = $result->fetch_all(MYSQLI_ASSOC);
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
        <div class="table-responsive">
            <table class="table table-hover datatable align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Receipt No</th>
                        <th>Admission No</th>
                        <th>Student Name</th>
                        <th>Fee Type</th>
                        <th>Amount</th>
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
                        <tr><td colspan="8" class="text-center py-4">No records found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../footer.php'; ?>