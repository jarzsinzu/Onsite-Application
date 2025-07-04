<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_role = $_POST['selected_role'] ?? '';
    if ($selected_role === 'admin' || $selected_role === 'user') {
        $_SESSION['active_role'] = $selected_role;
        header("Location: " . ($selected_role === 'admin' ? "dashboard-admin.php" : "../user/dashboard-user.php"));
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Pilih Role - ACTIVin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../asset/css/role-admin.css">
</head>

<body>
    <div class="container-role">
        <h2>Halo, <?= htmlspecialchars($_SESSION['user']) ?></h2>
        <p>Pilih peran yang ingin Anda gunakan saat ini:</p>

        <form method="POST" class="role-form">
            <div class="role-options">
                <button type="submit" name="selected_role" value="admin" class="role-card">
                    <i class="bi bi-shield-lock-fill"></i>
                    <h5>Admin</h5>
                    <p>Kelola aktivitas, verifikasi, dan data user</p>
                </button>

                <button type="submit" name="selected_role" value="user" class="role-card">
                    <i class="bi bi-person-fill"></i>
                    <h5>User</h5>
                    <p>Ajukan aktivitas on-site dan lihat histori</p>
                </button>
            </div>
        </form>
    </div>
</body>

</html>