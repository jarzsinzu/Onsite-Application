<?php
require_once 'include/send_telegram.php';

$chat_id = 7570636987; // Ganti dengan chat ID kamu (dari getUpdates)
$message = "📢 Notifikasi berhasil dari aplikasi onsite!";

sendTelegram($chat_id, $message);
echo "✅ Pesan dikirim ke Telegram.";
?>