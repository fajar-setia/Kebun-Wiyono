<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}
require '../config.php';

// Check if table structure needs updating
$checkTable = $conn->query("SHOW COLUMNS FROM produk LIKE 'id_kategori'");
if ($checkTable->num_rows == 0) {
    // Column doesn't exist, add it
    $conn->query("ALTER TABLE produk ADD COLUMN id_kategori INT");
}

// Inisialisasi variabel alert
$alertMessage = '';
$alertType = '';

// Handle delete product
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = intval($_GET['delete']);

    // Get image name before deleting
    $getImage = $conn->query("SELECT gambar FROM produk WHERE id = $id");
    if ($getImage && $row = $getImage->fetch_assoc()) {
        $imagePath = "gambarProduk/" . $row['gambar'];
        if (file_exists($imagePath)) {
            unlink($imagePath); // Delete image file
        }
    }
    
    $conn->query("DELETE FROM pesanan_item WHERE produk_id = $id");

    $deleteQuery = "DELETE FROM produk WHERE id = $id";
    if ($conn->query($deleteQuery)) {
        $alertMessage = "✅ Produk berhasil dihapus!";
        $alertType = "success";
    } else {
        $alertMessage = "❌ Gagal menghapus produk: " . $conn->error;
        $alertType = "danger";
    }
}

// Handle form submission for adding product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    if (isset($_POST['nama_produk'], $_POST['deskripsi'], $_POST['harga'], $_POST['id_kategori'], $_FILES['gambar'])) {
        $nama_produk = htmlspecialchars($_POST['nama_produk']);
        $deskripsi   = htmlspecialchars($_POST['deskripsi']);
        $harga       = intval($_POST['harga']);
        $id_kategori = intval($_POST['id_kategori']);

        // Check upload folder
        $uploadDir = "gambarProduk/";
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $nama_file = basename($_FILES['gambar']['name']);
        $tmp_file  = $_FILES['gambar']['tmp_name'];
        $path      = $uploadDir . $nama_file;

        // Upload image
        if (move_uploaded_file($tmp_file, $path)) {
            $sql = "INSERT INTO produk (nama_produk, deskripsi, harga, gambar, id_kategori)
                    VALUES ('$nama_produk', '$deskripsi', '$harga', '$nama_file', '$id_kategori')";

            if ($conn->query($sql) === TRUE) {
                $alertMessage = "✅ Produk <strong>$nama_produk</strong> berhasil ditambahkan!";
                $alertType = "success";
            } else {
                $alertMessage = "❌ Terjadi kesalahan: " . $conn->error;
                $alertType = "danger";
            }
        } else {
            $alertMessage = "❌ Gagal mengunggah gambar. Pastikan format gambar valid.";
            $alertType = "danger";
        }
    } else {
        $alertMessage = "⚠️ Semua field wajib diisi!";
        $alertType = "warning";
    }
}

// Get notification counts
$resultPending = mysqli_query($conn, "SELECT COUNT(*) as total FROM pesanan WHERE status = 'pending'");
$pendingOrders = mysqli_fetch_assoc($resultPending)['total'];

$resultTotal = mysqli_query($conn, "SELECT COUNT(*) as total FROM pesanan");
$totalOrders = mysqli_fetch_assoc($resultTotal)['total'];

$total_notifications = $totalOrders + $pendingOrders;

// Get all categories for filter and form
$kategori_result = $conn->query("SELECT * FROM kategori ORDER BY nama_kategori");
$kategoris = [];
while ($kategori = $kategori_result->fetch_assoc()) {
    $kategoris[] = $kategori;
}

// Build query for products
$whereClause = "";
$conditions = [];

// Filter by category
$filter_kategori = isset($_GET['kategori']) ? intval($_GET['kategori']) : 0;
if ($filter_kategori > 0) {
    $conditions[] = "p.id_kategori = $filter_kategori";
}

// Search by product name
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
if (!empty($search)) {
    $conditions[] = "p.nama_produk LIKE '%$search%'";
}

if (!empty($conditions)) {
    $whereClause = "WHERE " . implode(" AND ", $conditions);
}

// Get products with pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$result = $conn->query("SELECT p.*, k.nama_kategori 
                        FROM produk p
                        LEFT JOIN kategori k ON p.id_kategori = k.id_kategori
                        $whereClause
                        ORDER BY p.id DESC
                        LIMIT $limit OFFSET $offset");

// Get total count for pagination
$countResult = $conn->query("SELECT COUNT(*) as total FROM produk p $whereClause");
$totalProducts = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalProducts / $limit);

