<?php
// File: admin/update_status_pesanan.php
session_start();
require '../config.php';

// Cek apakah user adalah admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../index.php");
    exit;
}

$pesanan_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($pesanan_id <= 0) {
    $_SESSION['error'] = "ID pesanan tidak valid!";
    header("Location: pesanan.php");
    exit;
}

// Ambil data pesanan
$query = mysqli_query($conn, "
    SELECT p.*, u.username, u.email 
    FROM pesanan p 
    JOIN pengguna u ON p.user_id = u.id 
    WHERE p.id = $pesanan_id
");
$pesanan = mysqli_fetch_assoc($query);

if (!$pesanan) {
    $_SESSION['error'] = "Pesanan tidak ditemukan!";
    header("Location: pesanan.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_status    = mysqli_real_escape_string($conn, $_POST['status']);
    $catatan_admin = mysqli_real_escape_string($conn, $_POST['catatan_admin']);
    
    $update_query = "UPDATE pesanan SET 
                        status = '$new_status',
                        catatan_admin = '$catatan_admin'
                     WHERE id = $pesanan_id";
    
    if (mysqli_query($conn, $update_query)) {
        $_SESSION['success'] = "Status pesanan berhasil diperbarui!";
        header("Location: pesanan.php");
        exit;
    } else {
        $_SESSION['error'] = "Gagal memperbarui status pesanan! " . mysqli_error($conn);
    }
}


$status_options = [
    'Menunggu Pembayaran',
    'Menunggu Verifikasi', 
    'Dikonfirmasi',
    'Diproses',
    'Dikirim',
    'Selesai',
    'Dibatalkan'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Status Pesanan | Kebun Wiyono</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Update Status Pesanan</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger">
                                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mb-4">
                            <h6>Informasi Pesanan:</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td width="150">Nomor Pesanan:</td>
                                    <td><strong><?= $pesanan['nomor_pesanan'] ?></strong></td>
                                </tr>
                                <tr>
                                    <td>Pembeli:</td>
                                    <td><?= $pesanan['username'] ?> (<?= $pesanan['email'] ?>)</td>
                                </tr>
                                <tr>
                                    <td>Total:</td>
                                    <td>Rp<?= number_format($pesanan['total_bayar']) ?></td>
                                </tr>
                                <tr>
                                    <td>Status Saat Ini:</td>
                                    <td><span class="badge bg-primary"><?= $pesanan['status'] ?></span></td>
                                </tr>
                            </table>
                        </div>

                        <form method="POST">
                            <div class="mb-3">
                                <label for="status" class="form-label">Status Baru:</label>
                                <select class="form-select" id="status" name="status" required>
                                    <?php foreach ($status_options as $status): ?>
                                        <option value="<?= $status ?>" <?= $pesanan['status'] == $status ? 'selected' : '' ?>>
                                            <?= $status ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="catatan_admin" class="form-label">Catatan Admin (Opsional):</label>
                                <textarea class="form-control" id="catatan_admin" name="catatan_admin" rows="3" 
                                          placeholder="Tambahkan catatan jika diperlukan..."><?= $pesanan['catatan_admin'] ?? '' ?></textarea>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Update Status
                                </button>
                                <a href="pesanan.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Kembali
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>