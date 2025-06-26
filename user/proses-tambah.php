<?php
// Tampilkan error saat debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require('../include/koneksi.php');

if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit();
}

function simpanData($conn, $data, $user_id)
{
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

    if ($stmt->execute()) {
        return $conn->insert_id;
    } else {
        return false;
    }
}

if (isset($_POST['simpan'])) {
    $user_id = $_POST['user_id'];
    $status_pembayaran = 'Menunggu';
    $upload_dir_dok = '../uploads/';
    $upload_dir_csv = '../csv/';
    $dokumentasi = '';
    $file_csv = '';

    // Upload dokumentasi
    if (!empty($_FILES['dokumentasi']['name'])) {
        $dok = $_FILES['dokumentasi'];
        $ext = strtolower(pathinfo($dok['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];

        if (in_array($ext, $allowed)) {
            $new_name = time() . '-' . basename($dok['name']);
            $target = $upload_dir_dok . $new_name;
            if (move_uploaded_file($dok['tmp_name'], $target)) {
                $dokumentasi = $new_name;
            }
        }
    }

    // Upload CSV
    if (!empty($_FILES['file_csv']['name'])) {
        $csv = $_FILES['file_csv'];
        $ext = strtolower(pathinfo($csv['name'], PATHINFO_EXTENSION));
        if ($ext === 'csv') {
            $new_name = time() . '-' . uniqid() . '-' . basename($csv['name']);
            $target = $upload_dir_csv . $new_name;
            if (move_uploaded_file($csv['tmp_name'], $target)) {
                $file_csv = $new_name;
            }
        }
    }

    // Validasi input wajib
    if (!empty($_POST['tanggal']) && !empty($_POST['keterangan_kegiatan'])) {
        // 🔒 Validasi tanggal tidak boleh di masa lalu
        $tanggal = $_POST['tanggal'];
        $tanggal_input = strtotime($tanggal);
        $tanggal_sekarang = strtotime(date("Y-m-d"));

        if ($tanggal_input < $tanggal_sekarang) {
            echo "<script>alert('❌ Tanggal tidak boleh di masa lalu.'); window.history.back();</script>";
            exit();
        }

        $data = [
            'tanggal' => $tanggal,
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

        // Simpan data ke tabel utama
        $id_onsite = simpanData($conn, $data, $user_id);

        if ($id_onsite) {
            // Simpan anggota tim
            if (!empty($_POST['anggota_ids']) && is_array($_POST['anggota_ids'])) {
                $stmt = $conn->prepare("INSERT INTO tim_onsite (id_onsite, id_anggota) VALUES (?, ?)");

                foreach ($_POST['anggota_ids'] as $id_anggota) {
                    $id_anggota = (int)$id_anggota;
                    $stmt->bind_param("ii", $id_onsite, $id_anggota);
                    $stmt->execute();
                }
                $stmt->close();
            }

            header("Location: dashboard-user.php?sukses=1");
            exit();
        } else {
            echo "<script>alert('❌ Gagal menyimpan data ke database.'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('❌ Tanggal dan keterangan kegiatan wajib diisi.'); window.history.back();</script>";
    }
}
