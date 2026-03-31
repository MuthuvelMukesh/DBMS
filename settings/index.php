<?php
require_once dirname(__DIR__) . '/header.php';

// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Access Denied.</div></div>";
    require_once dirname(__DIR__) . '/footer.php';
    exit();
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = ['school_name', 'school_address', 'academic_year', 'school_phone'];
    
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            $value = trim($_POST[$field]);
            $stmt = $conn->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->bind_param("ss", $value, $field);
            $stmt->execute();
        }
    }

    // Handle Logo Upload
    if (isset($_FILES['school_logo']) && $_FILES['school_logo']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['school_logo']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $upload_dir = dirname(__DIR__) . '/uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
            $new_filename = 'logo.' . $ext;
            $dest = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['school_logo']['tmp_name'], $dest)) {
                $db_path = 'uploads/' . $new_filename;
                $stmt = $conn->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = 'logo_path'");
                $stmt->bind_param("s", $db_path);
                $stmt->execute();
            } else {
                $error = "Failed to upload logo.";
            }
        } else {
            $error = "Invalid file format for logo (JPG, PNG, GIF only).";
        }
    }

    if (empty($error)) {
        $success = "System settings updated successfully!";
        // Refresh local settings array
        $settings_query = $conn->query("SELECT setting_key, setting_value FROM system_settings");
        while($row = $settings_query->fetch_assoc()){
            $sys_settings[$row['setting_key']] = $row['setting_value'];
        }
        $school_name = isset($sys_settings['school_name']) ? $sys_settings['school_name'] : '';
    }
}

// Ensure defaults if not set
$curr_name = $sys_settings['school_name'] ?? '';
$curr_address = $sys_settings['school_address'] ?? '';
$curr_year = $sys_settings['academic_year'] ?? '';
$curr_phone = $sys_settings['school_phone'] ?? '';
$curr_logo = $sys_settings['logo_path'] ?? '';
?>

<div class="container-fluid py-4">
    <h1 class="page-title"><i class="fas fa-cogs text-primary"></i> System Settings</h1>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-xl-8 col-lg-10">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold">General Configuration</h6>
                </div>
                <div class="card-body">
                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="school_name" class="form-label">School Name</label>
                                <input type="text" class="form-control" id="school_name" name="school_name" value="<?php echo htmlspecialchars($curr_name); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="academic_year" class="form-label">Academic Year</label>
                                <input type="text" class="form-control" id="academic_year" name="academic_year" value="<?php echo htmlspecialchars($curr_year); ?>" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="school_phone" class="form-label">Phone Number</label>
                                <input type="text" class="form-control" id="school_phone" name="school_phone" value="<?php echo htmlspecialchars($curr_phone); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="school_logo" class="form-label">School Logo (Optional)</label>
                                <input class="form-control" type="file" id="school_logo" name="school_logo" accept="image/*">
                                <?php if($curr_logo): ?>
                                    <div class="mt-2 text-muted small">Current Logo: <img src="<?php echo BASE_URL . htmlspecialchars($curr_logo); ?>" height="30" class="ms-2"></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="school_address" class="form-label">School Address</label>
                            <textarea class="form-control" id="school_address" name="school_address" rows="3"><?php echo htmlspecialchars($curr_address); ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-save"></i> Save Settings
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/footer.php'; ?>