<?php
$filename = $_GET['file'] ?? '';
$filepath = __DIR__ . '/uploads/csv/' . basename($filename);

if (!empty($filename) && file_exists($filepath)) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header("Content-Disposition: attachment; filename=\"" . basename($filepath) . "\"");
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Content-Length: ' . filesize($filepath));
    readfile($filepath);
    exit;
} else {
    echo "File tidak ditemukan.";
}
