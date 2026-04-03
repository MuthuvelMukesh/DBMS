<?php
require_once dirname(__DIR__) . '/header.php';

if (!in_array($_SESSION['role'], ['admin', 'teacher'])) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Access Denied.</div></div>";
    require_once dirname(__DIR__) . '/footer.php';
    exit();
}

$success = '';
$error = '';

// Handle Delete/Toggle State
if (isset($_GET['toggle']) && $_SESSION['role'] === 'admin') {
    $id = (int)$_GET['toggle'];
    $conn->query("UPDATE classes SET status = IF(status='active', 'inactive', 'active') WHERE id = $id");
    $success = "Class status updated.";
}

// Handle Add Class
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_class']) && $_SESSION['role'] === 'admin') {
    $c_name = trim($_POST['class_name']);
    $section = trim($_POST['section']);
    $teacher_id = !empty($_POST['class_teacher_id']) ? (int)$_POST['class_teacher_id'] : "NULL";

    if (empty($c_name) || empty($section)) {
        $error = "Class Name and Section are required.";
    } else {
        $sql = "INSERT INTO classes (class_name, section, class_teacher_id) VALUES (?, ?, " . ($teacher_id === "NULL" ? "NULL" : "?") . ")";
        $stmt = $conn->prepare($sql);
        if ($teacher_id !== "NULL") {
            $stmt->bind_param("ssi", $c_name, $section, $teacher_id);
        } else {
            $stmt->bind_param("ss", $c_name, $section);
        }
        
        try {
            if ($stmt->execute()) {
                $success = "Class added successfully.";
            } else {
                $error = "Failed to add class.";
            }
        } catch (mysqli_sql_exception $e) {
            $error = "Duplicate class and section combination.";
        }
    }
}

// Fetch all classes
$classes_query = "
    SELECT c.*, s.full_name as teacher_name
    FROM classes c
    LEFT JOIN staff s ON c.class_teacher_id = s.user_id
    ORDER BY 
        CASE 
            WHEN c.class_name LIKE 'Pre%' THEN 1
            WHEN c.class_name LIKE 'LKG%' THEN 2
            WHEN c.class_name LIKE 'UKG%' THEN 3
            WHEN c.class_name LIKE 'I Std%' THEN 4
            WHEN c.class_name LIKE 'II Std%' THEN 5
            WHEN c.class_name LIKE 'III Std%' THEN 6
            WHEN c.class_name LIKE 'IV Std%' THEN 7
            WHEN c.class_name LIKE 'V Std%' THEN 8
            WHEN c.class_name LIKE 'VI Std%' THEN 9
            WHEN c.class_name LIKE 'VII Std%' THEN 10
            WHEN c.class_name LIKE 'VIII Std%' THEN 11
            WHEN c.class_name LIKE 'IX Std%' THEN 12
            WHEN c.class_name LIKE 'X Std%' THEN 13
            WHEN c.class_name LIKE 'XI Std%' THEN 14
            WHEN c.class_name LIKE 'XII Std%' THEN 15
            ELSE 99 
        END, c.class_name, c.section
";
$classes = $conn->query($classes_query);

// Fetch teachers for dropdown
$teachers = $conn->query("SELECT u.id, s.full_name, s.staff_id FROM users u INNER JOIN staff s ON u.id = s.user_id WHERE u.role IN ('admin', 'teacher') AND u.status = 'active'");
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="page-title mb-0"><i class="fas fa-chalkboard text-primary"></i> Manage Classes (TN Curriculum)</h1>
        <?php if ($_SESSION['role'] === 'admin'): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addClassModal">
            <i class="fas fa-plus"></i> Add New Class
        </button>
        <?php endif; ?>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover datatable align-middle w-100">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Class standard</th>
                            <th>Section / Group</th>
                            <th>Class Teacher</th>
                            <th>Status</th>
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                            <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $classes->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td class="fw-bold text-primary"><?php echo htmlspecialchars($row['class_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['section']); ?></td>
                            <td>
                                <?php if ($row['teacher_name']): ?>
                                    <span class="badge bg-info text-dark"><i class="fas fa-user-tie mt-1"></i> <?php echo htmlspecialchars(ucfirst($row['teacher_name'])); ?></span>
                                <?php else: ?>
                                    <span class="text-muted"><i class="fas fa-user-times"></i> Unassigned</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($row['status'] === 'active'): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                            <td>
                                <a href="?toggle=<?php echo $row['id']; ?>" class="btn btn-sm btn-<?php echo $row['status'] === 'active' ? 'warning' : 'success'; ?> me-1" title="Toggle Status">
                                    <i class="fas fa-power-off"></i>
                                </a>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if ($_SESSION['role'] === 'admin'): ?>
<!-- Add Class Modal -->
<div class="modal fade" id="addClassModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-chalkboard"></i> Add Classroom</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="add_class" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label">Class Name / Standard <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="class_name" required placeholder="e.g. XI Std">
                        <div class="form-text">Follow convention: LKG, I Std, IX Std, XII Std</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Section or Group <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="section" required placeholder="e.g. A, B, A (Bio-Maths)">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Assign Class Teacher</label>
                        <select class="form-select" name="class_teacher_id">
                            <option value="">-- Leave Unassigned --</option>
                            <?php while ($tRow = $teachers->fetch_assoc()): ?>
                                <option value="<?php echo $tRow['id']; ?>"><?php echo htmlspecialchars($tRow['full_name'] . ' (' . $tRow['staff_id'] . ')'); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Class</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once dirname(__DIR__) . '/footer.php'; ?>