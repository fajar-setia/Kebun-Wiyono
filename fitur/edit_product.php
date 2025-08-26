<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}
require '../config.php';

// Inisialisasi variabel alert
$alertMessage = '';
$alertType = '';

// Get product ID from URL
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($product_id <= 0) {
    header("Location: proses_tambah_produk.php");
    exit;
}

// Get product data
$product_query = "SELECT p.*, k.nama_kategori 
                  FROM produk p 
                  LEFT JOIN kategori k ON p.id_kategori = k.id_kategori 
                  WHERE p.id = $product_id";
$product_result = $conn->query($product_query);

if (!$product_result || $product_result->num_rows == 0) {
    $_SESSION['alert_message'] = "❌ Produk tidak ditemukan!";
    $_SESSION['alert_type'] = "danger";
    header("Location: proses_tambah_produk.php");
    exit;
}

$product = $product_result->fetch_assoc();

// Handle form submission for updating product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    if (isset($_POST['nama_produk'], $_POST['deskripsi'], $_POST['harga'], $_POST['id_kategori'])) {
        $nama_produk = htmlspecialchars($_POST['nama_produk']);
        $deskripsi   = htmlspecialchars($_POST['deskripsi']);
        $harga       = intval($_POST['harga']);
        $id_kategori = intval($_POST['id_kategori']);
        
        $gambar_baru = null;
        $gambar_lama = $product['gambar'];

        // Handle image upload if new image is provided
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
            // Check upload folder
            $uploadDir = "gambarProduk/";
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $nama_file = time() . '_' . basename($_FILES['gambar']['name']);
            $tmp_file  = $_FILES['gambar']['tmp_name'];
            $path      = $uploadDir . $nama_file;

            // Validate file type
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            $file_type = $_FILES['gambar']['type'];
            
            if (in_array($file_type, $allowed_types)) {
                // Upload new image
                if (move_uploaded_file($tmp_file, $path)) {
                    $gambar_baru = $nama_file;
                    
                    // Delete old image if exists
                    if (!empty($gambar_lama) && file_exists($uploadDir . $gambar_lama)) {
                        unlink($uploadDir . $gambar_lama);
                    }
                } else {
                    $alertMessage = "❌ Gagal mengunggah gambar baru.";
                    $alertType = "danger";
                }
            } else {
                $alertMessage = "❌ Format gambar tidak valid. Gunakan JPG, PNG, atau GIF.";
                $alertType = "danger";
            }
        }

        // Update database if no error occurred
        if (empty($alertMessage)) {
            $gambar_update = $gambar_baru ? ", gambar = '$gambar_baru'" : "";
            
            $sql = "UPDATE produk SET 
                    nama_produk = '$nama_produk', 
                    deskripsi = '$deskripsi', 
                    harga = $harga, 
                    id_kategori = $id_kategori
                    $gambar_update
                    WHERE id = $product_id";

            if ($conn->query($sql) === TRUE) {
                $_SESSION['alert_message'] = "✅ Produk <strong>$nama_produk</strong> berhasil diperbarui!";
                $_SESSION['alert_type'] = "success";
                header("Location: proses_tambah_produk.php");
                exit;
            } else {
                $alertMessage = "❌ Terjadi kesalahan: " . $conn->error;
                $alertType = "danger";
            }
        }
    } else {
        $alertMessage = "⚠️ Semua field wajib diisi!";
        $alertType = "warning";
    }
    
    // Refresh product data if update failed
    $product_result = $conn->query($product_query);
    $product = $product_result->fetch_assoc();
}

// Get notification counts
$resultPending = mysqli_query($conn, "SELECT COUNT(*) as total FROM pesanan WHERE status = 'pending'");
$pendingOrders = mysqli_fetch_assoc($resultPending)['total'];

$resultTotal = mysqli_query($conn, "SELECT COUNT(*) as total FROM pesanan");
$totalOrders = mysqli_fetch_assoc($resultTotal)['total'];

$total_notifications = $totalOrders + $pendingOrders;

