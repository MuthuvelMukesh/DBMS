<?php
require_once '../header.php';

$exam_id = isset($_GET['exam']) ? (int)$_GET['exam'] : 0;

if ($exam_id == 0) {
    header("Location: report.php");
    exit();
}

// Get exam info
$stmt = $conn->prepare("SELECT id, exam_name, subject, max_marks, pass_marks, class_id FROM exams WHERE id = ?");
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$exam_result = $stmt->get_result();
$exam = $exam_result->fetch_assoc();
$stmt->close();

if (!$exam) {
    header("Location: report.php");
    exit();
}

// Get results
$stmt = $conn->prepare("
    SELECT r.id, r.student_id, r.marks_obtained, r.grade, 
           s.admission_no, s.full_name
    FROM results r
    JOIN students s ON r.student_id = s.id
    WHERE r.exam_id = ?
    ORDER BY r.marks_obtained DESC
");
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$results_result = $stmt->get_result();
$results = $results_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marksheet - School Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f5f5f5;
            padding: 2rem 0;
        }
        .marksheet-container {
            background: white;
            max-width: 900px;
            margin: 0 auto;
            padding: 3rem;
            border: 2px solid #667eea;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .marksheet-header {
            text-align: center;
            border-bottom: 2px solid #667eea;
            padding-bottom: 1rem;
            margin-bottom: 2rem;
        }
        .marksheet-header h1 {
            color: #667eea;
            margin-bottom: 0.5rem;
            font-size: 1.8rem;
        }
        .marksheet-header p {
            color: #666;
            margin: 0;
            font-size: 0.9rem;
        }
        .exam-info {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 2rem;
        }
        .exam-info h5 {
            color: #667eea;
            margin-bottom: 1rem;
        }
        .marksheet-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 0.85rem;
        }
        .print-button {
            text-align: center;
            margin: 2rem 0;
        }
        .table th {
            background-color: #667eea;
            color: white;
        }
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .print-button {
                display: none;
            }
            .marksheet-container {
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }
    </style>
</head>
<body>
    <div class="marksheet-container">
        <div class="marksheet-header">
            <h1><i class="fas fa-graduation-cap"></i> SchoolMS</h1>
            <p>School Management System</p>
            <p class="mt-2">EXAMINATION MARKSHEET</p>
        </div>

        <div class="exam-info">
            <h5><i class="fas fa-info-circle"></i> Exam Details</h5>
            <div class="row">
                <div class="col-md-3">
                    <p><strong>Exam Name:</strong> <?php echo htmlspecialchars($exam['exam_name']); ?></p>
                </div>
                <div class="col-md-3">
                    <p><strong>Subject:</strong> <?php echo htmlspecialchars($exam['subject']); ?></p>
                </div>
                <div class="col-md-3">
                    <p><strong>Max Marks:</strong> <?php echo $exam['max_marks']; ?></p>
                </div>
                <div class="col-md-3">
                    <p><strong>Pass Marks:</strong> <?php echo $exam['pass_marks']; ?></p>
                </div>
            </div>
            <p class="mb-0"><strong>Date:</strong> <?php echo date('d-m-Y'); ?></p>
        </div>

        <?php if (!empty($results)): ?>
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th style="width: 10%;">Rank</th>
                        <th style="width: 15%;">Admission No</th>
                        <th style="width: 35%;">Student Name</th>
                        <th style="width: 15%;">Marks</th>
                        <th style="width: 10%;">Grade</th>
                        <th style="width: 15%;">Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $rank = 1;
                    foreach ($results as $result): 
                        $passed = ($result['marks_obtained'] >= $exam['pass_marks']) ? 'Passed' : 'Failed';
                    ?>
                        <tr>
                            <td style="text-align: center;"><strong><?php echo $rank++; ?></strong></td>
                            <td><?php echo htmlspecialchars($result['admission_no']); ?></td>
                            <td><?php echo htmlspecialchars($result['full_name']); ?></td>
                            <td style="text-align: center;">
                                <strong><?php echo $result['marks_obtained']; ?>/<?php echo $exam['max_marks']; ?></strong>
                            </td>
                            <td style="text-align: center;">
                                <strong><?php echo htmlspecialchars($result['grade']); ?></strong>
                            </td>
                            <td style="text-align: center;">
                                <span class="badge bg-<?php echo $passed == 'Passed' ? 'success' : 'danger'; ?>">
                                    <?php echo $passed; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="row mt-4">
            <div class="col-md-4">
                <div style="border-top: 2px solid black; height: 3rem; margin-top: 2rem; display: flex; align-items: flex-end;">
                    <small>Principal Signature</small>
                </div>
            </div>
            <div class="col-md-4">
                <div style="border-top: 2px solid black; height: 3rem; margin-top: 2rem; display: flex; align-items: flex-end; justify-content: center;">
                    <small>Exam Controller</small>
                </div>
            </div>
            <div class="col-md-4">
                <div style="border-top: 2px solid black; height: 3rem; margin-top: 2rem; display: flex; align-items: flex-end; justify-content: flex-end;">
                    <small>Date</small>
                </div>
            </div>
        </div>

        <?php else: ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> No results found for this exam.
        </div>
        <?php endif; ?>

        <div class="marksheet-footer">
            <p>This is the official examination marksheet generated on <?php echo date('d-m-Y H:i:s'); ?></p>
        </div>
    </div>

    <div class="print-button">
        <a href="javascript:window.print()" class="btn btn-primary"><i class="fas fa-print"></i> Print Marksheet</a>
        <a href="report.php?exam=<?php echo $exam_id; ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
