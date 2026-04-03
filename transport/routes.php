<?php
require_once '../header.php';

if (!in_array($role, ['admin', 'staff'])) {
    header('Location: ' . BASE_URL . 'dashboard.php?error=Access Denied');
    exit();
}


$error = '';
$success = '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Fetch transport routes
$stmt = $conn->prepare("
    SELECT id, route_name, vehicle_no, driver_name, driver_contact, stops, capacity, monthly_fee, status
    FROM transport
    ORDER BY route_name ASC
    LIMIT ? OFFSET ?
");
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$routes_result = $stmt->get_result();
$routes = $routes_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get total count
$count_result = $conn->query("SELECT COUNT(*) as total FROM transport");
$count_row = $count_result->fetch_assoc();
$total_records = $count_row['total'];
$total_pages = ceil($total_records / $limit);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'add') {
        $route_name = isset($_POST['route_name']) ? trim($_POST['route_name']) : '';
        $vehicle_no = isset($_POST['vehicle_no']) ? trim($_POST['vehicle_no']) : '';
        $driver_name = isset($_POST['driver_name']) ? trim($_POST['driver_name']) : '';
        $driver_contact = isset($_POST['driver_contact']) ? trim($_POST['driver_contact']) : '';
        $stops = isset($_POST['stops']) ? trim($_POST['stops']) : '';
        $capacity = isset($_POST['capacity']) ? (int)$_POST['capacity'] : 0;
        $monthly_fee = isset($_POST['monthly_fee']) ? (float)$_POST['monthly_fee'] : 0;

        if (empty($route_name) || empty($vehicle_no) || empty($driver_name) || empty($driver_contact) || empty($stops) || $capacity <= 0 || $monthly_fee <= 0) {
            $error = 'All fields are required!';
        } else {
            $stmt = $conn->prepare("
                INSERT INTO transport (route_name, vehicle_no, driver_name, driver_contact, stops, capacity, monthly_fee, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'active')
            ");
            $stmt->bind_param("sssssid", $route_name, $vehicle_no, $driver_name, $driver_contact, $stops, $capacity, $monthly_fee);

            if ($stmt->execute()) {
                $success = 'Route added successfully!';
                header("Location: routes.php");
                exit();
            } else {
                $error = 'Error adding route: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
}
?>

<h1 class="page-title"><i class="fas fa-bus"></i> Transport Routes</h1>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-light border-bottom d-flex justify-content-between align-items-center">
        <h5 class="mb-0">All Routes</h5>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addRouteModal">
            <i class="fas fa-plus"></i> Add Route
        </button>
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

        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Route Name</th>
                        <th>Vehicle No</th>
                        <th>Driver Name</th>
                        <th>Contact</th>
                        <th>Capacity</th>
                        <th>Monthly Fee</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($routes)): ?>
                        <?php foreach ($routes as $route): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($route['route_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($route['vehicle_no']); ?></td>
                                <td><?php echo htmlspecialchars($route['driver_name']); ?></td>
                                <td><?php echo htmlspecialchars($route['driver_contact']); ?></td>
                                <td><?php echo $route['capacity']; ?></td>
                                <td>₹<?php echo number_format($route['monthly_fee'], 2); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo ($route['status'] == 'active') ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($route['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No routes found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Route Modal -->
<div class="modal fade" id="addRouteModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Route</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="route_name" class="form-label">Route Name *</label>
                            <input type="text" class="form-control" id="route_name" name="route_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="vehicle_no" class="form-label">Vehicle Number *</label>
                            <input type="text" class="form-control" id="vehicle_no" name="vehicle_no" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="driver_name" class="form-label">Driver Name *</label>
                            <input type="text" class="form-control" id="driver_name" name="driver_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="driver_contact" class="form-label">Driver Contact *</label>
                            <input type="tel" class="form-control" id="driver_contact" name="driver_contact" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="capacity" class="form-label">Capacity *</label>
                            <input type="number" class="form-control" id="capacity" name="capacity" min="1" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="monthly_fee" class="form-label">Monthly Fee *</label>
                            <input type="number" class="form-control" id="monthly_fee" name="monthly_fee" step="0.01" min="0" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="stops" class="form-label">Stops (comma-separated) *</label>
                        <textarea class="form-control" id="stops" name="stops" rows="3" placeholder="e.g., Stop 1, Stop 2, Stop 3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Add Route</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../footer.php'; ?>
