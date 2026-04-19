<?php
require_once __DIR__ . '/includes/header.php';
?>
<div class="row">
    <div class="col-lg-8 col-xl-6">
        <h1 class="page-title"><i class="fas fa-user-circle me-2"></i>My Profile</h1>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0">Account Overview</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted mb-1">Username</label>
                    <div class="fw-semibold"><?php echo htmlspecialchars($username); ?></div>
                </div>
                <div class="mb-0">
                    <label class="form-label text-muted mb-1">Role</label>
                    <div><span class="badge bg-primary-subtle text-primary-emphasis border"><?php echo ucfirst(htmlspecialchars($role)); ?></span></div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>