<?php
session_start();
require('../include/koneksi.php');

if (!isset($_SESSION['user']) || !isset($_SESSION['user_id'])) {
  header("Location: ../login.php");
  exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['user'];

// Handle AJAX request untuk search dan pagination
if (isset($_POST['action']) && $_POST['action'] == 'search_data') {
  $search = $_POST['search'] ?? '';
  $page = $_POST['page'] ?? 1;
  $records_per_page = 5;
  $offset = ($page - 1) * $records_per_page;

  $search = mysqli_real_escape_string($conn, $search);

  // Menghitung total data untuk pagination
  $count_query = "SELECT COUNT(DISTINCT to1.id) as total 
                    FROM tambah_onsite to1
                    LEFT JOIN tim_onsite t ON to1.id = t.id_onsite
                    LEFT JOIN anggota a ON t.id_anggota = a.id
                    WHERE to1.user_id = $user_id
                    AND to1.status_pembayaran = 'Menunggu'";
  if (!empty($search)) {
    $count_query .= " AND (
            to1.tanggal LIKE '%$search%' 
            OR to1.keterangan_kegiatan LIKE '%$search%' 
            OR to1.status_pembayaran LIKE '%$search%' 
            OR a.nama LIKE '%$search%'
        )";
  }

  $count_result = mysqli_query($conn, $count_query);
  $total_rows = mysqli_fetch_assoc($count_result)['total'];
  $total_pages = ceil($total_rows / $records_per_page);

  // Query ambil data sesuai filter dan halaman
  $data_query = "SELECT DISTINCT to1.* 
                   FROM tambah_onsite to1
                   LEFT JOIN tim_onsite t ON to1.id = t.id_onsite
                   LEFT JOIN anggota a ON t.id_anggota = a.id
                   WHERE to1.user_id = $user_id
                   AND to1.status_pembayaran = 'Menunggu'";

  if (!empty($search)) {
    $data_query .= " AND (
            to1.tanggal LIKE '%$search%' 
            OR to1.keterangan_kegiatan LIKE '%$search%' 
            OR to1.status_pembayaran LIKE '%$search%' 
            OR a.nama LIKE '%$search%'
        )";
  }

  $data_query .= " ORDER BY to1.id DESC LIMIT $offset, $records_per_page";
  $result = mysqli_query($conn, $data_query);

  // Output HTML untuk AJAX response
