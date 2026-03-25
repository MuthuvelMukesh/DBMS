<?php
require_once '../header.php';

$fee_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($fee_id == 0) {
    header("Location: list.php");
    exit();
}

// Fetch fee details
$stmt = $conn->prepare("
    SELECT f.id, f.student_id, f.fee_type, f.amount, f.due_date, f.paid_date, 
           f.payment_status, f.receipt_no, f.created_at,
           s.admission_no, s.full_name, s.class_id, s.section
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

        <div class="receipt-body">
            <div class="receipt-row">
                <span class="label">Receipt Number</span>
                <span class="value"><strong><?php echo htmlspecialchars($fee['receipt_no']); ?></strong></span>
            </div>

            <div class="receipt-row">
                <span class="label">Date</span>
                <span class="value"><?php echo date('d-m-Y', strtotime($fee['paid_date'] ?? $fee['created_at'])); ?></span>
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
                    <span class="label">Due Date</span>
                    <span class="value"><?php echo date('d-m-Y', strtotime($fee['due_date'])); ?></span>
                </div>
                <div class="receipt-row" style="border: none;">
                    <span class="label">Payment Date</span>
                    <span class="value"><?php echo date('d-m-Y', strtotime($fee['paid_date'] ?? date('Y-m-d'))); ?></span>
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
