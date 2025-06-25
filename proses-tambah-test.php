<?php
session_start();
require('include/koneksi.php');

if (!isset($_SESSION['user'])) {
    header("Location: test-login.php");
    exit();
}

function simpanData($conn, $data, $user_id) {
    $stmt = $conn->prepare("INSERT INTO tambah_onsite (
        user_id, tanggal, latitude, longitude, keterangan_kegiatan,
        jam_mulai, jam_selesai, estimasi_biaya, dokumentasi, file_csv, status_pembayaran
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $estimasi_biaya = (float)$data['estimasi_biaya'];

    $stmt->bind_param(
        "sssssssdsss",
        $user_id,
        $data['tanggal'],
        $data['latitude'],
        $data['longitude'],
        $data['keterangan_kegiatan'],
        $data['jam_mulai'],
        $data['jam_selesai'],
        $estimasi_biaya,
        $data['dokumentasi'],
        $data['file_csv'],
        $data['status_pembayaran']
    );

    return $stmt->execute();
}

if (isset($_POST['simpan'])) {
    $user_id = $_POST['user_id'];
    $status_pembayaran = 'Menunggu';
    $upload_dir_dok = 'uploads/';
    $upload_dir_csv = 'csv/';
    $dokumentasi = '';
    $file_csv = '';

    // ✅ Upload dokumentasi (jpg/png/pdf)
    if (!empty($_FILES['dokumentasi']['name'])) {
        $dok = $_FILES['dokumentasi'];
        $file_ext = strtolower(pathinfo($dok['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'pdf'];

        if (in_array($file_ext, $allowed_ext)) {
            $new_name = time() . '-' . basename($dok['name']);
            $target = $upload_dir_dok . $new_name;
            if (move_uploaded_file($dok['tmp_name'], $target)) {
                $dokumentasi = $new_name;
            }
        }
    }

    // ✅ Upload file CSV (hanya sebagai lampiran, tidak dibaca)
    if (!empty($_FILES['file_csv']['name'])) {
        $csv = $_FILES['file_csv'];
        $file_ext = strtolower(pathinfo($csv['name'], PATHINFO_EXTENSION));
        if ($file_ext === 'csv') {
            $csv_name = time() . '-' . uniqid() . '-' . basename($csv['name']);
            $csv_target = $upload_dir_csv . $csv_name;
            if (move_uploaded_file($csv['tmp_name'], $csv_target)) {
                $file_csv = $csv_name;
            }
        }
    }

    // ✅ Simpan data manual dari form
    if (!empty($_POST['tanggal']) && !empty($_POST['keterangan_kegiatan'])) {
        $data = [
            'tanggal' => $_POST['tanggal'],
            'latitude' => $_POST['latitude'],
            'longitude' => $_POST['longitude'],
            'keterangan_kegiatan' => $_POST['keterangan_kegiatan'],
            'jam_mulai' => $_POST['jam_mulai'],
            'jam_selesai' => $_POST['jam_selesai'],
            'estimasi_biaya' => $_POST['estimasi_biaya'],
            'dokumentasi' => $dokumentasi,
            'file_csv' => $file_csv,
            'status_pembayaran' => $status_pembayaran
        ];

        if (simpanData($conn, $data, $user_id)) {
            header("Location: user-test.php?sukses=1");
            exit();
        } else {
            echo "❌ Gagal menyimpan data.";
        }
    }
}
?>
