<?php
session_start();
require('../include/koneksi.php');

if (!isset($_SESSION['user']) || !isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$current_page = basename($_SERVER['PHP_SELF']);
$user_id = $_SESSION['user_id'];
$username = $_SESSION['user'];

// Handle AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    $search = $_POST['search'] ?? '';
    $page = $_POST['page'] ?? 1;
    $records_per_page = 5;
    $offset = ($page - 1) * $records_per_page;

    $search = mysqli_real_escape_string($conn, $search);

    // Hitung total data untuk pagination
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

    // Query ambil data
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

    // Output AJAX content
    ob_start();
?>

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
    echo ob_get_clean();
    exit();
}

// Untuk data anggota
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
            background-color: #f5f5f5;
            color: #333;
            font-family: 'Inter', sans-serif;
            transition: margin-left 0.3s ease;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 200px;
            background: #1c1c1c;
            color: #fff;
            padding: 30px 20px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            transform: translateX(0);
            transition: transform 0.3s ease;
        }

        .sidebar.hidden {
            transform: translateX(-100%);
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

        /* Main Content */
        .main {
            margin-left: 200px;
            padding: 20px;
            width: calc(100% - 200px);
            transition: margin-left 0.3s ease, width 0.3s ease;
        }

        .main.expanded {
            margin-left: 0;
            width: 100%;
        }

        /* Hamburger Menu */
        .hamburger {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
            background: #1c1c1c;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
        }

        .hamburger:hover {
            background: #48cfcb;
        }

        /* Topbar */
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
            max-width: 300px;
            width: 100%;
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
            gap: 10px;
        }

        .profile span {
            color: #1c1c1c;
            padding: 5px;
            font-weight: bold;
        }

        /* Header Section */
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 30px 0 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .header-section button {
            background-color: #48cfcb;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            white-space: nowrap;
        }

        .header-section button:hover {
            background-color: #229799;
        }

        /* Onsite Card Styles */
        .onsite-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .onsite-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 10px;
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

        .onsite-files a {
            margin-right: 10px;
            text-decoration: none;
            color: #0d6efd;
        }

        /* Pagination */
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

        /* Modal Styles */
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

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .hamburger {
                display: block;
            }

            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main {
                margin-left: 0;
                width: 100%;
                padding: 70px 15px 20px;
            }

            .topbar {
                flex-direction: column;
                align-items: stretch;
            }

            .input-with-icon {
                max-width: 100%;
            }

            .profile {
                justify-content: center;
            }

            .header-section {
                flex-direction: column;
                align-items: stretch;
            }

            .header-section h2 {
                text-align: center;
            }

            .onsite-details {
                flex-direction: column;
            }

            .onsite-info {
                min-width: auto;
            }

            .map-box {
                max-width: 100%;
                height: 200px;
            }

            .pagination {
                justify-content: center;
            }

            .modal-footer {
                flex-direction: column !important;
                align-items: stretch !important;
            }

            .modal-footer .btn {
                width: 100%;
            }

            .modal-footer .d-flex {
                flex-direction: column !important;
                gap: 10px;
            }
        }

        @media (max-width: 576px) {
            .main {
                padding: 70px 10px 15px;
            }

            .onsite-card {
                padding: 15px;
            }

            .onsite-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .profile span {
                font-size: 14px;
            }
        }

        /* Overlay for mobile sidebar */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        .sidebar-overlay.show {
            display: block;
        }
    </style>
</head>

