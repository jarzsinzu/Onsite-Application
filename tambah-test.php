    <?php
    session_start();
    require('include/koneksi.php');

    // Cek apakah pengguna sudah login
    if (!isset($_SESSION['user'])) {
        header("Location: test-login.php");
        exit();
    }

    $current_page = basename($_SERVER['PHP_SELF']);
    ?>

    <!DOCTYPE html>
    <html lang="id">

    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Tambah Onsite - ACTIVin</title>
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

            .container-center {
                display: flex;
                justify-content: center;
                align-items: flex-start;
                min-height: 100vh;
                width: 100%;
                padding-top: 40px;
                background-color: #f5f5f5;
            }

            .main {
                width: 100%;
                max-width: 800px;
                /* batasi lebar konten agar tidak terlalu lebar */
                padding: 0 20px;
            }

            .form-container {
                background: #fff;
                border-radius: 10px;
                padding: 30px;
                box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2);
                color: #333;
                width: 100%;
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
                color: #fff;
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
        <div class="container-center">
            <div class="main">
                <h3 style="color: #f5f5f5; font-weight:bold;">Tambah Data Onsite</h3><br>

                <div class="form-container">
                    <form action="proses-tambah-test.php" method="post" enctype="multipart/form-data" class="myForm">
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
                            <input type="hidden" name="latitude" id="latitude">
                            <input type="hidden" name="longitude" id="longitude">
                            <p id="lokasi-status">Mendeteksi lokasi...</p>
                            <iframe id="mapPreview" style="width:300px; height:300px; border:1px solid #ccc;" loading="lazy"></iframe>
                        </div>

                        <div class="form-group">
                            <label for="keterangan_kegiatan">Keterangan Kegiatan</label>
                            <textarea id="keterangan_kegiatan" name="keterangan_kegiatan" required></textarea>
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
                                    <p>Klik untuk mengunggah atau drag & drop</p>
                                    <small>PDF, JPG, PNG (maks. 5MB)</small>
                                </label>
                                <input type="file" id="dokumentasi" name="dokumentasi" accept=".pdf,.jpg,.jpeg,.png">
                            </div>
                        </div>

                        <div class="btn-container">
                            <a href="user-test.php"><button type="button" class="btn btn-secondary">Batal</button></a>
                            <button type="submit" class="btn btn-primary" name="simpan">Simpan</button>
                        </div>
                    </form>
                </div>
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

            // Lokasi otomatis
            function getLocation() {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(showPosition, showError);
                } else {
                    alert("Geolocation tidak didukung browser Anda.");
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

            function showError(error) {
                document.getElementById("lokasi-status").textContent = "Gagal mendeteksi lokasi.";
            }

            document.addEventListener("DOMContentLoaded", getLocation);

            document.querySelector(".myForm").addEventListener("submit", function(e) {
                const lat = document.getElementById("latitude").value;
                const lon = document.getElementById("longitude").value;
                if (!lat || !lon) {
                    e.preventDefault();
                    alert("Tunggu hingga lokasi terdeteksi otomatis sebelum menyimpan.");
                }
            });
        </script>

    </body>

    </html>