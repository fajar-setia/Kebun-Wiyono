<?php
session_start();
require 'config.php'; // koneksi database + setting OAuth GitHub

// Ambil data user dari GitHub API (misalnya sudah dapat di $githubUser)
$github_id = $githubUser['github_id'];
$nama_lengkap = $githubUser['nama_lengkap'] ?? $githubUser['login']; // kalau name kosong pakai login
$username = "githubuser";

// Cek apakah user sudah ada di DB
$stmt = $pdo->prepare("SELECT * FROM pengguna WHERE facebook_id = ?");
$stmt->execute([$github_id]);
$user = $stmt->fetch();

if ($user) {
    // Kalau sudah ada, langsung login
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
} else {
    // Kalau belum ada, insert user baru
    $stmt = $pdo->prepare("INSERT INTO pengguna (username, nama_lengkap, facebook_id) VALUES (?, ?, ?)");
    $stmt->execute([$username, $nama_lengkap, $facebook_id]);

    $newId = $pdo->lastInsertId();

    $_SESSION['user_id'] = $newId;
    $_SESSION['username'] = $username;
    $_SESSION['nama_lengkap'] = $nama_lengkap;
}

// Setelah login sukses → redirect ke halaman user.php
header("Location: user.php");
exit;
?>
