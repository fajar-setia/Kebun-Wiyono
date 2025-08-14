<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}
require '../config.php';

// Get product ID from URL
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($product_id <= 0) {
    header("Location: proses_tambah_produk.php");
    exit;
}

// Get product details
$result = $conn->query("SELECT p.*, k.nama_kategori 
                        FROM produk p
                        LEFT JOIN kategori k ON p.id_kategori = k.id_kategori
                        WHERE p.id = $product_id");

if (!$result || $result->num_rows == 0) {
    header("Location: proses_tambah_produk.php");
    exit;
}

$product = $result->fetch_assoc();

// Get notification counts
$resultPending = mysqli_query($conn, "SELECT COUNT(*) as total FROM pesanan WHERE status = 'pending'");
$pendingOrders = mysqli_fetch_assoc($resultPending)['total'];

$resultTotal = mysqli_query($conn, "SELECT COUNT(*) as total FROM pesanan");
$totalOrders = mysqli_fetch_assoc($resultTotal)['total'];

$total_notifications = $totalOrders + $pendingOrders;

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Produk - <?= htmlspecialchars($product['nama_produk']) ?> | Kebun Wiyono</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin/tambah_produk.css">
    <link rel="stylesheet" href="../assets/css/admin/detail_produk.css">
</head>
<body>
    <!-- Mobile Toggle Button -->
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
            <li class="nav-item"><a href="dashboard.php" class="nav-link text-white"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
            <li class="nav-item"><a href="proses_tambah_produk.php" class="nav-link text-white active"><i class="fas fa-leaf me-2"></i>Produk</a></li>
            <li class="nav-item"><a href="kategori.php" class="nav-link text-white"><i class="fas fa-tags me-2"></i>Kategori</a></li>
            <li class="nav-item">
                <a href="pesanan.php" class="nav-link text-white">
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

    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="mb-2"><i class="fas fa-eye me-3"></i>Detail Produk</h1>
                    <p class="mb-0 opacity-75">Informasi lengkap produk <?= htmlspecialchars($product['nama_produk']) ?></p>
                </div>
                <div class="action-buttons">
                    <a href="proses_tambah_produk.php" class="btn btn-secondary btn-action">
                        <i class="fas fa-arrow-left me-2"></i>Kembali
                    </a>
                    <button class="btn btn-warning btn-action" onclick="editProduct()">
                        <i class="fas fa-edit me-2"></i>Edit
                    </button>
                    <button class="btn btn-danger btn-action" onclick="deleteProduct()">
                        <i class="fas fa-trash me-2"></i>Hapus
                    </button>
                </div>
            </div>
        </div>

        <!-- Product Stats -->
        <div class="product-stats">
            <div class="stat-item">
                <i class="fas fa-leaf"></i>
                <h5>ID Produk</h5>
                <p class="text-muted mb-0">#<?= $product['id'] ?></p>
            </div>
            <div class="stat-item">
                <i class="fas fa-calendar-alt"></i>
                <h5>Status</h5>
                <span class="badge bg-success">Aktif</span>
            </div>
            <div class="stat-item">
                <i class="fas fa-tags"></i>
                <h5>Kategori</h5>
                <p class="text-muted mb-0"><?= htmlspecialchars($product['nama_kategori'] ?? 'Tidak ada kategori') ?></p>
            </div>
        </div>

        <!-- Product Details -->
        <div class="row">
            <!-- Product Image -->
            <div class="col-lg-6">
                <div class="content-card">
                    <?php if (!empty($product['gambar']) && file_exists("gambarProduk/" . $product['gambar'])): ?>
                        <img src="gambarProduk/<?= htmlspecialchars($product['gambar']) ?>" 
                             alt="<?= htmlspecialchars($product['nama_produk']) ?>" 
                             class="product-detail-image">
                    <?php else: ?>
                        <div class="no-image-placeholder">
                            <i class="fas fa-image"></i>
                            <h5>Tidak ada gambar</h5>
                            <p class="text-muted">Gambar produk tidak tersedia</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Product Info -->
            <div class="col-lg-6 mt-3">
                <div class="product-info-card">
                    <h2 class="mb-3"><?= htmlspecialchars($product['nama_produk']) ?></h2>
                    
                    <div class="mb-3">
                        <span class="category-badge">
                            <i class="fas fa-tag me-2"></i>
                            <?= htmlspecialchars($product['nama_kategori'] ?? 'Tidak ada kategori') ?>
                        </span>
                    </div>
                    
                    <div class="mb-4">
                        <div class="price-badge">
                            <i class="fas fa-money-bill-wave me-2"></i>
                            Rp <?= number_format($product['harga'], 0, ',', '.') ?>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-3">
                        <div class="text-center">
                            <i class="fas fa-calendar-plus fa-2x mb-2"></i>
                            <p class="mb-0 small">Produk ID</p>
                            <strong>#<?= $product['id'] ?></strong>
                        </div>
                        <div class="text-center">
                            <i class="fas fa-check-circle fa-2x mb-2"></i>
                            <p class="mb-0 small">Status</p>
                            <strong>Aktif</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Description -->
        <div class="description-card">
            <h4 class="mb-3">
                <i class="fas fa-align-left me-2 text-primary"></i>
                Deskripsi Produk
            </h4>
            <div class="border-start border-primary border-4 ps-3">
                <p class="mb-0 text-muted" style="line-height: 1.8; font-size: 1.1rem;">
                    <?= nl2br(htmlspecialchars($product['deskripsi'])) ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar for mobile
        document.getElementById('toggleSidebar')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('show');
        });

        // Product actions
        function editProduct() {
            const id = <?= $product['id'] ?>;
            const nama = '<?= htmlspecialchars($product['nama_produk']) ?>';
            const deskripsi = '<?= htmlspecialchars($product['deskripsi']) ?>';
            const harga = <?= $product['harga'] ?>;
            const kategori = <?= $product['id_kategori'] ?? 0 ?>;
            
            const url = `edit_product.php?id=${encodeURIComponent(id)}&nama=${encodeURIComponent(nama)}&deskripsi=${encodeURIComponent(deskripsi)}&harga=${encodeURIComponent(harga)}&kategori=${encodeURIComponent(kategori)}`;
            window.location.href = url;
        }

        function deleteProduct() {
            const id = <?= $product['id'] ?>;
            const nama = '<?= htmlspecialchars($product['nama_produk']) ?>';
            
            if (confirm('Apakah Anda yakin ingin menghapus produk "' + nama + '"?\nTindakan ini tidak dapat dibatalkan!')) {
                window.location.href = 'proses_tambah_produk.php?delete=' + id;
            }
        }

        function duplicateProduct() {
            const id = <?= $product['id'] ?>;
            if (confirm('Apakah Anda ingin menduplikasi produk ini?')) {
                // Redirect to add form with pre-filled data
                const nama = '<?= htmlspecialchars($product['nama_produk']) ?>' + ' (Copy)';
                const deskripsi = '<?= htmlspecialchars($product['deskripsi']) ?>';
                const harga = <?= $product['harga'] ?>;
                const kategori = <?= $product['id_kategori'] ?? 0 ?>;
                
                const url = `proses_tambah_produk.php?duplicate=1&nama=${encodeURIComponent(nama)}&deskripsi=${encodeURIComponent(deskripsi)}&harga=${encodeURIComponent(harga)}&kategori=${encodeURIComponent(kategori)}`;
                window.location.href = url;
            }
        }

        function shareProduct() {
            const url = window.location.href;
            const title = 'Detail Produk: <?= htmlspecialchars($product['nama_produk']) ?>';
            
            if (navigator.share) {
                navigator.share({
                    title: title,
                    url: url
                });
            } else {
                // Fallback: copy to clipboard
                navigator.clipboard.writeText(url).then(() => {
                    alert('Link produk telah disalin ke clipboard!');
                });
            }
        }

        function exportProduct() {
            const productData = {
                id: <?= $product['id'] ?>,
                nama_produk: '<?= htmlspecialchars($product['nama_produk']) ?>',
                deskripsi: '<?= htmlspecialchars($product['deskripsi']) ?>',
                harga: <?= $product['harga'] ?>,
                kategori: '<?= htmlspecialchars($product['nama_kategori'] ?? 'Tidak ada kategori') ?>',
                gambar: '<?= htmlspecialchars($product['gambar']) ?>'
            };
            
            const dataStr = JSON.stringify(productData, null, 2);
            const dataUri = 'data:application/json;charset=utf-8,'+ encodeURIComponent(dataStr);
            
            const exportFileDefaultName = 'produk_<?= $product['id'] ?>.json';
            
            const linkElement = document.createElement('a');
            linkElement.setAttribute('href', dataUri);
            linkElement.setAttribute('download', exportFileDefaultName);
            linkElement.click();
        }

        // Image zoom functionality
        document.querySelector('.product-detail-image')?.addEventListener('click', function() {
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.innerHTML = `
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Gambar Produk</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body text-center">
                            <img src="${this.src}" class="img-fluid" alt="${this.alt}">
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
            
            modal.addEventListener('hidden.bs.modal', function() {
                document.body.removeChild(modal);
            });
        });
    </script>
</body>

</html>

<?php $conn->close(); ?>