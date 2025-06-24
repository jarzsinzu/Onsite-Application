<?php
session_start();

require('../include/koneksi.php');

// Cek apakah pengguna sudah login
if (!isset($_SESSION['user'])) {
    // Jika belum login, redirect ke halaman login
    header("Location: ../login.php");
    exit(); // Penting untuk menghentikan eksekusi kode setelah redirect
}
?>



<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Tambah Onsite - OnsiteApp</title>

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />



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
      /* warna oranye latar belakang */
      color: #fff;
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

    /* Form Styling */
    .form-container {
      background: #fff;
      border-radius: 10px;
      padding: 30px;
      box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2);
      color: #333;
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      color: #1c1c1c;
    }

    .form-group input,
    .form-group textarea,
    .form-group select {
      width: 100%;
      padding: 12px;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: 14px;
    }

    .form-group textarea {
      min-height: 100px;
      resize: vertical;
    }

    select option:hover {
      background-color: #48cfcb !important;
      color: black !important;
    }


    .file-upload {
      display: flex;
      flex-direction: column;
    }

    .file-upload-label {
      padding: 12px;
      background-color: #f5f5f5;
      border: 1px dashed #ccc;
      border-radius: 8px;
      text-align: center;
      cursor: pointer;
      transition: all 0.3s;
    }

    .file-upload-label:hover {
      background-color: #e9e9e9;
    }

    .file-upload input[type="file"] {
      display: none;
    }

    .btn-container {
      display: flex;
      justify-content: flex-end;
      gap: 15px;
      margin-top: 30px;
    }

    .btn {
      padding: 12px 24px;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
    }

    .btn-primary {
      background-color: #48cfcb;
      color: white;
      border: none;
    }

    .btn-primary:hover {
      background-color: #229799;
    }

    .btn-secondary {
      background-color: transparent;
      color: #48cfcb;
      border: 1px solid #48cfcb;
    }

    .btn-secondary:hover {
      background-color: #1c1c1c;
    }

    .dropdown-container {
      position: relative;
      width: 300px;
    }

    .dropdown-display {
      border: 1px solid #ccc;
      border-radius: 8px;
      padding: 10px 15px;
      background-color: #fff;
      cursor: pointer;
    }

    .dropdown-list {
      position: absolute;
      top: 100%;
      left: 0;
      right: 0;
      border: 1px solid #ccc;
      border-top: none;
      background-color: #fff;
      border-radius: 0 0 8px 8px;
      display: none;
      max-height: 200px;
      overflow-y: auto;
      z-index: 999;
    }

    .dropdown-list div {
      padding: 10px 15px;
      cursor: pointer;
    }

    .dropdown-list div:hover {
      background-color: #48cfcb;
      color: black;
      font-weight: bold;
    }

    .selected-badges {
      margin-top: 10px;
    }

    .badge {
      display: inline-block;
      background: #1c1c1c;
      /* border: 1px solid #48cfcb; */
      color: #f5f5f5;
      padding: 5px 10px;
      border-radius: 5px;
      margin: 2px;
      cursor: pointer;
    }
  </style>
</head>

