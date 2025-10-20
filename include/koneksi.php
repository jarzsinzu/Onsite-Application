<?php
$host = "172.15.10.80";      
$user = "onsite_app_user01";           
$password = "Cyberark1";           
$database = "onsite_app";        

// Membuat koneksi
$conn = mysqli_connect($host, $user, $password, $database);

// Cek koneksi
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>
