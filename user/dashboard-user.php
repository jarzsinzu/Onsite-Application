<?php
session_start();
require('../include/koneksi.php');

if (!isset($_SESSION['user']) || !isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$current_page = basename($_SERVER['PHP_SELF']);
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 5;
$offset = ($page - 1) * $records_per_page;

$user_id = $_SESSION['user_id'];
$username = $_SESSION['user'];

$count_query = "SELECT COUNT(*) as total FROM tambah_onsite WHERE user_id = $user_id";
$count_result = mysqli_query($conn, $count_query);
$total_rows = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_rows / $records_per_page);

$query = "SELECT * FROM tambah_onsite WHERE user_id = $user_id ORDER BY id DESC LIMIT $offset, $records_per_page";
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

        /* Bisa disisipkan ke <style> dashboard-user.php */
        .onsite-badge {
            background-color: #48cfcb;
            color: #fff;
            border-radius: 20px;
            padding: 4px 10px;
            font-size: 0.8rem;
            margin: 3px;
            display: inline-block;
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
            <?php include 'search-ajax-user.php'; ?>
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

    <!-- Script tambahan dari form -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const searchInput = document.getElementById("search-input");
            const dataContainer = document.getElementById("data-container");

            function fetchData(page = 1, keyword = "") {
                const xhr = new XMLHttpRequest();
                xhr.open("POST", "search-ajax-user.php", true);
                xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

                xhr.onload = function() {
                    if (xhr.status === 200) {
                        dataContainer.innerHTML = xhr.responseText;
                    }
                };

                xhr.send("page=" + page + "&search=" + encodeURIComponent(keyword));
            }

            // Handle pagination click
            dataContainer.addEventListener("click", function(e) {
                if (e.target.classList.contains("pagination-link")) {
                    e.preventDefault();
                    const page = e.target.getAttribute("data-page");
                    const keyword = searchInput.value;
                    fetchData(page, keyword);
                }
            });

            // Handle search input
            searchInput.addEventListener("input", function() {
                fetchData(1, this.value);
            });
        });

        document.addEventListener("DOMContentLoaded", () => {
            const tanggalInput = document.getElementById("tanggal");
            const today = new Date().toISOString().split('T')[0];
            tanggalInput.setAttribute("min", today);
        });

        function getLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(showPosition, showError);
            } else {
                document.getElementById("lokasi-status").innerText = "Geolocation tidak didukung oleh browser Anda.";
            }
        }

        function showPosition(pos) {
            let lat = pos.coords.latitude;
            let lon = pos.coords.longitude;
            document.getElementById("latitude").value = lat;
            document.getElementById("longitude").value = lon;
            const mapUrl = `https://www.google.com/maps?q=${lat},${lon}&hl=id&z=15&output=embed`;
            document.getElementById("mapPreview").src = mapUrl;
            document.getElementById("lokasi-status").textContent = "Lokasi berhasil dideteksi.";
        }

        function showError() {
            document.getElementById("lokasi-status").textContent = "Gagal mendeteksi lokasi.";
        }

        document.addEventListener("DOMContentLoaded", getLocation);

        const anggotaData = <?= json_encode(mysqli_fetch_all(mysqli_query($conn, "SELECT id, nama FROM anggota"), MYSQLI_ASSOC)); ?>;
        const anggotaInput = document.getElementById('anggota-input');
        const anggotaList = document.getElementById('anggota-list');
        const anggotaTerpilih = document.getElementById('anggota-terpilih');
        let selectedAnggota = [];

        function renderList(filtered) {
            anggotaList.innerHTML = '';
            filtered.forEach(a => {
                if (!selectedAnggota.find(item => item.id == a.id)) {
                    const div = document.createElement('div');
                    div.textContent = a.nama;
                    div.className = 'p-1 anggota-item hover-bg';
                    div.style.cursor = 'pointer';
                    div.onclick = () => tambahAnggota(a);
                    anggotaList.appendChild(div);
                }
            });
        }

        function tambahAnggota(anggota) {
            selectedAnggota.push(anggota);
            updateBadge();
            anggotaInput.value = '';
            renderList(anggotaData);
        }

        function hapusAnggota(id) {
            selectedAnggota = selectedAnggota.filter(a => a.id != id);
            updateBadge();
            renderList(anggotaData);
        }

        function updateBadge() {
            anggotaTerpilih.innerHTML = '';
            selectedAnggota.forEach(a => {
                const span = document.createElement('span');
                span.className = 'badge bg-info text-white me-1 mb-1';
                span.innerText = a.nama;
                span.onclick = () => hapusAnggota(a.id);

                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'anggota_ids[]';
                hidden.value = a.id;

                anggotaTerpilih.appendChild(span);
                anggotaTerpilih.appendChild(hidden);
            });
        }

        anggotaInput.addEventListener('input', () => {
            const keyword = anggotaInput.value.toLowerCase();
            const filtered = anggotaData.filter(a => a.nama.toLowerCase().includes(keyword));
            renderList(filtered);
        });

        document.addEventListener('DOMContentLoaded', () => {
            renderList(anggotaData);
        });
    </script>

</body>

</html>