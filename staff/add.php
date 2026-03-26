<?php
require_once '../header.php';

if ($role != 'admin') {
    echo '<div class="alert alert-danger">You do not have permission to access this page.</div>';
    require_once '../footer.php';
    exit();
}

$error = '';
$success = '';

// Get next staff ID
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM staff");
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$next_staff_id = 'STF' . str_pad($row['total'] + 1, 5, '0', STR_PAD_LEFT);
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $staff_id = $next_staff_id;
    $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $designation = isset($_POST['designation']) ? trim($_POST['designation']) : '';
    $department = isset($_POST['department']) ? trim($_POST['department']) : '';
    $contact = isset($_POST['contact']) ? trim($_POST['contact']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $salary = isset($_POST['salary']) ? (float)$_POST['salary'] : 0;
    $join_date = isset($_POST['join_date']) ? $_POST['join_date'] : '';

    if (empty($full_name) || empty($designation) || empty($department) || empty($contact) || empty($join_date)) {
        $error = 'All required fields must be filled!';
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL) && !empty($email)) {
        $error = 'Invalid email format!';
    } else {
        $stmt = $conn->prepare("
            INSERT INTO staff (staff_id, full_name, designation, department, contact, email, salary, join_date, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')
        ");
        $stmt->bind_param("ssssssds", $staff_id, $full_name, $designation, $department, $contact, $email, $salary, $join_date);

        if ($stmt->execute()) {
            $success = 'Staff member added successfully! Staff ID: ' . $staff_id;
            $_POST = [];
        } else {
            $error = 'Error adding staff: ' . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<h1 class="page-title"><i class="fas fa-user-plus"></i> Add New Staff Member</h1>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-light border-bottom">
        <h5 class="mb-0">Staff Registration Form</h5>
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
                    <input type="text" class="form-control" id="full_name" name="full_name" required value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="designation" class="form-label">Designation *</label>
                    <input type="text" class="form-control" id="designation" name="designation" placeholder="e.g., Teacher, Vice-Principal" required value="<?php echo isset($_POST['designation']) ? htmlspecialchars($_POST['designation']) : ''; ?>">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="department" class="form-label">Department *</label>
                    <input type="text" class="form-control" id="department" name="department" placeholder="e.g., Science, English, Administration" required value="<?php echo isset($_POST['department']) ? htmlspecialchars($_POST['department']) : ''; ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="contact" class="form-label">Contact Number *</label>
                    <input type="tel" class="form-control" id="contact" name="contact" pattern="[0-9]{10}" placeholder="10-digit mobile number" required value="<?php echo isset($_POST['contact']) ? htmlspecialchars($_POST['contact']) : ''; ?>">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="join_date" class="form-label">Join Date *</label>
                    <input type="date" class="form-control" id="join_date" name="join_date" required value="<?php echo isset($_POST['join_date']) ? htmlspecialchars($_POST['join_date']) : ''; ?>">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="salary" class="form-label">Salary (Monthly)</label>
                    <input type="number" class="form-control" id="salary" name="salary" step="0.01" min="0" value="<?php echo isset($_POST['salary']) ? htmlspecialchars($_POST['salary']) : ''; ?>">
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Add Staff Member</button>
                <a href="list.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../footer.php'; ?>
