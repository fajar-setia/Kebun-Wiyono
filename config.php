<?php
// Konfigurasi database
$host = 'localhost'; 
$user = 'fajar';    
$pass = 'strongpassword';          
$db = 'website_project'; 

// Membuat koneksi ke database
$conn = new mysqli($host, $user, $pass, $db);

// Memeriksa koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>
