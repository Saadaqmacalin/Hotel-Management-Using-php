<?php
// 1. Pathing and Configuration
// Going up two levels from 'pulblic/dashboard/' to reach 'src/config.php'
require_once __DIR__ . '/../../src/config.php';

// 2. Authentication Check 
if (!isLoggedIn()) {
    redirect('../users/login.php');
}

// 3. Fetch Dashboard Totals using the $conn object from config.php
// Total Guests
$res_guests = $conn->query("SELECT COUNT(*) as total FROM guests");
$total_guests = ($res_guests) ? $res_guests->fetch_assoc()['total'] : 0;

// Total Users (Staff/Admins)
$res_users = $conn->query("SELECT COUNT(*) as total FROM users");
$total_users = ($res_users) ? $res_users->fetch_assoc()['total'] : 0;

// Total Rooms
$res_rooms = $conn->query("SELECT COUNT(*) as total FROM rooms");
$total_rooms = ($res_rooms) ? $res_rooms->fetch_assoc()['total'] : 0;

// Total Bookings
$res_bookings = $conn->query("SELECT COUNT(*) as total FROM bookings");
$total_bookings = ($res_bookings) ? $res_bookings->fetch_assoc()['total'] : 0;

// Total Payments (Total Revenue)
$res_payments = $conn->query("SELECT SUM(amount) as total FROM payments");
$total_revenue = ($res_payments) ? $res_payments->fetch_assoc()['total'] : 0;

$page_title = 'System Overview';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> | Hotel Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
        #mainContent { margin-left: 260px; padding: 20px; transition: all 0.3s; }
        .stat-card { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); transition: 0.3s; }
        .stat-card:hover { transform: translateY(-5px); }
        .icon-box { width: 60px; height: 60px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; }
        @media (max-width: 992px) { #mainContent { margin-left: 0; } }
    </style>
</head>
<body>

<div class="d-flex">
    <?php include 'sidebar.php'; ?>

    <div id="mainContent">
        <header class="mb-4 d-flex justify-content-between align-items-center bg-white p-3 rounded shadow-sm">
            <h4 class="fw-bold mb-0 text-dark"><i class="bi bi-speedometer2 me-2"></i>Dashboard</h4>
            <div class="text-muted small">Logged in as: <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong></div>
        </header>

        <div class="row g-4">
            <div class="col-md-3">
                <div class="card stat-card p-3">
                    <div class="d-flex align-items-center">
                        <div class="icon-box bg-primary-subtle text-primary me-3"><i class="bi bi-door-closed"></i></div>
                        <div>
                            <p class="text-muted mb-0 small uppercase fw-bold">Rooms</p>
                            <h3 class="mb-0 fw-bold"><?php echo $total_rooms; ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card stat-card p-3">
                    <div class="d-flex align-items-center">
                        <div class="icon-box bg-success-subtle text-success me-3"><i class="bi bi-calendar-check"></i></div>
                        <div>
                            <p class="text-muted mb-0 small uppercase fw-bold">Bookings</p>
                            <h3 class="mb-0 fw-bold"><?php echo $total_bookings; ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card stat-card p-3">
                    <div class="d-flex align-items-center">
                        <div class="icon-box bg-info-subtle text-info me-3"><i class="bi bi-people"></i></div>
                        <div>
                            <p class="text-muted mb-0 small uppercase fw-bold">Guests</p>
                            <h3 class="mb-0 fw-bold"><?php echo $total_guests; ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card stat-card p-3">
                    <div class="d-flex align-items-center">
                        <div class="icon-box bg-warning-subtle text-warning me-3"><i class="bi bi-cash-stack"></i></div>
                        <div>
                            <p class="text-muted mb-0 small uppercase fw-bold">Revenue</p>
                            <h3 class="mb-0 fw-bold">$<?php echo number_format($total_revenue, 2); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-lg-4">
                <div class="card stat-card p-3 border-start border-4 border-danger">
                    <div class="d-flex align-items-center">
                        <div class="icon-box bg-danger-subtle text-danger me-3"><i class="bi bi-shield-lock"></i></div>
                        <div>
                            <p class="text-muted mb-0 small uppercase fw-bold">Staff/Users</p>
                            <h3 class="mb-0 fw-bold"><?php echo $total_users; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>