// Reset kategori result for form
$kategori_result = $conn->query("SELECT * FROM kategori ORDER BY nama_kategori");

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Produk - Kebun Wiyono</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="../assets/css/admin/tambah_produk.css">
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
            <li class="nav-item "><a href="dashboard.php" class="nav-link text-white "><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
            <li class="nav-item"><a href="proses_tambah_produk.php" class="nav-link text-white active"><i class="fas fa-leaf me-2"></i>Produk</a></li>
            <li class="nav-item"><a href="digitalisasi_Pembukuan_offline.php" class="nav-link text-white"><i class="fas fa-book me-2"></i>Pembukuan Offline</a></li>
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
                    <h1 class="mb-2"><i class="fas fa-leaf me-3"></i>Manajemen Produk</h1>
                    <p class="mb-0 opacity-75">Kelola semua produk kebun Anda dengan mudah</p>
                </div>
                <button class="btn btn-light btn-lg" data-bs-toggle="modal" data-bs-target="#addProductModal">
                    <i class="fas fa-plus me-2"></i>Tambah Produk
                </button>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if (!empty($alertMessage)) : ?>
            <div class="alert alert-<?php echo $alertType; ?> alert-dismissible fade show" role="alert">
                <?php echo $alertMessage; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="stats-cards">
            <div class="stat-card">
                <h3><?= $totalProducts ?></h3>
                <p class="text-muted mb-0"><i class="fas fa-leaf me-2"></i>Total Produk</p>
            </div>
            <div class="stat-card">
                <h3><?= count($kategoris) ?></h3>
                <p class="text-muted mb-0"><i class="fas fa-tags me-2"></i>Kategori</p>
            </div>
            <div class="stat-card">
                <h3><?= $pendingOrders ?></h3>
                <p class="text-muted mb-0"><i class="fas fa-clock me-2"></i>Pesanan Pending</p>
            </div>
        </div>

        <!-- Products Table -->
        <div class="content-card">
            <div class="content-header">
                <h4 class="mb-0"><i class="fas fa-list me-2"></i>Daftar Produk</h4>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="search" placeholder="Cari nama produk..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="kategori">
                            <option value="">Semua Kategori</option>
                            <?php foreach ($kategoris as $kategori): ?>
                                <option value="<?= $kategori['id_kategori'] ?>" <?= ($filter_kategori == $kategori['id_kategori']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($kategori['nama_kategori']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i>Filter
                        </button>
                    </div>
                    <div class="col-md-3">
                        <a href="proses_tambah_produk.php" class="btn btn-secondary">
                            <i class="fas fa-refresh me-1"></i>Reset
                        </a>
                    </div>
                </form>
            </div>

            <!-- Table -->
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Gambar</th>
                            <th>Nama Produk</th>
                            <th>Kategori</th>
                            <th>Harga</th>
                            <th>Deskripsi</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php $no = $offset + 1; ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td>
                                        <?php if (!empty($row['gambar'])): ?>
                                            <img src="gambarProduk/<?= htmlspecialchars($row['gambar']) ?>"
                                                alt="<?= htmlspecialchars($row['nama_produk']) ?>"
                                                class="product-image">
                                        <?php else: ?>
                                            <div class="product-image bg-light d-flex align-items-center justify-content-center">
                                                <i class="fas fa-image text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($row['nama_produk']) ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?= htmlspecialchars($row['nama_kategori'] ?? 'Tidak ada kategori') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong class="text-success">Rp <?= number_format($row['harga'], 0, ',', '.') ?></strong>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?= strlen($row['deskripsi']) > 50 ? substr(htmlspecialchars($row['deskripsi']), 0, 50) . '...' : htmlspecialchars($row['deskripsi']) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <!-- disini ada problem -->
                                        <button class="btn btn-sm btn-warning btn-action"
                                            onclick="editProduct(<?= $row['id'] ?>, '<?= htmlspecialchars($row['nama_produk']) ?>', '<?= htmlspecialchars($row['deskripsi']) ?>', <?= $row['harga'] ?>, <?= $row['id_kategori'] ?? 0 ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-info btn-action"
                                            onclick="viewProduct(<?= $row['id'] ?>, '<?= htmlspecialchars($row['nama_produk']) ?>', '<?= htmlspecialchars($row['deskripsi']) ?>', <?= $row['harga'] ?>, <?= $row['id_kategori'] ?? 0 ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger btn-action"
                                            onclick="deleteProduct(<?= $row['id'] ?>, '<?= htmlspecialchars($row['nama_produk']) ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Tidak ada produk yang ditemukan</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav class="px-3 pb-3">
                    <ul class="pagination">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&kategori=<?= $filter_kategori ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&kategori=<?= $filter_kategori ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&kategori=<?= $filter_kategori ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Tambah Produk Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nama_produk" class="form-label">Nama Produk *</label>
                                    <input type="text" class="form-control" id="nama_produk" name="nama_produk" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="id_kategori" class="form-label">Kategori *</label>
                                    <select class="form-select" id="id_kategori" name="id_kategori" required>
                                        <option value="">-- Pilih Kategori --</option>
                                        <?php while ($kategori = $kategori_result->fetch_assoc()) : ?>
                                            <option value="<?php echo $kategori['id_kategori']; ?>">
                                                <?php echo htmlspecialchars($kategori['nama_kategori']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="deskripsi" class="form-label">Deskripsi *</label>
                            <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="harga" class="form-label">Harga (Rp) *</label>
                                    <input type="number" class="form-control" id="harga" name="harga" min="0" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="gambar" class="form-label">Gambar Produk *</label>
                                    <input class="form-control" type="file" id="gambar" name="gambar" accept="image/*" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Simpan Produk
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script>
        // Toggle sidebar for mobile
        document.getElementById('toggleSidebar')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('show');
        });

        // Product actions
        function editProduct(id, nama, deskripsi, harga, kategori) {
            const url = `edit_product.php?id=${encodeURIComponent(id)}&nama=${encodeURIComponent(nama)}&deskripsi=${encodeURIComponent(deskripsi)}&harga=${encodeURIComponent(harga)}&kategori=${encodeURIComponent(kategori)}`;
            window.location.href = url;
        }

        function viewProduct(id, nama, deskripsi, harga, kategori) {
            const url = `detail_produk.php?id=${encodeURIComponent(id)}&nama=${encodeURIComponent(nama)}&deskripsi=${encodeURIComponent(deskripsi)}&harga=${encodeURIComponent(harga)}&kategori=${encodeURIComponent(kategori)}`;
            window.location.href = url;
        }

        function deleteProduct(id, nama) {
            if (confirm('Apakah Anda yakin ingin menghapus produk "' + nama + '"?\nTindakan ini tidak dapat dibatalkan!')) {
                window.location.href = '?delete=' + id;
            }
        }

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Format number input
        document.getElementById('harga')?.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            e.target.value = value;
        });
    </script>
</body>

</html>

<?php $conn->close(); ?>