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

    <style>
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background-color: #48cfcb;
            background-image: url('asset/test.png');
            background-size: cover;
            background-position: center;
            backdrop-filter: blur(2px);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            color: #fff;
        }

        .container-role {
            background: rgba(30, 30, 30, 0.96);
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.5);
            max-width: 700px;
            width: 100%;
            text-align: center;
        }

        .container-role h2 {
            margin-bottom: 10px;
            font-size: 26px;
            font-weight: 600;
        }

        .container-role p {
            font-size: 14px;
            color: #ccc;
            margin-bottom: 30px;
        }

        .role-options {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .role-card {
            background-color: #2a2a2a;
            border: 2px solid transparent;
            border-radius: 12px;
            padding: 30px 20px;
            width: 240px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .role-card:hover {
            transform: translateY(-5px);
            border-color: #48cfcb;
            box-shadow: 0 0 12px rgba(72, 207, 203, 0.3);
        }

        .role-card i {
            font-size: 40px;
            margin-bottom: 15px;
            color: #48cfcb;
        }

        .role-card h5 {
            color: #f5f5f5;
            font-size: 18px;
            margin-bottom: 5px;
        }

        .role-card p {
            font-size: 13px;
            color: #aaa;
            margin: 0;
        }

        .role-form input[type="submit"] {
            display: none;
        }

        @media (max-width: 600px) {
            .role-card {
                width: 100%;
            }
        }
    </style>
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