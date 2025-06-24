<?php
session_start();
require_once('../include/koneksi.php');

// Cek apakah pengguna sudah login
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit();
}

if(isset($_POST['aksi'])) {
    if($_POST['aksi'] == "add") {

        $tanggal = $_POST['tanggal'];
        $lokasi = $_POST['lokasi'];
        $keterangan_kegiatan = $_POST['keterangan_kegiatan'];
        $estimasi_biaya = $_POST['estimasi_biaya'];
        $dokumentasi = $_FILES['dokumentasi']['file'];
        $status_pembayaran = 'Menunggu';

        $dir = "../uploads/";
        $tmpFile = $dokumentasi = $_FILES['dokumentasi']['file'];

        move_uploaded_file($tmpFile, $dir.$dokumentasi);

        $query = "INSERT INTO tambah_onsite VALUES(null, '$tanggal', '$lokasi', '$keterangan_kegiatan', '$estimasi_biaya', '$dokumentasi', '$status_pembayaran')";
        $sql = mysqli_query($conn, $query);

        if($sql) {
            header("location: dashboard-user.php");
            exit(); 
        } else {
            echo $query;
        }
    }
}

?>