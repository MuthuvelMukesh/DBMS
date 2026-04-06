<?php
require_once '../header.php';

$fee_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';
$schema_notice = '';

if ($fee_id == 0) {
    header("Location: list.php");
    exit();
}

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
$created_at_select = $has_created_at
    ? 'f.created_at'
    : "NULL AS created_at";

// Fetch fee details
$stmt = null;
if ($role === 'student') {
    $stmt = $conn->prepare("
        SELECT f.id, f.student_id, f.fee_type, f.amount, {$paid_amount_select}, f.due_date, f.paid_date,
               f.payment_status, {$receipt_no_select}, {$created_at_select},
               s.admission_no, s.full_name, s.class_id, s.section
        FROM fees f
        JOIN students s ON f.student_id = s.id
        WHERE f.id = ? AND s.user_id = ?
    ");
    if ($stmt) {
        $stmt->bind_param("ii", $fee_id, $_SESSION['user_id']);
    }
} elseif ($role === 'parent') {
    if (!ensure_parent_student_links_table($conn)) {
        header("Location: list.php");
        exit();
    }
    $stmt = $conn->prepare("
        SELECT f.id, f.student_id, f.fee_type, f.amount, {$paid_amount_select}, f.due_date, f.paid_date,
               f.payment_status, {$receipt_no_select}, {$created_at_select},
               s.admission_no, s.full_name, s.class_id, s.section
        FROM fees f
        JOIN students s ON f.student_id = s.id
        JOIN parent_student_links psl ON psl.student_id = s.id
        WHERE f.id = ?
          AND psl.parent_user_id = ?
          AND psl.status = 'active'
    ");
    if ($stmt) {
        $stmt->bind_param("ii", $fee_id, $_SESSION['user_id']);
    }
} else {
    $stmt = $conn->prepare("
        SELECT f.id, f.student_id, f.fee_type, f.amount, {$paid_amount_select}, f.due_date, f.paid_date,
               f.payment_status, {$receipt_no_select}, {$created_at_select},
               s.admission_no, s.full_name, s.class_id, s.section
        FROM fees f
        JOIN students s ON f.student_id = s.id
        WHERE f.id = ?
    ");
    if ($stmt) {
        $stmt->bind_param("i", $fee_id);
    }
}

$fee = null;
if ($error === '' && !$stmt) {
    $error = 'Unable to prepare receipt query: ' . $conn->error;
}

if ($error === '' && $stmt) {
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result instanceof mysqli_result) {
            $fee = $result->fetch_assoc();
        } else {
            $error = 'Unable to read receipt details.';
        }
    } else {
        $error = 'Unable to load receipt details: ' . $stmt->error;
    }
    $stmt->close();
}

if (!$fee) {
    if ($error === '') {
        header("Location: list.php");
        exit();
    }

    ?>
    <h1 class="page-title"><i class="fas fa-file-invoice"></i> Fee Receipt</h1>
    <div class="alert alert-danger" role="alert">
        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
    </div>
    <a href="list.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
    <?php
    require_once '../footer.php';
    exit();
}

$paid_amount = (float) ($fee['paid_amount'] ?? 0);
$remaining_amount = max(0, (float) $fee['amount'] - $paid_amount);
$transaction_date_source = !empty($fee['paid_date'])
    ? $fee['paid_date']
    : (!empty($fee['created_at']) ? $fee['created_at'] : date('Y-m-d'));
$payment_date_source = !empty($fee['paid_date']) ? $fee['paid_date'] : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - School Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f5f5f5;
            padding: 2rem 0;
        }
        .receipt-container {
            background: white;
            max-width: 600px;
            margin: 0 auto;
            padding: 3rem;
            border: 2px solid #667eea;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .receipt-header {
            text-align: center;
            border-bottom: 2px solid #667eea;
            padding-bottom: 1rem;
            margin-bottom: 1rem;
        }
        .receipt-header h1 {
            color: #667eea;
            margin-bottom: 0.5rem;
            font-size: 1.8rem;
        }
        .receipt-header p {
            color: #666;
            margin: 0;
            font-size: 0.9rem;
        }
        .receipt-body {
            margin: 2rem 0;
        }
        .receipt-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }
        .receipt-row.total {
            border-bottom: 2px solid #667eea;
            border-top: 2px solid #667eea;
            font-weight: bold;
            font-size: 1.1rem;
            padding: 0.8rem 0;
        }
        .label {
            color: #666;
            font-weight: 500;
        }
        .value {
            text-align: right;
            font-weight: 500;
        }
        .receipt-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 0.85rem;
        }
        .badge-paid {
            background: #28a745;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            color: white;
            display: inline-block;
            margin: 1rem 0;
        }
        .print-button {
            text-align: center;
            margin: 2rem 0;
        }
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .print-button {
                display: none;
            }
            .receipt-container {
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="receipt-header">
            <h1><i class="fas fa-graduation-cap"></i> SchoolMS</h1>
            <p>School Management System</p>
            <p class="mt-2">PAYMENT RECEIPT</p>
        </div>

        <?php if (!empty($schema_notice)): ?>
            <div class="alert alert-warning py-2 px-3" role="alert">
                <i class="fas fa-triangle-exclamation"></i> <?php echo htmlspecialchars($schema_notice); ?>
            </div>
        <?php endif; ?>

        <div class="receipt-body">
            <div class="receipt-row">
                <span class="label">Receipt Number</span>
                <span class="value"><strong><?php echo htmlspecialchars($fee['receipt_no'] ?? 'N/A'); ?></strong></span>
            </div>

            <div class="receipt-row">
                <span class="label">Date</span>
                <span class="value"><?php echo date('d-m-Y', strtotime($transaction_date_source)); ?></span>
            </div>

            <div style="margin: 1.5rem 0; padding: 1rem; background: #f8f9fa; border-radius: 5px;">
                <h6 class="text-muted mb-2">Student Information</h6>
                <div class="receipt-row" style="border: none;">
                    <span class="label">Name</span>
                    <span class="value"><?php echo htmlspecialchars($fee['full_name']); ?></span>
                </div>
                <div class="receipt-row" style="border: none;">
                    <span class="label">Admission No</span>
                    <span class="value"><?php echo htmlspecialchars($fee['admission_no']); ?></span>
                </div>
                <div class="receipt-row" style="border: none;">
                    <span class="label">Class</span>
                    <span class="value"><?php echo htmlspecialchars($fee['class_id']); ?> - <?php echo htmlspecialchars($fee['section']); ?></span>
                </div>
            </div>

            <div style="margin: 1.5rem 0; padding: 1rem; background: #f8f9fa; border-radius: 5px;">
                <h6 class="text-muted mb-2">Fee Details</h6>
                <div class="receipt-row" style="border: none;">
                    <span class="label">Fee Type</span>
                    <span class="value"><?php echo htmlspecialchars($fee['fee_type']); ?></span>
                </div>
                <div class="receipt-row" style="border: none;">
                    <span class="label">Amount</span>
                    <span class="value">₹<?php echo number_format($fee['amount'], 2); ?></span>
                </div>
                <div class="receipt-row" style="border: none;">
                    <span class="label">Paid Amount</span>
                    <span class="value">₹<?php echo number_format($paid_amount, 2); ?></span>
                </div>
                <div class="receipt-row" style="border: none;">
                    <span class="label">Remaining Due</span>
                    <span class="value">₹<?php echo number_format($remaining_amount, 2); ?></span>
                </div>
                <div class="receipt-row" style="border: none;">
                    <span class="label">Due Date</span>
                    <span class="value"><?php echo date('d-m-Y', strtotime($fee['due_date'])); ?></span>
                </div>
                <div class="receipt-row" style="border: none;">
                    <span class="label">Payment Date</span>
                    <span class="value"><?php echo $payment_date_source ? date('d-m-Y', strtotime($payment_date_source)) : 'N/A'; ?></span>
                </div>
            </div>

            <div class="receipt-row total">
                <span>Total Amount</span>
                <span>₹<?php echo number_format($fee['amount'], 2); ?></span>
            </div>

            <?php if ($fee['payment_status'] == 'Paid'): ?>
            <div style="text-align: center;">
                <div class="badge-paid">
                    <i class="fas fa-check-circle"></i> PAID
                </div>
            </div>
            <?php else: ?>
            <div style="text-align: center; margin: 1rem 0;">
                <span class="badge bg-warning" style="font-size: 0.9rem; padding: 0.5rem 1rem;">
                    STATUS: <?php echo htmlspecialchars($fee['payment_status']); ?>
                </span>
            </div>
            <?php endif; ?>
        </div>

        <div class="receipt-footer">
            <p>Thank you for your payment!</p>
            <p>This is an electronically generated receipt and does not require a signature.</p>
            <p style="color: #999; font-size: 0.8rem;">Generated on <?php echo date('d-m-Y H:i:s'); ?></p>
        </div>
    </div>

    <div class="print-button">
        <a href="javascript:window.print()" class="btn btn-primary"><i class="fas fa-print"></i> Print Receipt</a>
        <a href="list.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
