<?php
session_start();
require('../include/koneksi.php');

// Cek apakah pengguna sudah login
if (!isset($_SESSION['user'])) {
    // Jika belum login, redirect ke halaman login
    header("Location: ../login.php");
    exit(); // Penting untuk menghentikan eksekusi kode setelah redirect
}

$current_page = basename($_SERVER['PHP_SELF']);

// Pagination logic
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 5;
$offset = ($page - 1) * $records_per_page;

// Count total records
$count_query = "SELECT COUNT(*) as total FROM tambah_onsite";
$count_result = mysqli_query($conn, $count_query);
$total_rows = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_rows / $records_per_page);

// Get data for current page
$query = "SELECT * FROM tambah_onsite ORDER BY id DESC LIMIT $offset, $records_per_page";
$result = mysqli_query($conn, $query);

$username = $_SESSION['user'];
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard User - ACTIVin</title>

    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            display: flex;
            background-color: #f5f5f5;
            color: #333;
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

        .nav-container {
            display: flex;
            flex-direction: column;
            height: 100%;
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
            background-color: #fff;
            color: #333;
        }

        .input-with-icon i {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #888;
            pointer-events: none;
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 30px 0 20px;
        }

        .btn-tambah {
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            background-color: #48cfcb;
            color: white;
            font-weight: 600;
            text-decoration: none;
        }

        .btn-tambah:hover {
            background-color: #229799;
            color: white;
        }

        .iframe-map {
            width: 100%;
            height: 100px;
            border: 0;
            border-radius: 6px;
        }

        /* Fix pagination agar lebih rapi */
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
            font-weight: 500;
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
            /* Menambahkan shadow */
            background-color: white;
            /* Menambahkan latar belakang putih */
        }

        .table th {
            background-color: #1c1c1c;
            /* Warna latar belakang untuk header */
            color: white;
            /* Warna teks untuk header */
        }

        .table td {
            border-bottom: 2px solid #dee2e6;
            text-align: center;
            /* Garis bawah untuk sel */
        }

        .table tr:hover {
            background-color: #f8f9fa;
            /* Warna latar belakang saat hover */
        }

        .profile {
          display: flex;
          align-items: center;
        }

        .profile span {
          color: #1c1c1c;
          font-weight: bold;
          padding: 5px;
        }
    </style>
</head>

<body>
    <div class="sidebar">
        <h2>ACTIV<span style="color: #48cfcb;">in</span></h2>
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

    <div class="main">
        <form class="search-bar" method="post">
            <div class="topbar">
                <div class="input-with-icon">
                    <input type="text" placeholder="Cari onsite..." name="search">
                    <i class="bi bi-search"></i>
                </div>
                <div class="profile">
                    <span><?php echo $_SESSION['user'] = $username;?></span>
                    <i class="fas fa-user-circle fa-2x" style="color:#1c1c1c; font-size:35px;"></i>
                </div>
            </div>
        </form>

        <div class="header-section">
            <h2 style="font-weight: bold;">Data <span style="color: #48cfcb;">Onsite</span> Karyawan</h2>
            <a href="user/tambah.php" class="btn-tambah">+ Tambah Data Onsite</a>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered align-middle table-rounded">
                <thead class="table-dark">
                    <tr style="text-align: center;">
                        <th style="width: 100px;">Anggota</th>
                        <th>Tanggal</th>
                        <th>Lokasi</th>
                        <th>Detail Kegiatan</th>
                        <th>Waktu</th>
                        <th>Dokumentasi</th>
                        <th>Biaya</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    while ($row = mysqli_fetch_assoc($result)) :
                        $latitude = $row['latitude'];
                        $longitude = $row['longitude'];
                    ?>
                        <tr>
                            <td>Syams<br>Fajar<br>Farza</td>
                            <td><?= htmlspecialchars($row['tanggal']) ?></td>
                            <td style="width: 240px; height: 240px;">
                                <?php if ($latitude && $longitude): ?>
                                    <iframe
                                        src="https://www.google.com/maps?q=<?= $row['latitude'] ?>,<?= $row['longitude'] ?>&hl=id&z=15&output=embed" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy">
                                    </iframe>
                                <?php else: ?>
                                    <em>Lokasi tidak tersedia</em>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($row['keterangan_kegiatan']) ?></td>
                            <td><?= date('H:i', strtotime($row['jam_mulai'])) ?>-<?= date('H:i', strtotime($row['jam_selesai'])) ?></td>
                            <td>
                                <?php if (!empty($row['dokumentasi'])): ?>
                                    <a href="../uploads/<?= urlencode($row['dokumentasi']) ?>" target="_blank">Lihat</a>
                                <?php else: ?>
                                    Tidak ada
                                <?php endif; ?>
                            </td>
                            <td style="color: #006400; font-weight:bold;">Rp. <?= number_format($row['estimasi_biaya'], 0, ',', '.') ?></td>
                            <td><span class="badge bg-warning text-dark"><?= htmlspecialchars($row['status_pembayaran']) ?></span></td>
                            <!-- <td>
                                <div class="badge bg-warning text-dark">
                                    <?php
                                    $status = $row['status_pembayaran'];
                                    if ($status == 'Disetujui') {
                                        echo '<span class="badge bg-success">' . $status . '</span>';
                                    } elseif ($status == 'Menunggu') {
                                        echo '<span class="badge bg-warning badge-bold-black">' . $status . '</span>';
                                    } else {
                                        echo '<span class="badge bg-danger">' . $status . '</span>';
                                    }
                                    ?>
                                </div>
                            </td> -->
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>">&laquo;</a>
                <?php endif; ?>

                <?php
                // Tampilkan maksimal 5 nomor halaman di sekitar halaman aktif
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);

                if ($start_page > 1) {
                    echo '<a href="?page=1">1</a>';
                    if ($start_page > 2) {
                        echo '<span>...</span>';
                    }
                }

                for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <a href="?page=<?= $i ?>" <?= ($i == $page) ? 'class="active"' : '' ?>>
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($end_page < $total_pages) {
                    if ($end_page < $total_pages - 1) {
                        echo '<span>...</span>';
                    }
                    echo '<a href="?page=' . $total_pages . '">' . $total_pages . '</a>';
                }
                ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?>">&raquo;</a>
                <?php endif; ?>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

<!-- function ubahStatus(statusEl) {
    if (statusEl.classList.contains('pending')) {
        statusEl.classList.remove('pending');
        statusEl.classList.add('paid');
        statusEl.innerText = 'Dibayar';
    } else if (statusEl.classList.contains('paid')) {
        statusEl.classList.remove('paid');
        statusEl.classList.add('rejected');
        statusEl.innerText = 'Ditolak';
    } else {
        statusEl.classList.remove('rejected');
        statusEl.classList.add('pending');
        statusEl.innerText = 'Menunggu';
    }
} -->