<?php
session_start();
require '../include/koneksi.php'; 

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

if (isset($_FILES['csvFile']) && $_FILES['csvFile']['error'] === 0) {
    $file = $_FILES['csvFile']['tmp_name'];
    $handle = fopen($file, 'r');

    fgetcsv($handle);

    $user_id = $_SESSION['user_id'];
    $status_pembayaran = 'Menunggu';

    $sukses = 0;
    $gagal = 0;

    while (($row = fgetcsv($handle, 1000, ',')) !== false) {
        if (count($row) < 7) {
            $gagal++;
            continue;
        }

        $tanggal               = mysqli_real_escape_string($conn, $row[0]);
        $latitude              = mysqli_real_escape_string($conn, $row[1]);
        $longitude             = mysqli_real_escape_string($conn, $row[2]);
        $keterangan_kegiatan   = mysqli_real_escape_string($conn, $row[3]);
        $jam_mulai             = mysqli_real_escape_string($conn, $row[4]);
        $jam_selesai           = mysqli_real_escape_string($conn, $row[5]);
        $estimasi_biaya        = (int)$row[6];

        // Validasi data dasar
        if (!is_numeric($latitude) || !is_numeric($longitude) || !is_numeric($estimasi_biaya)) {
            $gagal++;
            continue;
        }

        $query = "INSERT INTO tambah_onsite 
                    (user_id, tanggal, latitude, longitude, keterangan_kegiatan, jam_mulai, jam_selesai, estimasi_biaya, status_pembayaran)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($conn, $query);
        if ($stmt) {
            mysqli_stmt_bind_param(
                $stmt,
                "sssssssis",
                $user_id,
                $tanggal,
                $latitude,
                $longitude,
                $keterangan_kegiatan,
                $jam_mulai,
                $jam_selesai,
                $estimasi_biaya,
                $status_pembayaran
            );

            if (mysqli_stmt_execute($stmt)) {
                $sukses++;
            } else {
                $gagal++;
            }
        } else {
            $gagal++;
        }
    }

    fclose($handle);

    echo "<script>alert('Upload selesai. Berhasil: $sukses baris, Gagal: $gagal baris.'); window.location.href = 'user/tambah-data.php';</script>";
} else {
    echo "<script>alert('Gagal mengupload file.'); window.location.href='user/tambah-data.php';</script>";
}
