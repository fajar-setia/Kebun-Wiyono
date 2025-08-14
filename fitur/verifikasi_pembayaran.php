<?php
// File: admin/verifikasi_pembayaran.php
session_start();
require '../config.php';

// Cek apakah user adalah admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../index.php");
    exit;
}

if (!isset($_GET['action']) || !isset($_GET['order'])) {
    header("Location: pesanan.php");
    exit;
}

$action = $_GET['action'];
$order_number = mysqli_real_escape_string($conn, $_GET['order']);

// Ambil data pesanan
$query = mysqli_query($conn, "SELECT * FROM pesanan WHERE nomor_pesanan = '$order_number'");
$order = mysqli_fetch_assoc($query);

if (!$order) {
    $_SESSION['error'] = "Pesanan tidak ditemukan!";
    header("Location: pesanan.php");
    exit;
}

if ($action == 'approve') {
    // Setujui pembayaran
    $update_query = "UPDATE pesanan SET 
                    status = 'Dikonfirmasi',
                    tanggal_verifikasi = NOW(),
                    verified_by = {$_SESSION['admin_id']}
                    WHERE nomor_pesanan = '$order_number'";
    
    if (mysqli_query($conn, $update_query)) {
        $_SESSION['success'] = "Pembayaran pesanan {$order_number} berhasil disetujui!";
        
        // Optional: Kirim notifikasi email ke customer
        // sendEmailNotification($order['user_id'], 'payment_approved', $order);
        
    } else {
        $_SESSION['error'] = "Gagal memperbarui status pesanan!";
    }
    
} elseif ($action == 'reject') {
    // Tolak pembayaran
    $update_query = "UPDATE pesanan SET 
                    status = 'Menunggu Pembayaran',
                    bukti_pembayaran = NULL,
                    tanggal_upload_bukti = NULL,
                    catatan_admin = 'Bukti pembayaran ditolak. Silakan upload ulang bukti pembayaran yang valid.'
                    WHERE nomor_pesanan = '$order_number'";
    
    if (mysqli_query($conn, $update_query)) {
        $_SESSION['success'] = "Pembayaran pesanan {$order_number} ditolak. Customer diminta upload ulang.";
        
        // Optional: Kirim notifikasi email ke customer
        // sendEmailNotification($order['user_id'], 'payment_rejected', $order);
        
    } else {
        $_SESSION['error'] = "Gagal memperbarui status pesanan!";
    }
}

header("Location: pesanan.php?status=Menunggu Verifikasi");
exit;

// Function untuk kirim email notifikasi (opsional)
function sendEmailNotification($user_id, $type, $order) {
    global $conn;
    
    // Ambil data user
    $user_query = mysqli_query($conn, "SELECT * FROM pengguna WHERE id = $user_id");
    $user = mysqli_fetch_assoc($user_query);
    
    if (!$user) return false;
    
    $subject = '';
    $message = '';
    
    switch($type) {
        case 'payment_approved':
            $subject = "Pembayaran Dikonfirmasi - Pesanan {$order['nomor_pesanan']}";
            $message = "
                <h2>Pembayaran Dikonfirmasi!</h2>
                <p>Halo {$user['username']},</p>
                <p>Pembayaran untuk pesanan <strong>{$order['nomor_pesanan']}</strong> telah dikonfirmasi.</p>
                <p>Pesanan Anda sedang diproses dan akan segera dikirim.</p>
                <p>Terima kasih telah berbelanja di Kebun Wiyono!</p>
            ";
            break;
            
        case 'payment_rejected':
            $subject = "Bukti Pembayaran Perlu Diperbaiki - Pesanan {$order['nomor_pesanan']}";
            $message = "
                <h2>Bukti Pembayaran Perlu Diperbaiki</h2>
                <p>Halo {$user['username']},</p>
                <p>Bukti pembayaran untuk pesanan <strong>{$order['nomor_pesanan']}</strong> perlu diperbaiki.</p>
                <p>Silakan upload ulang bukti pembayaran yang jelas dan valid melalui halaman pesanan Anda.</p>
                <p>Jika ada pertanyaan, hubungi customer service kami.</p>
            ";
            break;
    }
    
    // Di sini Anda bisa implementasi pengiriman email
    // menggunakan PHPMailer atau fungsi mail() PHP
    
    return true;
}
?>