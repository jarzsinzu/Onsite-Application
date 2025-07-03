<?php
function sendTelegram($chat_id, $message) {
    $token = '7529271277:AAHrU_3RiKYUGZtBnZgq5QFg1V-2B2Hv3Ng'; // Ganti dengan token Bot kamu
    $message = urlencode($message); // Encode pesan agar aman di URL

    $url = "https://api.telegram.org/bot{$token}/sendMessage?chat_id={$chat_id}&text={$message}";

    file_get_contents($url); // Kirim request ke Telegram
}
