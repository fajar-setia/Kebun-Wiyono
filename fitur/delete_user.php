<?php
session_start();
require '../config.php';

// Cek apakah user adalah admin
if (!isset($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Validasi method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Validasi parameter ID
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID pengguna tidak valid']);
    exit;
}

$userId = (int)$_POST['id'];

// Mulai transaksi
mysqli_begin_transaction($conn);

try {
    // Cek apakah pengguna memiliki pesanan
    $checkOrdersQuery = "SELECT COUNT(*) as total_orders FROM pesanan WHERE user_id = ?";
    $checkStmt = mysqli_prepare($conn, $checkOrdersQuery);
    mysqli_stmt_bind_param($checkStmt, 'i', $userId);
    mysqli_stmt_execute($checkStmt);
    $checkResult = mysqli_stmt_get_result($checkStmt);
    $orderCount = mysqli_fetch_assoc($checkResult)['total_orders'];
    
    if ($orderCount > 0) {
        // Jika pengguna memiliki pesanan, Anda bisa memilih untuk:
        // 1. Tidak menghapus pengguna dan memberikan pesan error
        // 2. Menghapus semua pesanan terkait terlebih dahulu
        // 3. Mengubah status pengguna menjadi "deleted" tanpa menghapus data
        
        // Opsi 1: Tidak menghapus jika ada pesanan
        throw new Exception('Pengguna tidak dapat dihapus karena memiliki riwayat pesanan');
        
        // Opsi 2: Hapus pesanan terlebih dahulu (uncomment jika diperlukan)
        /*
        $deleteOrdersQuery = "DELETE FROM pesanan WHERE user_id = ?";
        $deleteOrdersStmt = mysqli_prepare($conn, $deleteOrdersQuery);
        mysqli_stmt_bind_param($deleteOrdersStmt, 'i', $userId);
        mysqli_stmt_execute($deleteOrdersStmt);
        mysqli_stmt_close($deleteOrdersStmt);
        */
    }
    
    // Hapus pengguna
    $deleteQuery = "DELETE FROM pengguna WHERE id = ?";
    $deleteStmt = mysqli_prepare($conn, $deleteQuery);
    
    if (!$deleteStmt) {
        throw new Exception('Error preparing delete statement');
    }
    
    mysqli_stmt_bind_param($deleteStmt, 'i', $userId);
    $deleteResult = mysqli_stmt_execute($deleteStmt);
    
    if (!$deleteResult) {
        throw new Exception('Error executing delete statement');
    }
    
    $affectedRows = mysqli_stmt_affected_rows($deleteStmt);
    
    if ($affectedRows === 0) {
        throw new Exception('Pengguna tidak ditemukan atau sudah dihapus');
    }
    
    // Commit transaksi
    mysqli_commit($conn);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Pengguna berhasil dihapus']);
    
    mysqli_stmt_close($deleteStmt);
    mysqli_stmt_close($checkStmt);
    
} catch (Exception $e) {
    // Rollback transaksi jika terjadi error
    mysqli_rollback($conn);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

mysqli_close($conn);
?>