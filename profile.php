<?php
require_once 'header.php';
?>
<div class="row">
    <div class="col-md-12">
        <h1 class="page-title">My Profile</h1>
        <div class="card">
            <div class="card-body">
                <p><strong>Username:</strong> <?php echo htmlspecialchars($username); ?></p>
                <p><strong>Role:</strong> <?php echo ucfirst(htmlspecialchars($role)); ?></p>
            </div>
        </div>
    </div>
</div>
<?php require_once 'footer.php'; ?>