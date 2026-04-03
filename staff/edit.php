<?php
require_once '../header.php';

if ($role != 'admin') {
    echo '<div class="alert alert-danger">You do not have permission to access this page.</div>';
    require_once '../footer.php';
    exit();
}

$error = '';
$success = '';
$staff_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($staff_id == 0) {
    header("Location: list.php");
    exit();
}

// Fetch staff details
$stmt = $conn->prepare("SELECT * FROM staff WHERE id = ? AND status != 'deleted'");
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$result = $stmt->get_result();
$staff = $result->fetch_assoc();
$stmt->close();

if (!$staff) {
    header("Location: list.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $designation = isset($_POST['designation']) ? trim($_POST['designation']) : '';
    $department = isset($_POST['department']) ? trim($_POST['department']) : '';
    $contact = isset($_POST['contact']) ? trim($_POST['contact']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $salary = isset($_POST['salary']) ? (float)$_POST['salary'] : 0;
    $join_date = isset($_POST['join_date']) ? $_POST['join_date'] : '';
    $status = isset($_POST['status']) ? $_POST['status'] : 'active';

    if (empty($full_name) || empty($designation) || empty($department) || empty($contact) || empty($join_date)) {
        $error = 'All required fields must be filled!';
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL) && !empty($email)) {
        $error = 'Invalid email format!';
    } else {
        $stmt = $conn->prepare("
            UPDATE staff
            SET full_name = ?, designation = ?, department = ?, contact = ?, email = ?, salary = ?, join_date = ?, status = ?
            WHERE id = ?
        ");
        $stmt->bind_param("sssssdssi", $full_name, $designation, $department, $contact, $email, $salary, $join_date, $status, $staff_id);

        if ($stmt->execute()) {
            
            // Sync with users table
            $role_lower = strtolower($designation);
            $new_user_role = 'staff';
            if (strpos($role_lower, 'teacher') !== false || strpos($role_lower, 'prof') !== false || strpos($role_lower, 'lecturer') !== false) {
                $new_user_role = 'teacher';
            }
            if (!empty($staff['user_id'])) {
                $stmt_user = $conn->prepare("UPDATE users SET role = ?, status = ? WHERE id = ?");
                $stmt_user->bind_param("ssi", $new_user_role, $status, $staff['user_id']);
                $stmt_user->execute();
                $stmt_user->close();
            }

            $success = 'Staff member updated successfully!';
            $staff = [
                'id' => $staff_id,
                'staff_id' => $staff['staff_id'],
                'full_name' => $full_name,
                'designation' => $designation,
                'department' => $department,
                'contact' => $contact,
                'email' => $email,
                'salary' => $salary,
                'join_date' => $join_date,
                'status' => $status
            ];
        } else {
            $error = 'Error updating staff: ' . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<h1 class="page-title"><i class="fas fa-edit"></i> Edit Staff Member</h1>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-light border-bottom">
        <h5 class="mb-0">Staff Details - ID: <?php echo htmlspecialchars($staff['staff_id']); ?></h5>
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

        <form method="POST">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="full_name" class="form-label">Full Name *</label>
                    <input type="text" class="form-control" id="full_name" name="full_name" required value="<?php echo htmlspecialchars($staff['full_name']); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="designation" class="form-label">Designation *</label>
                    <input type="text" class="form-control" id="designation" name="designation" required value="<?php echo htmlspecialchars($staff['designation']); ?>">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="department" class="form-label">Department *</label>
                    <input type="text" class="form-control" id="department" name="department" required value="<?php echo htmlspecialchars($staff['department']); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="contact" class="form-label">Contact Number *</label>
                    <input type="tel" class="form-control" id="contact" name="contact" pattern="[0-9]{10}" required value="<?php echo htmlspecialchars($staff['contact']); ?>">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($staff['email'] ?? ''); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="join_date" class="form-label">Join Date *</label>
                    <input type="date" class="form-control" id="join_date" name="join_date" required value="<?php echo htmlspecialchars($staff['join_date']); ?>">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="salary" class="form-label">Salary (Monthly)</label>
                    <input type="number" class="form-control" id="salary" name="salary" step="0.01" min="0" value="<?php echo htmlspecialchars($staff['salary'] ?? 0); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="active" <?php echo ($staff['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo ($staff['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        <option value="retired" <?php echo ($staff['status'] == 'retired') ? 'selected' : ''; ?>>Retired</option>
                    </select>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Staff Member</button>
                <a href="list.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../footer.php'; ?>
