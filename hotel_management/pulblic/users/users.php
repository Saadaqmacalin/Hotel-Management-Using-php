<?php
// 1. DATABASE CONFIGURATION & CONNECTION
$host = 'localhost';
$dbname = 'hotel_management';
$user = 'root'; 
$pass = ''; 

try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

$message = "";
$message_type = "";

// 2. HANDLE DELETE LOGIC
if (isset($_GET['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$_GET['delete_id']]);
    $message = "User deleted successfully."; 
    $message_type = "success";
}

// 3. CHECK IF EDITING
$edit_mode = false;
$user_id = null;
$user_data = ['full_name' => '', 'username' => '', 'email' => '', 'role' => 'staff'];

if (isset($_GET['edit_id'])) {
    $edit_mode = true;
    $user_id = $_GET['edit_id'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user_data) { $message = "User not found."; $message_type="danger"; $edit_mode = false; }
}

// 4. HANDLE FORM SUBMISSION
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username  = trim($_POST['username']);
    $email     = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $full_name = trim($_POST['full_name']);
    $role      = $_POST['role'];
    $password  = $_POST['password'];

    try {
        if ($edit_mode) {
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET username=?, password=?, email=?, full_name=?, role=? WHERE id=?";
                $params = [$username, $hashed_password, $email, $full_name, $role, $user_id];
            } else {
                $sql = "UPDATE users SET username=?, email=?, full_name=?, role=? WHERE id=?";
                $params = [$username, $email, $full_name, $role, $user_id];
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            header("Location: " . $_SERVER['PHP_SELF'] . "?msg=updated");
            exit;
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (username, password, email, full_name, role) VALUES (?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$username, $hashed_password, $email, $full_name, $role]);
            $message = "User registered successfully!";
            $message_type = "success";
        }
    } catch (PDOException $e) {
        $message = ($e->getCode() == 23000) ? "Username or Email already exists." : "Error: " . $e->getMessage();
        $message_type = "danger";
    }
}

if (isset($_GET['msg']) && $_GET['msg'] == 'updated') {
    $message = "User updated successfully!";
    $message_type = "success";
}

// 5. SEARCH & FETCH
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
if ($search) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username LIKE ? OR full_name LIKE ? OR email LIKE ? ORDER BY id DESC");
    $stmt->execute(["%$search%", "%$search%", "%$search%"]);
} else {
    $stmt = $pdo->query("SELECT * FROM users ORDER BY id DESC");
}
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Management | Hotel Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { 
            background-color: #f4f7f6; 
            font-family: 'Segoe UI', sans-serif; 
        }
        /* Adjusted for Sidebar Space */
        .main-content { 
            margin-left: 260px; /* Adjust this width to match your sidebar width */
            padding: 40px;
        }
        .card { border: none; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .header-box { background: #2c3e50; color: white; padding: 15px; border-radius: 10px 10px 0 0; }
        .thead-dark { background-color: #2c3e50; color: white; }
        
        /* Tablet/Mobile logic: Remove margin if sidebar disappears */
        @media (max-width: 992px) {
            .main-content { margin-left: 0; padding: 20px; }
        }
    </style>
</head>
<body>
<?php include('../dashboard/sidebar.php'); ?>
<div class="main-content">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>User Management</h2>
        <?php if ($edit_mode): ?>
            <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-outline-secondary btn-sm">Back to Registration</a>
        <?php endif; ?>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type; ?> alert-dismissible fade show shadow-sm">
            <?= $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card mb-5">
        <div class="header-box">
            <h5 class="mb-0"><?= $edit_mode ? '<i class="bi bi-pencil-square"></i> Edit User Details' : '<i class="bi bi-person-plus"></i> Register New User' ?></h5>
        </div>
        <div class="card-body p-4">
            <form action="<?= $_SERVER['PHP_SELF'] . ($edit_mode ? '?edit_id='.$user_id : '') ?>" method="POST" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Full Name</label>
                    <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user_data['full_name']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Username</label>
                    <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user_data['username']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Email</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user_data['email']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Password <?= $edit_mode ? '<small class="text-muted">(Leave blank to keep)</small>' : '' ?></label>
                    <input type="password" name="password" class="form-control" <?= $edit_mode ? '' : 'required' ?>>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Role</label>
                    <select name="role" class="form-select">
                        <option value="staff" <?= $user_data['role'] == 'staff' ? 'selected' : '' ?>>Staff</option>        
                        <option value="admin" <?= $user_data['role'] == 'admin' ? 'selected' : '' ?>>Administrator</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100"><?= $edit_mode ? 'Save Changes' : 'Register User' ?></button>
                </div>
            </form>
        </div>
    </div>

    <hr class="my-5">

    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-0">Existing Users</h5>
                </div>
                <div class="col-md-6">
                    <form class="d-flex">
                        <input type="text" name="search" class="form-control me-2" placeholder="Search by name or email..." value="<?= htmlspecialchars($search) ?>">
                        <button class="btn btn-dark">Search</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="thead-dark">
                    <tr>
                        <th class="ps-4">Full Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($users)): ?>
                        <tr><td colspan="5" class="text-center py-4">No users found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td class="ps-4 fw-bold"><?= htmlspecialchars($user['full_name']) ?></td>
                        <td><?= htmlspecialchars($user['username']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td>
                            <span class="badge <?= $user['role'] == 'admin' ? 'bg-danger' : 'bg-primary' ?> text-uppercase">
                                <?= $user['role'] ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="?edit_id=<?= $user['id'] ?>" class="btn btn-outline-warning" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="?delete_id=<?= $user['id'] ?>" class="btn btn-outline-danger" title="Delete"
                                   onclick="return confirm('Are you sure you want to delete this user?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>