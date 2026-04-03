<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'dbconfig.php';

$error = '';
$success = '';

if (isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $stmt = $conn->prepare("SELECT id, username, password, role, status FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();

            if ($user['status'] === 'active' && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                header("Location: " . BASE_URL . "dashboard.php");
                exit();
            } else {
                $error = 'Invalid username or password, or your account is inactive.';
            }
        } else {
            $error = 'Invalid username or password.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - School Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #334155 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .login-container {
            background: #ffffff;
            border-radius: 1.5rem;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
            padding: 3rem 2.5rem;
            width: 100%;
            max-width: 420px;
            animation: slideUp 0.5s ease-out forwards;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .login-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        .login-header i {
            color: #3b82f6;
            font-size: 3rem;
            margin-bottom: 1rem;
            background: #eff6ff;
            padding: 1.25rem;
            border-radius: 50%;
        }
        .login-header h1 {
            color: #0f172a;
            font-size: 1.75rem;
            margin-bottom: 0.5rem;
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        .login-header p {
            color: #64748b;
            font-size: 0.95rem;
            margin-bottom: 0;
            font-weight: 600;
        }
        .form-label {
            font-weight: 700;
            color: #334155;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        .input-group {
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            border-radius: 0.75rem;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .input-group:focus-within {
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
        }
        .form-control {
            border: 1px solid #cbd5e1;
            border-left: none;
            padding: 0.75rem 1rem;
        }
        .form-control:focus {
            border-color: #cbd5e1;
            box-shadow: none;
        }
        .input-group-text {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-right: none;
            color: #94a3b8;
            padding-left: 1.25rem;
        }
        .btn-login {
            background: #3b82f6;
            border: none;
            color: white;
            padding: 0.85rem;
            border-radius: 0.75rem;
            font-weight: 700;
            font-size: 1rem;
            width: 100%;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            margin-top: 1rem;
            box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.4);
        }
        .btn-login:hover {
            background: #2563eb;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.5);
        }
        .alert {
            border-radius: 0.75rem;
            font-weight: 600;
            border: none;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <i class="fas fa-graduation-cap d-inline-block"></i>
            <h1>SchoolMS</h1>
            <p>School Management System</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                </div>
            </div>

            <button type="submit" class="btn btn-login">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>

        <div class="mt-3 text-center text-muted small">
            <p class="mb-1">Demo Login: Username <strong>admin</strong> | Password <strong>admin123</strong></p>
            <p class="mb-0">No public role registration is enabled. Accounts and roles are created by the admin for security and access control.</p>
        </div>

        <div class="mt-2 text-center">
            <a href="<?php echo BASE_URL; ?>request_account.php" class="text-decoration-none small fw-semibold">
                Need an account? Submit a request
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
