<?php
// 1. Pathing and Configuration
// Going up two levels from 'pulblic/dashboard/' to reach 'src/config.php'
require_once __DIR__ . '/../../src/config.php';

// 2. Authentication Check 
if (!isLoggedIn()) {
    redirect('../users/login.php'); // haduusan user login sameyn waxaa nalageynaa login form
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

// NEW: Chart Data Logic
$chart_start = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$chart_end = $_GET['end_date'] ?? date('Y-m-d');

// Helper to generate date range
try {
    $period = new DatePeriod(
         new DateTime($chart_start),
         new DateInterval('P1D'),
         (new DateTime($chart_end))->modify('+1 day')
    );
    $dates = [];
    foreach ($period as $date) {
        $dates[] = $date->format('Y-m-d');
    }
} catch (Exception $e) {
    // Fallback if invalid dates
    $dates = [date('Y-m-d')];
}

// Initialize arrays
$chart_revenue = array_fill_keys($dates, 0);
$chart_bookings = array_fill_keys($dates, 0);
$chart_guests = array_fill_keys($dates, 0);

// Fetch Revenue
$sql_rev_chart = "SELECT DATE(payment_date) as d, SUM(amount) as total 
                  FROM payments 
                  WHERE payment_date BETWEEN '$chart_start 00:00:00' AND '$chart_end 23:59:59' 
                  GROUP BY d";
$res_rev_chart = $conn->query($sql_rev_chart);
if ($res_rev_chart) {
    while($row = $res_rev_chart->fetch_assoc()) {
        if(isset($chart_revenue[$row['d']])) $chart_revenue[$row['d']] = (float)$row['total'];
    }
}

// Fetch Bookings
$sql_book_chart = "SELECT DATE(check_in_date) as d, COUNT(*) as total 
                   FROM bookings 
                   WHERE check_in_date BETWEEN '$chart_start' AND '$chart_end' 
                   GROUP BY d";
$res_book_chart = $conn->query($sql_book_chart);
if ($res_book_chart) {
    while($row = $res_book_chart->fetch_assoc()) {
        if(isset($chart_bookings[$row['d']])) $chart_bookings[$row['d']] = (int)$row['total'];
    }
}

// Fetch Guests
$sql_guest_chart = "SELECT DATE(check_in_date) as d, COUNT(DISTINCT guest_id) as total 
                    FROM bookings 
                    WHERE check_in_date BETWEEN '$chart_start' AND '$chart_end' 
                    GROUP BY d";
$res_guest_chart = $conn->query($sql_guest_chart);
if ($res_guest_chart) {
    while($row = $res_guest_chart->fetch_assoc()) {
        if(isset($chart_guests[$row['d']])) $chart_guests[$row['d']] = (int)$row['total'];
    }
}

// Prepare JSON for JS
$json_dates = json_encode(array_values($dates));
$json_revenue = json_encode(array_values($chart_revenue));
$json_bookings = json_encode(array_values($chart_bookings));
$json_guests = json_encode(array_values($chart_guests));

$page_title = 'System Overview';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> | Hotel Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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



        <!-- Staff/Users Section -->
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

        <!-- Reports Section -->
        <!-- Charts Section -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card stat-card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h5 class="fw-bold mb-0 text-secondary"><i class="bi bi-graph-up-arrow me-2"></i>Performance Trends</h5>
                        
                        <!-- Filter Form -->
                        <form class="d-flex gap-2" method="GET">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light border-0 fw-bold">From</span>
                                <input type="date" name="start_date" class="form-control border-light bg-light" value="<?= $chart_start ?>">
                            </div>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light border-0 fw-bold">To</span>
                                <input type="date" name="end_date" class="form-control border-light bg-light" value="<?= $chart_end ?>">
                            </div>
                            <button type="submit" class="btn btn-sm btn-primary px-3 rounded-pill fw-bold">Filter</button>
                            <a href="dashboard.php" class="btn btn-sm btn-outline-secondary rounded-pill">Reset</a>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Revenue Chart -->
            <div class="col-lg-12 mb-4">
                <div class="card stat-card p-3 shadow-sm">
                    <h6 class="fw-bold text-muted mb-3">Revenue Over Time</h6>
                    <div style="height: 300px;">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Bookings & Guests Charts -->
            <div class="col-lg-6 mb-4">
                <div class="card stat-card p-3 shadow-sm">
                    <h6 class="fw-bold text-muted mb-3">Bookings Trend</h6>
                    <div style="height: 250px;">
                        <canvas id="bookingsChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 mb-4">
                <div class="card stat-card p-3 shadow-sm">
                    <h6 class="fw-bold text-muted mb-3">Active Guests</h6>
                    <div style="height: 250px;">
                        <canvas id="guestsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <script>
            // Data from PHP
            const labels = <?= $json_dates ?>;
            const revenueData = <?= $json_revenue ?>;
            const bookingsData = <?= $json_bookings ?>;
            const guestsData = <?= $json_guests ?>;

            // Global Defaults
            Chart.defaults.font.family = "'Segoe UI', sans-serif";
            Chart.defaults.color = '#6c757d';

            // 1. Revenue Chart
            new Chart(document.getElementById('revenueChart'), {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Revenue ($)',
                        data: revenueData,
                        borderColor: '#ffc107',
                        backgroundColor: 'rgba(255, 193, 7, 0.1)',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { borderDash: [5, 5] } },
                        x: { grid: { display: false } }
                    }
                }
            });

            // 2. Bookings Chart
            new Chart(document.getElementById('bookingsChart'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Bookings',
                        data: bookingsData,
                        backgroundColor: '#198754',
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { borderDash: [5, 5] } },
                        x: { grid: { display: false } }
                    }
                }
            });

            // 3. Guests Chart
            new Chart(document.getElementById('guestsChart'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Guests',
                        data: guestsData,
                        backgroundColor: '#0dcaf0',
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { borderDash: [5, 5] } },
                        x: { grid: { display: false } }
                    }
                }
            });
        </script>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>