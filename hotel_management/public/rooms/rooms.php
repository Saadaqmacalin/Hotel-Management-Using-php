<?php
// include './dashboard/slidepar.php'
// 1. DATABASE CONNECTION
$host = 'localhost'; $dbname = 'hotel_management'; $user = 'root'; $pass = ''; 
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e) {
    die("Connection Failed: " . $e->getMessage());
}

$message = ""; $message_type = "";

// 2. DELETE ROOM
if (isset($_GET['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = ?");
    $stmt->execute([$_GET['delete_id']]);
    header("Location: " . $_SERVER['PHP_SELF'] . "?msg=deleted");
    exit;
}

// 3. EDIT MODE
$edit_mode = false;
$room_data = ['room_number'=>'','room_type'=>'Single','price_per_night'=>'','capacity'=>'','amenities'=>'','status'=>'Available'];
if (isset($_GET['edit_id'])) {
    $edit_mode = true;
    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
    $stmt->execute([$_GET['edit_id']]);
    $room_data = $stmt->fetch(PDO::FETCH_ASSOC);
}

// 4. HANDLE FORM SUBMISSION
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $room_num = $_POST['room_number'];
    $type     = $_POST['room_type'];
    $price    = $_POST['price_per_night'];
    $cap      = $_POST['capacity'];
    $amen     = $_POST['amenities'];
    $stat     = $_POST['status'];

    try {
        if ($edit_mode) {
            $sql = "UPDATE rooms SET room_number=?, room_type=?, price_per_night=?, capacity=?, amenities=?, status=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$room_num, $type, $price, $cap, $amen, $stat, $_GET['edit_id']]);
            header("Location: " . $_SERVER['PHP_SELF'] . "?msg=updated");
            exit;
        } else {
            $sql = "INSERT INTO rooms (room_number, room_type, price_per_night, capacity, amenities, status) VALUES (?,?,?,?,?,?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$room_num, $type, $price, $cap, $amen, $stat]);
            $message = "Room added successfully!"; $message_type = "success";
        }
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage(); $message_type = "danger";
    }
}

if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'updated') { $message = "Room updated successfully!"; $message_type = "success"; }
    if ($_GET['msg'] == 'deleted') { $message = "Room removed from system."; $message_type = "warning"; }
}

$rooms = $pdo->query("SELECT * FROM rooms ORDER BY room_number ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Room Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background: #f4f6f9; font-size: 0.9rem; zoom: 0.95; margin: 0; padding: 0; }
        
        /* THE SIDEBAR SPACE */
        .main-content {
            margin-left: 260px; /* Adjust this value to match your sidebar width */
            padding: 20px;
            min-height: 100vh;
        }

        .card { border: none; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075); border-radius: 10px; margin-bottom: 2rem; }
        .table thead { background: #1a237e; color: white; }
        .status-badge { font-size: 0.75rem; padding: 4px 8px; border-radius: 5px; text-transform: uppercase; font-weight: 700; }
        
        /* Smooth transition if you decide to toggle the sidebar later */
        .main-content { transition: margin-left 0.3s ease; }

        @media (max-width: 992px) {
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>
<?php include('../dashboard/sidebar.php'); ?>
<div class="main-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <?php if ($message): ?>
                    <div class="alert alert-<?= $message_type ?> alert-dismissible fade show border-0 shadow-sm">
                        <?= $message ?> <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card p-4">
                    <h5 class="mb-4 fw-bold text-primary">
                        <i class="bi bi-plus-circle-fill me-2"></i>
                        <?= $edit_mode ? "Update Room #".$room_data['room_number'] : "Add New Room" ?>
                    </h5>
                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Room Number</label>
                                <input type="text" name="room_number" class="form-control" value="<?= htmlspecialchars($room_data['room_number']) ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Type</label>
                                <select name="room_type" class="form-select">
                                    <?php foreach(['Single','Double','Suite','Deluxe'] as $t): ?>
                                        <option value="<?= $t ?>" <?= $room_data['room_type']==$t ? 'selected':'' ?>><?= $t ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold">Price ($)</label>
                                <input type="number" step="0.01" name="price_per_night" class="form-control" value="<?= $room_data['price_per_night'] ?>" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold">Capacity</label>
                                <input type="number" name="capacity" class="form-control" value="<?= $room_data['capacity'] ?>" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold">Status</label>
                                <select name="status" class="form-select">
                                    <option value="Available" <?= $room_data['status']=='Available'?'selected':'' ?>>Available</option>
                                    <option value="Occupied" <?= $room_data['status']=='Occupied'?'selected':'' ?>>Occupied</option>
                                    <option value="Maintenance" <?= $room_data['status']=='Maintenance'?'selected':'' ?>>Maintenance</option>
                                </select>
                            </div>
                            <div class="col-md-9">
                                <label class="form-label fw-bold">Amenities</label>
                                <input type="text" name="amenities" class="form-control" value="<?= htmlspecialchars($room_data['amenities']) ?>" placeholder="WiFi, TV, AC...">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <div class="w-100 btn-group shadow-sm">
                                    <button type="submit" class="btn btn-primary"><?= $edit_mode ? "Update" : "Save Room" ?></button>
                                    <?php if($edit_mode): ?>
                                        <a href="?" class="btn btn-outline-secondary">Cancel</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card overflow-hidden">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-0">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-list-ul me-2"></i>Room Inventory Listing</h6>
                        <span class="badge bg-primary rounded-pill"><?= count($rooms) ?> Total</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">Room #</th>
                                    <th>Type</th>
                                    <th>Price</th>
                                    <th>Capacity</th>
                                    <th>Status</th>
                                    <th class="text-center pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rooms as $room): ?>
                                <tr>
                                    <td class="ps-4">
                                        <span class="fw-bold text-dark">#<?= htmlspecialchars($room['room_number']) ?></span>
                                        <div class="text-muted" style="font-size: 0.75rem;"><?= htmlspecialchars($room['amenities']) ?></div>
                                    </td>
                                    <td><span class="badge bg-light text-dark border"><?= $room['room_type'] ?></span></td>
                                    <td class="fw-bold text-success">$<?= number_format($room['price_per_night'], 2) ?></td>
                                    <td><i class="bi bi-people me-1"></i> <?= $room['capacity'] ?></td>
                                    <td>
                                        <?php 
                                            $color = ($room['status'] == 'Available') ? 'success' : (($room['status'] == 'Occupied') ? 'danger' : 'warning text-dark');
                                        ?>
                                        <span class="status-badge bg-<?= $color ?>"><?= $room['status'] ?></span>
                                    </td>
                                    <td class="text-center pe-4">
                                        <div class="btn-group">
                                            <a href="?edit_id=<?= $room['id'] ?>" class="btn btn-sm btn-outline-primary border-0"><i class="bi bi-pencil-square"></i></a>
                                            <a href="?delete_id=<?= $room['id'] ?>" class="btn btn-sm btn-outline-danger border-0" onclick="return confirm('Delete room?')"><i class="bi bi-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>