<body>
    <!-- Hamburger Menu -->
    <button class="hamburger" id="hamburger">
        <i class="bi bi-list"></i>
    </button>

    <!-- Sidebar Overlay -->
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
                <a href="../logout.php"><i class="bi bi-box-arrow-left"></i> Logout</a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main" id="main-content">
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
            <!-- Content will be loaded here via AJAX -->
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

    <script>
        // Complete JavaScript for Dashboard User ACTIVin
        document.addEventListener("DOMContentLoaded", function() {
            const searchInput = document.getElementById("search-input");
            const dataContainer = document.getElementById("data-container");
            const hamburger = document.getElementById("hamburger");
            const sidebar = document.getElementById("sidebar");
            const sidebarOverlay = document.getElementById("sidebar-overlay");
            const mainContent = document.getElementById("main-content");
            const modal = document.getElementById("modalTambahOnsite");

            // Initial load
            fetchData(1, "");

            // Hamburger menu functionality
            hamburger.addEventListener("click", function() {
                sidebar.classList.toggle("show");
                sidebarOverlay.classList.toggle("show");
                document.body.style.overflow = sidebar.classList.contains("show") ? "hidden" : "auto";
            });

            // Close sidebar when clicking overlay
            sidebarOverlay.addEventListener("click", function() {
                closeSidebar();
            });

            // Close sidebar when clicking on nav links (mobile)
            sidebar.addEventListener("click", function(e) {
                if (e.target.tagName === 'A' && window.innerWidth <= 768) {
                    closeSidebar();
                }
            });

            // Handle window resize
            window.addEventListener("resize", function() {
                if (window.innerWidth > 768) {
                    closeSidebar();
                    document.body.style.overflow = "auto";
                }
            });

            // Function to close sidebar
            function closeSidebar() {
                sidebar.classList.remove("show");
                sidebarOverlay.classList.remove("show");
                document.body.style.overflow = "auto";
            }

            // AJAX fetch data function
            function fetchData(page = 1, keyword = "") {
                const xhr = new XMLHttpRequest();
                xhr.open("POST", "", true);
                xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

                // Show loading indicator
                dataContainer.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-info" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Memuat data...</p>
            </div>`;

                xhr.onload = function() {
                    if (xhr.status === 200) {
                        dataContainer.innerHTML = xhr.responseText;

                        // Show no data message if empty
                        if (xhr.responseText.trim() === '' || xhr.responseText.includes('No data found')) {
                            dataContainer.innerHTML = `
                        <div class="text-center py-5">
                            <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                            <p class="mt-3 text-muted">Tidak ada data onsite yang ditemukan</p>
                        </div>`;
                        }
                    } else {
                        dataContainer.innerHTML = `
                    <div class="alert alert-danger text-center">
                        <i class="bi bi-exclamation-triangle"></i>
                        Terjadi kesalahan saat memuat data. Silakan coba lagi.
                    </div>`;
                    }
                };

                xhr.onerror = function() {
                    dataContainer.innerHTML = `
                <div class="alert alert-danger text-center">
                    <i class="bi bi-wifi-off"></i>
                    Koneksi bermasalah. Silakan periksa koneksi internet Anda.
                </div>`;
                };

                xhr.send("ajax=1&page=" + page + "&search=" + encodeURIComponent(keyword));
            }

            // Handle pagination click
            dataContainer.addEventListener("click", function(e) {
                if (e.target.classList.contains("pagination-link")) {
                    e.preventDefault();
                    const page = e.target.getAttribute("data-page");
                    const keyword = searchInput.value;
                    fetchData(page, keyword);

                    // Scroll to top of data container
                    dataContainer.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });

            // Handle search input with debounce
            let searchTimeout;
            searchInput.addEventListener("input", function() {
                clearTimeout(searchTimeout);
                const keyword = this.value;

                searchTimeout = setTimeout(() => {
                    fetchData(1, keyword);
                }, 300); // 300ms delay for better UX
            });

            // Clear search on ESC key
            searchInput.addEventListener("keydown", function(e) {
                if (e.key === "Escape") {
                    this.value = "";
                    fetchData(1, "");
                }
            });

            // Modal functionality
            if (modal) {
                // Set minimum date to today
                const tanggalInput = document.getElementById("tanggal");
                if (tanggalInput) {
                    const today = new Date().toISOString().split('T')[0];
                    tanggalInput.setAttribute("min", today);
                }

                // Get location when modal opens
                modal.addEventListener('shown.bs.modal', function() {
                    getLocation();
                    initializeAnggotaSelection();
                });

                // Reset form when modal closes
                modal.addEventListener('hidden.bs.modal', function() {
                    const form = modal.querySelector('form');
                    if (form) {
                        form.reset();
                        document.getElementById("anggota-terpilih").innerHTML = "";
                        document.getElementById("anggota-list").innerHTML = "";
                        document.getElementById("mapPreview").src = "";
                        document.getElementById("lokasi-status").innerText = "Mendeteksi lokasi...";
                    }
                });
            }

            // Geolocation functions
            function getLocation() {
                const lokasiStatus = document.getElementById("lokasi-status");

                if (navigator.geolocation) {
                    lokasiStatus.innerText = "Mendeteksi lokasi...";
                    navigator.geolocation.getCurrentPosition(showPosition, showError, {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 300000
                    });
                } else {
                    lokasiStatus.innerText = "Geolocation tidak didukung oleh browser Anda.";
                    lokasiStatus.style.color = "#dc3545";
                }
            }

            function showPosition(pos) {
                const lat = pos.coords.latitude;
                const lon = pos.coords.longitude;
                const lokasiStatus = document.getElementById("lokasi-status");

                document.getElementById("latitude").value = lat;
                document.getElementById("longitude").value = lon;

                const mapUrl = `https://www.google.com/maps?q=${lat},${lon}&hl=id&z=15&output=embed`;
                document.getElementById("mapPreview").src = mapUrl;

                lokasiStatus.innerText = `Lokasi terdeteksi: ${lat.toFixed(6)}, ${lon.toFixed(6)}`;
                lokasiStatus.style.color = "#198754";
            }

            function showError(error) {
                const lokasiStatus = document.getElementById("lokasi-status");
                lokasiStatus.style.color = "#dc3545";

                switch (error.code) {
                    case error.PERMISSION_DENIED:
                        lokasiStatus.innerText = "Akses lokasi ditolak. Mohon izinkan akses lokasi.";
                        break;
                    case error.POSITION_UNAVAILABLE:
                        lokasiStatus.innerText = "Informasi lokasi tidak tersedia.";
                        break;
                    case error.TIMEOUT:
                        lokasiStatus.innerText = "Timeout saat mendeteksi lokasi.";
                        break;
                    default:
                        lokasiStatus.innerText = "Terjadi kesalahan saat mendeteksi lokasi.";
                        break;
                }
            }

            // Anggota selection functionality
            function initializeAnggotaSelection() {
                const anggotaInput = document.getElementById("anggota-input");
                const anggotaList = document.getElementById("anggota-list");
                const anggotaTerpilih = document.getElementById("anggota-terpilih");

                // Get anggota data from PHP (assuming it's available globally)
                const anggotaData = <?php echo json_encode($anggota_array); ?>;
                let selectedAnggota = [];

                anggotaInput.addEventListener("input", function() {
                    const keyword = this.value.toLowerCase();
                    const filtered = anggotaData.filter(anggota =>
                        anggota.nama.toLowerCase().includes(keyword) &&
                        !selectedAnggota.some(selected => selected.id === anggota.id)
                    );

                    displayAnggotaList(filtered);
                });

                function displayAnggotaList(anggotaArray) {
                    anggotaList.innerHTML = "";

                    if (anggotaArray.length === 0) {
                        anggotaList.innerHTML = '<small class="text-muted">Tidak ada anggota ditemukan</small>';
                        return;
                    }

                    anggotaArray.forEach(anggota => {
                        const div = document.createElement("div");
                        div.className = "p-2 border-bottom cursor-pointer hover-bg-light";
                        div.style.cursor = "pointer";
                        div.innerText = anggota.nama;

                        div.addEventListener("click", function() {
                            addAnggota(anggota);
                            anggotaInput.value = "";
                            anggotaList.innerHTML = "";
                        });

                        div.addEventListener("mouseenter", function() {
                            this.style.backgroundColor = "#f8f9fa";
                        });

                        div.addEventListener("mouseleave", function() {
                            this.style.backgroundColor = "";
                        });

                        anggotaList.appendChild(div);
                    });
                }

                function addAnggota(anggota) {
                    if (!selectedAnggota.some(selected => selected.id === anggota.id)) {
                        selectedAnggota.push(anggota);
                        updateAnggotaTerpilih();
                    }
                }

                function removeAnggota(anggotaId) {
                    selectedAnggota = selectedAnggota.filter(anggota => anggota.id !== anggotaId);
                    updateAnggotaTerpilih();
                }

                function updateAnggotaTerpilih() {
                    anggotaTerpilih.innerHTML = "";

                    if (selectedAnggota.length === 0) {
                        anggotaTerpilih.innerHTML = '<small class="text-muted">Belum ada anggota yang dipilih</small>';
                        return;
                    }

                    selectedAnggota.forEach(anggota => {
                        const badge = document.createElement("span");
                        badge.className = "badge bg-info text-white me-2 mb-2 d-inline-flex align-items-center";
                        badge.innerHTML = `
                    ${anggota.nama}
                    <button type="button" class="btn-close btn-close-white ms-2" style="font-size: 0.7em;" onclick="removeAnggota(${anggota.id})"></button>
                    <input type="hidden" name="anggota[]" value="${anggota.id}">
                `;

                        const closeBtn = badge.querySelector('.btn-close');
                        closeBtn.addEventListener('click', function(e) {
                            e.preventDefault();
                            removeAnggota(anggota.id);
                        });

                        anggotaTerpilih.appendChild(badge);
                    });
                }

                // Make removeAnggota globally accessible for onclick handler
                window.removeAnggota = removeAnggota;
            }

            // Form validation
            const form = document.querySelector('#modalTambahOnsite form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const requiredFields = form.querySelectorAll('[required]');
                    let isValid = true;

                    requiredFields.forEach(field => {
                        if (!field.value.trim()) {
                            isValid = false;
                            field.classList.add('is-invalid');
                        } else {
                            field.classList.remove('is-invalid');
                        }
                    });

                    // Check if at least one anggota is selected
                    const selectedAnggota = document.querySelectorAll('input[name="anggota[]"]');
                    if (selectedAnggota.length === 0) {
                        isValid = false;
                        Swal.fire({
                            title: 'Peringatan!',
                            text: 'Pilih minimal satu anggota tim.',
                            icon: 'warning',
                            confirmButtonColor: '#48cfcb'
                        });
                    }

                    // Check if location is detected
                    const latitude = document.getElementById('latitude').value;
                    const longitude = document.getElementById('longitude').value;
                    if (!latitude || !longitude) {
                        isValid = false;
                        Swal.fire({
                            title: 'Peringatan!',
                            text: 'Lokasi belum terdeteksi. Pastikan Anda mengizinkan akses lokasi.',
                            icon: 'warning',
                            confirmButtonColor: '#48cfcb'
                        });
                    }

                    if (!isValid) {
                        e.preventDefault();
                    }
                });
            }

            // File upload validation
            const dokumentasiInput = document.querySelector('input[name="dokumentasi"]');
            const csvInput = document.querySelector('input[name="file_csv"]');

            if (dokumentasiInput) {
                dokumentasiInput.addEventListener('change', function() {
                    const file = this.files[0];
                    if (file) {
                        const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
                        const maxSize = 5 * 1024 * 1024; // 5MB

                        if (!allowedTypes.includes(file.type)) {
                            Swal.fire({
                                title: 'File Tidak Valid!',
                                text: 'Hanya file PDF, JPG, JPEG, dan PNG yang diizinkan.',
                                icon: 'error',
                                confirmButtonColor: '#48cfcb'
                            });
                            this.value = '';
                            return;
                        }

                        if (file.size > maxSize) {
                            Swal.fire({
                                title: 'File Terlalu Besar!',
                                text: 'Ukuran file maksimal 5MB.',
                                icon: 'error',
                                confirmButtonColor: '#48cfcb'
                            });
                            this.value = '';
                            return;
                        }
                    }
                });
            }

            if (csvInput) {
                csvInput.addEventListener('change', function() {
                    const file = this.files[0];
                    if (file) {
                        const maxSize = 2 * 1024 * 1024; // 2MB

                        if (file.type !== 'text/csv' && !file.name.endsWith('.csv')) {
                            Swal.fire({
                                title: 'File Tidak Valid!',
                                text: 'Hanya file CSV yang diizinkan.',
                                icon: 'error',
                                confirmButtonColor: '#48cfcb'
                            });
                            this.value = '';
                            return;
                        }

                        if (file.size > maxSize) {
                            Swal.fire({
                                title: 'File Terlalu Besar!',
                                text: 'Ukuran file CSV maksimal 2MB.',
                                icon: 'error',
                                confirmButtonColor: '#48cfcb'
                            });
                            this.value = '';
                            return;
                        }
                    }
                });
            }

            // Smooth scrolling for internal links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });

            // Auto-refresh data every 30 seconds (optional)
            setInterval(function() {
                if (!document.hidden) { // Only refresh when tab is active
                    const currentKeyword = searchInput.value;
                    const currentPage = document.querySelector('.pagination-link.active')?.getAttribute('data-page') || 1;
                    fetchData(currentPage, currentKeyword);
                }
            }, 30000);

            // Handle connection status
            window.addEventListener('online', function() {
                Swal.fire({
                    title: 'Koneksi Tersambung',
                    text: 'Koneksi internet tersambung kembali.',
                    icon: 'success',
                    toast: true,
                    position: 'top-end',
                    timer: 3000,
                    showConfirmButton: false
                });
            });

            window.addEventListener('offline', function() {
                Swal.fire({
                    title: 'Koneksi Terputus',
                    text: 'Periksa koneksi internet Anda.',
                    icon: 'warning',
                    toast: true,
                    position: 'top-end',
                    timer: 3000,
                    showConfirmButton: false
                });
            });
        });

        // Global functions for backward compatibility
        function getLocation() {
            // This function is now handled in the DOMContentLoaded event
            console.log('getLocation called - handled by modal event');
        }

        // Utility functions
        function formatCurrency(amount) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(amount);
        }

        function formatDate(dateString) {
            return new Date(dateString).toLocaleDateString('id-ID', {
                day: '2-digit',
                month: 'short',
                year: 'numeric'
            });
        }

        function formatTime(timeString) {
            return new Date('1970-01-01T' + timeString).toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
            });
        }
    </script>
</body>
</html>