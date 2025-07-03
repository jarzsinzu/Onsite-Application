<?php
session_start();
require('../include/koneksi.php');
require_once('../include/send_telegram.php'); // Notifikasi Telegram

// Akses hanya untuk admin
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $status = trim($_POST['status_pembayaran'] ?? '');

    if ($id && in_array($status, ['Menunggu', 'Disetujui', 'Ditolak'])) {
        $stmt = $conn->prepare("UPDATE tambah_onsite SET status_pembayaran = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        $stmt->execute();
        $stmt->close();

        // Ambil data user terkait
        $query = "SELECT u.nama, u.telegram_chat_id 
                  FROM users u 
                  JOIN tambah_onsite t ON t.user_id = u.id 
                  WHERE t.id = ?";
        $stmtUser = $conn->prepare($query);
        $stmtUser->bind_param("i", $id);
        $stmtUser->execute();
        $result = $stmtUser->get_result();
        $user = $result->fetch_assoc();
        $stmtUser->close();

        if ($user && !empty($user['telegram_chat_id'])) {
            $nama_user = $user['nama'];
            $chat_id_user = $user['telegram_chat_id'];

            $pesan = "📢 Halo $nama_user,\nStatus pengajuan onsite kamu: *$status*.";
            sendTelegram($chat_id_user, $pesan);
        }

        $_SESSION['message'] = "Status berhasil diperbarui.";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Permintaan tidak valid.";
        $_SESSION['message_type'] = "danger";
    }
}

header("Location: dashboard-admin.php");
exit();
