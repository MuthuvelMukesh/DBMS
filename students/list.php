<?php
require_once '../header.php';

// Just fetch all students and let DataTables handle searching and pagination
$query = "SELECT s.id, s.admission_no, s.full_name, s.dob, s.gender, s.parent_name, s.contact, s.status, 
          c.class_name, c.section 
          FROM students s
          LEFT JOIN classes c ON s.class_id = c.id
          WHERE s.status != 'deleted' 
          ORDER BY s.full_name ASC";

$result = $conn->query($query);
$students = $result->fetch_all(MYSQLI_ASSOC);

?>

<h1 class="page-title"><i class="fas fa-users"></i> Student Management</h1>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-light border-bottom d-flex justify-content-between align-items-center">
        <h5 class="mb-0">All Students</h5>
        <?php if (in_array($role, ['admin', 'teacher'])): ?>
            <a href="add.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add New Student</a>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <!-- Added 'datatable' class here for automatic pagination, search, and export buttons -->
            <table class="table table-hover datatable align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Admission No</th>
                        <th>Full Name</th>
                        <th>Class</th>
                        <th>Gender</th>
                        <th>Parent Name</th>
                        <th>Contact</th>
                        <th>Status</th>
                        <?php if (in_array($role, ['admin', 'teacher'])): ?>
                            <th class="text-end">Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr><td colspan="8" class="text-center py-4">No students found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['admission_no']); ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle bg-primary text-white me-2" style="width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                                            <?php echo strtoupper(substr($student['full_name'], 0, 1)); ?>
                                        </div>
                                        <?php echo htmlspecialchars($student['full_name']); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                        if ($student['class_name']) {
                                            echo htmlspecialchars($student['class_name'] . ' - ' . $student['section']);
                                        } else {
                                            echo '<span class="text-muted">Not Assigned</span>';
                                        }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($student['gender']); ?></td>
                                <td><?php echo htmlspecialchars($student['parent_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['contact']); ?></td>
                                <td>
                                    <?php if ($student['status'] === 'active'): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <?php if (in_array($role, ['admin', 'teacher'])): ?>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="view.php?id=<?php echo $student['id']; ?>" class="btn btn-outline-info" title="View"><i class="fas fa-eye"></i></a>
                                        <a href="edit.php?id=<?php echo $student['id']; ?>" class="btn btn-outline-primary" title="Edit"><i class="fas fa-edit"></i></a>
                                        <?php if ($role === 'admin'): ?>
                                        <a href="delete.php?id=<?php echo $student['id']; ?>" class="btn btn-outline-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this student?');"><i class="fas fa-trash"></i></a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../footer.php'; ?>