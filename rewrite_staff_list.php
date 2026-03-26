<?php
$content = <<<'EOD'
<?php
require_once '../header.php';

if ($role != 'admin') {
    echo '<div class="alert alert-danger">You do not have permission to access this page.</div>';
    require_once '../footer.php';
    exit();
}

$query = "SELECT id, staff_id, full_name, designation, department, contact, email, salary, join_date, status 
          FROM staff WHERE status != 'deleted' ORDER BY full_name ASC";
$result = $conn->query($query);
$staff = $result->fetch_all(MYSQLI_ASSOC);

?>

<h1 class="page-title"><i class="fas fa-briefcase"></i> Staff Management</h1>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-light border-bottom d-flex justify-content-between align-items-center">
        <h5 class="mb-0">All Staff Members</h5>
        <a href="add.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add New Staff</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover datatable align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Staff ID</th>
                        <th>Full Name</th>
                        <th>Designation</th>
                        <th>Department</th>
                        <th>Contact</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($staff)): ?>
                        <tr><td colspan="8" class="text-center py-4">No staff found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($staff as $s): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($s['staff_id']); ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle bg-success text-white me-2" style="width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                                            <?php echo strtoupper(substr($s['full_name'], 0, 1)); ?>
                                        </div>
                                        <?php echo htmlspecialchars($s['full_name']); ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($s['designation']); ?></td>
                                <td><?php echo htmlspecialchars($s['department']); ?></td>
                                <td><?php echo htmlspecialchars($s['contact']); ?></td>
                                <td><?php echo htmlspecialchars($s['email']); ?></td>
                                <td>
                                    <?php if ($s['status'] === 'active'): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="edit.php?id=<?php echo $s['id']; ?>" class="btn btn-outline-primary" title="Edit"><i class="fas fa-edit"></i></a>
                                        <a href="delete.php?id=<?php echo $s['id']; ?>" class="btn btn-outline-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this staff member?');"><i class="fas fa-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../footer.php'; ?>
EOD;

file_put_contents('staff/list.php', $content);
?>