<?php
session_start();
require('../include/koneksi.php');

// // Cek apakah pengguna sudah login
// if (!isset($_SESSION['user'])) {
//     header("Location: ../login.php");
//     exit();
// }

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
    $dokumentasi = $_FILES['dokumentasi'];
    $status_pembayaran = 'Menunggu'; // default status  

    // Validasi upload
    $allowed_ext = ['pdf', 'jpg', 'jpeg', 'png'];
    $file_name = basename($dokumentasi['name']);
    $file_tmp = $dokumentasi['tmp_name'];
    $file_size = $dokumentasi['size'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    // Folder upload
    $upload_dir = '../uploads/';
    $unique_name = time() . '-' . preg_replace("/[^a-zA-Z0-9.]/", "", $file_name); // aman dan unik
    $target_file = $upload_dir . $unique_name;

    // Cek ekstensi
    if (!in_array($file_ext, $allowed_ext)) {
        echo "<script>
            alert('Format file tidak diizinkan!');
            window.location.href = 'tambah.php';
        </script>";
        die();
    }

    // Cek ukuran
    if ($file_size > 5 * 1024 * 1024) {
        echo "<script>
            alert('Ukuran file maksimal 5MB!');
            window.location.href = 'tambah.php';
        </script>";
        die();;
    }

    // Validasi lokasi
    if (empty($latitude) || empty($longitude)) {
        echo "<script>
                alert('Lokasi tidak terdeteksi! Pastikan akses lokasi diaktifkan.');
                window.location.href = 'tambah.php';
            </script>";
        exit();
    }

    if (!is_numeric($latitude) || !is_numeric($longitude)) {
        echo "<script>
                alert('Format lokasi tidak valid.');
                window.location.href = 'tambah.php';
            </script>";
        exit();
    }


    // Upload file
    if (move_uploaded_file($file_tmp, $target_file)) {
        // Simpan ke database
        $query = "INSERT INTO tambah_onsite (user_id, tanggal, latitude, longitude, keterangan_kegiatan, estimasi_biaya, dokumentasi, status_pembayaran, jam_mulai, jam_selesai)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sssssssss", $user_id, $tanggal, $latitude, $longitude, $keterangan_kegiatan, $estimasi_biaya, $unique_name, $status_pembayaran, $jam_mulai, $jam_selesai);

        if (mysqli_stmt_execute($stmt)) {
            header("Location: dashboard-user.php?success=1");
            exit();
        } else {
            echo "Gagal menyimpan ke database: " . mysqli_error($conn);
        }
    } else {
        echo "Upload file gagal.";
    }
    
}

// // Ambil data dari form
// $tanggal = $_POST['tanggal'] ?? '';
// $lokasi = $_POST['lokasi'] ?? '';
// $keterangan_kegiatan = $_POST['keterangan_kegiatan'] ?? '';
// $estimasi_biaya = $_POST['estimasi_biaya'] ?? '';
// $dokumentasi = $_FILES['dokumentasi'];
// $status_pembayaran = 'Menunggu'; // default status

// // Validasi upload
// $allowed_ext = ['pdf', 'jpg', 'jpeg', 'png'];
// $file_name = basename($dokumentasi['name']);
// $file_tmp = $dokumentasi['tmp_name'];
// $file_size = $dokumentasi['size'];
// $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

// // Folder upload
// $upload_dir = '../uploads/';
// $unique_name = time() . '-' . preg_replace("/[^a-zA-Z0-9.]/", "", $file_name); // aman dan unik
// $target_file = $upload_dir . $unique_name;

// // Cek ekstensi
// if (!in_array($file_ext, $allowed_ext)) {
//     echo "<script>
//         alert('Format file tidak diizinkan!');
//         window.location.href = 'tambah.php';
//     </script>";
//     die();
// }

// // Cek ukuran
// if ($file_size > 5 * 1024 * 1024) {
//     echo "<script>
//         alert('Ukuran file maksimal 5MB!');
//         window.location.href = 'tambah.php';
//     </script>";
//     die();;
// }

// // Upload file
// if (move_uploaded_file($file_tmp, $target_file)) {
//     // Simpan ke database
//     $query = "INSERT INTO tambah_onsite (tanggal, lokasi, keterangan_kegiatan, estimasi_biaya, dokumentasi, status_pembayaran)
//               VALUES (?, ?, ?, ?, ?, ?)";
//     $stmt = mysqli_prepare($conn, $query);
//     mysqli_stmt_bind_param($stmt, "sssdss", $tanggal, $lokasi, $keterangan_kegiatan, $estimasi_biaya, $unique_name, $status_pembayaran);

//     if (mysqli_stmt_execute($stmt)) {
//         header("Location: dashboard-user.php?success=1");
//         exit();
//     } else {
//         echo "Gagal menyimpan ke database: " . mysqli_error($conn);
//     }
// } else {
//     echo "Upload file gagal.";
// }