// Get all categories for form
$kategori_result = $conn->query("SELECT * FROM kategori ORDER BY nama_kategori");
$kategoris = [];
while ($kategori = $kategori_result->fetch_assoc()) {
    $kategoris[] = $kategori;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Produk - Kebun Wiyono</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin/tambah_produk.css">
    <style>
        .product-preview {
            max-width: 200px;
            max-height: 200px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #e9ecef;
        }
        .preview-container {
            position: relative;
            display: inline-block;
        }
        .preview-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
            border-radius: 8px;
        }
        .preview-container:hover .preview-overlay {
            opacity: 1;
        }
        .form-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .section-title {
            color: #2d5a27;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
        }
    </style>
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
                    <h1 class="mb-2"><i class="fas fa-edit me-3"></i>Edit Produk</h1>
                    <p class="mb-0 opacity-75">Perbarui informasi produk <?= htmlspecialchars($product['nama_produk']) ?></p>
                </div>
                <div>
                    <a href="proses_tambah_produk.php" class="btn btn-secondary me-2">
                        <i class="fas fa-arrow-left me-2"></i>Kembali
                    </a>
                </div>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if (!empty($alertMessage)) : ?>
            <div class="alert alert-<?php echo $alertType; ?> alert-dismissible fade show" role="alert">
                <?php echo $alertMessage; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Edit Form -->
        <form action="" method="POST" enctype="multipart/form-data" id="editForm">
            <input type="hidden" name="action" value="update">
            
            <!-- Current Image Section -->
            <div class="form-section">
                <h4 class="section-title"><i class="fas fa-image me-2"></i>Gambar Produk Saat Ini</h4>
                <div class="row">
                    <div class="col-md-4">
                        <?php if (!empty($product['gambar'])): ?>
                            <div class="preview-container">
                                <img src="gambarProduk/<?= htmlspecialchars($product['gambar']) ?>" 
                                     alt="<?= htmlspecialchars($product['nama_produk']) ?>" 
                                     class="product-preview img-fluid">
                                <div class="preview-overlay">
                                    <span class="text-white"><i class="fas fa-eye fa-2x"></i></span>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="product-preview bg-light d-flex align-items-center justify-content-center">
                                <i class="fas fa-image fa-3x text-muted"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="gambar" class="form-label">Ganti Gambar (Opsional)</label>
                            <input class="form-control" type="file" id="gambar" name="gambar" accept="image/*">
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i>
                                Kosongkan jika tidak ingin mengganti gambar. Format yang didukung: JPG, PNG, GIF (Max: 5MB)
                            </div>
                        </div>
                        <div id="imagePreview" class="mt-3" style="display: none;">
                            <p class="text-muted mb-2">Preview gambar baru:</p>
                            <img id="previewImg" src="" alt="Preview" class="product-preview">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Information Section -->
            <div class="form-section">
                <h4 class="section-title"><i class="fas fa-info-circle me-2"></i>Informasi Produk</h4>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="nama_produk" class="form-label">Nama Produk *</label>
                            <input type="text" class="form-control" id="nama_produk" name="nama_produk" 
                                   value="<?= htmlspecialchars($product['nama_produk']) ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="id_kategori" class="form-label">Kategori *</label>
                            <select class="form-select" id="id_kategori" name="id_kategori" required>
                                <option value="">-- Pilih Kategori --</option>
                                <?php foreach ($kategoris as $kategori) : ?>
                                    <option value="<?php echo $kategori['id_kategori']; ?>" 
                                            <?= ($kategori['id_kategori'] == $product['id_kategori']) ? 'selected' : '' ?>>
                                        <?php echo htmlspecialchars($kategori['nama_kategori']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="deskripsi" class="form-label">Deskripsi *</label>
                    <textarea class="form-control" id="deskripsi" name="deskripsi" rows="4" required><?= htmlspecialchars($product['deskripsi']) ?></textarea>
                    <div class="form-text">
                        <span id="charCount">0</span> karakter
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="harga" class="form-label">Harga (Rp) *</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control" id="harga" name="harga" 
                                       value="<?= $product['harga'] ?>" min="0" required>
                            </div>
                            <div class="form-text">
                                <span id="hargaFormat">Rp <?= number_format($product['harga'], 0, ',', '.') ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="form-section">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted">
                            <i class="fas fa-clock me-1"></i>
                            Terakhir diubah: <?= date('d M Y H:i', strtotime($product['created_at'] ?? 'now')) ?>
                        </small>
                    </div>
                    <div>
                        <a href="proses_tambah_produk.php" class="btn btn-secondary me-2">
                            <i class="fas fa-times me-2"></i>Batal
                        </a>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="fas fa-save me-2"></i>Simpan Perubahan
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
    <script>
        // Toggle sidebar for mobile
        document.getElementById('toggleSidebar')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('show');
        });

        // Image preview functionality
        document.getElementById('gambar').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('imagePreview');
            const previewImg = document.getElementById('previewImg');
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
            }
        });

        // Character count for description
        const deskripsiField = document.getElementById('deskripsi');
        const charCount = document.getElementById('charCount');
        
        function updateCharCount() {
            charCount.textContent = deskripsiField.value.length;
        }
        
        deskripsiField.addEventListener('input', updateCharCount);
        updateCharCount(); // Initialize count

        // Format price display
        const hargaField = document.getElementById('harga');
        const hargaFormat = document.getElementById('hargaFormat');
        
        function formatRupiah(angka) {
            return 'Rp ' + angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }
        
        hargaField.addEventListener('input', function() {
            const value = parseInt(this.value) || 0;
            hargaFormat.textContent = formatRupiah(value);
        });

        // Form validation
        document.getElementById('editForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Menyimpan...';
            submitBtn.disabled = true;
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Confirm before leaving if form has changes
        let formChanged = false;
        const formInputs = document.querySelectorAll('#editForm input, #editForm textarea, #editForm select');
        
        formInputs.forEach(input => {
            input.addEventListener('change', () => {
                formChanged = true;
            });
        });

        window.addEventListener('beforeunload', function(e) {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        // Reset form changed flag on submit
        document.getElementById('editForm').addEventListener('submit', function() {
            formChanged = false;
        });
    </script>
</body>
</html>

<?php $conn->close(); ?>