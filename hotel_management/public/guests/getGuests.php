<?php
session_start();

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

/** 1. AJAX DELETE HANDLER **/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_delete'])) {
    header('Content-Type: application/json');
    try {
        $stmt = $pdo->prepare("DELETE FROM guests WHERE id = ?");
        $result = $stmt->execute([$_POST['guest_id']]);
        echo json_encode(['success' => $result]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit; 
}

/** 2. PHP UPDATE HANDLER (For Edit Modal) **/
if (isset($_POST['update_guest'])) {
    try {
        $sql = "UPDATE guests SET first_name=?, last_name=?, email=?, phone=?, address=?, id_proof=? WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['first_name'], $_POST['last_name'], $_POST['email'], 
            $_POST['phone'], $_POST['address'], $_POST['id_proof'], $_POST['guest_id']
        ]);
        $_SESSION['msg'] = "Guest updated successfully!";
        $_SESSION['msg_type'] = "success";
    } catch (Exception $e) {
        $_SESSION['msg'] = "Update failed: " . $e->getMessage();
        $_SESSION['msg_type'] = "danger";
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// 3. Fetch Guests
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sql = "SELECT * FROM guests";
if (!empty($search)) {
    $sql .= " WHERE first_name LIKE :s OR last_name LIKE :s OR email LIKE :s";
    $stmt = $pdo->prepare($sql . " ORDER BY id DESC");
    $stmt->execute(['s' => "%$search%"]);
} else {
    $stmt = $pdo->query($sql . " ORDER BY id DESC");
}
$guests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Guest Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f4f7f6; }
        .main-content { margin-left: 260px; padding: 30px; }
        .row-hidden { opacity: 0; transform: scale(0.9); transition: all 0.4s ease; }
        .alert-fixed { position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px; }
    </style>
</head>
<body>
<?php include('../dashboard/sidebar.php'); ?>

<div class="main-content">
    <?php if (isset($_SESSION['msg'])): ?>
        <div class="alert alert-<?= $_SESSION['msg_type'] ?> alert-dismissible fade show alert-fixed shadow">
            <?= $_SESSION['msg'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['msg']); unset($_SESSION['msg_type']); ?>
    <?php endif; ?>

    <div id="live-alert" class="alert-fixed"></div>

    <div class="container-fluid bg-white p-4 shadow-sm rounded">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold text-primary">Guest Management</h2>
            <span class="badge bg-dark">Total: <span id="count"><?= count($guests) ?></span></span>
        </div>

        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($guests as $g): ?>
                <tr id="row-<?= $g['id'] ?>">
                    <td>#<?= $g['id'] ?></td>
                    <td class="fw-bold"><?= htmlspecialchars($g['first_name'] . " " . $g['last_name']) ?></td>
                    <td><?= htmlspecialchars($g['email']) ?></td>
                    <td><?= htmlspecialchars($g['phone']) ?></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary me-1" onclick='openEditModal(<?= json_encode($g) ?>)'>
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteGuest(<?= $g['id'] ?>)">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" method="POST">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Edit Guest</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="guest_id" id="edit_id">
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label small fw-bold">First Name</label>
                        <input type="text" name="first_name" id="edit_first" class="form-control" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold">Last Name</label>
                        <input type="text" name="last_name" id="edit_last" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">Email</label>
                        <input type="email" name="email" id="edit_email" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">Phone Number</label>
                        <input type="text" name="phone" id="edit_phone" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">ID Proof</label>
                        <input type="text" name="id_proof" id="edit_proof" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">Address</label>
                        <textarea name="address" id="edit_address" class="form-control" rows="2"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" name="update_guest" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// 1. OPEN MODAL & FILL DATA
function openEditModal(guest) {
    document.getElementById('edit_id').value = guest.id;
    document.getElementById('edit_first').value = guest.first_name;
    document.getElementById('edit_last').value = guest.last_name;
    document.getElementById('edit_email').value = guest.email;
    document.getElementById('edit_phone').value = guest.phone;
    document.getElementById('edit_proof').value = guest.id_proof;
    document.getElementById('edit_address').value = guest.address;
    
    var myModal = new bootstrap.Modal(document.getElementById('editModal'));
    myModal.show();
}

// 2. AJAX DELETE
async function deleteGuest(id) {
    if (!confirm("Are you sure?")) return;

    const params = new URLSearchParams();
    params.append('ajax_delete', '1');
    params.append('guest_id', id);

    try {
        const response = await fetch(window.location.href, {
            method: 'POST',
            body: params
        });
        const data = await response.json();

        if (data.success) {
            const row = document.getElementById(`row-${id}`);
            row.classList.add('row-hidden');
            setTimeout(() => row.remove(), 400);
            
            const count = document.getElementById('count');
            count.innerText = parseInt(count.innerText) - 1;

            const alertDiv = document.getElementById('live-alert');
            alertDiv.innerHTML = `<div class="alert alert-danger shadow">Guest deleted successfully.</div>`;
            setTimeout(() => alertDiv.innerHTML = '', 3000);
        }
    } catch (err) {
        alert("Delete failed. Check console.");
    }
}
</script>

</body>
</html>