?>
  <table class="table table-bordered align-middle table-rounded rounded-4 overflow-hidden shadow">
    <thead class="table-dark">
      <tr style="text-align: center;">
        <th style="width: 120px;">Anggota</th>
        <th style="width: 120px;">Tanggal</th>
        <th>Lokasi</th>
        <th style="width: 150px;">Detail Kegiatan</th>
        <th style="width: 120px;">Waktu</th>
        <th><i class="bi bi-folder2 fs-5"></i></th>
        <th><i class="bi bi-filetype-csv fs-5"></i></th>
        <th>Biaya</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($row = mysqli_fetch_assoc($result)) : ?>
        <tr>
          <td style="text-align: left;">
            <ul style="padding-left: 15px;">
              <?php
              $id_onsite = $row['id'];
              $anggota_query = "
                                SELECT a.nama 
                                FROM tim_onsite t
                                JOIN anggota a ON t.id_anggota = a.id
                                WHERE t.id_onsite = $id_onsite
                            ";
              $anggota_result = mysqli_query($conn, $anggota_query);
              while ($anggota = mysqli_fetch_assoc($anggota_result)) {
                echo "<li>" . htmlspecialchars($anggota['nama']) . "</li>";
              }
              ?>
            </ul>
          </td>
          <td><?= htmlspecialchars($row['tanggal']) ?></td>
          <td style="width: 180px; height: 180px;">
            <?php if ($row['latitude'] && $row['longitude']) : ?>
              <iframe src="https://www.google.com/maps?q=<?= $row['latitude'] ?>,<?= $row['longitude'] ?>&hl=id&z=15&output=embed"
                width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
            <?php else : ?>
              <em>Lokasi tidak tersedia</em>
            <?php endif; ?>
          </td>
          <td><?= htmlspecialchars($row['keterangan_kegiatan']) ?></td>
          <td><?= date('H:i', strtotime($row['jam_mulai'])) ?>-<?= date('H:i', strtotime($row['jam_selesai'])) ?></td>
          <td>
            <?php if (!empty($row['dokumentasi'])) : ?>
              <a href="../uploads/<?= urlencode($row['dokumentasi']) ?>" target="_blank">Lihat</a>
            <?php else : ?>
              Tidak ada
            <?php endif; ?>
          </td>
          <td>
            <?php if (!empty($row['file_csv'])): ?>
              <a href="../download.php?file=<?= urlencode($row['file_csv']) ?>">CSV</a>
            <?php else : ?>
              Tidak ada
            <?php endif; ?>
          </td>
          <td style="color: #006400; font-weight:bold;">Rp. <?= number_format($row['estimasi_biaya'], 0, ',', '.') ?></td>
          <td>
            <?php
            $status = $row['status_pembayaran'];
            $badge = 'warning';
            if ($status === 'Disetujui') $badge = 'success';
            elseif ($status === 'Ditolak') $badge = 'danger';
            ?>
            <span class="badge bg-<?= $badge ?>"><?= htmlspecialchars($status) ?></span>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>

  <!-- Pagination -->
  <div class="pagination">
    <?php if ($page > 1): ?>
      <a href="#" class="pagination-link" data-page="<?= $page - 1 ?>">&laquo;</a>
    <?php endif; ?>

    <?php
    $start = max(1, $page - 2);
    $end = min($total_pages, $page + 2);

    // Halaman pertama
    if ($start > 1) {
      echo '<a href="#" class="pagination-link" data-page="1">1</a>';
      if ($start > 2) echo '<span>...</span>';
    }

    for ($i = $start; $i <= $end; $i++) {
      $active = ($i == $page) ? 'active' : '';
      echo "<a href='#' class='pagination-link $active' data-page='$i'>$i</a>";
    }

    // Halaman akhir
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
  exit; // Keluar setelah handle AJAX
}

// Data untuk halaman pertama (non-AJAX)
$current_page = basename($_SERVER['PHP_SELF']);
$page = 1;
$records_per_page = 5;
$offset = ($page - 1) * $records_per_page;

$count_query = "SELECT COUNT(*) as total FROM tambah_onsite WHERE user_id = $user_id AND status_pembayaran = 'Menunggu'";
$count_result = mysqli_query($conn, $count_query);
$total_rows = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_rows / $records_per_page);

$query = "SELECT * FROM tambah_onsite WHERE user_id = $user_id AND status_pembayaran = 'Menunggu' ORDER BY id DESC LIMIT $offset, $records_per_page";
$result = mysqli_query($conn, $query);

