<?php
session_start();
require '../config.php';

// Cek apakah user adalah admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../index.php");
    exit;
}

// Filter pesanan berdasarkan status
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$where_clause = '';

if (!empty($status_filter)) {
    $status_filter_safe = mysqli_real_escape_string($conn, $status_filter);
    $where_clause = "WHERE pesanan.status = '$status_filter_safe'";
} else {
    $where_clause = "";
}

$sql = "
SELECT 
    pesanan.id,
    pesanan.nomor_pesanan,
    pesanan.status,
    pesanan.tanggal_pesanan,
    pesanan.total_harga,
    pesanan.metode_pembayaran,
    pesanan.bukti_pembayaran,
    pesanan.tanggal_upload_bukti,
    pengguna.username AS nama_pembeli,
    pengguna.email AS email_pembeli,
    (
        SELECT COUNT(*) 
        FROM pesanan_item 
        WHERE pesanan_item.pesanan_id = pesanan.id
    ) AS jumlah_item
FROM 
    pesanan
JOIN 
    pengguna ON pesanan.user_id = pengguna.id
" . $where_clause . "
ORDER BY 
    pesanan.id DESC
";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Query Error: " . mysqli_error($conn));
}

// Hitung jumlah pesanan yang perlu perhatian admin
$count_new_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM pesanan WHERE status = 'Menunggu Pembayaran'");
$new_orders_count = mysqli_fetch_assoc($count_new_query)['count'];

$count_verification_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM pesanan WHERE status = 'Menunggu Verifikasi'");
$verification_count = mysqli_fetch_assoc($count_verification_query)['count'];

$resultTotal = mysqli_query($conn, "SELECT COUNT(*) as total FROM pesanan");
$totalOrdersNotif = $resultTotal ? mysqli_fetch_assoc($resultTotal)['total'] : 0;

$resultPending = mysqli_query($conn, "SELECT COUNT(*) as total FROM pesanan WHERE status = 'pending'");
$pendingOrders = $resultPending ? mysqli_fetch_assoc($resultPending)['total'] : 0;
// Hitung jumlah pesanan untuk setiap status
$status_counts = [];
$status_list = ['Menunggu Pembayaran', 'Menunggu Verifikasi', 'Dikonfirmasi', 'Diproses', 'Dikirim', 'Selesai', 'Dibatalkan'];

foreach ($status_list as $status) {
    $count_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM pesanan WHERE status = '$status'");
    $status_counts[$status] = mysqli_fetch_assoc($count_query)['count'];
}

