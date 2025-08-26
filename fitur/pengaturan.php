<?php
session_start();
require '../config.php';

// Cek apakah user adalah admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../index.php");
    exit;
}

// Ambil data admin yang login
$admin_id = $_SESSION['admin_id'];

// PERBAIKAN: Jika session berisi boolean true, artinya login berhasil tapi ID tidak tersimpan
// Kita ambil admin pertama yang ada dan perbaiki session
if (is_bool($_SESSION['admin_id']) && $_SESSION['admin_id'] === true) {
    // Ambil admin pertama yang ada
    $firstAdminQuery = mysqli_query($conn, "SELECT * FROM admin LIMIT 1");
    $firstAdmin = mysqli_fetch_assoc($firstAdminQuery);
    
    if ($firstAdmin) {
        // Perbaiki session dengan ID yang benar
        $_SESSION['admin_id'] = $firstAdmin['id_admin'];
        $admin_id = $firstAdmin['id_admin'];
        $adminData = $firstAdmin;
    } else {
        $adminData = null;
    }
} else {
    // Proses normal jika session berisi ID yang benar
    $admin_id = (int)$admin_id;
    $adminQuery = mysqli_query($conn, "SELECT * FROM admin WHERE id_admin = '$admin_id'");
    $adminData = mysqli_fetch_assoc($adminQuery);
}

// Jika admin tidak ditemukan, redirect ke login
if (!$adminData) {
    echo "Admin tidak ditemukan di database. ID dari session: " . htmlspecialchars($_SESSION['admin_id']);
    session_destroy();
    header("Location: ../index.php");
    exit;
}

// Handle form submissions
$message = '';
$messageType = '';

// Update Profil Admin
if (isset($_POST['update_profile'])) {
    $new_email = mysqli_real_escape_string($conn, $_POST['email']);

    // Cek apakah email sudah digunakan admin lain
    $checkEmail = mysqli_query($conn, "SELECT id_admin FROM admin WHERE email = '$new_email' AND id_admin != '$admin_id'");

    if (mysqli_num_rows($checkEmail) > 0) {
        $message = 'Email sudah digunakan oleh admin lain!';
        $messageType = 'danger';
    } else {
        $updateProfile = mysqli_query($conn, "UPDATE admin SET email = '$new_email' WHERE id_admin = '$admin_id'");

        if ($updateProfile) {
            $message = 'Profil berhasil diperbarui!';
            $messageType = 'success';
            $adminData['email'] = $new_email;
        } else {
            $message = 'Gagal memperbarui profil!';
            $messageType = 'danger';
        }
    }
}

