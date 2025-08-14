<?php
// File: admin/detail_pesanan.php
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

// Ambil data pesanan dengan detail user
$query = mysqli_query($conn, "
    SELECT p.*, u.username, u.email, u.alamat, u.telepon
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

// Ambil item pesanan
$items_query = mysqli_query($conn, "
    SELECT pi.*, pr.nama_produk, pr.gambar
    FROM pesanan_item pi
    JOIN produk pr ON pi.produk_id = pr.id
    WHERE pi.pesanan_id = $pesanan_id
    ORDER BY pi.id
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pesanan <?= $pesanan['nomor_pesanan'] ?> | Kebun Wiyono</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<?php if (isset($_GET['cetak']) && $_GET['cetak'] == '1'): ?>
    <script>
        window.addEventListener('load', function() {
            setTimeout(() => {
                window.print();
            }, 500);
        });
    </script>
<?php endif; ?>

<style>
    @media print {
        .no-print {
            display: none !important;
        }
    }
</style>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Detail Pesanan <?= $pesanan['nomor_pesanan'] ?></h2>
            <div>
                <a href="pesanan.php" class="btn btn-secondary no-print">
                    <i class="fas fa-arrow-left me-2"></i>Kembali
                </a>
                <a href="update_status_pesanan.php?id=<?= $pesanan_id ?>" class="btn btn-warning no-print">
                    <i class="fas fa-edit me-2"></i>Update Status
                </a>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?= $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Informasi Pesanan -->
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Informasi Pesanan</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td>Nomor Pesanan:</td>
                                <td><strong><?= $pesanan['nomor_pesanan'] ?></strong></td>
                            </tr>
                            <tr>
                                <td>Tanggal:</td>
                                <td><?= date('d/m/Y H:i', strtotime($pesanan['tanggal_pesanan'])) ?></td>
                            </tr>
                            <tr>
                                <td>Status:</td>
                                <td>
                                    <?php
                                    $status_class = '';
                                    switch($pesanan['status']) {
                                        case 'Menunggu Pembayaran': $status_class = 'bg-warning text-dark'; break;
                                        case 'Menunggu Verifikasi': $status_class = 'bg-info text-white'; break;
                                        case 'Dikonfirmasi': $status_class = 'bg-primary text-white'; break;
                                        case 'Diproses': $status_class = 'bg-secondary text-white'; break;
                                        case 'Dikirim': $status_class = 'bg-success text-white'; break;
                                        case 'Selesai': $status_class = 'bg-dark text-white'; break;
                                        case 'Dibatalkan': $status_class = 'bg-danger text-white'; break;
                                    }
                                    ?>
                                    <span class="badge <?= $status_class ?>"><?= $pesanan['status'] ?></span>
                                </td>
                            </tr>
                            <tr>
                                <td>Metode Pembayaran:</td>
                                <td><?= ucfirst($pesanan['metode_pembayaran']) ?></td>
                            </tr>
                            <tr>
                                <td>Total Bayar:</td>
                                <td><strong>Rp<?= number_format($pesanan['total_bayar']) ?></strong></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Informasi Pembeli -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Informasi Pembeli</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td>Nama:</td>
                                <td><?= $pesanan['username'] ?></td>
                            </tr>
                            <tr>
                                <td>Email:</td>
                                <td><?= $pesanan['email'] ?></td>
                            </tr>
                            <tr>
                                <td>No. Telepon:</td>
                                <td><?= $pesanan['no_telepon'] ?? '-' ?></td>
                            </tr>
                            <tr>
                                <td>Alamat:</td>
                                <td><?= $pesanan['alamat'] ?? '-' ?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Bukti Pembayaran -->
                <?php if (!empty($pesanan['bukti_pembayaran'])): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Bukti Pembayaran</h5>
                    </div>
                    <div class="card-body text-center">
                        <img src="../fitur/bukti_pembayaran/<?= $pesanan['bukti_pembayaran'] ?>" 
                             alt="Bukti Pembayaran" class="img-fluid mb-3" style="max-height: 300px;">
                        <br>
                        <small class="text-muted">Diupload: <?= date('d/m/Y H:i', strtotime($pesanan['tanggal_upload_bukti'])) ?></small>
                        
                        <?php if ($pesanan['status'] == 'Menunggu Verifikasi'): ?>
                        <div class="mt-3">
                            <a href="verifikasi_pembayaran.php?action=approve&order=<?= $pesanan['nomor_pesanan'] ?>" 
                               class="btn btn-sm btn-success" 
                               onclick="return confirm('Setujui pembayaran ini?')">
                                <i class="fas fa-check me-1"></i>Setujui
                            </a>
                            <a href="verifikasi_pembayaran.php?action=reject&order=<?= $pesanan['nomor_pesanan'] ?>" 
                               class="btn btn-sm btn-danger" 
                               onclick="return confirm('Tolak pembayaran ini?')">
                                <i class="fas fa-times me-1"></i>Tolak
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Daftar Item -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Daftar Item Pesanan</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Produk</th>
                                        <th>Harga</th>
                                        <th>Qty</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $total = 0;
                                    while ($item = mysqli_fetch_assoc($items_query)): 
                                        $subtotal = $item['harga'] * $item['quantity'];
                                        $total += $subtotal;
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if (!empty($item['gambar'])): ?>
                                                <img src="gambarProduk/<?= $item['gambar'] ?>" 
                                                     alt="<?= $item['nama_produk'] ?>" 
                                                     class="me-3" 
                                                     style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                                <?php endif; ?>
                                                <div>
                                                    <strong><?= $item['nama_produk'] ?></strong>
                                                </div>
                                            </div>
                                        </td>
                                        <td>Rp<?= number_format($item['harga']) ?>/ kg</td>
                                        <td><?= $item['subtotal'] ?></td>
                                        <td>Rp<?= number_format($subtotal) ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="table-info">
                                        <td colspan="3"><strong>Total:</strong></td>
                                        <td><strong>Rp<?= number_format($total) ?></strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Catatan Admin -->
                <?php if (!empty($pesanan['catatan_admin'])): ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Catatan Admin</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-0"><?= nl2br($pesanan['catatan_admin']) ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>