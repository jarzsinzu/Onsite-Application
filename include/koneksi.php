<?php
$host = "db";      
$user = "user";           
$password = "userpass";           
$database = "onsite_db";    

// Membuat koneksi
$conn = mysqli_connect($host, $user, $password, $database);

// Cek koneksi
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>
