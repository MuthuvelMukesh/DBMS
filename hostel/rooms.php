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

// Fetch all hostel rooms with occupancy count
$stmt = $conn->prepare("
    SELECT r.id, r.room_no, r.floor, r.capacity, r.room_type, r.fee_per_month,
           COUNT(a.id) as occupancy
    FROM hostel_rooms r
    LEFT JOIN hostel_assignments a ON r.id = a.room_id AND a.status = 'active'
    GROUP BY r.id
    ORDER BY r.floor, r.room_no
    LIMIT ? OFFSET ?
");
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$rooms = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get total count
$count_result = $conn->query("SELECT COUNT(*) as total FROM hostel_rooms");
$count_row = $count_result->fetch_assoc();
$total_records = $count_row['total'];
$total_pages = ceil($total_records / $limit);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'add') {
        $room_no = isset($_POST['room_no']) ? trim($_POST['room_no']) : '';
        $floor = isset($_POST['floor']) ? (int)$_POST['floor'] : 0;
        $capacity = isset($_POST['capacity']) ? (int)$_POST['capacity'] : 0;
        $room_type = isset($_POST['room_type']) ? trim($_POST['room_type']) : '';
        $fee_per_month = isset($_POST['fee_per_month']) ? (float)$_POST['fee_per_month'] : 0;

        if (empty($room_no) || $floor <= 0 || $capacity <= 0 || empty($room_type) || $fee_per_month <= 0) {
            $error = 'All fields are required!';
        } else {
            // Check if room_no already exists
            $stmt = $conn->prepare("SELECT id FROM hostel_rooms WHERE room_no = ?");
            $stmt->bind_param("s", $room_no);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($existing) {
                $error = 'Room number already exists!';
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO hostel_rooms (room_no, floor, capacity, room_type, fee_per_month)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("siids", $room_no, $floor, $capacity, $room_type, $fee_per_month);

                if ($stmt->execute()) {
                    $success = 'Room added successfully!';
                    header("Location: rooms.php");
                    exit();
                } else {
                    $error = 'Error adding room: ' . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}
?>

<h1 class="page-title"><i class="fas fa-bed"></i> Hostel Rooms</h1>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-light border-bottom d-flex justify-content-between align-items-center">
        <h5 class="mb-0">All Rooms</h5>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addRoomModal">
            <i class="fas fa-plus"></i> Add Room
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
                        <th>Room No</th>
                        <th>Floor</th>
                        <th>Type</th>
                        <th>Capacity</th>
                        <th>Occupancy</th>
                        <th>Occupancy %</th>
                        <th>Monthly Fee</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($rooms)): ?>
                        <?php foreach ($rooms as $room): ?>
                            <?php 
                                $occupancy_percent = $room['capacity'] > 0 ? ($room['occupancy'] / $room['capacity']) * 100 : 0;
                                $badge_color = $occupancy_percent >= 100 ? 'danger' : ($occupancy_percent >= 75 ? 'warning' : 'success');
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($room['room_no']); ?></strong></td>
                                <td><?php echo $room['floor']; ?></td>
                                <td><?php echo htmlspecialchars($room['room_type']); ?></td>
                                <td><?php echo $room['capacity']; ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $badge_color; ?>">
                                        <?php echo $room['occupancy']; ?>/<?php echo $room['capacity']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-<?php echo $badge_color; ?>" style="width: <?php echo min($occupancy_percent, 100); ?>%;">
                                            <?php echo number_format($occupancy_percent, 0); ?>%
                                        </div>
                                    </div>
                                </td>
                                <td>₹<?php echo number_format($room['fee_per_month'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No rooms found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Room Modal -->
<div class="modal fade" id="addRoomModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Room</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="room_no" class="form-label">Room Number *</label>
                        <input type="text" class="form-control" id="room_no" name="room_no" placeholder="e.g., 101" required>
                    </div>
                    <div class="mb-3">
                        <label for="floor" class="form-label">Floor *</label>
                        <input type="number" class="form-control" id="floor" name="floor" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label for="room_type" class="form-label">Room Type *</label>
                        <select class="form-control" id="room_type" name="room_type" required>
                            <option value="">-- Select Type --</option>
                            <option value="Single">Single</option>
                            <option value="Double">Double</option>
                            <option value="Triple">Triple</option>
                            <option value="Quad">Quad</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="capacity" class="form-label">Capacity (1-4) *</label>
                        <input type="number" class="form-control" id="capacity" name="capacity" min="1" max="4" required>
                    </div>
                    <div class="mb-3">
                        <label for="fee_per_month" class="form-label">Monthly Fee *</label>
                        <input type="number" class="form-control" id="fee_per_month" name="fee_per_month" step="0.01" min="0" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Add Room</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($total_pages > 1): ?>
    <nav class="d-flex justify-content-center mt-4">
        <ul class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>

<?php require_once '../footer.php'; ?>
