<?php
// Database Connection
$host = 'localhost';
$db   = 'hotel_management';
$user = 'root'; 
$pass = ''; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$payments = $pdo->query("SELECT * FROM payments ORDER BY payment_date DESC, id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Records | Hotel Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
   <style>
    body { 
        background-color: #f4f7f6; 
        min-height: 100vh;
    }

    /* This creates the space for the sidebar */
    .main-content { 
        margin-left: 260px; /* Adjust this to match your sidebar width */
        padding: 30px;
        transition: all 0.3s;
    }

    .table-container { 
        background: white; 
        border-radius: 12px; 
        box-shadow: 0 10px 30px rgba(0,0,0,0.05); 
        padding: 30px;
    }

    .header-section { 
        border-bottom: 2px solid #f1f1f1; 
        margin-bottom: 25px; 
        padding-bottom: 20px; 
    }

    .badge-method {
        background-color: #e9ecef;
        color: #495057;
        font-weight: 500;
    }

    /* Responsive adjustment: remove margin on small screens */
    @media (max-width: 992px) {
        .main-content {
            margin-left: 0;
            padding: 15px;
        }
    }
</style>
</head>
<body>

<?php include('../dashboard/sidebar.php'); ?>

<div class="main-content">
    <div class="container-fluid table-container">
        
        <div class="header-section d-flex justify-content-between align-items-center">
            <div>
                <h2 class="text-dark fw-bold mb-0">
                    <i class="bi bi-cash-stack text-primary me-2"></i> Payment History
                </h2>
                <p class="text-muted mb-0">Review and manage guest financial transactions</p>
            </div>
            <div class="text-end">
                <span class="badge bg-primary rounded-pill px-3 py-2">Total: <?= count($payments) ?> Records</span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="border-0">ID</th>
                        <th class="border-0">Booking</th>
                        <th class="border-0">Amount</th>
                        <th class="border-0">Method</th>
                        <th class="border-0">Date</th>
                        <th class="border-0">Transaction Ref</th>
                        <th class="border-0">Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($payments) > 0): ?>
                        <?php foreach ($payments as $p): ?>
                        <tr>
                            <td class="text-muted fw-bold"><?= htmlspecialchars($p['id']) ?></td>
                            <td>
                                <span class="badge bg-light text-primary border border-primary-subtle">
                                    <i class="bi bi-hash"></i><?= htmlspecialchars($p['booking_id']) ?>
                                </span>
                            </td>
                            <td class="fw-bold text-dark">$<?= number_format($p['amount'], 2) ?></td>
                            <td>
                                <span class="badge badge-method py-2 px-3 rounded-pill">
                                    <i class="bi bi-wallet2 me-1"></i>
                                    <?= htmlspecialchars($p['payment_method']) ?>
                                </span>
                            </td>
                            <td class="text-nowrap">
                                <i class="bi bi-calendar3 me-2 text-muted"></i>
                                <?= date('M d, Y', strtotime($p['payment_date'])) ?>
                            </td>
                            <td>
                                <small class="text-uppercase font-monospace text-danger bg-danger-subtle px-2 py-1 rounded">
                                    <?= htmlspecialchars($p['transaction_id']) ?>
                                </small>
                            </td>
                            <td class="small text-muted italic"><?= htmlspecialchars($p['notes']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">No records found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>