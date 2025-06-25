<?php
session_start();
require('include/koneksi.php');

if (!isset($_SESSION['user'])) {
    header("Location: test-login.php");
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
    </style>
</head>

<body>
    <div class="container">
        <h4 class="mb-4">Form Tambah Data Onsite (Manual & CSV)</h4>
        <form action="proses-tambah-test.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="user_id" value="<?= $user_id ?>">

            <div class="mb-3">
                <label>Tanggal</label>
                <input type="date" name="tanggal" class="form-control">
            </div>

            <!-- ✅ Tambahan: Pilih Anggota Tim -->
            <div class="mb-3">
                <label for="anggota_ids">Pilih Anggota Tim</label>
                <select name="anggota_ids[]" id="anggota_ids" class="form-control" multiple required>
                    <?php
                    require('include/koneksi.php'); // pastikan koneksi dimuat di atas file
                    $query = mysqli_query($conn, "SELECT id, nama FROM anggota");
                    while ($row = mysqli_fetch_assoc($query)) {
                        echo "<option value='{$row['id']}'>{$row['nama']}</option>";
                    }
                    ?>
                </select>
                <small class="form-text text-muted">Tekan Ctrl (Windows) atau Command (Mac) untuk memilih lebih dari satu anggota.</small>
            </div>



            <div class="mb-3">
                <label>Latitude</label>
                <input type="text" name="latitude" id="latitude" class="form-control" readonly>
            </div>
            <div class="mb-3">
                <label>Longitude</label>
                <input type="text" name="longitude" id="longitude" class="form-control" readonly>
            </div>

            <div class="mb-3">
                <label>Preview Lokasi di Google Maps</label>
                <p id="lokasi-status">Mendeteksi lokasi...</p>
                <iframe id="mapPreview"></iframe>
            </div>

            <div class="mb-3">
                <label>Keterangan Kegiatan</label>
                <textarea name="keterangan_kegiatan" class="form-control"></textarea>
            </div>
            <div class="mb-3">
                <label>Jam Mulai</label>
                <input type="time" name="jam_mulai" class="form-control">
            </div>
            <div class="mb-3">
                <label>Jam Selesai</label>
                <input type="time" name="jam_selesai" class="form-control">
            </div>
            <div class="mb-3">
                <label>Estimasi Biaya</label>
                <input type="number" name="estimasi_biaya" class="form-control">
            </div>
            <div class="mb-3">
                <label>Dokumentasi (PDF/JPG/PNG)</label>
                <input type="file" name="dokumentasi" accept=".pdf,.jpg,.jpeg,.png" class="form-control">
            </div>

            <div class="mb-3">
                <label>Upload File CSV (.csv)</label>
                <input type="file" name="file_csv" accept=".csv" class="form-control">
            </div>

            <div class="d-flex justify-content-between">
                <button type="submit" name="simpan" class="btn btn-primary">Simpan</button>
                <a href="template/template_onsite.csv" class="btn btn-success">Download Template CSV</a>
            </div>
        </form>
    </div>

    <script>
        function getLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(showPosition, showError);
            } else {
                document.getElementById("lokasi-status").innerText = "Geolocation tidak didukung.";
            }
        }

        function showPosition(pos) {
            let lat = pos.coords.latitude;
            let lon = pos.coords.longitude;
            document.getElementById("latitude").value = lat;
            document.getElementById("longitude").value = lon;
            document.getElementById("mapPreview").src = `https://www.google.com/maps?q=${lat},${lon}&hl=id&z=15&output=embed`;
            document.getElementById("lokasi-status").textContent = "Lokasi berhasil dideteksi.";
        }

        function showError() {
            document.getElementById("lokasi-status").textContent = "Gagal mendeteksi lokasi.";
        }

        document.addEventListener("DOMContentLoaded", getLocation);
    </script>
</body>

</html>