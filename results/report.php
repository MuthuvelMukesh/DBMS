<?php
require_once '../header.php';

$selected_exam = isset($_GET['exam']) ? (int)$_GET['exam'] : 0;

// Get exams
$exams_result = $conn->query("SELECT id, CONCAT(exam_name, ' - ', subject) as exam_name FROM exams ORDER BY exam_date DESC");
$exams = $exams_result->fetch_all(MYSQLI_ASSOC);

$results = [];
$exam_info = null;

if ($selected_exam > 0) {
    // Get exam info
    $stmt = $conn->prepare("SELECT id, exam_name, subject, max_marks, pass_marks FROM exams WHERE id = ?");
    $stmt->bind_param("i", $selected_exam);
    $stmt->execute();
    $exam_result = $stmt->get_result();
    $exam_info = $exam_result->fetch_assoc();
    $stmt->close();

    if ($exam_info) {
        // Get results
        if ($role === 'student') {
            $stmt = $conn->prepare("
                SELECT r.id, r.student_id, r.marks_obtained, r.grade,
                       s.admission_no, s.full_name
                FROM results r
                JOIN students s ON r.student_id = s.id
                WHERE r.exam_id = ? AND s.user_id = ?
                ORDER BY r.marks_obtained DESC
            ");
            $stmt->bind_param("ii", $selected_exam, $_SESSION['user_id']);
        } else {
            $stmt = $conn->prepare("
                SELECT r.id, r.student_id, r.marks_obtained, r.grade,
                       s.admission_no, s.full_name
                FROM results r
                JOIN students s ON r.student_id = s.id
                WHERE r.exam_id = ?
                ORDER BY r.marks_obtained DESC
            ");
            $stmt->bind_param("i", $selected_exam);
        }
        $stmt->execute();
        $results_result = $stmt->get_result();
        $results = $results_result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}
?>

<h1 class="page-title"><i class="fas fa-chart-bar"></i> Results Report</h1>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-light border-bottom">
        <h5 class="mb-0">Exam Results</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="" class="mb-3">
            <label for="exam" class="form-label">Select Exam</label>
            <select class="form-select" id="exam" name="exam" onchange="this.form.submit();">
                <option value="">Select Exam</option>
                <?php foreach ($exams as $exam): ?>
                    <option value="<?php echo $exam['id']; ?>" <?php echo ($exam['id'] == $selected_exam) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($exam['exam_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <?php if (!empty($results) && $exam_info): ?>
        <div class="alert alert-info mb-3">
            <strong><?php echo htmlspecialchars($exam_info['exam_name']); ?></strong> | 
            Subject: <strong><?php echo htmlspecialchars($exam_info['subject']); ?></strong> | 
            Max Marks: <strong><?php echo $exam_info['max_marks']; ?></strong> | 
            Pass Marks: <strong><?php echo $exam_info['pass_marks']; ?></strong>
        </div>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Rank</th>
                        <th>Admission No</th>
                        <th>Student Name</th>
                        <th>Marks Obtained</th>
                        <th>Percentage</th>
                        <th>Grade</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $rank = 1;
                    foreach ($results as $result): 
                        $percentage = ($result['marks_obtained'] / $exam_info['max_marks']) * 100;
                        $passed = ($result['marks_obtained'] >= $exam_info['pass_marks']) ? true : false;
                    ?>
                        <tr>
                            <td><strong><?php echo $rank++; ?></strong></td>
                            <td><?php echo htmlspecialchars($result['admission_no']); ?></td>
                            <td><?php echo htmlspecialchars($result['full_name']); ?></td>
                            <td><?php echo $result['marks_obtained']; ?>/<?php echo $exam_info['max_marks']; ?></td>
                            <td>
                                <div class="progress" style="height: 1.5rem;">
                                    <div class="progress-bar bg-<?php echo ($percentage >= 70) ? 'success' : (($percentage >= 50) ? 'warning' : 'danger'); ?>" 
                                         style="width: <?php echo min($percentage, 100); ?>%;" role="progressbar">
                                        <?php echo round($percentage, 1); ?>%
                                    </div>
                                </div>
                            </td>
                            <td><strong><?php echo htmlspecialchars($result['grade']); ?></strong></td>
                            <td>
                                <span class="badge bg-<?php echo $passed ? 'success' : 'danger'; ?>">
                                    <?php echo $passed ? 'Passed' : 'Failed'; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="row mt-4 mb-3">
            <div class="col-md-3">
                <div class="card bg-light border-0">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Total Students</h6>
                        <h4 class="text-primary mb-0"><?php echo count($results); ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light border-0">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Pass Percentage</h6>
                        <h4 class="text-success mb-0">
                            <?php
                            if (count($results) > 0 && $exam_info) {
                                $passed_count = count(array_filter($results, function($r) use ($exam_info) {
                                    return $r['marks_obtained'] >= $exam_info['pass_marks'];
                                }));
                                echo round(($passed_count / count($results)) * 100, 1) . '%';
                            } else {
                                echo '0%';
                            }
                            ?>
                        </h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light border-0">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Average Marks</h6>
                        <h4 class="text-warning mb-0">
                            <?php
                            if (count($results) > 0) {
                                $total_marks = array_sum(array_column($results, 'marks_obtained'));
                                echo round($total_marks / count($results), 2);
                            } else {
                                echo '0';
                            }
                            ?>
                        </h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light border-0">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Highest Marks</h6>
                        <h4 class="text-info mb-0">
                            <?php 
                            $highest = max(array_column($results, 'marks_obtained'));
                            echo $highest;
                            ?>
                        </h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2">
            <?php if ($role !== 'student'): ?>
            <a href="add.php?exam=<?php echo $selected_exam; ?>" class="btn btn-primary"><i class="fas fa-edit"></i> Edit Results</a>
            <?php endif; ?>
            <a href="marksheet.php?exam=<?php echo $selected_exam; ?>" target="_blank" class="btn btn-success"><i class="fas fa-file-pdf"></i> View Marksheet</a>
        </div>

        <?php elseif ($selected_exam > 0): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> No results found for the selected exam.
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../footer.php'; ?>
