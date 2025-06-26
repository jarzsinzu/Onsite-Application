<?php
session_start();
require('../include/koneksi.php');

if (!isset($_SESSION['user']) || !isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

$current_page = basename($_SERVER['PHP_SELF']);
$username = $_SESSION['user'];
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <title>History User - ACTIVin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <style>
    body {
      display: flex;
      background: #f5f5f5;
      font-family: 'Inter', sans-serif;
    }

    .sidebar {
      width: 200px;
      background-color: #1c1c1c;
      color: white;
      padding: 30px 20px;
      height: 100vh;
      position: fixed;
      display: flex;
      flex-direction: column;
    }

    .sidebar h2 {
      font-size: 25px;
      font-weight: bold;
      margin-bottom: 40px;
    }

    .header-section {
      margin: 30px 0 20px;
    }

    .profile {
      display: flex;
      align-items: center;
    }

    .profile strong {
      padding-right: 5px;
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
      margin: 15px 0;
      padding: 10px;
      border-radius: 8px;
      text-decoration: none;
    }

    .logout-link a:hover {
      background-color: red;
      color: white;
      font-weight: bold;
    }

    .nav-links a.active,
    .nav-links a:hover {
      background: #48cfcb;
      color: black;
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
      background: #48cfcb;
      color: white;
    }

    .table {
      background: white;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
      border-radius: 10px;
    }

    .table th {
      background: #1c1c1c;
      color: white;
      text-align: center;
    }

    .table td {
      text-align: center;
    }
  </style>
</head>

<body>
  <div class="sidebar">
    <h2>ACTIV<span style="color: #48cfcb;">in</span></h2>
    <div class="nav-container">
      <div class="nav-links">
        <a href="dashboard-user.php" class="<?= $current_page == 'dashboard-user.php' ? 'active' : '' ?>">
          <i class="bi bi-columns-gap"></i> Dashboard
        </a>
        <a href="history.php" class="<?= $current_page == 'history.php' ? 'active' : '' ?>">
          <i class="bi bi-clock-history"></i> History
        </a>
      </div>
      <div class="logout-link"><a href="../logout.php"><i class="bi bi-box-arrow-left"></i> Logout</a></div>
    </div>
  </div>

  <div class="main">
    <div class="topbar">
      <div class="input-with-icon">
        <input type="text" id="search-input" placeholder="Cari onsite...">
        <i class="bi bi-search"></i>
      </div>
      <div class="profile">
        <strong><?= htmlspecialchars($username) ?></strong>
        <i class="fas fa-user-circle fa-2x" style="color:#1c1c1c; font-size:35px;"></i>
      </div>
    </div>

    <div class="header-section">
      <h2 style="font-weight: bold;">History <span style="color: #48cfcb;">Onsite</span></h2>
    </div>

    <div class="table-responsive" id="data-container">
      <?php include 'history-search.php'; ?>
    </div>
  </div>

  <script>
    function loadHistory(page = 1) {
      const keyword = document.getElementById("search-input").value;
      const formData = new FormData();
      formData.append("search", keyword);
      formData.append("page", page);

      fetch("history-search.php", {
          method: "POST",
          body: formData
        })
        .then(res => res.text())
        .then(html => {
          document.getElementById("data-container").innerHTML = html;
        });
    }

    document.getElementById("search-input").addEventListener("input", function() {
      loadHistory(1); // reset ke halaman 1 saat pencarian
    });

    document.addEventListener("click", function(e) {
      if (e.target.classList.contains("pagination-link")) {
        e.preventDefault();
        const page = e.target.getAttribute("data-page");
        loadHistory(page);
      }
    });
  </script>
</body>

</html>