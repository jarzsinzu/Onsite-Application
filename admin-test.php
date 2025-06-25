<?php
session_start();
require('include/koneksi.php');

// if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
//     header("Location: test-login.php");
//     exit();
// }

$current_page = basename($_SERVER['PHP_SELF']);
$username = $_SESSION['user'];
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard Admin - ACTIVin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            background-color: #f5f5f5;
            color: #333;
            font-family: 'Inter', sans-serif;
        }

        .sidebar {
            width: 200px;
            background: #1c1c1c;
            color: #fff;
            padding: 30px 20px;
            height: 100vh;
            position: fixed;
        }

        .sidebar h2 {
            font-size: 25px;
            font-weight: bold;
            margin-bottom: 40px;
        }

        .nav-container {
            display: flex;
            flex-direction: column;
            height: 90%;
            justify-content: space-between;
        }

        .nav-links a,
        .logout-link a {
            display: block;
            color: white;
            text-decoration: none;
            margin: 15px 0;
            padding: 10px;
            border-radius: 8px;
            transition: background 0.3s;
        }

        .nav-links a.active,
        .nav-links a:hover {
            background-color: #48cfcb;
            color: #000;
            font-weight: bold;
        }

        .logout-link a:hover {
            background-color: red;
            color: #fff;
            font-weight: bold;
        }

        .main {
            margin-left: 200px;
            padding: 40px;
            width: 100%;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .input-with-icon {
            position: relative;
            max-width: 300px;
        }

        .input-with-icon input {
            width: 100%;
            padding: 10px 40px 10px 16px;
            border-radius: 20px;
            border: 1px solid #ccc;
        }

        .input-with-icon i {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #888;
        }

        .profile {
            display: flex;
            align-items: center;
        }

        .profile span {
            color: #1c1c1c;
            padding: 5px;
            font-weight: bold;
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 30px 0 20px;
        }

        .btn-tambah {
            background-color: #48cfcb;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            text-decoration: none;
        }

        .btn-tambah:hover {
            background-color: #229799;
        }

        .iframe-map {
            width: 100%;
            height: 100px;
            border: 0;
            border-radius: 6px;
        }

        .pagination {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
            gap: 5px;
            flex-wrap: wrap;
        }

        .pagination a,
        .pagination span {
            padding: 6px 12px;
            border: 1px solid #ddd;
            color: #48cfcb;
            border-radius: 4px;
            text-decoration: none;
        }

        .pagination a.active {
            background-color: #48cfcb;
            color: white;
            border-color: #48cfcb;
        }

        .pagination a:hover:not(.active) {
            background-color: #ddd;
        }

        .badge {
            font-size: 0.85rem;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
        }

        .table {
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            background-color: white;
        }

        .table th {
            background-color: #1c1c1c;
            color: white;
        }

        .table td {
            border-bottom: 2px solid #dee2e6;
            text-align: center;
        }

        .table tr:hover {
            background-color: #f8f9fa;
        }
    </style>
</head>

<body>
    <div class="sidebar">
        <h2>ACTIV<span style="color: #48cfcb;">in</span></h2>
        <div class="nav-container">
            <div class="nav-links">
                <a href="admin-test.php" class="<?= $current_page == 'admin-test.php' ? 'active' : '' ?>">
                    <i class="bi bi-columns-gap"></i> Dashboard
                </a>
            </div>
            <div class="logout-link">
                <a href="logout.php"><i class="bi bi-box-arrow-left"></i> Logout</a>
            </div>
        </div>
    </div>

    <div class="main">
        <form class="search-bar" method="post">
            <div class="topbar">
                <div class="input-with-icon">
                    <input type="text" placeholder="Cari onsite..." name="search" id="search-input-admin" autocomplete="off">
                    <i class="bi bi-search"></i>
                </div>
                <div class="profile">
                    <span><?= htmlspecialchars($username) ?></span>
                    <i class="fas fa-user-circle fa-2x" style="color:#1c1c1c; font-size:35px;"></i>
                </div>
            </div>
        </form>

        <div class="header-section">
            <h2 style="font-weight: bold;">Data <span style="color: #48cfcb;">Onsite</span> Karyawan</h2>
            <a href="tambah-test.php" class="btn-tambah">+ Tambah Data Onsite</a>
        </div>

        <div class="table-responsive" id="admin-data-container">
            <?php include 'search-ajax-admin.php'; ?>
        </div>
    </div>

    <script>
        function loadAdminPage(page) {
            const keyword = document.getElementById("search-input-admin").value;
            const formData = new FormData();
            formData.append("search", keyword);
            formData.append("page", page);

            fetch("search-ajax-admin.php", {
                    method: "POST",
                    body: formData
                })
                .then(res => res.text())
                .then(html => {
                    document.getElementById("admin-data-container").innerHTML = html;
                });
        }

        document.getElementById("search-input-admin").addEventListener("input", function() {
            loadAdminPage(1);
        });

        document.addEventListener("click", function(e) {
            if (e.target.classList.contains("pagination-link")) {
                e.preventDefault();
                const page = e.target.getAttribute("data-page");
                loadAdminPage(page);
            }
        });
    </script>
</body>

</html>
