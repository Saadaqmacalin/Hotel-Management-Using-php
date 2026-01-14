<?php
require_once __DIR__ . '/../../src/config.php'; 

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $new_pass = $_POST['new_password'];

    // 1. Check if the email exists in the system
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // 2. Update to new password using password_hash for security
        $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
        $update = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $update->bind_param("ss", $hashed, $email);
        
        if ($update->execute()) {
            $message = "Success! Your password has been updated.";
            $message_type = "success";
        } else {
            $message = "Error: Could not update password.";
            $message_type = "danger";
        }
    } else {
        $message = "No account found with that email address.";
        $message_type = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | Hotel Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            background: #f4f7f6; 
            height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
        }
        .reset-card { 
            width: 100%; 
            max-width: 400px; 
            border-radius: 15px; 
            border: none; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.1); 
            background: #ffffff;
        }
        .btn-primary { background: #2c3e50; border: none; }
        .btn-primary:hover { background: #1a252f; }
    </style>
</head>
<body>
    <div class="card reset-card p-4">
        <div class="text-center mb-4">
            <h4 class="fw-bold text-dark">Reset Password</h4>
            <p class="text-muted small">Enter your email and new password</p>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?> py-2 text-center small"><?= $message ?></div>
        <?php endif; ?>

        <form method="POST" action="resetPassword.php">
            <div class="mb-3">
                <label class="form-label small">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="your@email.com" required>
            </div>
            <div class="mb-3">
                <label class="form-label small">New Password</label>
                <input type="password" name="new_password" class="form-control" placeholder="••••••••" required>
            </div>
            
            <button type="submit" class="btn btn-primary w-100 mb-3 py-2">Update Password</button>
            
            <div class="text-center">
                <a href="login.php" class="small text-muted text-decoration-none">
                    <i class="bi bi-arrow-left"></i> Back to Login
                </a>
            </div>
        </form>
    </div>
</body>
</html>