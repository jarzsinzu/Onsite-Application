<?php
session_start();
require('include/koneksi.php');

// Hanya admin yang boleh mengakses
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    header("Location: test-login.php");
    exit();
}

// Validasi input
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $status = trim($_POST['status_pembayaran'] ?? '');

    if ($id && in_array($status, ['Menunggu', 'Disetujui', 'Ditolak'])) {
        $stmt = $conn->prepare("UPDATE tambah_onsite SET status_pembayaran = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        $stmt->execute();
        $stmt->close();

        $_SESSION['message'] = "Status berhasil diperbarui.";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Permintaan tidak valid.";
        $_SESSION['message_type'] = "danger";
    }
}

header("Location: admin-test.php");
exit();
