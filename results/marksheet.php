<?php
require_once dirname(__DIR__) . '/includes/header.php';
if (!in_array($role, ['admin', 'teacher'], true)) {
    header('Location: ' . BASE_URL . 'dashboard.php?error=Access Denied');
    exit();
}

$error = '';
$exam_id = isset($_GET['exam']) ? (int) $_GET['exam'] : 0;

$exams_result = $conn->query("SELECT id, CONCAT(exam_name, ' - ', subject) as exam_name FROM exams ORDER BY exam_date DESC");
$exams = $exams_result instanceof mysqli_result ? $exams_result->fetch_all(MYSQLI_ASSOC) : [];

$exam = null;
$results = [];

if ($exam_id > 0) {
    $stmt = $conn->prepare("SELECT id, exam_name, subject, max_marks, pass_marks, class_id FROM exams WHERE id = ?");
    $stmt->bind_param("i", $exam_id);
    $stmt->execute();
    $exam_result = $stmt->get_result();
    $exam = $exam_result->fetch_assoc();
    $stmt->close();

    if ($exam) {
        $stmt = $conn->prepare(
            "SELECT r.id, r.student_id, r.marks_obtained, r.grade,
                    s.admission_no, s.full_name
             FROM results r
             JOIN students s ON r.student_id = s.id
             WHERE r.exam_id = ?
             ORDER BY r.marks_obtained DESC"
        );
        $stmt->bind_param("i", $exam_id);
        $stmt->execute();
        $results_result = $stmt->get_result();
        $results = $results_result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else {
        $error = 'Selected exam was not found.';
    }
}
?>

<style>
    .marksheet-container {
        background: #ffffff;
        max-width: 980px;
        margin: 0 auto;
        padding: 2rem;
        border: 1px solid #dbe2ea;
        border-radius: 0.9rem;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
    }

    .marksheet-header {
        text-align: center;
        border-bottom: 2px solid #1d4ed8;
        padding-bottom: 1rem;
        margin-bottom: 1.5rem;
    }

    .marksheet-header h1 {
        color: #1d4ed8;
        margin-bottom: 0.4rem;
        font-size: 1.7rem;
    }

    .marksheet-header p {
        color: #475569;
        margin: 0;
        font-size: 0.95rem;
    }

    .exam-info {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        padding: 1rem;
        border-radius: 0.55rem;
        margin-bottom: 1.5rem;
    }

    .marksheet-footer {
        text-align: center;
        margin-top: 1.5rem;
        padding-top: 1rem;
        border-top: 1px solid #e2e8f0;
        color: #64748b;
        font-size: 0.85rem;
    }

    @media print {
        .no-print,
        .app-menu-toggle,
        .sidebar-wrapper,
        .app-sidebar-overlay,
        footer {
            display: none !important;
        }

        .app-main {
            padding: 0 !important;
        }

        .marksheet-container {
            box-shadow: none;
            border: 1px solid #d1d5db;
            max-width: 100%;
            margin: 0;
        }
    }
</style>

<h1 class="page-title no-print"><i class="fas fa-file-pdf"></i> Marksheet</h1>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger no-print" role="alert">
        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<?php if ($exam_id === 0 || !$exam): ?>
    <div class="card border-0 shadow-sm no-print">
        <div class="card-header bg-light border-bottom">
            <h5 class="mb-0">Choose Exam</h5>
        </div>
        <div class="card-body">
            <?php if (!empty($exams)): ?>
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-9">
                        <label for="exam" class="form-label">Exam</label>
                        <select class="form-select" id="exam" name="exam" required>
                            <option value="">Select exam...</option>
                            <?php foreach ($exams as $exam_option): ?>
                                <option value="<?php echo (int) $exam_option['id']; ?>"><?php echo htmlspecialchars($exam_option['exam_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 d-grid">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-eye"></i> Open Marksheet</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="alert alert-info mb-0" role="alert">
                    <i class="fas fa-info-circle"></i> No exams found. Create an exam first.
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
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
                    <p><strong>Max Marks:</strong> <?php echo (int) $exam['max_marks']; ?></p>
                </div>
                <div class="col-md-3">
                    <p><strong>Pass Marks:</strong> <?php echo (int) $exam['pass_marks']; ?></p>
                </div>
            </div>
            <p class="mb-0"><strong>Date:</strong> <?php echo date('d-m-Y'); ?></p>
        </div>

        <?php if (!empty($results)): ?>
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <caption class="visually-hidden">Ranked exam results with marks, grades, and pass status</caption>
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
                            $passed = ((int) $result['marks_obtained'] >= (int) $exam['pass_marks']) ? 'Passed' : 'Failed';
                        ?>
                            <tr>
                                <td class="text-center"><strong><?php echo $rank++; ?></strong></td>
                                <td><?php echo htmlspecialchars($result['admission_no']); ?></td>
                                <td><?php echo htmlspecialchars($result['full_name']); ?></td>
                                <td class="text-center"><strong><?php echo (int) $result['marks_obtained']; ?>/<?php echo (int) $exam['max_marks']; ?></strong></td>
                                <td class="text-center"><strong><?php echo htmlspecialchars($result['grade']); ?></strong></td>
                                <td class="text-center">
                                    <span class="badge bg-<?php echo $passed === 'Passed' ? 'success' : 'danger'; ?>">
                                        <?php echo $passed; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info" role="alert">
                <i class="fas fa-info-circle"></i> No results found for this exam.
            </div>
        <?php endif; ?>

        <div class="marksheet-footer">
            <p>This is the official examination marksheet generated on <?php echo date('d-m-Y H:i:s'); ?></p>
        </div>
    </div>

    <div class="d-flex gap-2 justify-content-center mt-3 no-print">
        <button type="button" class="btn btn-primary" onclick="window.print();"><i class="fas fa-print"></i> Print Marksheet</button>
        <a href="report.php?exam=<?php echo (int) $exam_id; ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
<?php endif; ?>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
