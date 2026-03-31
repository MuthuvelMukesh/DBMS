<?php
require_once '../header.php';
if (!in_array($role, ['admin', 'teacher'])) {
    header('Location: ' . BASE_URL . 'dashboard.php?error=Access Denied');
    exit();
}


$error = '';
$success = '';
$selected_exam = isset($_GET['exam']) ? (int)$_GET['exam'] : 0;

// Get exams
$exams_result = $conn->query("SELECT id, CONCAT(exam_name, ' - ', subject) as exam_name FROM exams WHERE status IN ('scheduled', 'completed') ORDER BY exam_date DESC");
$exams = $exams_result->fetch_all(MYSQLI_ASSOC);

$students = [];
$exam_info = null;

if ($selected_exam > 0) {
    // Get exam info
    $stmt = $conn->prepare("SELECT id, pass_marks, max_marks, class_id FROM exams WHERE id = ?");
    $stmt->bind_param("i", $selected_exam);
    $stmt->execute();
    $exam_result = $stmt->get_result();
    $exam_info = $exam_result->fetch_assoc();
    $stmt->close();

    if ($exam_info) {
        // Get students for this class
        $stmt = $conn->prepare("SELECT id, admission_no, full_name FROM students WHERE class_id = ? AND status = 'active' ORDER BY full_name");
        $stmt->bind_param("i", $exam_info['class_id']);
        $stmt->execute();
        $students_result = $stmt->get_result();
        $students = $students_result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $exam_id = isset($_POST['exam_id']) ? (int)$_POST['exam_id'] : 0;
    
    if ($exam_id == 0) {
        $error = 'Please select an exam!';
    } else {
        // Get exam info for marks range validation
        $stmt = $conn->prepare("SELECT pass_marks, max_marks FROM exams WHERE id = ?");
        $stmt->bind_param("i", $exam_id);
        $stmt->execute();
        $exam_result = $stmt->get_result();
        $exam = $exam_result->fetch_assoc();
        $stmt->close();

        // Process each student result
        $students_to_insert = $conn->query("SELECT id FROM students WHERE class_id = (SELECT class_id FROM exams WHERE id = $exam_id) AND status = 'active'");
        
        while ($student = $students_to_insert->fetch_assoc()) {
            $student_id = $student['id'];
            $marks = isset($_POST['marks_' . $student_id]) ? (int)$_POST['marks_' . $student_id] : 0;
            
            if ($marks < 0 || $marks > $exam['max_marks']) {
                continue;
            }

            // Calculate grade
            $percentage = ($marks / $exam['max_marks']) * 100;
            if ($percentage >= 90) $grade = 'A';
            elseif ($percentage >= 80) $grade = 'B';
            elseif ($percentage >= 70) $grade = 'C';
            elseif ($percentage >= 60) $grade = 'D';
            elseif ($percentage >= $exam['pass_marks']) $grade = 'E';
            else $grade = 'F';

            // Delete existing result if any
            $stmt = $conn->prepare("DELETE FROM results WHERE student_id = ? AND exam_id = ?");
            $stmt->bind_param("ii", $student_id, $exam_id);
            $stmt->execute();
            $stmt->close();

            // Insert new result
            $stmt = $conn->prepare("INSERT INTO results (student_id, exam_id, marks_obtained, grade) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiis", $student_id, $exam_id, $marks, $grade);
            $stmt->execute();
            $stmt->close();
        }

        // Update exam status to completed
        $stmt = $conn->prepare("UPDATE exams SET status = 'completed' WHERE id = ?");
        $stmt->bind_param("i", $exam_id);
        $stmt->execute();
        $stmt->close();

        $success = 'Results uploaded successfully!';
        $_POST = [];
    }
}
?>

<h1 class="page-title"><i class="fas fa-edit"></i> Add Exam Results</h1>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-light border-bottom">
        <h5 class="mb-0">Enter Student Marks</h5>
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
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

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

        <?php if (!empty($students) && $exam_info): ?>
        <hr>
        <form method="POST" action="">
            <input type="hidden" name="exam_id" value="<?php echo htmlspecialchars($selected_exam); ?>">

            <div class="alert alert-info">
                <strong>Max Marks:</strong> <?php echo $exam_info['max_marks']; ?> | 
                <strong>Pass Marks:</strong> <?php echo $exam_info['pass_marks']; ?>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Admission No</th>
                            <th>Student Name</th>
                            <th>Marks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($student['admission_no']); ?></strong></td>
                                <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                <td>
                                    <input type="number" class="form-control" name="marks_<?php echo $student['id']; ?>" 
                                           min="0" max="<?php echo $exam_info['max_marks']; ?>" placeholder="Enter marks">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Results</button>
                <a href="report.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
        </form>
        <?php elseif ($selected_exam > 0): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> No students found for the selected exam.
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../footer.php'; ?>
