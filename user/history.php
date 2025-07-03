<?php
session_start();
require('../include/koneksi.php');

if (!isset($_SESSION['user']) || !isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

$current_page = basename($_SERVER['PHP_SELF']);
$username = $_SESSION['user'];
$user_id = $_SESSION['user_id'];

// Handle AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
  $search = mysqli_real_escape_string($conn, $_POST['search'] ?? '');
  $page = max((int)($_POST['page'] ?? 1), 1);
  $limit = 5;
  $offset = ($page - 1) * $limit;

  // Hitung total data untuk pagination
  $count_sql = "
    SELECT COUNT(DISTINCT to1.id) as total
    FROM tambah_onsite to1
    LEFT JOIN tim_onsite t ON to1.id = t.id_onsite
    LEFT JOIN anggota a ON t.id_anggota = a.id
    WHERE to1.user_id = $user_id 
      AND to1.status_pembayaran IN ('Disetujui', 'Ditolak')
      AND (
        to1.tanggal LIKE '%$search%' 
        OR to1.keterangan_kegiatan LIKE '%$search%'
        OR a.nama LIKE '%$search%'
      )
  ";

  $total_result = mysqli_query($conn, $count_sql);
  $total_rows = mysqli_fetch_assoc($total_result)['total'];
  $total_pages = ceil($total_rows / $limit);

  // Ambil data berdasarkan pencarian & halaman
  $sql = "
    SELECT DISTINCT to1.* 
    FROM tambah_onsite to1
    LEFT JOIN tim_onsite t ON to1.id = t.id_onsite
    LEFT JOIN anggota a ON t.id_anggota = a.id
    WHERE to1.user_id = $user_id 
      AND to1.status_pembayaran IN ('Disetujui', 'Ditolak')
      AND (
        to1.tanggal LIKE '%$search%' 
        OR to1.keterangan_kegiatan LIKE '%$search%'
        OR a.nama LIKE '%$search%'
      )
    ORDER BY to1.id DESC
    LIMIT $offset, $limit
  ";

  $result = mysqli_query($conn, $sql);
?>

  <!-- Card Content -->
  <?php while ($row = mysqli_fetch_assoc($result)) : ?>
    <div class="onsite-card">
      <div class="onsite-header">
        <div>
          <strong><?= htmlspecialchars($row['keterangan_kegiatan']) ?></strong><br>
          <small><?= date('d M Y', strtotime($row['tanggal'])) ?> | <?= date('H:i', strtotime($row['jam_mulai'])) ?> - <?= date('H:i', strtotime($row['jam_selesai'])) ?></small>
        </div>
        <div>
          <?php
          $status = $row['status_pembayaran'];
          $statusClass = match ($status) {
            'Menunggu' => 'warning',
            'Disetujui' => 'success',
            'Ditolak' => 'danger',
            default => 'secondary'
          };
          ?>
          <span class="badge-status <?= $statusClass ?>"><?= htmlspecialchars($status) ?></span>
        </div>
      </div>

      <div class="onsite-details">
        <div class="onsite-info">
          <div><strong>Anggota:</strong><br>
            <?php
            $id_onsite = $row['id'];
            $anggota_result = mysqli_query($conn, "
              SELECT a.nama 
              FROM tim_onsite t
              JOIN anggota a ON t.id_anggota = a.id
              WHERE t.id_onsite = $id_onsite
            ");
            while ($anggota = mysqli_fetch_assoc($anggota_result)) {
              echo '<span class="onsite-badge">' . htmlspecialchars($anggota['nama']) . '</span>';
            }
            ?>
          </div>
          <div class="mt-2"><strong>Biaya:</strong> Rp. <?= number_format($row['estimasi_biaya'], 0, ',', '.') ?></div>
          <div class="mt-2 onsite-files">
            <?php if (!empty($row['dokumentasi'])): ?>
              <a href="../uploads/<?= urlencode($row['dokumentasi']) ?>" target="_blank"><i class="bi bi-folder2-open"></i> Dokumentasi</a>
            <?php endif; ?>
            <?php if (!empty($row['file_csv'])): ?>
              <a href="../download.php?file=<?= urlencode($row['file_csv']) ?>"><i class="bi bi-filetype-csv"></i> CSV</a>
            <?php endif; ?>
          </div>
        </div>

        <div class="map-box">
          <?php if ($row['latitude'] && $row['longitude']) : ?>
            <iframe src="https://www.google.com/maps?q=<?= $row['latitude'] ?>,<?= $row['longitude'] ?>&hl=id&z=15&output=embed"
              width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
          <?php else : ?>
            <em>Lokasi tidak tersedia</em>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endwhile; ?>

  <!-- Pagination -->
  <div class="pagination">
    <?php if ($page > 1): ?>
      <a href="#" class="pagination-link" data-page="<?= $page - 1 ?>">&laquo;</a>
    <?php endif; ?>
    <?php
    $start = max(1, $page - 2);
    $end = min($total_pages, $page + 2);
    if ($start > 1) {
      echo '<a href="#" class="pagination-link" data-page="1">1</a>';
      if ($start > 2) echo '<span>...</span>';
    }
    for ($i = $start; $i <= $end; $i++) {
      $active = ($i == $page) ? 'active' : '';
      echo "<a href='#' class='pagination-link $active' data-page='$i'>$i</a>";
    }
    if ($end < $total_pages) {
      if ($end < $total_pages - 1) echo '<span>...</span>';
      echo '<a href="#" class="pagination-link" data-page="' . $total_pages . '">' . $total_pages . '</a>';
    }
    ?>
    <?php if ($page < $total_pages): ?>
      <a href="#" class="pagination-link" data-page="<?= $page + 1 ?>">&raquo;</a>
    <?php endif; ?>
  </div>

<?php
  exit();
}

// Initial load - get data for first page
$search = '';
$page = 1;
$limit = 5;
$offset = 0;

$count_sql = "
  SELECT COUNT(DISTINCT to1.id) as total
  FROM tambah_onsite to1
  LEFT JOIN tim_onsite t ON to1.id = t.id_onsite
  LEFT JOIN anggota a ON t.id_anggota = a.id
  WHERE to1.user_id = $user_id 
    AND to1.status_pembayaran IN ('Disetujui', 'Ditolak')
";

$total_result = mysqli_query($conn, $count_sql);
$total_rows = mysqli_fetch_assoc($total_result)['total'];
$total_pages = ceil($total_rows / $limit);

$sql = "
  SELECT DISTINCT to1.* 
  FROM tambah_onsite to1
  LEFT JOIN tim_onsite t ON to1.id = t.id_onsite
  LEFT JOIN anggota a ON t.id_anggota = a.id
  WHERE to1.user_id = $user_id 
    AND to1.status_pembayaran IN ('Disetujui', 'Ditolak')
  ORDER BY to1.id DESC
  LIMIT $offset, $limit
";

$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>History User - ACTIVin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <!-- CSS yang sudah dimodifikasi untuk responsive -->
  <style>
    body {
      display: flex;
      background-color: #f5f5f5;
      color: #333;
      font-family: 'Inter', sans-serif;
      margin: 0;
      padding: 0;
    }

    .sidebar {
      width: 200px;
      background: #1c1c1c;
      color: #fff;
      padding: 30px 20px;
      height: 100vh;
      position: fixed;
      z-index: 1001;
      transition: transform 0.3s ease;
      left: 0;
      top: 0;
    }

    .sidebar .card-logo {
      width: 100%;
      height: auto;
      margin-bottom: 28px;
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
      width: calc(100% - 200px);
      min-height: 100vh;
    }

    .topbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
      flex-wrap: wrap;
      gap: 15px;
    }

    .input-with-icon {
      position: relative;
      width: 100%;
      max-width: 300px;
    }

    .input-with-icon input {
      width: 100%;
      padding: 10px 40px 10px 16px;
      border-radius: 20px;
      border: 1px solid #ccc;
      box-sizing: border-box;
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
      gap: 10px;
    }

    .profile span {
      color: #1c1c1c;
      padding: 5px;
      font-weight: bold;
    }

    .header-section {
      margin: 30px 0 20px;
    }

    .header-section h2 {
      font-weight: bold;
      font-size: 1.8rem;
    }

    /* Card Styles */
    .onsite-card {
      background: white;
      border-radius: 12px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
      margin-bottom: 20px;
      padding: 20px;
      display: flex;
      flex-direction: column;
      gap: 15px;
    }

    .onsite-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      flex-wrap: wrap;
      gap: 10px;
    }

    .onsite-header>div:first-child {
      flex: 1;
      min-width: 200px;
    }

    .onsite-badge {
      background-color: #48cfcb;
      color: #fff;
      border-radius: 20px;
      padding: 5px 12px;
      font-size: 0.85rem;
      margin: 2px;
      display: inline-block;
    }

    .map-box {
      width: 100%;
      max-width: 300px;
      height: 180px;
      border-radius: 10px;
      overflow: hidden;
      flex-shrink: 0;
    }

    .onsite-details {
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      gap: 20px;
      margin-top: 10px;
    }

    .onsite-info {
      flex: 1;
      min-width: 250px;
    }

    .badge-status {
      padding: 6px 14px;
      border-radius: 30px;
      font-weight: 500;
      font-size: 0.85rem;
      white-space: nowrap;
    }

    .badge-status.warning {
      background-color: #fff4cc;
      color: #b38f00;
    }

    .badge-status.success {
      background-color: #d4edda;
      color: #155724;
    }

    .badge-status.danger {
      background-color: #f8d7da;
      color: #721c24;
    }

    .onsite-files {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-top: 10px;
    }

    .onsite-files a {
      text-decoration: none;
      color: #0d6efd;
      font-size: 0.9rem;
    }

    .pagination {
      display: flex;
      justify-content: right;
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
      min-width: 40px;
      text-align: center;
    }

    .pagination a.active {
      background-color: #48cfcb;
      color: white;
      border-color: #48cfcb;
    }

    .pagination a:hover:not(.active) {
      background-color: #ddd;
    }

    /* Mobile Menu Toggle */
    .mobile-menu-toggle {
      display: none;
      background: none;
      border: none;
      font-size: 1.5rem;
      color: #1c1c1c;
      cursor: pointer;
      padding: 10px;
    }

    /* Sidebar Overlay */
    .sidebar-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      z-index: 1000;
      opacity: 0;
      visibility: hidden;
      transition: opacity 0.3s ease, visibility 0.3s ease;
    }

    .sidebar-overlay.active {
      opacity: 1;
      visibility: visible;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
      body {
        flex-direction: column;
      }

      .sidebar {
        transform: translateX(-100%);
      }

      .sidebar.active {
        transform: translateX(0);
      }

      .main {
        margin-left: 0;
        padding: 20px 15px;
        width: 100%;
      }

      .mobile-menu-toggle {
        display: block;
      }

      .topbar {
        flex-direction: column;
        align-items: stretch;
        gap: 15px;
      }

      .topbar .d-flex {
        justify-content: space-between;
        align-items: center;
      }

      .input-with-icon {
        max-width: 100%;
        order: 2;
      }

      .profile {
        justify-content: flex-end;
      }

      .header-section h2 {
        font-size: 1.5rem;
      }

      .onsite-card {
        padding: 15px;
      }

      .onsite-header {
        flex-direction: column;
        align-items: flex-start;
      }

      .onsite-header>div:first-child {
        min-width: 100%;
        margin-bottom: 10px;
      }

      .onsite-details {
        flex-direction: column;
        gap: 15px;
      }

      .onsite-info {
        min-width: 100%;
      }

      .map-box {
        max-width: 100%;
        height: 200px;
      }

      .onsite-files {
        flex-direction: column;
        gap: 8px;
      }

      .pagination {
        justify-content: center;
        margin-top: 15px;
      }

      .pagination a,
      .pagination span {
        padding: 8px 12px;
        min-width: 35px;
        font-size: 0.9rem;
      }
    }

    @media (max-width: 480px) {
      .main {
        padding: 15px 10px;
      }

      .header-section h2 {
        font-size: 1.3rem;
      }

      .onsite-card {
        padding: 12px;
      }

      .onsite-header strong {
        font-size: 0.95rem;
      }

      .onsite-header small {
        font-size: 0.8rem;
      }

      .badge-status {
        padding: 4px 10px;
        font-size: 0.8rem;
      }

      .onsite-badge {
        padding: 3px 8px;
        font-size: 0.8rem;
      }

      .map-box {
        height: 160px;
      }

      .pagination a,
      .pagination span {
        padding: 6px 8px;
        min-width: 30px;
        font-size: 0.8rem;
      }
    }
  </style>
