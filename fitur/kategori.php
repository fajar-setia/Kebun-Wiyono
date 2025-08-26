<?php
session_start();
require '../config.php';

// Cek apakah user adalah admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../index.php");
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $nama_kategori = mysqli_real_escape_string($conn, $_POST['nama_kategori']);
                $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);

                $query = "INSERT INTO kategori (nama_kategori, deskripsi) VALUES ('$nama_kategori', '$deskripsi')";
                if (mysqli_query($conn, $query)) {
                    $success = "Kategori berhasil ditambahkan";
                } else {
                    $error = "Gagal menambahkan kategori: " . mysqli_error($conn);
                }
                break;

            case 'edit':
                $id = (int)$_POST['id'];
                $nama_kategori = mysqli_real_escape_string($conn, $_POST['nama_kategori']);
                $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);

                $query = "UPDATE kategori SET nama_kategori='$nama_kategori', deskripsi='$deskripsi' WHERE id=$id";
                if (mysqli_query($conn, $query)) {
                    $success = "Kategori berhasil diperbarui";
                } else {
                    $error = "Gagal memperbarui kategori: " . mysqli_error($conn);
                }
                break;

            case 'delete':
                $id = (int)$_POST['id'];

                // Cek apakah kategori digunakan oleh produk
                $checkQuery = "SELECT COUNT(*) as count FROM produk WHERE id_kategori = $id";
                $checkResult = mysqli_query($conn, $checkQuery);
                $productCount = mysqli_fetch_assoc($checkResult)['count'];

                if ($productCount > 0) {
                    $error = "Kategori tidak dapat dihapus karena masih digunakan oleh $productCount produk";
                } else {
                    $query = "DELETE FROM kategori WHERE id_kategori = $id";
                    if (mysqli_query($conn, $query)) {
                        $success = "Kategori berhasil dihapus";
                    } else {
                        $error = "Gagal menghapus kategori: " . mysqli_error($conn);
                    }
                }
                break;
        }
    }
}

// Fetch kategori data
$kategorieQuery = "
    SELECT k.*, COUNT(p.id) as jumlah_produk 
    FROM kategori k 
    LEFT JOIN produk p ON k.id_kategori = p.id_kategori
    GROUP BY k.id_kategori
    ORDER BY k.nama_kategori ASC
";
$kategorieResult = mysqli_query($conn, $kategorieQuery);

// Statistik
$totalKategori = mysqli_num_rows($kategorieResult);
$totalProdukQuery = "SELECT COUNT(*) as total FROM produk";
$totalProduk = mysqli_fetch_assoc(mysqli_query($conn, $totalProdukQuery))['total'];

// Kategori terpopuler
$popularKategoriQuery = "
    SELECT k.nama_kategori, COUNT(p.id) as jumlah_produk
    FROM kategori k
    LEFT JOIN produk p ON k.id_kategori = p.id_kategori
    GROUP BY k.id_kategori
    ORDER BY jumlah_produk DESC
    LIMIT 5
";
$popularKategoriResult = mysqli_query($conn, $popularKategoriQuery);

// Notification count (for sidebar)
$resultPending = mysqli_query($conn, "SELECT COUNT(*) as total FROM pesanan WHERE status = 'pending'");
$pendingOrders = mysqli_fetch_assoc($resultPending)['total'];
$resultTotal = mysqli_query($conn, "SELECT COUNT(*) as total FROM pesanan");
$totalOrders = mysqli_fetch_assoc($resultTotal)['total'];
$total_notifications = $totalOrders + $pendingOrders;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategori | Kebun Wiyono</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin/kategori.css">

