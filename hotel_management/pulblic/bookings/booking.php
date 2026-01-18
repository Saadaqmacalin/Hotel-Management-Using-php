<?php
session_start();
$host = 'localhost'; 
$dbname = 'hotel_management'; 
$user = 'root'; 
$pass = ''; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4",
     $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e) { 
    die("Connection Failed: " . $e->getMessage()); 
}

$msg = "";
$msg_type = "info";

// 1. SIGN UP 
if (isset($_POST['signup'])) {
    try {
        $sql = "INSERT INTO guests (first_name, last_name, email, phone, address, id_proof) VALUES (?,?,?,?,?,?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$_POST['first_name'], $_POST['last_name'], $_POST['email'], $_POST['phone'], $_POST['address'], $_POST['id_proof']]);
        
        $_SESSION['guest_id'] = $pdo->lastInsertId();
        $_SESSION['guest_name'] = $_POST['first_name'];
        $msg = "Welcome! Account created successfully.";
        $msg_type = "success";
    } catch (PDOException $e) {
        $msg = ($e->getCode() == 23000) ? "Email already exists." : "Error: " . $e->getMessage();
        $msg_type = "danger";
    }
}

// 2. SIGN IN
if (isset($_POST['signin'])) {
    $stmt = $pdo->prepare("SELECT * FROM guests WHERE email = ? AND phone = ?");
    $stmt->execute([$_POST['email'], $_POST['phone']]);
    $user = $stmt->fetch();
    if ($user) {
        $_SESSION['guest_id'] = $user['id'];
        $_SESSION['guest_name'] = $user['first_name'];
        $msg = "Welcome back, " . $user['first_name'] . "!";
        $msg_type = "success";
    } else { 
        $msg = "Invalid Email or Phone combination."; 
        $msg_type = "danger";
    }
}

// 3. BOOKING & AUTOMATIC PAYMENT
if (isset($_POST['book_now']) && isset($_SESSION['guest_id'])) {
    try {
        $pdo->beginTransaction(); 
        $guest_id = $_SESSION['guest_id'];
        $room_id = $_POST['room_id']; 
        $check_in = $_POST['check_in'];
        $check_out = $_POST['check_out'];
        $price_per_night = $_POST['price'];

        $date1 = new DateTime($check_in);
        $date2 = new DateTime($check_out);
        $interval = $date1->diff($date2);
        $nights = ($interval->days <= 0) ? 1 : $interval->days; 
        $total_amount = $price_per_night * $nights;

        $sqlB = "INSERT INTO bookings (guest_id, room_id, check_in_date, check_out_date, total_amount, booking_status, payment_status) 
                 VALUES (?, ?, ?, ?, ?, 'Confirmed', 'Paid')";
        $stmtB = $pdo->prepare($sqlB);
        $stmtB->execute([$guest_id, $room_id, $check_in, $check_out, $total_amount]);
        $new_booking_id = $pdo->lastInsertId();

        $payment_date = date('Y-m-d H:i:s');
        $transaction_id = "TXN-" . strtoupper(bin2hex(random_bytes(4)));
        $sqlP = "INSERT INTO payments (booking_id, amount, payment_method, payment_date, transaction_id, notes) 
                 VALUES (?, ?, 'Credit Card', ?, ?, ?)";
        $stmtP = $pdo->prepare($sqlP);
        $stmtP->execute([$new_booking_id, $total_amount, $payment_date, $transaction_id, "Web booking for Room ID: $room_id"]);

        $pdo->prepare("UPDATE rooms SET status = 'Booked' WHERE id = ?")->execute([$room_id]);
        $pdo->commit(); 

        $msg = "Booking #$new_booking_id confirmed! Payment processed.";
        $msg_type = "success";
    } catch (Exception $e) {
        $pdo->rollBack(); 
        $msg = "Error: " . $e->getMessage();
        $msg_type = "danger";
    }
}

if (isset($_GET['logout'])) { session_destroy(); header("Location: " . $_SERVER['PHP_SELF']); exit; }

// FETCH ALL ROOMS - ARRANGE BY ROOM NUMBER
// Pagination Logic
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 6; // Number of rooms per page
$offset = ($page - 1) * $perPage;

// Get total count
$total_rooms = $pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
$total_pages = ceil($total_rooms / $perPage);

