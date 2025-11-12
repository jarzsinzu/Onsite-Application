<?php
session_start();
require('../include/koneksi.php');
require_once('../include/send_telegram.php'); // Kirim notifikasi ke Telegram

if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit();
}

// Fungsi untuk menyimpan data ke database
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

// Cek apakah form disubmit
if (isset($_POST['simpan'])) {
    $user_id = $_POST['user_id'];
    $status_pembayaran = 'Menunggu';
// Tentukan base directory secara dinamis (naik 1 folder dari /user/)
$base_dir = dirname(__DIR__);

// Path upload otomatis sesuai lokasi project (bisa jalan di lokal & server)
$upload_dir_dok = $base_dir . '/uploads/dokumentasi/';
$upload_dir_csv = $base_dir . '/uploads/csv/';
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

    // Validasi input tanggal yang terlewat
    if (!empty($_POST['tanggal']) && !empty($_POST['keterangan_kegiatan'])) {
        $tanggal = $_POST['tanggal'];
        $tanggal_input = strtotime($tanggal);
        $tanggal_sekarang = strtotime(date("Y-m-d"));

        if ($tanggal_input < $tanggal_sekarang) {
            echo "<script>alert('Tanggal tidak boleh di masa lalu!'); window.history.back();</script>";
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

        // Simpan anggota yang dipilih
        $id_onsite = simpanData($conn, $data, $user_id);

        if ($id_onsite) {
            if (!empty($_POST['anggota']) && is_array($_POST['anggota'])) {
                $stmt = $conn->prepare("INSERT INTO tim_onsite (id_onsite, id_anggota) VALUES (?, ?)");
                foreach ($_POST['anggota'] as $id_anggota) {
                    $id_anggota = (int)$id_anggota;
                    $stmt->bind_param("ii", $id_onsite, $id_anggota);
                    $stmt->execute();
                }
                $stmt->close();
            }

            // Ambil nama user
            $query_user = mysqli_query($conn, "SELECT nama FROM users WHERE id = '$user_id'");
            $data_user = mysqli_fetch_assoc($query_user);
            $nama_user = $data_user['nama'] ?? 'User Tidak Diketahui';

            $pesan = "📢 Ada pengajuan onsite baru dari $nama_user.";

            // Kirim notifikasi ke semua admin yang punya chat_id
            $query_admins = mysqli_query($conn, "SELECT telegram_chat_id FROM users WHERE role = 'admin' AND telegram_chat_id IS NOT NULL");

            while ($row = mysqli_fetch_assoc($query_admins)) {
                $chat_id = $row['telegram_chat_id'];
                sendTelegram($chat_id, $pesan);
            }


            // Tandai berhasil dengan session
            $_SESSION['tambah_berhasil'] = true;
            header("Location: dashboard-user.php");
            exit();
        } else {
            echo "<script>alert('Gagal menyimpan data ke database.'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('Tanggal dan keterangan kegiatan wajib diisi.'); window.history.back();</script>";
    }
}
