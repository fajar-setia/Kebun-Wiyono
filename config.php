<?php
// Konfigurasi database
$host = 'localhost'; 
$user = 'fajar';    
$pass = 'strongpassword';          
$db = 'project_db'; 

// Membuat koneksi ke database
$conn = new mysqli($host, $user, $pass, $db);

// Memeriksa koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>
