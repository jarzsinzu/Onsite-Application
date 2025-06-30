<?php
session_start();
require('../include/koneksi.php');

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

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
    </style>
</head>

<body>

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

    <div class="main">
        <form onsubmit="return false;" class="search-bar" method="post">
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
        </div>

        <div class="table-responsive" id="admin-data-container"></div>
    </div>


    <script>
        // Dropdown perubahan status
        let selectedFormToSubmit = null;

        // Update status & mengubah warna dropdown sesuai status
        function setupStatusDropdowns() {
            document.querySelectorAll('.status-dropdown').forEach(select => {
                updateStatusColor(select);
                select.addEventListener('change', () => {
                    selectedFormToSubmit = select.closest('form');
                    document.getElementById('customAlert').style.display = 'flex';
                });
            });
        }

        // Tombol batal mengubah status
        document.getElementById('cancelBtn').onclick = () => {
            document.getElementById('customAlert').style.display = 'none';
            location.reload(); // reset dropdown ke semula
        };

        // Tombol konfirmasi mengubah status
        document.getElementById('confirmBtn').onclick = () => {
            if (selectedFormToSubmit) {
                selectedFormToSubmit.submit();
            }
        };
    </script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Memberikan warna latar pada <select> sesuai status yang ditentukan
        function updateStatusColor(select) {
            const value = select.value;
            select.classList.remove('bg-success', 'bg-danger', 'bg-warning', 'text-white');
            if (value === 'Disetujui') select.classList.add('bg-success', 'text-white');
            else if (value === 'Ditolak') select.classList.add('bg-danger', 'text-white');
            else if (value === 'Menunggu') select.classList.add('bg-warning');
        }

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

        // Search seluruh data tanpa me reload halaman
        function loadData(search = '', page = 1) {
            $.post('search-ajax-admin.php', {
                search: search,
                page: page
            }, function(res) {
                $('#admin-data-container').html(res);
                setupStatusDropdowns(); // <== penting!
            });
        }

        // Otomatis menampilkan data saat halaman dimuat termasuk saat search dan pagination
        $(document).ready(function() {
            loadData();

            $('#search-input-admin').on('input', function() {
                const keyword = $(this).val();
                loadData(keyword);
            });

            $(document).on('click', '.pagination-link', function(e) {
                e.preventDefault();
                const page = $(this).data('page');
                const keyword = $('#search-input-admin').val();
                loadData(keyword, page);
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
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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