// Update Password
if (isset($_POST['update_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Verifikasi password lama
    if (!password_verify($current_password, $adminData['password'])) {
        $message = 'Password lama tidak sesuai!';
        $messageType = 'danger';
    } elseif ($new_password !== $confirm_password) {
        $message = 'Konfirmasi password tidak sesuai!';
        $messageType = 'danger';
    } elseif (strlen($new_password) < 6) {
        $message = 'Password minimal 6 karakter!';
        $messageType = 'danger';
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $updatePassword = mysqli_query($conn, "UPDATE admin SET password = '$hashed_password' WHERE id_admin = '$admin_id'");

        if ($updatePassword) {
            $message = 'Password berhasil diperbarui!';
            $messageType = 'success';
            // Update data admin di variabel lokal
            $adminData['password'] = $hashed_password;
        } else {
            $message = 'Gagal memperbarui password!';
            $messageType = 'danger';
        }
    }
}

// Statistik untuk dashboard - dengan error handling
$totalUsersResult = mysqli_query($conn, "SELECT COUNT(*) as total FROM pengguna");
$totalUsers = $totalUsersResult ? mysqli_fetch_assoc($totalUsersResult)['total'] : 0;

$totalProductsResult = mysqli_query($conn, "SELECT COUNT(*) as total FROM produk");
$totalProducts = $totalProductsResult ? mysqli_fetch_assoc($totalProductsResult)['total'] : 0;

$totalOrdersResult = mysqli_query($conn, "SELECT COUNT(*) as total FROM pesanan");
$totalOrders = $totalOrdersResult ? mysqli_fetch_assoc($totalOrdersResult)['total'] : 0;

$totalCategoriesResult = mysqli_query($conn, "SELECT COUNT(*) as total FROM kategori");
$totalCategories = $totalCategoriesResult ? mysqli_fetch_assoc($totalCategoriesResult)['total'] : 0;

// Total pesanan dan notifikasi - dengan error handling
$resultTotal = mysqli_query($conn, "SELECT COUNT(*) as total FROM pesanan");
$totalOrdersNotif = $resultTotal ? mysqli_fetch_assoc($resultTotal)['total'] : 0;

$resultPending = mysqli_query($conn, "SELECT COUNT(*) as total FROM pesanan WHERE status = 'pending'");
$pendingOrders = $resultPending ? mysqli_fetch_assoc($resultPending)['total'] : 0;

$total_notifications = $totalOrdersNotif + $pendingOrders;

// Server information dengan null checking
$server_software = isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'Unknown';
$mysql_version = mysqli_get_server_info($conn);

date_default_timezone_set('Asia/Jakarta');
$timezone = date_default_timezone_get();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan | Kebun Wiyono</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin/pengaturan.css">
</head>

<body>
    <!-- Toggle Button for Mobile -->
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
            <li class="nav-item"><a href="#" class="nav-link text-white active"><i class="fas fa-cog me-2"></i>Pengaturan</a></li>
            <li class="nav-item"><a href="../logout.php" class="nav-link text-white"><i class="fas fa-sign-out-alt me-2"></i>Keluar</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="content">
        <!-- Page Header -->
        <div class="page-header fade-in">
            <h1><i class="fas fa-cog me-3"></i>Pengaturan</h1>
            <p class="welcome-text">Kelola pengaturan sistem dan profil admin Anda.</p>
        </div>

        <!-- Alert Messages -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show fade-in" role="alert">
                <i class="fas <?= $messageType == 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle' ?> me-2"></i>
                <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics Overview -->
        <div class="stats-overview fade-in">
            <div class="stat-overview-card users">
                <div class="stat-number"><?= $totalUsers ?></div>
                <div class="stat-label">Total Pengguna</div>
            </div>
            <div class="stat-overview-card products">
                <div class="stat-number"><?= $totalProducts ?></div>
                <div class="stat-label">Total Produk</div>
            </div>
            <div class="stat-overview-card orders">
                <div class="stat-number"><?= $totalOrders ?></div>
                <div class="stat-label">Total Pesanan</div>
            </div>
            <div class="stat-overview-card categories">
                <div class="stat-number"><?= $totalCategories ?></div>
                <div class="stat-label">Total Kategori</div>
            </div>
        </div>

        <!-- Settings Forms -->
        <div class="settings-grid fade-in">
            <!-- Update Profile Card -->
            <div class="settings-card">
                <div class="card-header">
                    <h5><i class="fas fa-user-edit"></i>Profil Admin</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-group">
                            <label for="email" class="form-label">Email Admin</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($adminData['email'] ?? '') ?>" required>
                            <small class="form-text text-muted">Email ini akan digunakan untuk login ke panel admin.</small>
                        </div>
                        <button type="submit" name="update_profile" class="btn btn-custom w-100">
                            <i class="fas fa-save me-2"></i>Simpan Perubahan
                        </button>
                    </form>
                </div>
            </div>

            <!-- Update Password Card -->
            <div class="settings-card">
                <div class="card-header">
                    <h5><i class="fas fa-key"></i>Ubah Password</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-group">
                            <label for="current_password" class="form-label">Password Lama</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        <div class="form-group">
                            <label for="new_password" class="form-label">Password Baru</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" minlength="6" required>
                            <small class="form-text text-muted">Password minimal 6 karakter.</small>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="6" required>
                        </div>
                        <button type="submit" name="update_password" class="btn btn-custom w-100">
                            <i class="fas fa-lock me-2"></i>Update Password
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- System Information -->
        <div class="system-info fade-in">
            <h5 class="mb-4"><i class="fas fa-info-circle me-2"></i>Informasi Sistem</h5>
            <div class="info-item">
                <span class="info-label">Versi PHP</span>
                <span class="info-value"><?= phpversion() ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Server</span>
                <span class="info-value"><?= $server_software ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Database</span>
                <span class="info-value">MySQL <?= $mysql_version ?></span>
            </div>
            
            <div class="info-item">
                <span class="info-label">Zona Waktu Server</span>
                <span class="info-value"><?= $timezone; ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Waktu Server</span>
                <span class="info-value"><?= date('Y-m-d H:i:s') ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Admin Login</span>
                <span class="info-value"><?= htmlspecialchars($adminData['email'] ?? 'N/A') ?></span>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Toggle Sidebar for Mobile
        const toggleBtn = document.getElementById('toggleSidebar');
        const sidebar = document.getElementById('sidebar');

        if (toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                sidebar.classList.toggle('show');
            });
        }

        // Close sidebar when clicking outside
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
                    sidebar.classList.remove('show');
                }
            }
        });

        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;

            if (newPassword !== confirmPassword) {
                this.setCustomValidity('Password tidak cocok');
            } else {
                this.setCustomValidity('');
            }
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>

</html>