</head>
<body>
    <!-- Toggle Button -->
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
            <li class="nav-item"><a href="proses_tambah_produk.php" class="nav-link text-white"><i class="fas fa-leaf me-2"></i>Produk</a></li>
            <li class="nav-item"><a href="digitalisasi_Pembukuan_offline.php" class="nav-link text-white"><i class="fas fa-book me-2"></i>Pembukuan Offline</a></li>
            <li class="nav-item"><a href="kategori.php" class="nav-link text-white active"><i class="fas fa-tags me-2 active"></i>Kategori</a></li>
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

    <div class="content">
        <div class="page-header fade-in">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1><i class="fas fa-tags me-3"></i>Kategori Produk</h1>
                    <p class="welcome-text">Kelola kategori produk untuk mengorganisir toko Anda dengan lebih baik.</p>
                </div>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                    <i class="fas fa-plus me-2"></i>Tambah Kategori
                </button>
            </div>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="stats-mini fade-in">
            <div class="stat-mini">
                <div class="value"><?= $totalKategori ?></div>
                <div class="label">Total Kategori</div>
            </div>
            <div class="stat-mini">
                <div class="value"><?= $totalProduk ?></div>
                <div class="label">Total Produk</div>
            </div>
            <div class="stat-mini">
                <div class="value"><?= $totalKategori > 0 ? round($totalProduk / $totalKategori, 1) : 0 ?></div>
                <div class="label">Rata-rata Produk/Kategori</div>
            </div>
        </div>

        <!-- Popular Categories -->
        <?php if (mysqli_num_rows($popularKategoriResult) > 0): ?>
            <div class="popular-categories fade-in">
                <h5 class="mb-3"><i class="fas fa-fire me-2"></i>Kategori Terpopuler</h5>
                <?php while ($popular = mysqli_fetch_assoc($popularKategoriResult)): ?>
                    <div class="popular-item">
                        <span class="popular-name"><?= htmlspecialchars($popular['nama_kategori']) ?></span>
                        <span class="popular-count"><?= $popular['jumlah_produk'] ?> produk</span>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>

        <!-- Categories List -->
        <div class="fade-in">
            <?php if (mysqli_num_rows($kategorieResult) > 0): ?>
                <div class="row">
                    <?php
                    // Reset result pointer
                    mysqli_data_seek($kategorieResult, 0);
                    while ($kategori = mysqli_fetch_assoc($kategorieResult)):
                    ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="category-card">
                                <div class="category-header">
                                    <h5 class="category-title"><?= htmlspecialchars($kategori['nama_kategori']) ?></h5>
                                    <span class="category-count"><?= $kategori['jumlah_produk'] ?> produk</span>
                                </div>
                                <div class="category-description">
                                    <?= $kategori['deskripsi'] ? htmlspecialchars($kategori['deskripsi']) : '<em>Tidak ada deskripsi</em>' ?>
                                </div>
                                <div class="category-actions">
                                    <button class="btn btn-outline-primary btn-category" onclick="editCategory(<?= $kategori['id_kategori'] ?>, '<?= addslashes($kategori['nama_kategori']) ?>', '<?= addslashes($kategori['deskripsi']) ?>')">
                                        <i class="fas fa-edit me-1"></i>Edit
                                    </button>
                                    <button class="btn btn-outline-danger btn-category" onclick="deleteCategory(<?= $kategori['id_kategori'] ?>, '<?= addslashes($kategori['nama_kategori']) ?>', <?= $kategori['jumlah_produk'] ?>)">
                                        <i class="fas fa-trash me-1"></i>Hapus
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-tags"></i>
                    <h4>Belum Ada Kategori</h4>
                    <p>Mulai dengan menambahkan kategori pertama untuk mengorganisir produk Anda.</p>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        <i class="fas fa-plus me-2"></i>Tambah Kategori Pertama
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Tambah Kategori Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label for="nama_kategori" class="form-label">Nama Kategori</label>
                            <input type="text" class="form-control" id="nama_kategori" name="nama_kategori" required>
                        </div>
                        <div class="mb-3">
                            <label for="deskripsi" class="form-label">Deskripsi</label>
                            <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3" placeholder="Deskripsi kategori (opsional)"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">Simpan Kategori</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Kategori</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label for="edit_nama_kategori" class="form-label">Nama Kategori</label>
                            <input type="text" class="form-control" id="edit_nama_kategori" name="nama_kategori" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_deskripsi" class="form-label">Deskripsi</label>
                            <textarea class="form-control" id="edit_deskripsi" name="deskripsi" rows="3" placeholder="Deskripsi kategori (opsional)"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Perbarui Kategori</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Category Modal -->
    <div class="modal fade" id="deleteCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-trash me-2"></i>Hapus Kategori</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter: invert(1);"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="delete_id">
                        <div class="text-center">
                            <i class="fas fa-exclamation-triangle text-warning" style="font-size: 3rem;"></i>
                            <h4 class="mt-3">Apakah Anda yakin?</h4>
                            <p class="text-muted" id="delete_message">Kategori ini akan dihapus secara permanen.</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Ya, Hapus</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar
        const toggleBtn = document.getElementById('toggleSidebar');
        const sidebar = document.getElementById('sidebar');

        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('show');
        });

        // Close sidebar when clicking outside
        document.addEventListener('click', function(e) {
            if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
                sidebar.classList.remove('show');
            }
        });

        // Edit category function
        function editCategory(id, nama, deskripsi) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nama_kategori').value = nama;
            document.getElementById('edit_deskripsi').value = deskripsi;
            new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
        }

        // Delete category function
        function deleteCategory(id, nama, jumlahProduk) {
            document.getElementById('delete_id').value = id;

            let message = `Kategori "${nama}" akan dihapus secara permanen.`;
            if (jumlahProduk > 0) {
                message = `Kategori "${nama}" tidak dapat dihapus karena masih digunakan oleh ${jumlahProduk} produk. Silakan pindahkan produk ke kategori lain terlebih dahulu.`;
                document.querySelector('#deleteCategoryModal .btn-danger').style.display = 'none';
            } else {
                document.querySelector('#deleteCategoryModal .btn-danger').style.display = 'inline-block';
            }

            document.getElementById('delete_message').textContent = message;
            new bootstrap.Modal(document.getElementById('deleteCategoryModal')).show();
        }

        // Clear form when modal is closed
        document.getElementById('addCategoryModal').addEventListener('hidden.bs.modal', function() {
            this.querySelector('form').reset();
        });

        // Fade in animation
        document.addEventListener('DOMContentLoaded', function() {
            const elements = document.querySelectorAll('.fade-in');
            elements.forEach((element, index) => {
                element.style.opacity = '0';
                element.style.transform = 'translateY(20px)';
                element.style.transition = 'opacity 0.6s ease, transform 0.6s ease';

                setTimeout(() => {
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                }, index * 200);
            });
        });
    </script>
</body>

</html>