// Total notifikasi untuk sidebar
$total_notifications = $totalOrdersNotif + $pendingOrders;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin - Daftar Pesanan | Kebun Wiyono</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin/pesanan.css">
</head>
<body>
    <!-- ini navbar -->
    <button class="btn btn-success d-md-none position-fixed" style="top: 1rem; left: 1rem; z-index: 1100;" id="toggleSidebar">
        <i class="fas fa-bars"></i>
    </button>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h4><i class="fas fa-seedling me-2"></i>Kebun Wiyono</h4>
            <small>Panel Admin</small>
        </div>
        <ul class="nav flex-column sidebar-menu">
            <li class="nav-item "><a href="dashboard.php" class="nav-link text-white "><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
            <li class="nav-item"><a href="proses_tambah_produk.php" class="nav-link text-white"><i class="fas fa-leaf me-2"></i>Produk</a></li>
            <li class="nav-item"><a href="digitalisasi_Pembukuan_offline.php" class="nav-link text-white"><i class="fas fa-book me-2"></i>Pembukuan Offline</a></li>
            <li class="nav-item"><a href="kategori.php" class="nav-link text-white"><i class="fas fa-tags me-2"></i>Kategori</a></li>
            <li class="nav-item">
                <a href="pesanan.php" class="nav-link text-white active">
                    <i class="fas fa-shopping-cart me-2"></i>Pesanan
                    <?php if ($total_notifications > 0): ?>
                        <span class="badge bg-warning ms-2"><?= $total_notifications ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item"><a href="pengguna.php" class="nav-link text-white"><i class="fas fa-users me-2"></i>Pengguna</a></li>
            <li class="nav-item"><a href="pengaturan.php" class="nav-link text-white"><i class="fas fa-cog me-2"></i>Pengaturan</a></li>
            <li class="nav-item"><a href="../logout.php" class="nav-link text-white"><i class="fas fa-sign-out-alt me-2"></i>Keluar</a></li>
        </ul>
    </div>

    <div class="content">
        <div class="page-header fade-in">
            
                <h2>Daftar Pesanan</h2>
                <?php if ($verification_count > 0): ?>
                    <div class="alert alert-warning alert-sm mt-2 mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong><?= $verification_count ?> pesanan</strong> menunggu verifikasi pembayaran!
                    </div>
                <?php endif; ?>
            
            <div class="eksport">
                <a href="export_pesanan.php" class="button text-white">
                    <i class="fas fa-file-export me-2 text-white"></i>Export Data
                </a>
            </div>
        </div>

        <div class="row mb-4">
            <?php foreach ($status_list as $index => $status): ?>
                <?php
                $icon_class = '';
                $bg_class = '';
                $card_class = '';

                switch ($status) {
                    case 'Menunggu Pembayaran':
                        $icon_class = 'fa-clock';
                        $bg_class = 'bg-warning text-dark';
                        if ($status_counts[$status] > 0) $card_class = 'priority-high';
                        break;
                    case 'Menunggu Verifikasi':
                        $icon_class = 'fa-search';
                        $bg_class = 'bg-info text-white';
                        if ($status_counts[$status] > 0) $card_class = 'priority-high';
                        break;
                    case 'Dikonfirmasi':
                        $icon_class = 'fa-check-circle';
                        $bg_class = 'bg-primary text-white';
                        break;
                    case 'Diproses':
                        $icon_class = 'fa-box';
                        $bg_class = 'bg-secondary text-white';
                        break;
                    case 'Dikirim':
                        $icon_class = 'fa-truck';
                        $bg_class = 'bg-success text-white';
                        break;
                    case 'Selesai':
                        $icon_class = 'fa-flag-checkered';
                        $bg_class = 'bg-dark text-white';
                        break;
                    case 'Dibatalkan':
                        $icon_class = 'fa-times-circle';
                        $bg_class = 'bg-danger text-white';
                        break;
                }
                ?>
                <div class="col-md-4 col-lg-12/7 mb-3">
                    <div class="card card-stats <?= $card_class ?>">
                        <div class="card-body text-center">
                            <div class="card-icon mx-auto <?= $bg_class ?>">
                                <i class="fas <?= $icon_class ?>"></i>
                            </div>
                            <h6 class="card-title"><?= $status ?></h6>
                            <h3 class="mb-0"><?= $status_counts[$status] ?></h3>
                            <a href="?status=<?= urlencode($status) ?>" class="btn btn-sm btn-link stretched-link">Lihat detail</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="card">
            <div class="card-header bg-white">
                <div class="filter-status d-flex flex-wrap gap-2">
                    <a href="pesanan.php" class="btn btn-sm <?= empty($status_filter) ? 'btn-primary' : 'btn-outline-secondary' ?>">
                        Semua Pesanan
                    </a>
                    <?php foreach ($status_list as $status): ?>
                        <a href="?status=<?= urlencode($status) ?>" class="btn btn-sm <?= $status_filter === $status ? 'btn-primary' : 'btn-outline-secondary' ?>">
                            <?= $status ?>
                            <?php if ($status_counts[$status] > 0): ?>
                                <span class="badge bg-light text-dark ms-1"><?= $status_counts[$status] ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>No. Pesanan</th>
                                <th>Tanggal</th>
                                <th>Pembeli</th>
                                <th>Item</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Pembayaran</th>
                                <th>Bukti</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($result) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                    <?php
                                    $status_class = '';
                                    switch ($row['status']) {
                                        case 'Menunggu Pembayaran':
                                            $status_class = 'bg-warning text-dark';
                                            break;
                                        case 'Menunggu Verifikasi':
                                            $status_class = 'bg-info text-white';
                                            break;
                                        case 'Dikonfirmasi':
                                            $status_class = 'bg-primary text-white';
                                            break;
                                        case 'Diproses':
                                            $status_class = 'bg-secondary text-white';
                                            break;
                                        case 'Dikirim':
                                            $status_class = 'bg-success text-white';
                                            break;
                                        case 'Selesai':
                                            $status_class = 'bg-dark text-white';
                                            break;
                                        case 'Dibatalkan':
                                            $status_class = 'bg-danger text-white';
                                            break;
                                    }

                                    // Logika status pembayaran yang lebih akurat
                                    $payment_class = '';
                                    $payment_text = '';

                                    if ($row['metode_pembayaran'] == 'cod') {
                                        $payment_class = 'bg-secondary text-white';
                                        $payment_text = 'COD';
                                    } elseif ($row['status'] == 'Menunggu Pembayaran') {
                                        $payment_class = 'bg-danger text-white';
                                        $payment_text = 'Belum Dibayar';
                                    } elseif ($row['status'] == 'Menunggu Verifikasi') {
                                        $payment_class = 'bg-warning text-dark';
                                        $payment_text = 'Perlu Verifikasi';
                                    } elseif (in_array($row['status'], ['Dikonfirmasi', 'Diproses', 'Dikirim', 'Selesai'])) {
                                        $payment_class = 'bg-success text-white';
                                        $payment_text = 'Lunas';
                                    } else {
                                        $payment_class = 'bg-secondary text-white';
                                        $payment_text = 'Unknown';
                                    }

                                    $date = date('d/m/Y H:i', strtotime($row['tanggal_pesanan']));
                                    ?>
                                    <tr>
                                        <td>
                                            <a href="detail_pesanan.php?id=<?= $row['id'] ?>" class="fw-bold text-decoration-none">
                                                <?= $row['nomor_pesanan'] ?>
                                                <?php if ($row['status'] == 'Menunggu Pembayaran'): ?>
                                                    <span class="badge bg-danger ms-1">Baru</span>
                                                <?php elseif ($row['status'] == 'Menunggu Verifikasi'): ?>
                                                    <span class="badge bg-warning text-dark ms-1">!</span>
                                                <?php endif; ?>
                                            </a>
                                        </td>
                                        <td><?= $date ?></td>
                                        <td>
                                            <?= $row['nama_pembeli'] ?>
                                            <br><small class="text-muted"><?= $row['email_pembeli'] ?></small>
                                        </td>
                                        <td><?= $row['jumlah_item'] ?> item</td>
                                        <td>Rp<?= number_format($row['total_harga']) ?></td>
                                        <td><span class="status-badge <?= $status_class ?>"><?= $row['status'] ?></span></td>
                                        <td><span class="status-badge <?= $payment_class ?>"><?= $payment_text ?></span></td>
                                        <td>
                                            <?php if (!empty($row['bukti_pembayaran'])): ?>
                                                <button class="btn btn-sm btn-info payment-proof-btn"
                                                    onclick="showPaymentProof('<?= $row['bukti_pembayaran'] ?>', '<?= $row['nomor_pesanan'] ?>')"
                                                    title="Lihat Bukti Pembayaran">
                                                    <i class="fas fa-image"></i>
                                                </button>
                                            <?php elseif ($row['metode_pembayaran'] != 'cod'): ?>
                                                <span class="text-muted">-</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">COD</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="detail_pesanan.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary" title="Detail">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="update_status_pesanan.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-success" title="Update Status">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="detail_pesanan.php?id=<?= $row['id'] ?>&cetak=1" class="btn btn-sm btn-outline-secondary" title="Cetak Invoice" target="_blank">
                                                    <i class="fas fa-print"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4">
                                        <div class="my-3">
                                            <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                                            <p>Tidak ada pesanan yang ditemukan</p>
                                            <?php if (!empty($status_filter)): ?>
                                                <a href="pesanan.php" class="btn btn-sm btn-primary">Lihat Semua Pesanan</a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal untuk menampilkan bukti pembayaran -->
    <div class="modal fade" id="paymentProofModal" tabindex="-1" aria-labelledby="paymentProofModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="paymentProofModalLabel">Bukti Pembayaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="paymentProofImage" src="" alt="Bukti Pembayaran" class="img-fluid" style="max-height: 500px;">
                    <div class="mt-3">
                        <p id="orderNumber" class="fw-bold"></p>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-success" onclick="approvePayment()">
                                <i class="fas fa-check me-2"></i>Setujui Pembayaran
                            </button>
                            <button type="button" class="btn btn-danger" onclick="rejectPayment()">
                                <i class="fas fa-times me-2"></i>Tolak Pembayaran
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>

        //toggle
        const toggleBtn = document.getElementById('toggleSidebar');
        const sidebar = document.getElementById('sidebar');

        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('show');
        });

        //menutup side bar
        document.addEventListener('click', function(e) {
            if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
                sidebar.classList.remove('show');
            }
        });

        function showPaymentProof(filename, orderNumber) {
            document.getElementById('paymentProofImage').src = '../fitur/bukti_pembayaran/' + filename;
            document.getElementById('orderNumber').textContent = 'Pesanan: ' + orderNumber;

            // Store current order info for approve/reject functions
            window.currentOrderProof = {
                filename: filename,
                orderNumber: orderNumber
            };

            new bootstrap.Modal(document.getElementById('paymentProofModal')).show();
        }

        function approvePayment() {
            if (confirm('Apakah Anda yakin ingin menyetujui pembayaran ini?')) {
                // Redirect to approval handler
                window.location.href = 'verifikasi_pembayaran.php?action=approve&order=' + window.currentOrderProof.orderNumber;
            }
        }

        function rejectPayment() {
            if (confirm('Apakah Anda yakin ingin menolak pembayaran ini?')) {
                // Redirect to rejection handler
                window.location.href = 'verifikasi_pembayaran.php?action=reject&order=' + window.currentOrderProof.orderNumber;
            }
        }

        // Auto refresh halaman setiap 5 menit untuk update notifikasi
        setTimeout(function() {
            location.reload();
        }, 300000); // 5 menit
    </script>
</body>

</html>