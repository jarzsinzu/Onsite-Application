<?php
session_start();
require('include/koneksi.php');

// Cek apakah pengguna sudah login
if (!isset($_SESSION['user'])) {
    header("Location: test-login.php");
    exit();
}

if (isset($_POST['simpan'])) {
    // Ambil data dari form
    $user_id = $_SESSION['user_id'];
    $tanggal = $_POST['tanggal'] ?? '';
    $latitude = $_POST['latitude'] ?? '';
    $longitude = $_POST['longitude'] ?? '';
    $keterangan_kegiatan = $_POST['keterangan_kegiatan'] ?? '';
    $jam_mulai = $_POST['jam_mulai'] ?? '';
    $jam_selesai = $_POST['jam_selesai'] ?? '';
    $estimasi_biaya = $_POST['estimasi_biaya'] ?? '';
    $status_pembayaran = 'Menunggu';

    // Anggota tim (array) dijadikan string dipisah koma
    $nama_tim = isset($_POST['nama_tim']) && is_array($_POST['nama_tim']) ? implode(', ', $_POST['nama_tim']) : '';

    // Validasi lokasi
    if (empty($latitude) || empty($longitude) || !is_numeric($latitude) || !is_numeric($longitude)) {
        echo "<script>
                alert('Lokasi tidak valid. Pastikan GPS aktif.');
                window.location.href = 'tambah-test.php';
              </script>";
        exit();
    }

    // Validasi dan upload file dokumentasi
    $dokumentasi = $_FILES['dokumentasi'];
    $allowed_ext = ['pdf', 'jpg', 'jpeg', 'png'];
    $file_name = basename($dokumentasi['name']);
    $file_tmp = $dokumentasi['tmp_name'];
    $file_size = $dokumentasi['size'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    $upload_dir = 'uploads/';
    $unique_name = time() . '-' . preg_replace("/[^a-zA-Z0-9.]/", "", $file_name);
    $target_file = $upload_dir . $unique_name;

    // Cek ekstensi file
    if (!in_array($file_ext, $allowed_ext)) {
        echo "<script>alert('Format file tidak diizinkan!'); window.location.href = 'tambah-test.php';</script>";
        exit();
    }

    // Cek ukuran file
    if ($file_size > 5 * 1024 * 1024) {
        echo "<script>alert('Ukuran file maksimal 5MB!'); window.location.href = 'tambah-test.php';</script>";
        exit();
    }

    // Upload file
    if (move_uploaded_file($file_tmp, $target_file)) {
        // Simpan ke database
        $query = "INSERT INTO tambah_onsite 
                    (user_id, tanggal, latitude, longitude, keterangan_kegiatan, jam_mulai, jam_selesai, estimasi_biaya, dokumentasi, status_pembayaran)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ssssssssss",
                $user_id, $tanggal, $latitude, $longitude,
                $keterangan_kegiatan, $jam_mulai, $jam_selesai,
                $estimasi_biaya, $unique_name, $status_pembayaran
            );

            if (mysqli_stmt_execute($stmt)) {
                header("Location: user-test.php?success=1");
                exit();
            } else {
                echo "Gagal menyimpan ke database: " . mysqli_error($conn);
            }
        } else {
            echo "Query prepare gagal: " . mysqli_error($conn);
        }
    } else {
        echo "<script>alert('Gagal mengunggah file.'); window.location.href = 'tambah-test.php';</script>";
    }
}
?>
