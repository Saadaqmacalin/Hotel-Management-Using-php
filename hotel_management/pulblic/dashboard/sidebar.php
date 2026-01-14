<?php
// sidebar.php - Sidebar Component
// Ensure session is started to display the user name
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get the current filename to set the "active" class on links
$current_page = basename($_SERVER['PHP_SELF']);

// Fallback if session name isn't set yet (matches keys from your login script)
$display_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : "Admin User";
?>

<style>
    :root {
        --sidebar-width: 260px;
        --sidebar-bg: #2c3e50;
        --sidebar-hover: #34495e;
        --accent-blue: #3498db;
        --text-gray: rgba(255, 255, 255, 0.7);
    }

    .sidebar {
        width: var(--sidebar-width);
        height: 100vh;
        background: var(--sidebar-bg);
        color: white;
        position: fixed;
        left: 0;
        top: 0;
        display: flex;
        flex-direction: column;
        z-index: 1000;
        box-shadow: 4px 0 10px rgba(0,0,0,0.1);
    }

    .sidebar-header {
        padding: 25px 20px;
        font-size: 1.3rem;
        font-weight: bold;
        border-bottom: 1px solid rgba(255,255,255,0.1);
        display: flex;
        align-items: center;
        gap: 12px;
        letter-spacing: 1px;
    }

    .nav-links {
        flex-grow: 1;
        padding: 20px 0;
        list-style: none;
        margin: 0;
        padding-left: 0;
    }

    .nav-links li a {
        display: flex;
        align-items: center;
        padding: 14px 25px;
        color: var(--text-gray);
        text-decoration: none;
        transition: all 0.3s ease;
        border-left: 4px solid transparent;
    }

    .nav-links li a i {
        margin-right: 15px;
        font-size: 1.2rem;
        width: 20px;
        text-align: center;
    }

    .nav-links li a:hover, 
    .nav-links li a.active {
        background: var(--sidebar-hover);
        color: white;
        border-left-color: var(--accent-blue);
    }

    .user-profile-section {
        padding: 20px;
        background: rgba(0,0,0,0.2);
        border-top: 1px solid rgba(255,255,255,0.1);
    }

    .logged-label {
        font-size: 0.65rem;
        text-transform: uppercase;
        color: rgba(255,255,255,0.5);
        letter-spacing: 1px;
        display: block;
        margin-bottom: 5px;
    }

    .user-name-display {
        font-weight: 600;
        font-size: 1rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .logout-link {
        color: #ff7675;
        text-decoration: none;
        font-size: 0.85rem;
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 12px;
        transition: 0.3s;
        cursor: pointer;
    }

    .logout-link:hover {
        color: #fab1a0;
    }

    @media (max-width: 992px) {
        .sidebar { transform: translateX(-100%); transition: 0.3s; }
        .sidebar.active { transform: translateX(0); }
    }
</style>

<div class="sidebar">
    <div class="sidebar-header">
        <i class="bi bi-building"></i>
        <span>HOTEL ADMIN</span>
    </div>
    
    <ul class="nav-links">
        <li>
            <a href="../dashboard/dashboard.php" class="<?= ($current_page == 'dashboard.php') ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        </li>
        <li>
            <a href="../bookings/booking.php" class="<?= ($current_page == 'booking.php') ? 'active' : '' ?>">
                <i class="bi bi-calendar-check"></i> Bookings
            </a>
        </li>
        <li>
            <a href="../guests/getGuests.php" class="<?= ($current_page == 'getGuests.php' || $current_page == 'guest.php') ? 'active' : '' ?>">
                <i class="bi bi-people"></i> Guests
            </a>
        </li>
        <li>
            <a href="../rooms/rooms.php" class="<?= ($current_page == 'rooms.php') ? 'active' : '' ?>">
                <i class="bi bi-door-open"></i> Rooms
            </a>
        </li>
        <li>
            <a href="../payments/payment.php" class="<?= ($current_page == 'payment.php') ? 'active' : '' ?>">
                <i class="bi bi-credit-card"></i> Payments
            </a>
        </li>
        <li>
            <a href="../users/users.php" class="<?= ($current_page == 'users.php') ? 'active' : '' ?>">
                <i class="bi bi-person-gear"></i> Users
            </a>
        </li>
    </ul>

    <div class="user-profile-section">
        <span class="logged-label">Logged in as</span>
        <div class="d-flex align-items-center">
            <i class="bi bi-person-circle fs-4 me-2 text-info"></i>
            <span class="user-name-display"><?= htmlspecialchars($display_name) ?></span>
        </div>
        <a href="../users/login.php" class="logout-link" onclick="return confirm('Are you sure you want to logout?')">
            <i class="bi bi-box-arrow-left"></i> Logout
        </a>
    </div>
</div>