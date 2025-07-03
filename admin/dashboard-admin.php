<?php
session_start();
require('../include/koneksi.php');

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$current_page = basename($_SERVER['PHP_SELF']);
$username = $_SESSION['user'];

// Handle AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    $search = mysqli_real_escape_string($conn, $_POST['search'] ?? '');
    $page = max((int)($_POST['page'] ?? 1), 1);
    $records_per_page = 5;
    $offset = ($page - 1) * $records_per_page;

    // Base query untuk menghitung total data
    $base_query = "
        FROM tambah_onsite o
        LEFT JOIN tim_onsite t ON o.id = t.id_onsite
        LEFT JOIN anggota a ON t.id_anggota = a.id
    ";

    $where = "";
    if (!empty($search)) {
        $where = "WHERE o.tanggal LIKE '%$search%' 
               OR o.keterangan_kegiatan LIKE '%$search%' 
               OR o.status_pembayaran LIKE '%$search%' 
               OR a.nama LIKE '%$search%'";
    }

    // Menghitung total data untuk pagination
    $count_query = "SELECT COUNT(DISTINCT o.id) as total $base_query $where";
    $count_result = mysqli_query($conn, $count_query);
    $total_rows = mysqli_fetch_assoc($count_result)['total'];
    $total_pages = ceil($total_rows / $records_per_page);

    // Query ambil data sesuai halaman
    $data_query = "
        SELECT DISTINCT o.* 
        $base_query 
        $where 
        ORDER BY o.id DESC 
        LIMIT $offset, $records_per_page
    ";
    $result = mysqli_query($conn, $data_query);
?>

    <!-- Card Content -->
    <div id="customAlert" class="modal-overlay" style="display: none;">
        <div class="modal-box">
            <h5>Konfirmasi</h5>
            <p>Apakah Anda yakin ingin mengubah status data ini?</p>
            <div class="text-end mt-3">
                <button id="cancelBtn" class="btn btn-secondary me-2">Batal</button>
                <button id="confirmBtn" class="btn btn-primary">Ya, Ubah</button>
            </div>
        </div>
    </div>

    <?php while ($row = mysqli_fetch_assoc($result)) : ?>
        <div class="onsite-card">
            <div class="onsite-header">
                <div>
                    <strong><?= htmlspecialchars($row['keterangan_kegiatan']) ?></strong><br>
                    <small><?= date('d M Y', strtotime($row['tanggal'])) ?> | <?= date('H:i', strtotime($row['jam_mulai'])) ?> - <?= date('H:i', strtotime($row['jam_selesai'])) ?></small>
                </div>
                <div class="status-section">
                    <form method="POST" action="ubah-status.php" style="display:inline-block;">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <select name="status_pembayaran" class="form-select status-dropdown" data-id="<?= $row['id'] ?>">
                            <option value="Menunggu" <?= $row['status_pembayaran'] == 'Menunggu' ? 'selected' : '' ?>>Menunggu</option>
                            <option value="Disetujui" <?= $row['status_pembayaran'] == 'Disetujui' ? 'selected' : '' ?>>Disetujui</option>
                            <option value="Ditolak" <?= $row['status_pembayaran'] == 'Ditolak' ? 'selected' : '' ?>>Ditolak</option>
                        </select>
                    </form>
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
                    <div class="mt-2"><strong>Biaya:</strong> <span style="color: #006400; font-weight:bold;">Rp. <?= number_format($row['estimasi_biaya'], 0, ',', '.') ?></span></div>
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

    <script>
        // Update status & mengubah warna dropdown sesuai status
        function setupStatusDropdowns() {
            document.querySelectorAll('.status-dropdown').forEach(select => {
                updateStatusColor(select);

                const cloned = select.cloneNode(true);
                select.parentNode.replaceChild(cloned, select);

                cloned.addEventListener('change', (event) => {
                    event.preventDefault();
                    selectedFormToSubmit = cloned.closest('form');
                    document.getElementById('customAlert').style.display = 'flex';
                });
            });
        }

        // Memberikan warna latar pada <select> sesuai status yang ditentukan
        function updateStatusColor(select) {
            const value = select.value;
            select.classList.remove('bg-success', 'bg-danger', 'bg-warning', 'text-white');
            if (value === 'Disetujui') select.classList.add('bg-success', 'text-white');
            else if (value === 'Ditolak') select.classList.add('bg-danger', 'text-white');
            else if (value === 'Menunggu') select.classList.add('bg-warning');
        }

        // Tombol batal mengubah status
        document.getElementById('cancelBtn').onclick = () => {
            document.getElementById('customAlert').style.display = 'none';
            location.reload();
        };

        // Tombol konfirmasi mengubah status
        document.getElementById('confirmBtn').onclick = () => {
            if (selectedFormToSubmit) {
                selectedFormToSubmit.submit();
            }
        };

        setupStatusDropdowns();
    </script>

