<?php
session_start();
include '../config.php';

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Pastikan method POST dan ada ID
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    header("Location: pesananSaya.php");
    exit();
}

$pesanan_id = (int)$_POST['id'];
$user_id = $_SESSION['user_id'];

try {
    // Mulai transaction
    $conn->begin_transaction();
    
    // Cek apakah pesanan ada dan milik user ini
    $query = "SELECT id, status, konfirmasi_user FROM pesanan 
              WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $pesanan_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Pesanan tidak ditemukan");
    }
    
    $pesanan = $result->fetch_assoc();
    
    // Cek apakah pesanan statusnya "Selesai" dari admin
    if ($pesanan['status'] !== 'Selesai') {
        throw new Exception("Pesanan belum bisa dikonfirmasi");
    }
    
    // Cek apakah sudah dikonfirmasi sebelumnya
    if ($pesanan['konfirmasi_user'] == 1) {
        throw new Exception("Pesanan sudah dikonfirmasi sebelumnya");
    }
    
    // Update konfirmasi user dan tanggal konfirmasi
    $update_query = "UPDATE pesanan 
                     SET konfirmasi_user = 1, 
                         tanggal_konfirmasi_user = NOW() 
                     WHERE id = ? AND user_id = ?";
    
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("ii", $pesanan_id, $user_id);
    
    if (!$update_stmt->execute()) {
        throw new Exception("Gagal mengupdate konfirmasi pesanan");
    }
    
    // Commit transaction
    $conn->commit();
    
    // Set session success message
    $_SESSION['success_message'] = "Pesanan berhasil dikonfirmasi! Terima kasih atas kepercayaan Anda.";
    
    // Redirect ke detail pesanan
    header("Location: detail_Pesanan.php?id=" . $pesanan_id);
    exit();
    
} catch (Exception $e) {
    // Rollback jika ada error
    $conn->rollback();
    
    // Set error message
    $_SESSION['error_message'] = $e->getMessage();
    
    // Redirect ke detail pesanan
    header("Location: detail_Pesanan.php?id=" . $pesanan_id);
    exit();
}
?>