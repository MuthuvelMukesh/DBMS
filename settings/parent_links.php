<?php
require_once dirname(__DIR__) . '/header.php';

if ($_SESSION['role'] !== 'admin') {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Access Denied.</div></div>";
    require_once dirname(__DIR__) . '/footer.php';
    exit();
}

$success = '';
$error = '';

if (!ensure_parent_student_links_table($conn)) {
    $error = 'Unable to initialize parent-student links table.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $action = $_POST['action'] ?? '';

    if ($action === 'link') {
        $parent_user_id = isset($_POST['parent_user_id']) ? (int) $_POST['parent_user_id'] : 0;
        $student_id = isset($_POST['student_id']) ? (int) $_POST['student_id'] : 0;
        $relationship = trim($_POST['relationship'] ?? '');

        if ($parent_user_id <= 0 || $student_id <= 0) {
            $error = 'Please select a parent and student.';
        } else {
            $stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'parent' AND status = 'active' LIMIT 1");
            $stmt->bind_param('i', $parent_user_id);
            $stmt->execute();
            $parent_exists = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $stmt = $conn->prepare("SELECT id FROM students WHERE id = ? AND status = 'active' LIMIT 1");
            $stmt->bind_param('i', $student_id);
            $stmt->execute();
            $student_exists = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$parent_exists || !$student_exists) {
                $error = 'Selected parent or student is invalid.';
            } else {
                $stmt = $conn->prepare(
                    "INSERT INTO parent_student_links (parent_user_id, student_id, relationship, status)
                     VALUES (?, ?, ?, 'active')
                     ON DUPLICATE KEY UPDATE relationship = VALUES(relationship), status = 'active'"
                );
                $stmt->bind_param('iis', $parent_user_id, $student_id, $relationship);

                if ($stmt->execute()) {
                    $success = 'Parent-student link saved successfully.';
                } else {
                    $error = 'Unable to save link: ' . $stmt->error;
                }
                $stmt->close();
            }
        }
    } elseif ($action === 'unlink') {
        $link_id = isset($_POST['link_id']) ? (int) $_POST['link_id'] : 0;

        if ($link_id <= 0) {
            $error = 'Invalid link selected.';
        } else {
            $stmt = $conn->prepare("UPDATE parent_student_links SET status = 'inactive' WHERE id = ?");
            $stmt->bind_param('i', $link_id);

            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $success = 'Parent-student link removed.';
            } else {
                $error = 'Link not found or already inactive.';
            }
            $stmt->close();
        }
    }
}

$parents = [];
$parents_q = $conn->query(
    "SELECT u.id,
            u.username,
            COALESCE(MAX(NULLIF(ar.full_name, '')), u.username) AS parent_name,
            SUM(CASE WHEN psl.status = 'active' THEN 1 ELSE 0 END) AS linked_students
     FROM users u
     LEFT JOIN account_requests ar ON ar.username = u.username AND ar.status = 'approved'
     LEFT JOIN parent_student_links psl ON psl.parent_user_id = u.id
     WHERE u.role = 'parent' AND u.status = 'active'
     GROUP BY u.id, u.username
     ORDER BY parent_name"
);
if ($parents_q) {
    $parents = $parents_q->fetch_all(MYSQLI_ASSOC);
}

$selected_parent_id = isset($_GET['parent_id']) ? (int) $_GET['parent_id'] : 0;
if ($selected_parent_id <= 0 && !empty($parents)) {
    $selected_parent_id = (int) $parents[0]['id'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['parent_user_id'])) {
    $posted_parent = (int) $_POST['parent_user_id'];
    if ($posted_parent > 0) {
        $selected_parent_id = $posted_parent;
    }
}

$linked_students = [];
$available_students = [];

