<?php
session_start();
require('../include/koneksi.php');

if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tambah Onsite - ACTIVin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            background-color: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: auto;
            background: #fff;
            padding: 20px;
            border-radius: 10px;
        }

        iframe {
            width: 100%;
            height: 300px;
            border: 1px solid #ccc;
        }

        .hover-bg:hover {
            background-color: #f8f9fa;
        }

        .badge-custom {
            background-color: #48cfcb;
            color: white;
            padding: 8px 12px;
            font-size: 14px;
            border-radius: 10px;
        }

        .badge-custom:hover {
            background-color: #3cbdb9;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2 class="mb-4">Form Tambah Data Onsite</h2>
        <form action="proses-tambah.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="user_id" value="<?= $user_id ?>">

            <div class="mb-3">
                <label><strong>Tanggal</strong></label>
                <input type="date" name="tanggal" id="tanggal" class="form-control" required>
            </div>

            <div class="mb-3">
                <label><strong>Pilih Anggota Tim</strong></label>
                <input type="text" id="anggota-input" class="form-control" placeholder="Ketik untuk cari anggota...">
                <div id="anggota-list" class="mt-2 border rounded p-2" style="max-height: 150px; overflow-y: auto;"></div>
                <div id="anggota-terpilih" class="mt-3"></div>
            </div>

            <input type="hidden" name="latitude" id="latitude" class="form-control" readonly>
            <input type="hidden" name="longitude" id="longitude" class="form-control" readonly>

            <div class="mb-3">
                <label><strong>Preview Lokasi di Google Maps</strong></label><br>
                <small class="form-text text-muted" id="lokasi-status">Mendeteksi lokasi...</small>
                <iframe id="mapPreview"></iframe>
            </div>

            <div class="mb-3">
                <label><strong>Keterangan Kegiatan</strong></label>
                <textarea name="keterangan_kegiatan" class="form-control" required></textarea>
            </div>

            <div class="mb-3">
                <label><strong>Jam Mulai</strong></label>
                <input type="time" name="jam_mulai" class="form-control" required>
            </div>

            <div class="mb-3">
                <label><strong>Jam Selesai</strong></label>
                <input type="time" name="jam_selesai" class="form-control" required>
            </div>

            <div class="mb-3">
                <label><strong>Estimasi Biaya</strong></label>
                <input type="number" name="estimasi_biaya" class="form-control" required>
            </div>

            <div class="mb-3">
                <label><strong>Dokumentasi (PDF/JPG/PNG)</strong></label>
                <input type="file" name="dokumentasi" accept=".pdf,.jpg,.jpeg,.png" class="form-control">
            </div>

            <div class="mb-3">
                <label><strong>Upload File CSV (.csv)</strong></label>
                <input type="file" name="file_csv" accept=".csv" class="form-control">
            </div>

            <div class="d-flex justify-content-between">
                <div>
                    <button type="submit" name="simpan" class="btn btn-info" style="color: #fff;">Simpan</button>
                    <a href="dashboard-user.php" class="btn btn-danger">Batal</a>
                </div>
                <a href="../template/template_onsite.csv" class="btn btn-success">Download Template CSV</a>
            </div>
        </form>
    </div>

    <script>
        // Validasi tanggal tidak boleh di masa lalu
        document.addEventListener("DOMContentLoaded", () => {
            const tanggalInput = document.getElementById("tanggal");
            const today = new Date().toISOString().split('T')[0];
            tanggalInput.setAttribute("min", today);
        });

        // Mendeteksi lokasi user secara otomatis
        function getLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(showPosition, showError, {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                });
            } else {
                document.getElementById("lokasi-status").innerText = "Geolocation tidak didukung oleh browser Anda.";
            }
        }

        // Menampilkan lokasi di google maps dan menyimpannya ke input
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

        document.addEventListener("DOMContentLoaded", getLocation); // Otomatis menjalankan deteksi lokasi saat halaman dibuka

        // Mengabmbil data anggota dari database
        const anggotaData = <?= json_encode(mysqli_fetch_all(mysqli_query($conn, "SELECT id, nama FROM anggota"), MYSQLI_ASSOC)); ?>;

        // Mengambil class dari html untuk pencarian anggota
        const anggotaInput = document.getElementById('anggota-input');
        const anggotaList = document.getElementById('anggota-list');
        const anggotaTerpilih = document.getElementById('anggota-terpilih');

        let selectedAnggota = [];

        // Menampilkan list filter hasil pencarian 
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

        // Menambahkan anggota ke daftar terpilih 
        function tambahAnggota(anggota) {
            selectedAnggota.push(anggota);
            updateBadge();
            anggotaInput.value = '';
            renderList(anggotaData);
        }

        // Untuk menghapus anggota dari daftar terpilih
        function hapusAnggota(id) {
            selectedAnggota = selectedAnggota.filter(a => a.id != id);
            updateBadge();
            renderList(anggotaData);
        }

        // Memperbarui badge untuk anggota terpilih
        function updateBadge() {
            anggotaTerpilih.innerHTML = '';
            selectedAnggota.forEach(a => {
                const span = document.createElement('span');
                span.className = 'badge badge-custom me-1 mb-1';
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

        // Live search
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