<?php
    exit();
}

// Initial load - get data for first page
$search = '';
$page = 1;
$records_per_page = 5;
$offset = 0;

$base_query = "
    FROM tambah_onsite o
    LEFT JOIN tim_onsite t ON o.id = t.id_onsite
    LEFT JOIN anggota a ON t.id_anggota = a.id
";

$count_query = "SELECT COUNT(DISTINCT o.id) as total $base_query";
$count_result = mysqli_query($conn, $count_query);
$total_rows = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_rows / $records_per_page);

$data_query = "
    SELECT DISTINCT o.* 
    $base_query 
    ORDER BY o.id DESC 
    LIMIT $offset, $records_per_page
";
$result = mysqli_query($conn, $data_query);
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
            flex-wrap: wrap;
            gap: 15px;
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
            gap: 10px;
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

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .modal-box {
            background: #fff;
            padding: 20px 25px;
            border-radius: 12px;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        /* Card Styles - sama seperti history */
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
            align-items: center;
            flex-wrap: wrap;
        }

        .status-section {
            min-width: 150px;
        }

        /* CSS untuk dropdown status - PERBAIKAN */
        .status-section {
            min-width: 120px;
            /* Kurangi dari 150px */
            max-width: 140px;
            /* Batasi maksimal width */
        }

        .status-dropdown {
            border-radius: 15px;
            /* Kurangi dari 20px */
            padding: 4px 8px;
            /* Kurangi padding */
            font-weight: 500;
            border: 1px solid #ddd;
            font-size: 0.85rem;
            /* Kurangi ukuran font */
            width: 100%;
            max-width: 120px;
            height: auto;
            /* Pastikan height otomatis */
            line-height: 1.2;
            /* Kurangi line-height */

            /* Pastikan dropdown tidak menampilkan multiple options */
            appearance: auto;
            -webkit-appearance: menulist;
            -moz-appearance: menulist;
        }

        /* Styling untuk option dalam dropdown */
        .status-dropdown option {
            padding: 2px 4px;
            font-size: 0.85rem;
            line-height: 1.2;
        }

        /* Warna background sesuai status */
        .status-dropdown.bg-success {
            background-color: #198754 !important;
            color: white !important;
            border-color: #198754;
        }

        .status-dropdown.bg-danger {
            background-color: #dc3545 !important;
            color: white !important;
            border-color: #dc3545;
        }

        .status-dropdown.bg-warning {
            background-color: #ffc107 !important;
            color: #000 !important;
            border-color: #ffc107;
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

        .onsite-files a {
            margin-right: 10px;
            text-decoration: none;
            color: #0d6efd;
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

        .status-section {
            min-width: 100px;
            max-width: 110px;
        }

        .status-dropdown {
            font-size: 0.8rem;
            padding: 3px 6px;
            max-width: 100px;
            border-radius: 12px;
        }

        .onsite-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }

        .onsite-header>div:first-child {
            width: 100%;
        }

        .status-section {
            width: 100%;
            max-width: 120px;
        }

        /* Perbaikan CSS untuk mobile sidebar */
        @media (max-width: 768px) {
            body {
                flex-direction: column;
            }

            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                /* Tambahkan transition untuk smooth animation */
                z-index: 1001;
                /* Pastikan sidebar di atas overlay */
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

        @media (max-width: 480px) {
            .status-dropdown {
                font-size: 0.75rem;
                padding: 2px 4px;
                max-width: 90px;
            }
        }
    </style>
</head>

<body>
    <!-- Overlay untuk mobile -->
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <!-- Popup saat mengubah status -->
    <div id="customAlert" class="modal-overlay" style="display: none;">
        <div class="modal-box">
            <h5>Konfirmasi</h5>
            <p>Apakah Anda yakin ingin mengubah status data ini?</p>
            <div class="text-end mt-3">
                <button id="cancelBtn" class="btn btn-secondary me-2">Batal</button>
                <button id="confirmBtn" class="btn btn-primary">Ya, Ubah</button>
            </div>
        </div>
    </div>

    <div class="sidebar">
        <img src="../asset/logo-E.png" alt="Logo" class="card-logo">
        <div class="nav-container">
            <div class="nav-links">
                <a href="dashboard-admin.php" class="<?= $current_page == 'dashboard-admin.php' ? 'active' : '' ?>">
                    <i class="bi bi-columns-gap"></i> Dashboard
                </a>
            </div>
            <div class="logout-link">
                <a href="../logout.php"><i class="bi bi-box-arrow-left"></i> Logout</a>
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
            <h2 style="font-weight: bold;">Data <span style="color: #48cfcb;">Onsite</span> Karyawan</h2>
        </div>

        <div id="admin-data-container">
            <!-- Initial card load -->
            <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                <div class="onsite-card">
                    <div class="onsite-header">
                        <div>
                            <strong><?= htmlspecialchars($row['keterangan_kegiatan']) ?></strong><br>
                            <small><?= date('d M Y', strtotime($row['tanggal'])) ?> | <?= date('H:i', strtotime($row['jam_mulai'])) ?> - <?= date('H:i', strtotime($row['jam_selesai'])) ?></small>
                        </div>
                        <div class="status-section">
                            <form method="POST" action="ubah-status.php" style="display:inline-block;">
                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                <select name="status_pembayaran" class="form-select status-dropdown" data-id="<?= $row['id'] ?>">
                                    <option value="Menunggu" <?= $row['status_pembayaran'] == 'Menunggu' ? 'selected' : '' ?>>Menunggu</option>
                                    <option value="Disetujui" <?= $row['status_pembayaran'] == 'Disetujui' ? 'selected' : '' ?>>Disetujui</option>
                                    <option value="Ditolak" <?= $row['status_pembayaran'] == 'Ditolak' ? 'selected' : '' ?>>Ditolak</option>
                                </select>
                            </form>
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
                            <div class="mt-2"><strong>Biaya:</strong> <span style="color: #006400; font-weight:bold;">Rp. <?= number_format($row['estimasi_biaya'], 0, ',', '.') ?></span></div>
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
        // Mobile menu functionality - PERBAIKAN
        const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
        const sidebar = document.querySelector('.sidebar'); // Menggunakan querySelector karena tidak ada ID
        const sidebarOverlay = document.getElementById('sidebar-overlay');

        // Event listener untuk toggle mobile menu
        if (mobileMenuToggle) {
            mobileMenuToggle.addEventListener('click', function() {
                if (sidebar) {
                    sidebar.classList.toggle('active'); // Menggunakan toggle untuk membuka/tutup
                }
                if (sidebarOverlay) {
                    sidebarOverlay.classList.toggle('active');
                }
            });
        }

        // Event listener untuk overlay - tutup sidebar ketika overlay diklik
        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', function() {
                if (sidebar) {
                    sidebar.classList.remove('active');
                }
                sidebarOverlay.classList.remove('active');
            });
        }

        // HAPUS BAGIAN INI - duplikasi dan menyebabkan error
        // document.getElementById('mobile-menu-toggle')?.addEventListener('click', function() {
        //     document.querySelector('.sidebar')?.classList.toggle('show'); // 'show' class tidak ada di CSS
        // });

        // Dropdown perubahan status
        let selectedFormToSubmit = null;

        // Update status & mengubah warna dropdown sesuai status
        // Perbaikan JavaScript untuk dropdown status
        function updateStatusColor(select) {
            const value = select.value;

            // Hapus semua class styling sebelumnya
            select.classList.remove('bg-success', 'bg-danger', 'bg-warning', 'text-white');

            // Tambahkan class sesuai status
            if (value === 'Disetujui') {
                select.classList.add('bg-success', 'text-white');
            } else if (value === 'Ditolak') {
                select.classList.add('bg-danger', 'text-white');
            } else if (value === 'Menunggu') {
                select.classList.add('bg-warning');
            }

            // Pastikan dropdown tetap dalam ukuran normal
            select.style.height = 'auto';
            select.style.minHeight = '32px';
        }

        function setupStatusDropdowns() {
            document.querySelectorAll('.status-dropdown').forEach(select => {
                // Update warna berdasarkan status awal
                updateStatusColor(select);

                // Clone element untuk menghapus event listener lama
                const cloned = select.cloneNode(true);
                select.parentNode.replaceChild(cloned, select);

                // Update warna lagi setelah clone
                updateStatusColor(cloned);

                // Tambahkan event listener untuk perubahan
                cloned.addEventListener('change', (event) => {
                    event.preventDefault();
                    selectedFormToSubmit = cloned.closest('form');
                    document.getElementById('customAlert').style.display = 'flex';
                });

                // Event listener untuk mengupdate warna saat dropdown berubah
                cloned.addEventListener('input', (event) => {
                    updateStatusColor(event.target);
                });
            });
        }

        // Memberikan warna latar pada <select> sesuai status yang ditentukan
        function updateStatusColor(select) {
            const value = select.value;
            select.classList.remove('bg-success', 'bg-danger', 'bg-warning', 'text-white');
            if (value === 'Disetujui') select.classList.add('bg-success', 'text-white');
            else if (value === 'Ditolak') select.classList.add('bg-danger', 'text-white');
            else if (value === 'Menunggu') select.classList.add('bg-warning');
        }

        // Search seluruh data tanpa me reload halaman
        function loadData(search = '', page = 1) {
            const formData = new FormData();
            formData.append('search', search);
            formData.append('page', page);
            formData.append('ajax', '1');

            fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.text())
                .then(html => {
                    document.getElementById('admin-data-container').innerHTML = html;
                    setupStatusDropdowns(); // <== penting!
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }

        // Otomatis menampilkan data saat halaman dimuat termasuk saat search dan pagination
        document.addEventListener('DOMContentLoaded', function() {
            setupStatusDropdowns();

            // PERBAIKAN: Menggunakan ID yang benar
            const searchInput = document.getElementById('search-input');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const keyword = this.value;
                    loadData(keyword);
                });
            }

            // Event delegation untuk pagination
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('pagination-link')) {
                    e.preventDefault();
                    const page = e.target.getAttribute('data-page');
                    // PERBAIKAN: Menggunakan ID yang benar
                    const searchInput = document.getElementById('search-input');
                    const keyword = searchInput ? searchInput.value : '';
                    loadData(keyword, page);
                }
            });
        });

        // Tombol batal mengubah status
        document.getElementById('cancelBtn').onclick = () => {
            document.getElementById('customAlert').style.display = 'none';
            location.reload();
        };

        // Tombol konfirmasi mengubah status
        document.getElementById('confirmBtn').onclick = () => {
            if (selectedFormToSubmit) {
                selectedFormToSubmit.submit();
            }
        };
    </script>

    <!-- Alert saat berhasil login -->
    <?php if (isset($_SESSION['login_success'])): ?>
        <script>
            Swal.fire({
                title: 'Login Berhasil',
                html: '<b>Selamat datang kembali,</b><br><span style="color:#48cfcb; font-weight:bold;"><?= htmlspecialchars($username) ?></span>',
                icon: 'success',
                background: '#1c1c1c',
                color: '#ffffff',
                iconColor: '#48cfcb',
                confirmButtonColor: '#48cfcb',
                timer: 3000,
                timerProgressBar: true,
                showConfirmButton: false,
                didOpen: () => {
                    const content = Swal.getHtmlContainer()
                    content.style.fontSize = '16px';
                }
            });
        </script>
        <?php unset($_SESSION['login_success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['message'])): ?>
        <script>
            Swal.fire({
                title: '<?= $_SESSION['message_type'] === "success" ? "Berhasil!" : "Gagal!" ?>',
                text: '<?= addslashes($_SESSION["message"]) ?>',
                icon: '<?= $_SESSION["message_type"] ?>',
                background: '#1c1c1c',
                color: '#fff',
                iconColor: '#48cfcb',
                confirmButtonColor: '#48cfcb',
                timer: 2500,
                showConfirmButton: false
            });
        </script>
        <?php
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
        ?>
    <?php endif; ?>
</body>

</html>