if ($selected_parent_id > 0) {
    $stmt = $conn->prepare(
        "SELECT psl.id AS link_id, psl.relationship,
                s.id AS student_id, s.admission_no, s.full_name, s.section,
                c.class_name
         FROM parent_student_links psl
         JOIN students s ON s.id = psl.student_id
         LEFT JOIN classes c ON c.id = s.class_id
         WHERE psl.parent_user_id = ?
           AND psl.status = 'active'
           AND s.status = 'active'
         ORDER BY s.full_name"
    );
    $stmt->bind_param('i', $selected_parent_id);
    $stmt->execute();
    $linked_students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $stmt = $conn->prepare(
        "SELECT s.id, s.admission_no, s.full_name, s.section, c.class_name
         FROM students s
         LEFT JOIN classes c ON c.id = s.class_id
         WHERE s.status = 'active'
           AND NOT EXISTS (
               SELECT 1
               FROM parent_student_links psl
               WHERE psl.parent_user_id = ?
                 AND psl.student_id = s.id
                 AND psl.status = 'active'
           )
         ORDER BY s.full_name"
    );
    $stmt->bind_param('i', $selected_parent_id);
    $stmt->execute();
    $available_students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>

<div class="container-fluid py-4">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="page-title mb-0"><i class="fas fa-link text-primary"></i> Parent-Student Links</h1>
        <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back to Settings</a>
    </div>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <?php if (!empty($parents)): ?>
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-6 col-lg-4">
                        <label class="form-label" for="parent_id">Select Parent</label>
                        <select class="form-select" name="parent_id" id="parent_id" onchange="this.form.submit()">
                            <?php foreach ($parents as $parent): ?>
                                <option value="<?php echo (int) $parent['id']; ?>" <?php echo ((int) $parent['id'] === $selected_parent_id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($parent['parent_name']); ?> (<?php echo htmlspecialchars($parent['username']); ?>)
                                    - <?php echo (int) $parent['linked_students']; ?> linked
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            <?php else: ?>
                <div class="text-muted">No active parent accounts found. Approve parent requests first.</div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($parents) && $selected_parent_id > 0): ?>
    <div class="row">
        <div class="col-lg-5 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0"><i class="fas fa-user-plus me-2"></i>Link Student</h6>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="link">
                        <div class="mb-3">
                            <label class="form-label" for="parent_user_id">Parent</label>
                            <select class="form-select" name="parent_user_id" id="parent_user_id" required>
                                <?php foreach ($parents as $parent): ?>
                                    <option value="<?php echo (int) $parent['id']; ?>" <?php echo ((int) $parent['id'] === $selected_parent_id) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($parent['parent_name']); ?> (<?php echo htmlspecialchars($parent['username']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="student_id">Student</label>
                            <select class="form-select" name="student_id" id="student_id" required>
                                <option value="">Select student...</option>
                                <?php foreach ($available_students as $student): ?>
                                    <option value="<?php echo (int) $student['id']; ?>">
                                        <?php
                                        $class_label = trim(($student['class_name'] ?? '') . ' ' . ($student['section'] ?? ''));
                                        echo htmlspecialchars($student['admission_no'] . ' - ' . $student['full_name'] . ($class_label !== '' ? ' (' . $class_label . ')' : ''));
                                        ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (empty($available_students)): ?>
                                <div class="form-text text-muted">All active students are already linked to this parent.</div>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="relationship">Relationship (Optional)</label>
                            <input type="text" class="form-control" id="relationship" name="relationship" maxlength="50" placeholder="e.g. Father, Mother, Guardian">
                        </div>
                        <button type="submit" class="btn btn-primary" <?php echo empty($available_students) ? 'disabled' : ''; ?>>
                            <i class="fas fa-link"></i> Save Link
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0"><i class="fas fa-list me-2"></i>Linked Students</h6>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($linked_students)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Admission No</th>
                                        <th>Student</th>
                                        <th>Class</th>
                                        <th>Relationship</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($linked_students as $linked): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($linked['admission_no']); ?></td>
                                            <td><?php echo htmlspecialchars($linked['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars(trim(($linked['class_name'] ?? '') . ' ' . ($linked['section'] ?? ''))); ?></td>
                                            <td><?php echo htmlspecialchars($linked['relationship'] ?: '-'); ?></td>
                                            <td>
                                                <form method="POST" onsubmit="return confirm('Remove this parent-student link?');">
                                                    <input type="hidden" name="action" value="unlink">
                                                    <input type="hidden" name="link_id" value="<?php echo (int) $linked['link_id']; ?>">
                                                    <input type="hidden" name="parent_user_id" value="<?php echo $selected_parent_id; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-unlink"></i> Remove
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="p-3 text-muted">No students linked to this parent yet.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once dirname(__DIR__) . '/footer.php'; ?>