$anggota_result = mysqli_query($conn, "SELECT id, nama FROM anggota");
$anggota_array = mysqli_fetch_all($anggota_result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard User - ACTIVin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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

    .sidebar .card-logo {
      width: 100%;
      height: auto;
      margin-bottom: 28px;
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

    .header-section button {
      background-color: #48cfcb;
      border: none;
      padding: 10px 20px;
      border-radius: 8px;
      color: white;
      font-weight: 600;
    }

    .header-section button:hover {
      background-color: #229799;
    }

    .badge {
      font-size: 0.9rem;
      padding: 8px 12px;
      border-radius: 20px;
    }

    .modal-body {
      max-height: 70vh;
      overflow-y: auto;
    }

    .modal-content {
      border-radius: 10px;
    }

    .modal-footer .btn {
      min-width: 110px;
    }

    @media (max-width: 576px) {
      .modal-footer {
        flex-direction: column !important;
        align-items: stretch !important;
      }

      .modal-footer .btn {
        width: 100%;
      }
    }

    /* Loading indicator styles */
    .loading-indicator {
      text-align: center;
      padding: 20px;
      color: #666;
      font-style: italic;
    }

    .no-results {
      text-align: center;
      padding: 20px;
      color: #999;
      font-style: italic;
    }
  </style>
</head>

<body>
  <div class="sidebar">
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
        <a href="../logout.php"><i class="bi bi-box-arrow-left"></i> Logout</a>
      </div>
    </div>
  </div>

  <div class="main">
    <form class="search-bar" method="post">
      <div class="topbar">
        <div class="input-with-icon">
          <input type="text" placeholder="Cari onsite..." name="search" id="search-input" autocomplete="off">
          <i class="bi bi-search"></i>
        </div>
        <div class="profile">
          <span><?= htmlspecialchars($username) ?></span>
          <i class="fas fa-user-circle fa-2x" style="color:#1c1c1c; font-size:35px;"></i>
        </div>
      </div>
    </form>

    <div class="header-section">
      <h2 style="font-weight: bold;">Data <span style="color: #48cfcb;">Onsite</span></h2>
      <button type="button" class="btn-tambah" data-bs-toggle="modal" data-bs-target="#modalTambahOnsite">+ Tambah Data Onsite</button>
    </div>

    <div class="table-responsive" id="data-container">
      <!-- Initial data load -->
      <table class="table table-bordered align-middle table-rounded rounded-4 overflow-hidden shadow">
        <thead class="table-dark">
          <tr style="text-align: center;">
            <th style="width: 120px;">Anggota</th>
            <th style="width: 120px;">Tanggal</th>
            <th>Lokasi</th>
            <th style="width: 150px;">Detail Kegiatan</th>
            <th style="width: 120px;">Waktu</th>
            <th><i class="bi bi-folder2 fs-5"></i></th>
            <th><i class="bi bi-filetype-csv fs-5"></i></th>
            <th>Biaya</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = mysqli_fetch_assoc($result)) : ?>
            <tr>
              <td style="text-align: left;">
                <ul style="padding-left: 15px;">
                  <?php
                  $id_onsite = $row['id'];
                  $anggota_query = "
                                        SELECT a.nama 
                                        FROM tim_onsite t
                                        JOIN anggota a ON t.id_anggota = a.id
                                        WHERE t.id_onsite = $id_onsite
                                    ";
                  $anggota_result = mysqli_query($conn, $anggota_query);
                  while ($anggota = mysqli_fetch_assoc($anggota_result)) {
                    echo "<li>" . htmlspecialchars($anggota['nama']) . "</li>";
                  }
                  ?>
                </ul>
              </td>
              <td><?= htmlspecialchars($row['tanggal']) ?></td>
              <td style="width: 180px; height: 180px;">
                <?php if ($row['latitude'] && $row['longitude']) : ?>
                  <iframe src="https://www.google.com/maps?q=<?= $row['latitude'] ?>,<?= $row['longitude'] ?>&hl=id&z=15&output=embed"
                    width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                <?php else : ?>
                  <em>Lokasi tidak tersedia</em>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($row['keterangan_kegiatan']) ?></td>
              <td><?= date('H:i', strtotime($row['jam_mulai'])) ?>-<?= date('H:i', strtotime($row['jam_selesai'])) ?></td>
              <td>
                <?php if (!empty($row['dokumentasi'])) : ?>
                  <a href="../uploads/<?= urlencode($row['dokumentasi']) ?>" target="_blank">Lihat</a>
                <?php else : ?>
                  Tidak ada
                <?php endif; ?>
              </td>
              <td>
                <?php if (!empty($row['file_csv'])): ?>
                  <a href="../download.php?file=<?= urlencode($row['file_csv']) ?>">CSV</a>
                <?php else : ?>
                  Tidak ada
                <?php endif; ?>
              </td>
              <td style="color: #006400; font-weight:bold;">Rp. <?= number_format($row['estimasi_biaya'], 0, ',', '.') ?></td>
              <td>
                <?php
                $status = $row['status_pembayaran'];
                $badge = 'warning';
                if ($status === 'Disetujui') $badge = 'success';
                elseif ($status === 'Ditolak') $badge = 'danger';
                ?>
                <span class="badge bg-<?= $badge ?>"><?= htmlspecialchars($status) ?></span>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>

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

  <?php if (isset($_SESSION['login_success'])): ?>
    <script>
      Swal.fire({
        title: 'Login Berhasil',
        html: '<b>Selamat datang kembali,</b><br><span style="color:#48cfcb; font-weight:bold;">' + <?= json_encode($username) ?> + '</span>',
        icon: 'success',
        background: '#1c1c1c',
        color: '#ffffff',
        iconColor: '#48cfcb',
        confirmButtonColor: '#48cfcb',
        timer: 3000,
        timerProgressBar: true,
        showConfirmButton: false
      });
    </script>
    <?php unset($_SESSION['login_success']); ?>
  <?php endif; ?>

  <?php if (isset($_SESSION['tambah_berhasil'])): ?>
    <script>
      Swal.fire({
        title: 'Berhasil!',
        text: 'Data onsite berhasil ditambahkan.',
        icon: 'success',
        background: '#1c1c1c',
        color: '#fff',
        iconColor: '#48cfcb',
        confirmButtonColor: '#48cfcb',
        timer: 2500,
        showConfirmButton: false
      });
    </script>
    <?php unset($_SESSION['tambah_berhasil']); ?>
  <?php endif; ?>

  <!-- Modal Tambah Data Onsite -->
  <div class="modal fade" id="modalTambahOnsite" tabindex="-1" aria-labelledby="modalTambahOnsiteLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
      <div class="modal-content">
        <form action="proses-tambah.php" method="POST" enctype="multipart/form-data">
          <input type="hidden" name="user_id" value="<?= $_SESSION['user_id'] ?>">
          <div class="modal-header bg-dark text-white">
            <h5 class="modal-title" id="modalTambahOnsiteLabel">Form Tambah Data Onsite</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body">
            <div class="container-fluid">
              <div class="col-12">
                <label class="form-label fw-semibold">Pilih Anggota Tim</label>
                <input type="text" id="anggota-input" class="form-control" placeholder="Ketik untuk cari anggota...">
                <div id="anggota-list" class="mt-2 border rounded p-2" style="max-height: 150px; overflow-y: auto;"></div>
                <div id="anggota-terpilih" class="mt-3"></div>
              </div>

              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label fw-semibold">Tanggal</label>
                  <input type="date" name="tanggal" id="tanggal" class="form-control" required>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-semibold">Estimasi Biaya</label>
                  <input type="number" name="estimasi_biaya" class="form-control" placeholder="Rp" required>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-semibold">Jam Mulai</label>
                  <input type="time" name="jam_mulai" class="form-control" required>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-semibold">Jam Selesai</label>
                  <input type="time" name="jam_selesai" class="form-control" required>
                </div>

                <div class="col-12">
                  <label class="form-label fw-semibold">Preview Lokasi</label>
                  <small class="form-text text-muted d-block mb-2" id="lokasi-status">Mendeteksi lokasi...</small>
                  <iframe id="mapPreview" class="w-100" style="height: 210px; border: 1px solid #ccc; border-radius: 8px;"></iframe>
                </div>

                <div class="col-12">
                  <label class="form-label fw-semibold">Keterangan Kegiatan</label>
                  <textarea name="keterangan_kegiatan" class="form-control" rows="3" required></textarea>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-semibold">Upload Dokumentasi (PDF/JPG/PNG)</label>
                  <input type="file" name="dokumentasi" accept=".pdf,.jpg,.jpeg,.png" class="form-control">
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-semibold">Upload File CSV (.csv)</label>
                  <input type="file" name="file_csv" accept=".csv" class="form-control">
                </div>

                <input type="hidden" name="latitude" id="latitude">
                <input type="hidden" name="longitude" id="longitude">
              </div>
            </div>
          </div>

          <div class="modal-footer d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
            <div class="d-flex gap-2">
              <button type="submit" name="simpan" class="btn btn-info text-white px-4">
                <i class="bi bi-save me-1"></i> Simpan
              </button>
              <button type="button" class="btn btn-danger px-4" data-bs-dismiss="modal">
                <i class="bi bi-x-circle me-1"></i> Batal
              </button>
            </div>
            <div class="ms-auto">
              <a href="../template/template_onsite.csv" class="btn btn-success px-4">
                <i class="bi bi-download me-1"></i> Download Template CSV
              </a>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- JavaScript -->
  <script>
    document.addEventListener("DOMContentLoaded", function() {
      const searchInput = document.getElementById("search-input");
      const dataContainer = document.getElementById("data-container");
      let searchTimeout;

      // Function untuk fetch data via AJAX
      function fetchData(page = 1, keyword = "") {
        // Clear timeout sebelumnya untuk menghindari request berlebihan
        if (searchTimeout) {
          clearTimeout(searchTimeout);
        }

        // Show loading indicator
        dataContainer.innerHTML = '<div class="loading-indicator"><i class="bi bi-hourglass-split"></i> Memuat data...</div>';

        // Set timeout untuk debouncing
        searchTimeout = setTimeout(() => {
          const formData = new FormData();
          formData.append('action', 'search_data');
          formData.append('search', keyword);
          formData.append('page', page);

          fetch(window.location.href, {
              method: 'POST',
              body: formData
            })
            .then(response => response.text())
            .then(data => {
              dataContainer.innerHTML = data;

              // Attach event listeners ke pagination links yang baru
              attachPaginationListeners();
            })
            .catch(error => {
              console.error('Error:', error);
              dataContainer.innerHTML = '<div class="no-results"><i class="bi bi-exclamation-triangle"></i> Terjadi kesalahan saat memuat data.</div>';
            });
        }, 300); // Delay 300ms untuk debouncing
      }

      // Function untuk attach event listeners ke pagination links
      function attachPaginationListeners() {
        const paginationLinks = document.querySelectorAll('.pagination-link');
        paginationLinks.forEach(link => {
          link.addEventListener('click', function(e) {
            e.preventDefault();
            const page = this.getAttribute('data-page');
            const keyword = searchInput.value.trim();
            fetchData(page, keyword);
          });
        });
      }

      // Event listener untuk search input
      searchInput.addEventListener('input', function() {
        const keyword = this.value.trim();
        fetchData(1, keyword); // Selalu mulai dari halaman 1 saat search
      });

      // Event listener untuk form submit (mencegah reload halaman)
      const searchForm = document.querySelector('.search-bar');
      if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
          e.preventDefault();
          const keyword = searchInput.value.trim();
          fetchData(1, keyword);
        });
      }

      // Attach pagination listeners untuk halaman pertama
      attachPaginationListeners();

      // Kode untuk modal dan anggota selection
      const anggotaArray = <?= json_encode($anggota_array) ?>;
      const anggotaInput = document.getElementById('anggota-input');
      const anggotaList = document.getElementById('anggota-list');
      const anggotaTerpilih = document.getElementById('anggota-terpilih');
      let selectedAnggota = [];

      // Function untuk menampilkan daftar anggota
      function tampilkanAnggota(filter = '') {
        anggotaList.innerHTML = '';
        const filteredAnggota = anggotaArray.filter(anggota =>
          anggota.nama.toLowerCase().includes(filter.toLowerCase()) &&
          !selectedAnggota.some(selected => selected.id === anggota.id)
        );

        if (filteredAnggota.length === 0) {
          anggotaList.innerHTML = '<div class="text-muted p-2">Tidak ada anggota yang ditemukan</div>';
          return;
        }

        filteredAnggota.forEach(anggota => {
          const div = document.createElement('div');
          div.className = 'anggota-item p-2 border-bottom cursor-pointer';
          div.style.cursor = 'pointer';
          div.textContent = anggota.nama;

          div.addEventListener('click', function() {
            pilihAnggota(anggota);
          });

          div.addEventListener('mouseenter', function() {
            this.style.backgroundColor = '#f8f9fa';
          });

          div.addEventListener('mouseleave', function() {
            this.style.backgroundColor = '';
          });

          anggotaList.appendChild(div);
        });
      }

      // Function untuk memilih anggota
      function pilihAnggota(anggota) {
        selectedAnggota.push(anggota);
        anggotaInput.value = '';
        updateAnggotaTerpilih();
        tampilkanAnggota();
      }

      // Function untuk menghapus anggota terpilih
      function hapusAnggota(id) {
        selectedAnggota = selectedAnggota.filter(anggota => anggota.id !== id);
        updateAnggotaTerpilih();
        tampilkanAnggota(anggotaInput.value);
      }

      // Function untuk update tampilan anggota terpilih
      function updateAnggotaTerpilih() {
        if (selectedAnggota.length === 0) {
          anggotaTerpilih.innerHTML = '<div class="text-muted">Belum ada anggota yang dipilih</div>';
          return;
        }

        let html = '<div class="fw-semibold mb-2">Anggota Terpilih:</div>';
        selectedAnggota.forEach(anggota => {
          html += `
                    <div class="badge bg-info text-dark me-2 mb-2 d-inline-flex align-items-center">
                        ${anggota.nama}
                        <button type="button" class="btn-close btn-close-white ms-2" style="font-size: 0.8em;" onclick="hapusAnggota(${anggota.id})"></button>
                        <input type="hidden" name="anggota[]" value="${anggota.id}">
                    </div>
                `;
        });
        anggotaTerpilih.innerHTML = html;
      }

      // Event listeners untuk anggota input
      anggotaInput.addEventListener('input', function() {
        tampilkanAnggota(this.value);
      });

      anggotaInput.addEventListener('focus', function() {
        tampilkanAnggota(this.value);
      });

      // Hide anggota list when clicking outside
      document.addEventListener('click', function(e) {
        if (!anggotaInput.contains(e.target) && !anggotaList.contains(e.target)) {
          anggotaList.innerHTML = '';
        }
      });

      // Initialize anggota terpilih
      updateAnggotaTerpilih();

      // Geolocation untuk mendapatkan lokasi saat modal dibuka
      const modal = document.getElementById('modalTambahOnsite');
      const mapPreview = document.getElementById('mapPreview');
      const lokasiStatus = document.getElementById('lokasi-status');
      const latitudeInput = document.getElementById('latitude');
      const longitudeInput = document.getElementById('longitude');

      modal.addEventListener('shown.bs.modal', function() {
        if (navigator.geolocation) {
          lokasiStatus.textContent = 'Mendeteksi lokasi...';
          navigator.geolocation.getCurrentPosition(
            function(position) {
              const lat = position.coords.latitude;
              const lng = position.coords.longitude;

              latitudeInput.value = lat;
              longitudeInput.value = lng;

              // Update map preview
              const mapUrl = `https://www.google.com/maps?q=${lat},${lng}&hl=id&z=15&output=embed`;
              mapPreview.src = mapUrl;

              lokasiStatus.textContent = `Lokasi berhasil dideteksi (${lat.toFixed(6)}, ${lng.toFixed(6)})`;
              lokasiStatus.style.color = '#28a745';
            },
            function(error) {
              console.error('Error getting location:', error);
              lokasiStatus.textContent = 'Gagal mendeteksi lokasi. Silakan cek pengaturan GPS/lokasi browser Anda.';
              lokasiStatus.style.color = '#dc3545';
              mapPreview.src = '';
            }, {
              enableHighAccuracy: true,
              timeout: 10000,
              maximumAge: 60000
            }
          );
        } else {
          lokasiStatus.textContent = 'Browser tidak mendukung geolocation.';
          lokasiStatus.style.color = '#dc3545';
        }
      });

      // Set minimum date to today
      const tanggalInput = document.getElementById('tanggal');
      if (tanggalInput) {
        const today = new Date().toISOString().split('T')[0];
        tanggalInput.setAttribute('min', today);
      }

      // Form validation
      const modalForm = modal.querySelector('form');
      modalForm.addEventListener('submit', function(e) {
        if (selectedAnggota.length === 0) {
          e.preventDefault();
          Swal.fire({
            title: 'Peringatan!',
            text: 'Pilih minimal satu anggota tim.',
            icon: 'warning',
            confirmButtonColor: '#48cfcb'
          });
          return false;
        }

        const jamMulai = modalForm.querySelector('input[name="jam_mulai"]').value;
        const jamSelesai = modalForm.querySelector('input[name="jam_selesai"]').value;

        if (jamMulai && jamSelesai && jamMulai >= jamSelesai) {
          e.preventDefault();
          Swal.fire({
            title: 'Peringatan!',
            text: 'Jam selesai harus lebih besar dari jam mulai.',
            icon: 'warning',
            confirmButtonColor: '#48cfcb'
          });
          return false;
        }
      });

      // Reset form when modal is closed
      modal.addEventListener('hidden.bs.modal', function() {
        selectedAnggota = [];
        updateAnggotaTerpilih();
        anggotaList.innerHTML = '';
        lokasiStatus.textContent = 'Mendeteksi lokasi...';
        lokasiStatus.style.color = '';
        mapPreview.src = '';
        modalForm.reset();
      });
    });
  </script>
</body>

</html>