</head>

<body>
  <!-- Overlay untuk mobile -->
  <div class="sidebar-overlay" id="sidebar-overlay"></div>

  <!-- Sidebar -->
  <div class="sidebar" id="sidebar">
    <img src="../asset/logo-E.png" alt="Logo" class="card-logo">
    <div class="nav-container">
      <div class="nav-links">
        <a href="dashboard-user.php" class="<?= $current_page == 'dashboard-user.php' ? 'active' : '' ?>">
          <i class="bi bi-columns-gap"></i> Dashboard
        </a>
        <a href="history.php" class="<?= $current_page == 'history.php' ? 'active' : '' ?>">
          <i class="bi bi-clock-history"></i> History
        </a>
      </div>
      <div class="logout-link">
        <a href="../logout.php">
          <i class="bi bi-box-arrow-left"></i> Logout
        </a>
      </div>
    </div>
  </div>

  <!-- Main Content -->
  <div class="main">
    <div class="topbar">
      <!-- Mobile menu toggle dan profile dalam satu baris -->
      <div class="d-flex justify-content-between align-items-center w-100 d-md-none">
        <button class="mobile-menu-toggle" id="mobile-menu-toggle">
          <i class="bi bi-list"></i>
        </button>
        <div class="profile">
          <span><?= htmlspecialchars($username) ?></span>
          <i class="fas fa-user-circle fa-2x" style="color:#1c1c1c; font-size:35px;"></i>
        </div>
      </div>

      <!-- Desktop layout -->
      <div class="input-with-icon">
        <input type="text" placeholder="Cari onsite..." id="search-input" autocomplete="off">
        <i class="bi bi-search"></i>
      </div>
      <div class="profile d-none d-md-flex">
        <span><?= htmlspecialchars($username) ?></span>
        <i class="fas fa-user-circle fa-2x" style="color:#1c1c1c; font-size:35px;"></i>
      </div>
    </div>

    <div class="header-section">
      <h2>History <span style="color: #48cfcb;">Onsite</span></h2>
    </div>

    <div id="data-container">
      <!-- Initial card load -->
      <?php while ($row = mysqli_fetch_assoc($result)) : ?>
        <div class="onsite-card">
          <div class="onsite-header">
            <div>
              <strong><?= htmlspecialchars($row['keterangan_kegiatan']) ?></strong><br>
              <small><?= date('d M Y', strtotime($row['tanggal'])) ?> | <?= date('H:i', strtotime($row['jam_mulai'])) ?> - <?= date('H:i', strtotime($row['jam_selesai'])) ?></small>
            </div>
            <div>
              <?php
              $status = $row['status_pembayaran'];
              $statusClass = match ($status) {
                'Menunggu' => 'warning',
                'Disetujui' => 'success',
                'Ditolak' => 'danger',
                default => 'secondary'
              };
              ?>
              <span class="badge-status <?= $statusClass ?>"><?= htmlspecialchars($status) ?></span>
            </div>
          </div>

          <div class="onsite-details">
            <div class="onsite-info">
              <div><strong>Anggota:</strong><br>
                <?php
                $id_onsite = $row['id'];
                $anggota_result = mysqli_query($conn, "
                  SELECT a.nama 
                  FROM tim_onsite t
                  JOIN anggota a ON t.id_anggota = a.id
                  WHERE t.id_onsite = $id_onsite
                ");
                while ($anggota = mysqli_fetch_assoc($anggota_result)) {
                  echo '<span class="onsite-badge">' . htmlspecialchars($anggota['nama']) . '</span>';
                }
                ?>
              </div>
              <div class="mt-2"><strong>Biaya:</strong> <span style="color: #006400; font-weight:bold;">Rp. <?= number_format($row['estimasi_biaya'], 0, ',', '.') ?></div>
              <div class="mt-2 onsite-files">
                <?php if (!empty($row['dokumentasi'])): ?>
                  <a href="../uploads/<?= urlencode($row['dokumentasi']) ?>" target="_blank"><i class="bi bi-folder2-open"></i> Dokumentasi</a>
                <?php endif; ?>
                <?php if (!empty($row['file_csv'])): ?>
                  <a href="../download.php?file=<?= urlencode($row['file_csv']) ?>"><i class="bi bi-filetype-csv"></i> CSV</a>
                <?php endif; ?>
              </div>
            </div>

            <div class="map-box">
              <?php if ($row['latitude'] && $row['longitude']) : ?>
                <iframe src="https://www.google.com/maps?q=<?= $row['latitude'] ?>,<?= $row['longitude'] ?>&hl=id&z=15&output=embed"
                  width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
              <?php else : ?>
                <em>Lokasi tidak tersedia</em>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endwhile; ?>

      <!-- Initial Pagination -->
      <div class="pagination">
        <?php if ($page > 1): ?>
          <a href="#" class="pagination-link" data-page="<?= $page - 1 ?>">&laquo;</a>
        <?php endif; ?>
        <?php
        $start = max(1, $page - 2);
        $end = min($total_pages, $page + 2);
        if ($start > 1) {
          echo '<a href="#" class="pagination-link" data-page="1">1</a>';
          if ($start > 2) echo '<span>...</span>';
        }
        for ($i = $start; $i <= $end; $i++) {
          $active = ($i == $page) ? 'active' : '';
          echo "<a href='#' class='pagination-link $active' data-page='$i'>$i</a>";
        }
        if ($end < $total_pages) {
          if ($end < $total_pages - 1) echo '<span>...</span>';
          echo '<a href="#" class="pagination-link" data-page="' . $total_pages . '">' . $total_pages . '</a>';
        }
        ?>
        <?php if ($page < $total_pages): ?>
          <a href="#" class="pagination-link" data-page="<?= $page + 1 ?>">&raquo;</a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <script>
    // Search and pagination functionality
    function loadHistory(page = 1) {
      const keyword = document.getElementById("search-input").value;
      const formData = new FormData();
      formData.append("search", keyword);
      formData.append("page", page);
      formData.append("ajax", "1");

      fetch(window.location.href, {
          method: "POST",
          body: formData
        })
        .then(res => res.text())
        .then(html => {
          document.getElementById("data-container").innerHTML = html;
        })
        .catch(error => {
          console.error('Error:', error);
        });
    }

    // Search input event
    document.getElementById("search-input").addEventListener("input", function() {
      loadHistory(1);
    });

    // Pagination click event
    document.addEventListener("click", function(e) {
      if (e.target.classList.contains("pagination-link")) {
        e.preventDefault();
        const page = e.target.getAttribute("data-page");
        loadHistory(page);
      }
    });

    // Mobile menu functionality
    const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebar-overlay');

    mobileMenuToggle?.addEventListener('click', function() {
      sidebar.classList.add('active');
      sidebarOverlay.classList.add('active');
    });

    sidebarOverlay?.addEventListener('click', function() {
      sidebar.classList.remove('active');
      sidebarOverlay.classList.remove('active');
    });

    // Search and pagination functionality
    function loadHistory(page = 1) {
      const keyword = document.getElementById("search-input").value;
      const formData = new FormData();
      formData.append("search", keyword);
      formData.append("page", page);
      formData.append("ajax", "1");

      fetch(window.location.href, {
          method: "POST",
          body: formData
        })
        .then(res => res.text())
        .then(html => {
          document.getElementById("data-container").innerHTML = html;
        })
        .catch(error => {
          console.error('Error:', error);
        });
    }

    // Search input event
    document.getElementById("search-input").addEventListener("input", function() {
      loadHistory(1);
    });

    // Pagination click event
    document.addEventListener("click", function(e) {
      if (e.target.classList.contains("pagination-link")) {
        e.preventDefault();
        const page = e.target.getAttribute("data-page");
        loadHistory(page);
      }
    });

    // Auto-close sidebar when clicking nav link on mobile
    document.addEventListener('click', function(e) {
      if (e.target.closest('.nav-links a') && window.innerWidth <= 768) {
        sidebar.classList.remove('active');
        sidebarOverlay.classList.remove('active');
      }
    });

    // Close sidebar on window resize if mobile
    window.addEventListener('resize', function() {
      if (window.innerWidth > 768) {
        sidebar.classList.remove('active');
        sidebarOverlay.classList.remove('active');
      }
    });
  </script>
</body>

</html>