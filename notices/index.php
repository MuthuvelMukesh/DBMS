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
    if (isset($_POST['delete_notice'])) {
        $id = isset($_POST['notice_id']) ? (int)$_POST['notice_id'] : 0;
        if ($id > 0) {
            $stmt = $conn->prepare("DELETE FROM notices WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $success = "Notice deleted successfully.";
            } else {
                $error = "Failed to delete notice.";
            }
            $stmt->close();
        } else {
            $error = "Invalid notice selected.";
        }
    } elseif (isset($_POST['toggle_notice'])) {
        $id = isset($_POST['notice_id']) ? (int)$_POST['notice_id'] : 0;
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE notices SET is_active = NOT is_active WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $success = "Notice status toggled.";
            } else {
                $error = "Failed to toggle notice status.";
            }
            $stmt->close();
        } else {
            $error = "Invalid notice selected.";
        }
    } elseif (isset($_POST['add_notice'])) {
        $title = trim($_POST['title']);
        $message = trim($_POST['message']);
        $type = trim($_POST['type']);
        $allowed_types = ['info', 'warning', 'danger', 'success'];
        if (!in_array($type, $allowed_types, true)) {
            $type = 'info';
        }

        if (empty($title) || empty($message)) {
            $error = "Title and message are required.";
        } else {
            $stmt = $conn->prepare("INSERT INTO notices (title, message, type) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $title, $message, $type);
            if ($stmt->execute()) {
                $success = "Notice published successfully!";
            } else {
                $error = "Database error: " . $conn->error;
            }
            $stmt->close();
        }
    }
}

// Fetch all notices
$notices = $conn->query("SELECT * FROM notices ORDER BY created_at DESC");
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="page-title mb-0"><i class="fas fa-bullhorn text-warning"></i> Noticeboard Management</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addNoticeModal">
            <i class="fas fa-plus"></i> Publish Notice
        </button>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover datatable align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Title</th>
                            <th>Message</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $notices->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('M d, Y h:i A', strtotime($row['created_at'])); ?></td>
                            <td class="fw-bold"><?php echo htmlspecialchars($row['title']); ?></td>
                            <td><?php echo htmlspecialchars(substr($row['message'], 0, 50)) . '...'; ?></td>
                            <td>
                                <span class="badge bg-<?php echo $row['type'] === 'error' ? 'danger' : $row['type']; ?>">
                                    <?php echo ucfirst($row['type']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if($row['is_active']): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Hidden</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="notice_id" value="<?php echo (int)$row['id']; ?>">
                                    <button type="submit" name="toggle_notice" value="1" class="btn btn-sm btn-<?php echo $row['is_active'] ? 'warning' : 'success'; ?> me-1" title="Toggle Visibility">
                                        <i class="fas fa-eye<?php echo $row['is_active'] ? '-slash' : ''; ?>"></i>
                                    </button>
                                </form>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Delete this notice permanently?');">
                                    <input type="hidden" name="notice_id" value="<?php echo (int)$row['id']; ?>">
                                    <button type="submit" name="delete_notice" value="1" class="btn btn-sm btn-danger" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Notice Modal -->
<div class="modal fade" id="addNoticeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-bullhorn"></i> Broadcast New Notice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="add_notice" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label">Notice Title</label>
                        <input type="text" class="form-control" name="title" required placeholder="e.g. Tomorrow is a Holiday">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notice Severity / Style</label>
                        <select class="form-select" name="type">
                            <option value="info">Info (Blue)</option>
                            <option value="success">Success (Green)</option>
                            <option value="warning">Warning (Yellow)</option>
                            <option value="danger">Danger/Urgent (Red)</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Message Content</label>
                        <textarea class="form-control" name="message" rows="4" required placeholder="Type your announcement here..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Publish</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/footer.php'; ?>