// Fetch rooms with LIMIT and OFFSET
$stmt = $pdo->prepare("SELECT * FROM rooms ORDER BY CAST(room_number AS UNSIGNED) ASC, room_number ASC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$rooms = $stmt->fetchAll();

$unique_images = [
    "https://images.unsplash.com/photo-1631049307264-da0ec9d70304?auto=format&fit=crop&w=600&q=80",
    "https://images.unsplash.com/photo-1611892440504-42a792e24d32?auto=format&fit=crop&w=600&q=80",
    "https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=600&q=80",
    "https://images.unsplash.com/photo-1566665797739-1674de7a421a?auto=format&fit=crop&w=600&q=80"
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Luxury Stay | Welcome</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background: #f8f9fa; }
        .hero { 
            background: linear-gradient(rgba(0,0,0,0.65), rgba(0,0,0,0.65)), url('https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?auto=format&fit=crop&w=1920&q=80'); 
            height: 450px; background-size: cover; background-position: center; color: white; 
            display: flex; align-items: center; justify-content: center; text-align: center; 
        }
        .hero-content { max-width: 800px; padding: 20px; }
        .room-card { border: none; transition: 0.3s; border-radius: 15px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .room-card:hover { transform: translateY(-10px); }
        .room-img { height: 220px; object-fit: cover; }
        .badge-available { background-color: #28a745; }
        .badge-booked { background-color: #dc3545; }
        .btn-gold { background: #c5a059; color: white; border: none; }
        .btn-gold:hover { background: #ae8a46; color: white; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="#"><i class="bi bi-door-open me-2 text-warning"></i>LUXURY STAY</a>
        <div class="ms-auto text-white">
            <?php if(isset($_SESSION['guest_id'])): ?>
                <span class="me-3 small">Hello, <strong><?= htmlspecialchars($_SESSION['guest_name']) ?></strong></span>
                <a href="?logout=1" class="btn btn-outline-light btn-sm rounded-pill">Logout</a>
            <?php else: ?>
                <button class="btn btn-gold btn-sm rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#authModal">Login / Register</button>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="hero mb-5">
    <div class="hero-content">
        <h1 class="display-3 fw-bold mb-3">Welcome to Luxury Stay</h1>
        <p class="lead fs-4">Where elegance meets comfort. Explore our exquisite collection of rooms designed for your perfect getaway.</p>
        <a href="#explore" class="btn btn-gold btn-lg rounded-pill mt-3 px-5">Explore Rooms</a>
    </div>
</div>

<div class="container mb-5" id="explore">
    <div class="text-center mb-5">
        <h2 class="fw-bold">Our Accommodations</h2>
        <div class="bg-warning mx-auto" style="height: 3px; width: 60px;"></div>
    </div>

    <?php if($msg): ?>
        <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show shadow-sm mb-4">
            <?= $msg ?> <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <?php foreach($rooms as $index => $r): 
            $img_url = $unique_images[$index % count($unique_images)];
            $is_available = ($r['status'] == 'Available');
        ?>
        <div class="col-md-4">
            <div class="card room-card h-100">
                <div class="position-relative">
                    <img src="<?= $img_url ?>" class="room-img w-100" alt="Room">
                    <span class="badge position-absolute top-0 end-0 m-3 <?= $is_available ? 'badge-available' : 'badge-booked' ?>">
                        <?= htmlspecialchars($r['status']) ?>
                    </span>
                </div>
                <div class="card-body">
                    <h5 class="fw-bold mb-1">Room <?= htmlspecialchars($r['room_number']) ?></h5>
                    <p class="text-muted small mb-3"><?= htmlspecialchars($r['room_type']) ?> • Max Capacity: <?= htmlspecialchars($r['capacity']) ?></p>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="h5 fw-bold text-dark mb-0">$<?= number_format($r['price_per_night'], 2) ?></span>
                        <?php if($is_available): ?>
                            <button class="btn btn-dark btn-sm rounded-pill" data-bs-toggle="modal" data-bs-target="<?= isset($_SESSION['guest_id']) ? '#bookModal'.$r['id'] : '#authModal' ?>">
                                Book Now
                            </button>
                        <?php else: ?>
                            <button class="btn btn-secondary btn-sm rounded-pill" disabled>Unavailable</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="bookModal<?= $r['id'] ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <form class="modal-content" method="POST">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="fw-bold">Reserve Room <?= htmlspecialchars($r['room_number']) ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="room_id" value="<?= $r['id'] ?>">
                        <input type="hidden" name="price" value="<?= $r['price_per_night'] ?>">
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="small fw-bold">Check-In</label>
                                <input type="date" name="check_in" class="form-control" min="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-6">
                                <label class="small fw-bold">Check-Out</label>
                                <input type="date" name="check_out" class="form-control" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="submit" name="book_now" class="btn btn-gold w-100 py-2 fw-bold rounded-pill">Confirm & Pay Now</button>
                    </div>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>



    <!-- Pagination Controls -->
    <?php if ($total_pages > 1): ?>
    <div class="row mt-5">
        <div class="col-12 d-flex justify-content-center">
            <nav aria-label="Page navigation">
                <ul class="pagination">
                    <!-- Previous -->
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page - 1 ?>#explore" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>

                    <!-- Page Numbers -->
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= ($page == $i) ? 'active bg-warning border-warning' : '' ?>">
                            <a class="page-link <?= ($page == $i) ? 'bg-warning border-warning text-white' : 'text-dark' ?>" href="?page=<?= $i ?>#explore"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <!-- Next -->
                    <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page + 1 ?>#explore" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="authModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-3">
            <ul class="nav nav-pills mb-3" id="pills-tab">
                <li class="nav-item w-50"><button class="nav-link active w-100" data-bs-toggle="pill" data-bs-target="#login">Login</button></li>
                <li class="nav-item w-50"><button class="nav-link w-100" data-bs-toggle="pill" data-bs-target="#register">Register</button></li>
            </ul>
            <div class="tab-content">
                <div class="tab-pane fade show active" id="login">
                    <form method="POST">
                        <div class="mb-3"><input type="email" name="email" class="form-control" placeholder="Email" required></div>
                        <div class="mb-3"><input type="text" name="phone" class="form-control" placeholder="Phone" required></div>
                        <button type="submit" name="signin" class="btn btn-gold w-100 fw-bold">Sign In</button>
                    </form>
                </div>
                <div class="tab-pane fade" id="register">
                    <form method="POST">
                        <div class="row g-2 mb-2">
                            <div class="col-6"><input type="text" name="first_name" class="form-control" placeholder="First Name" required></div>
                            <div class="col-6"><input type="text" name="last_name" class="form-control" placeholder="Last Name" required></div>
                        </div>
                        <input type="email" name="email" class="form-control mb-2" placeholder="Email" required>
                        <input type="text" name="phone" class="form-control mb-2" placeholder="Phone" required>
                        <input type="text" name="id_proof" class="form-control mb-2" placeholder="ID/Passport Number">
                        <textarea name="address" class="form-control mb-3" placeholder="Address" rows="2"></textarea>
                        <button type="submit" name="signup" class="btn btn-success w-100 fw-bold">Create Account</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>