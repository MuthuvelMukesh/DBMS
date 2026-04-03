<?php
require_once dirname(__DIR__) . '/header.php';

if ($_SESSION['role'] !== 'admin') {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Access Denied.</div></div>";
    require_once dirname(__DIR__) . '/footer.php';
    exit();
}

$success = '';
$error = '';
$allowed_roles = ['teacher', 'staff', 'parent', 'student'];

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
    $error = 'Unable to initialize account requests table.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $action = $_POST['action'] ?? '';
    $request_id = isset($_POST['request_id']) ? (int) $_POST['request_id'] : 0;
    $admin_note = trim($_POST['admin_note'] ?? '');

    if ($request_id <= 0) {
        $error = 'Invalid request selected.';
    } elseif ($action === 'approve') {
        $assigned_role = $_POST['assigned_role'] ?? '';

        if (!in_array($assigned_role, $allowed_roles, true)) {
            $error = 'Please select a valid role before approval.';
        } else {
            $conn->begin_transaction();

            try {
                $stmt = $conn->prepare("SELECT id, username, password_hash FROM account_requests WHERE id = ? AND status = 'pending' LIMIT 1");
                $stmt->bind_param('i', $request_id);
                $stmt->execute();
                $request_row = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$request_row) {
                    throw new Exception('The selected request is already processed or unavailable.');
                }

                $stmt = $conn->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
                $stmt->bind_param('s', $request_row['username']);
                $stmt->execute();
                $username_exists = $stmt->get_result()->num_rows > 0;
                $stmt->close();

                if ($username_exists) {
                    throw new Exception('Username already exists in users. Reject or rename the request username.');
                }

                $active_status = 'active';
                $stmt = $conn->prepare('INSERT INTO users (username, password, role, status) VALUES (?, ?, ?, ?)');
                $stmt->bind_param('ssss', $request_row['username'], $request_row['password_hash'], $assigned_role, $active_status);
                if (!$stmt->execute()) {
                    throw new Exception('Failed to create user account.');
                }
                $stmt->close();

                $reviewed_by = (int) $_SESSION['user_id'];
                $stmt = $conn->prepare("UPDATE account_requests SET status = 'approved', assigned_role = ?, admin_note = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?");
                $stmt->bind_param('ssii', $assigned_role, $admin_note, $reviewed_by, $request_id);
                if (!$stmt->execute()) {
                    throw new Exception('Failed to update request status.');
                }
                $stmt->close();

                $conn->commit();
                $success = 'Request approved. User account created and role assigned.';
            } catch (Throwable $e) {
                $conn->rollback();
                $error = $e->getMessage();
            }
        }
    } elseif ($action === 'reject') {
        $reviewed_by = (int) $_SESSION['user_id'];
        $rejected_status = 'rejected';
        $stmt = $conn->prepare('UPDATE account_requests SET status = ?, admin_note = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ? AND status = "pending"');
        $stmt->bind_param('ssii', $rejected_status, $admin_note, $reviewed_by, $request_id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $success = 'Request rejected.';
        } else {
            $error = 'Request was already processed or not found.';
        }
        $stmt->close();
    } else {
        $error = 'Invalid action.';
    }
}

$stats = ['pending_count' => 0, 'approved_count' => 0, 'rejected_count' => 0];
$stats_query = $conn->query("SELECT
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS approved_count,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) AS rejected_count
FROM account_requests");
if ($stats_query && $stats_query->num_rows > 0) {
    $stats = $stats_query->fetch_assoc();
}

$pending_requests = $conn->query("SELECT id, full_name, username, email, phone, request_note, created_at
FROM account_requests
WHERE status = 'pending'
ORDER BY created_at ASC");

$recent_decisions = $conn->query("SELECT ar.id, ar.full_name, ar.username, ar.status, ar.assigned_role, ar.admin_note, ar.reviewed_at, u.username AS reviewed_by_name
FROM account_requests ar
LEFT JOIN users u ON u.id = ar.reviewed_by
WHERE ar.status IN ('approved', 'rejected')
ORDER BY ar.reviewed_at DESC
LIMIT 15");
?>

<div class="container-fluid py-4">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="page-title mb-0"><i class="fas fa-user-check text-primary"></i> Account Requests</h1>
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

    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <p class="text-muted mb-1">Pending</p>
                    <h3 class="mb-0"><?php echo (int) ($stats['pending_count'] ?? 0); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <p class="text-muted mb-1">Approved</p>
                    <h3 class="mb-0 text-success"><?php echo (int) ($stats['approved_count'] ?? 0); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <p class="text-muted mb-1">Rejected</p>
                    <h3 class="mb-0 text-danger"><?php echo (int) ($stats['rejected_count'] ?? 0); ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white">
            <h6 class="mb-0"><i class="fas fa-inbox me-2"></i>Pending Requests</h6>
        </div>
        <div class="card-body">
            <?php if ($pending_requests && $pending_requests->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Contact</th>
                                <th>Requested At</th>
                                <th style="min-width: 320px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while ($row = $pending_requests->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['full_name']); ?></strong>
                                    <?php if (!empty($row['request_note'])): ?>
                                        <div class="small text-muted mt-1">Note: <?php echo htmlspecialchars($row['request_note']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                <td>
                                    <?php if (!empty($row['email'])): ?>
                                        <div><i class="fas fa-envelope text-muted"></i> <?php echo htmlspecialchars($row['email']); ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($row['phone'])): ?>
                                        <div><i class="fas fa-phone text-muted"></i> <?php echo htmlspecialchars($row['phone']); ?></div>
                                    <?php endif; ?>
                                    <?php if (empty($row['email']) && empty($row['phone'])): ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d M Y, h:i A', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <form method="POST" class="d-flex gap-2 mb-2">
                                        <input type="hidden" name="request_id" value="<?php echo (int) $row['id']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <select name="assigned_role" class="form-select form-select-sm" required>
                                            <option value="">Assign role...</option>
                                            <option value="teacher">Teacher</option>
                                            <option value="staff">Staff</option>
                                            <option value="parent">Parent</option>
                                            <option value="student">Student</option>
                                        </select>
                                        <input type="text" name="admin_note" class="form-control form-control-sm" placeholder="Optional note" maxlength="255">
                                        <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                    </form>

                                    <form method="POST" class="d-flex gap-2">
                                        <input type="hidden" name="request_id" value="<?php echo (int) $row['id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <input type="text" name="admin_note" class="form-control form-control-sm" placeholder="Reason (optional)" maxlength="255">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Reject</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="mb-0 text-muted">No pending requests.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white">
            <h6 class="mb-0"><i class="fas fa-history me-2"></i>Recent Decisions</h6>
        </div>
        <div class="card-body">
            <?php if ($recent_decisions && $recent_decisions->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Status</th>
                                <th>Role</th>
                                <th>Admin Note</th>
                                <th>Reviewed By</th>
                                <th>Reviewed At</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while ($row = $recent_decisions->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['username']); ?></strong>
                                    <div class="small text-muted"><?php echo htmlspecialchars($row['full_name']); ?></div>
                                </td>
                                <td>
                                    <?php if ($row['status'] === 'approved'): ?>
                                        <span class="badge bg-success">Approved</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Rejected</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['assigned_role'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($row['admin_note'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($row['reviewed_by_name'] ?? '-'); ?></td>
                                <td><?php echo !empty($row['reviewed_at']) ? date('d M Y, h:i A', strtotime($row['reviewed_at'])) : '-'; ?></td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="mb-0 text-muted">No decisions recorded yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/footer.php'; ?>