<body>
  <div class="sidebar">
    <h2>ACTIV<span style="color: #48cfcb;">in</span></h2>
    <div class="nav-container">
      <div class="nav-links">
        <div class="nav-links">
          <a href="dashboard-user.php" class="<?= $current_page == 'dashboard-user.php' ? 'active' : '' ?>">
            <i class="bi bi-columns-gap"></i> Dashboard
          </a>
          <a href="riwayat-user.php" class="<?= $current_page == 'riwayat-user.php' ? 'active' : '' ?>">
            <i class="bi bi-clock-history"></i> History Onsite
          </a>
        </div>
      </div>
      <div class="logout-link">
        <a href="../logout.php"><i class="bi bi-box-arrow-left"></i> Logout</a>
      </div>
    </div>
  </div>

  <div class="main">
    <h3 style="color: #1c1c1c; font-weight:bold;">Tambah Data Onsite</h3><br>

    <div class="form-container">
      <form action="proses-tambah.php" method="post" enctype="multipart/form-data" class="myForm">
        <div class="form-group">

          <label for="tim">Anggota Tim</label>
          <div class="dropdown-container" id="customDropdown">
            <div class="dropdown-display">-- Pilih --</div>
            <div class="dropdown-list">
              <div data-value="Muhammad Fajar Septiawan">Muhammad Fajar Septiawan</div>
              <div data-value="Muhammad Akbar Emur Hermawan">Muhammad Akbar Emur Hermawan</div>
              <div data-value="Farzaliano Dwi Putra Heryadi">Farzaliano Dwi Putra Heryadi</div>
              <div data-value="Asy Syams">Asy Syams</div>
            </div>
          </div>

          <div class="selected-badges" id="badgeContainer"></div>
          <input type="hidden" name="nama_tim" id="nama_tim">

          <!-- Hasil pilihan -->
          <div id="anggotaTerpilihContainer" style="margin-top:10px;"></div>

          <!-- Input tersembunyi untuk dikirim ke server -->
          <input type="hidden" name="nama_tim" id="nama_tim">

        </div>

        <div class="form-group">
          <label for="tanggal">Tanggal</label>
          <input type="date" id="tanggal" name="tanggal" required>
        </div>

        <div class="form-group">
          <label>Preview Lokasi di Google Maps</label>
          <input type="hidden" name="latitude" id="latitude" readonly placeholder="Latitude">
          <input type="hidden" name="longitude" id="longitude" readonly placeholder="Longitude">
          <p id="lokasi-status" style="color:#1c1c1c;">Mendeteksi lokasi...</p>
          <iframe id="mapPreview" style="width:100%; height:300px; border:1px solid #ccc;" loading="lazy"></iframe>
        </div>

        <div class="form-group">
          <label for="keterangan_kegiatan">Keterangan Kegiatan</label>
          <textarea id="keterangan_kegiatan" name="keterangan_kegiatan" placeholder="Jelaskan keterangan kegiatan anda" required></textarea>
        </div>

        <div class="form-group">
          <label for="jam_mulai">Jam Mulai</label>
          <input type="time" id="jam_mulai" name="jam_mulai" required>
        </div>

        <div class="form-group">
          <label for="jam_selesai">Jam Selesai</label>
          <input type="time" id="jam_selesai" name="jam_selesai" required>
        </div>


        <div class="form-group">
          <label for="estimasi_biaya">Estimasi Biaya</label>
          <input type="number" id="estimasi_biaya" name="estimasi_biaya" placeholder="Masukkan estimasi biaya" required>
        </div>

        <div class="form-group">
          <label>Dokumentasi</label>
          <div class="file-upload">
            <label for="dokumentasi" class="file-upload-label">
              <i class="fas fa-cloud-upload-alt"></i>
              <p>Klik untuk mengunggah dokumen atau drag & drop</p>
              <small>Format yang didukung: PDF, JPG, PNG (Maks. 5MB)</small>
            </label>
            <input type="file" id="dokumentasi" name="dokumentasi" accept=".pdf,.jpg,.jpeg,.png">
          </div>
        </div>

        <div class="btn-container">
          <a href="dashboard-user.php"><button type="button" class="btn btn-secondary">Batal</button></a>
          <button type="submit" class="btn btn-primary" name="simpan" value="add">Simpan</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    const dropdown = document.getElementById('customDropdown');
    const display = dropdown.querySelector('.dropdown-display');
    const list = dropdown.querySelector('.dropdown-list');
    const badgeContainer = document.getElementById('badgeContainer');
    const hiddenInput = document.getElementById('nama_tim');

    let selected = [];

    display.addEventListener('click', () => {
      list.style.display = list.style.display === 'block' ? 'none' : 'block';
    });

    list.addEventListener('click', (e) => {
      if (e.target.dataset.value && !selected.includes(e.target.dataset.value)) {
        selected.push(e.target.dataset.value);
        updateSelected();
      }
      list.style.display = 'none';
    });

    function updateSelected() {
      badgeContainer.innerHTML = '';
      selected.forEach((name, index) => {
        const badge = document.createElement('span');
        badge.className = 'badge';
        badge.textContent = name;
        badge.onclick = () => {
          selected.splice(index, 1);
          updateSelected();
        };
        badgeContainer.appendChild(badge);
      });
      hiddenInput.value = selected.join(',');
    }

    document.addEventListener('click', function(event) {
      if (!dropdown.contains(event.target)) {
        list.style.display = 'none';
      }
    });

    document.getElementById('dokumentasi').addEventListener('change', function() {
      const fileInput = this;
      const label = fileInput.previousElementSibling; // label yang mengandung isi teks

      if (fileInput.files.length > 0) {
        const fileName = fileInput.files[0].name;
        label.innerHTML = `<i class="fas fa-file-alt"></i><p>${fileName}</p>`;
      }
    });

    function getLocation() {
      if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(showPosition, showError, {
          enableHighAccuracy: true,
          timeout: 10000,
          maximumAge: 0
        });
      } else {
        alert("Browser tidak mendukung Geolocation.");
      }
    }


    function showPosition(position) {
      const lat = position.coords.latitude;
      const lon = position.coords.longitude;

      console.log("Latitude:", lat); // harusnya tampil di console
      console.log("Longitude:", lon);

      document.getElementById("latitude").value = lat;
      document.getElementById("longitude").value = lon;

      const mapUrl = `https://www.google.com/maps?q=${lat},${lon}&hl=id&z=15&output=embed`;
      document.getElementById("mapPreview").src = mapUrl;

      document.getElementById("lokasi-status").textContent = "Lokasi berhasil dideteksi.";
    }


    function showError(error) {
      switch (error.code) {
        case error.PERMISSION_DENIED:
          alert("Akses lokasi ditolak. Aktifkan lokasi di browser Anda.");
          break;
        case error.POSITION_UNAVAILABLE:
          alert("Informasi lokasi tidak tersedia.");
          break;
        case error.TIMEOUT:
          alert("Permintaan lokasi melebihi batas waktu.");
          break;
        case error.UNKNOWN_ERROR:
          alert("Terjadi kesalahan tidak dikenal.");
          break;
      }
    }

    // Validasi lokasi sebelum submit
    document.querySelector(".myForm").addEventListener("submit", function(e) {
      const lat = document.getElementById("latitude").value.trim();
      const lon = document.getElementById("longitude").value.trim();

      if (lat === "" || lon === "" || isNaN(lat) || isNaN(lon)) {
        e.preventDefault(); // hentikan pengiriman form
        alert("Tunggu sebentar hingga lokasi terdeteksi otomatis sebelum menyimpan.");
      }
    });
    document.addEventListener("DOMContentLoaded", function() {
      getLocation();
    });

    // Inisialisasi
    $(document).ready(function() {
      $('#anggotaDropdown').select2({
        placeholder: "-- Pilih anggota --",
        width: '100'
      });
    });
  </script>

</body>
<!-- Tambahkan sebelum </body> -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

</html>