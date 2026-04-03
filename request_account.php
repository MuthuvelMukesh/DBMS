<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'dbconfig.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit();
}

$error = '';
$success = '';
$full_name = '';
$username = '';
$email = '';
$phone = '';
$request_note = '';

$create_table_sql = "CREATE TABLE IF NOT EXISTS account_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    password_hash VARCHAR(255) NOT NULL,
    request_note VARCHAR(255) DEFAULT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    assigned_role ENUM('teacher', 'staff', 'parent', 'student') DEFAULT NULL,
    admin_note VARCHAR(255) DEFAULT NULL,
    reviewed_by INT DEFAULT NULL,
    reviewed_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_account_requests_status (status),
    INDEX idx_account_requests_username (username),
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (!$conn->query($create_table_sql)) {
    $error = 'Unable to initialize account requests. Please contact admin.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $full_name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $request_note = trim($_POST['request_note'] ?? '');

    if (strlen($full_name) < 3) {
        $error = 'Please enter your full name.';
    } elseif (!preg_match('/^[A-Za-z0-9_.-]{3,30}$/', $username)) {
        $error = 'Username must be 3-30 chars and can contain letters, numbers, dot, underscore, and hyphen.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm_password) {
        $error = 'Password and confirm password do not match.';
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } elseif (!empty($phone) && !preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) {
        $error = 'Enter a valid phone number.';
    } elseif (strlen($request_note) > 255) {
        $error = 'Note must be 255 characters or fewer.';
    } else {
        $stmt = $conn->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $exists_in_users = $stmt->get_result()->num_rows > 0;
        $stmt->close();

        if ($exists_in_users) {
            $error = 'This username is already taken.';
        } else {
            $stmt = $conn->prepare("SELECT id FROM account_requests WHERE username = ? AND status = 'pending' LIMIT 1");
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $has_pending_request = $stmt->get_result()->num_rows > 0;
            $stmt->close();

            if ($has_pending_request) {
                $error = 'You already have a pending request with this username.';
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO account_requests (full_name, username, email, phone, password_hash, request_note, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
                $stmt->bind_param('ssssss', $full_name, $username, $email, $phone, $password_hash, $request_note);

                if ($stmt->execute()) {
                    $success = 'Your request has been submitted. Admin will review and assign your role.';
                    $full_name = '';
                    $username = '';
                    $email = '';
                    $phone = '';
                    $request_note = '';
                } else {
                    $error = 'Failed to submit request. Please try again.';
                }

                $stmt->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Account - SchoolMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #334155 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .request-container {
            background: #ffffff;
            border-radius: 1.25rem;
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.45);
            padding: 2.2rem;
            width: 100%;
            max-width: 540px;
        }
        .request-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .request-header i {
            color: #2563eb;
            font-size: 2.2rem;
            margin-bottom: 0.75rem;
        }
        .request-header h1 {
            font-size: 1.6rem;
            font-weight: 800;
            margin-bottom: 0.3rem;
            color: #0f172a;
        }
        .request-header p {
            margin: 0;
            color: #64748b;
            font-weight: 600;
        }
        .form-label {
            font-weight: 700;
            color: #334155;
            margin-bottom: 0.35rem;
        }
        .btn-submit {
            background: #2563eb;
            color: #fff;
            font-weight: 700;
            border: 0;
            width: 100%;
            padding: 0.75rem;
            border-radius: 0.65rem;
        }
        .btn-submit:hover {
            background: #1d4ed8;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="request-container">
        <div class="request-header">
            <i class="fas fa-user-plus"></i>
            <h1>Request Account</h1>
            <p>Submit your details. Admin will approve and assign your role.</p>
        </div>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success" role="alert">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="full_name">Full Name</label>
                    <input type="text" class="form-control" id="full_name" name="full_name" maxlength="100" value="<?php echo htmlspecialchars($full_name); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="username">Username</label>
                    <input type="text" class="form-control" id="username" name="username" maxlength="50" value="<?php echo htmlspecialchars($username); ?>" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="email">Email (Optional)</label>
                    <input type="email" class="form-control" id="email" name="email" maxlength="100" value="<?php echo htmlspecialchars($email); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="phone">Phone (Optional)</label>
                    <input type="text" class="form-control" id="phone" name="phone" maxlength="20" value="<?php echo htmlspecialchars($phone); ?>">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" class="form-control" id="password" name="password" minlength="6" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="confirm_password">Confirm Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="6" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label" for="request_note">Note (Optional)</label>
                <textarea class="form-control" id="request_note" name="request_note" rows="2" maxlength="255" placeholder="Tell admin why you need access"><?php echo htmlspecialchars($request_note); ?></textarea>
            </div>

            <button type="submit" class="btn btn-submit mb-2">
                <i class="fas fa-paper-plane"></i> Submit Request
            </button>
        </form>

        <div class="text-center mt-2">
            <a href="<?php echo BASE_URL; ?>login.php" class="text-decoration-none">Back to Login</a>
        </div>
    </div>
</body>
</html>
