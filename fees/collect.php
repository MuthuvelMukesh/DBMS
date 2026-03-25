<?php
require_once '../header.php';

$error = '';
$success = '';
$fee_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($fee_id == 0) {
    header("Location: list.php");
    exit();
}

// Fetch fee details
$stmt = $conn->prepare("
    SELECT f.id, f.student_id, f.fee_type, f.amount, f.due_date, f.payment_status, 
           s.admission_no, s.full_name
    FROM fees f
    JOIN students s ON f.student_id = s.id
    WHERE f.id = ?
");
$stmt->bind_param("i", $fee_id);
$stmt->execute();
$result = $stmt->get_result();
$fee = $result->fetch_assoc();
$stmt->close();

if (!$fee) {
    header("Location: list.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_amount = isset($_POST['payment_amount']) ? (float)$_POST['payment_amount'] : 0;
    $payment_date = isset($_POST['payment_date']) ? $_POST['payment_date'] : date('Y-m-d');

    if ($payment_amount <= 0) {
        $error = 'Payment amount must be greater than 0!';
    } else if ($payment_amount > $fee['amount']) {
        $error = 'Payment amount cannot exceed the fee amount!';
    } else {
        $new_status = ($payment_amount == $fee['amount']) ? 'Paid' : 'Partial';
        $receipt_no = 'RCP' . time() . rand(1000, 9999);

        $stmt = $conn->prepare("
            UPDATE fees 
            SET payment_status = ?, paid_date = ?, receipt_no = ?
            WHERE id = ?
        ");
        $stmt->bind_param("sssi", $new_status, $payment_date, $receipt_no, $fee_id);

        if ($stmt->execute()) {
            $success = 'Payment collected successfully! Receipt No: ' . $receipt_no;
            $fee['payment_status'] = $new_status;
            $fee['receipt_no'] = $receipt_no;
        } else {
            $error = 'Error processing payment: ' . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<h1 class="page-title"><i class="fas fa-cash-register"></i> Collect Payment</h1>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-light border-bottom">
        <h5 class="mb-0">Payment Collection</h5>
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
                <a href="receipt.php?id=<?php echo $fee_id; ?>" class="btn btn-sm btn-info ms-2" target="_blank">View Receipt</a>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card bg-light border-0">
                    <div class="card-body">
                        <h6 class="text-muted">Student Information</h6>
                        <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($fee['full_name']); ?></p>
                        <p class="mb-1"><strong>Admission No:</strong> <?php echo htmlspecialchars($fee['admission_no']); ?></p>
                        <p class="mb-1"><strong>Fee Type:</strong> <?php echo htmlspecialchars($fee['fee_type']); ?></p>
                        <p class="mb-1"><strong>Total Amount:</strong> ₹<?php echo number_format($fee['amount'], 2); ?></p>
                        <p class="mb-0"><strong>Due Date:</strong> <?php echo date('d-m-Y', strtotime($fee['due_date'])); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-light border-0">
                    <div class="card-body">
                        <h6 class="text-muted">Current Status</h6>
                        <p class="mb-0">
                            <span class="badge bg-<?php 
                                if ($fee['payment_status'] == 'Paid') echo 'success';
                                elseif ($fee['payment_status'] == 'Pending') echo 'danger';
                                else echo 'warning';
                            ?>" style="font-size: 1rem; padding: 0.5rem 1rem;">
                                <?php echo htmlspecialchars($fee['payment_status']); ?>
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($fee['payment_status'] != 'Paid'): ?>
        <form method="POST">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="payment_amount" class="form-label">Payment Amount *</label>
                    <input type="number" class="form-control" id="payment_amount" name="payment_amount" step="0.01" min="0" max="<?php echo $fee['amount']; ?>" placeholder="Enter amount" required>
                    <small class="text-muted">Max: ₹<?php echo number_format($fee['amount'], 2); ?></small>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="payment_date" class="form-label">Payment Date *</label>
                    <input type="date" class="form-control" id="payment_date" name="payment_date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success"><i class="fas fa-money-bill"></i> Collect Payment</button>
                <a href="list.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
        </form>
        <?php else: ?>
        <div class="alert alert-info">
            <i class="fas fa-check-circle"></i> This fee has already been paid. Receipt No: <strong><?php echo htmlspecialchars($fee['receipt_no']); ?></strong>
        </div>
        <a href="list.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../footer.php'; ?>
