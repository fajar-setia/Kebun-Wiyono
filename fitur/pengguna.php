<?php
session_start();
require '../config.php';

// Cek apakah user adalah admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../index.php");
    exit;
}

// Pagination dan Search
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';

// Query untuk menghitung total pengguna
$countQuery = "SELECT COUNT(*) as total FROM pengguna WHERE 1=1";
$whereClause = "";

if (!empty($search)) {
    $whereClause .= " AND (username LIKE '%$search%' OR nama_lengkap LIKE '%$search%' OR email LIKE '%$search%' OR telepon LIKE '%$search%')";
}

if (!empty($filter_status)) {
    if ($filter_status == 'complete') {
        $whereClause .= " AND nama_lengkap IS NOT NULL AND telepon IS NOT NULL AND alamat IS NOT NULL";
    } elseif ($filter_status == 'incomplete') {
        $whereClause .= " AND (nama_lengkap IS NULL OR telepon IS NULL OR alamat IS NULL)";
    }
}

$countResult = mysqli_query($conn, $countQuery . $whereClause);
$totalUsers = mysqli_fetch_assoc($countResult)['total'];
$totalPages = ceil($totalUsers / $limit);

// Query untuk mengambil data pengguna
$usersQuery = "SELECT * FROM pengguna WHERE 1=1" . $whereClause . " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$usersResult = mysqli_query($conn, $usersQuery);

// Statistik pengguna
$statsQuery = mysqli_query($conn, "
    SELECT 
        COUNT(*) as total_users,
        COUNT(CASE WHEN nama_lengkap IS NOT NULL AND telepon IS NOT NULL AND alamat IS NOT NULL THEN 1 END) as complete_profiles,
        COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as new_today,
        COUNT(CASE WHEN DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as new_this_week
    FROM pengguna
");
$stats = mysqli_fetch_assoc($statsQuery);

// Total pesanan dan notifikasi
$resultTotal = mysqli_query($conn, "SELECT COUNT(*) as total FROM pesanan");
$totalOrders = mysqli_fetch_assoc($resultTotal)['total'];
$resultPending = mysqli_query($conn, "SELECT COUNT(*) as total FROM pesanan WHERE status = 'pending'");
$pendingOrders = mysqli_fetch_assoc($resultPending)['total'];
$total_notifications = $totalOrders + $pendingOrders;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengguna | Kebun Wiyono</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="../assets/css/admin/pengguna.css">
</head>
<style>
   
</style>
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
            <li class="nav-item"><a href="#" class="nav-link text-white active"><i class="fas fa-users me-2"></i>Pengguna</a></li>
            <li class="nav-item"><a href="pengaturan.php" class="nav-link text-white"><i class="fas fa-cog me-2"></i>Pengaturan</a></li>
            <li class="nav-item"><a href="../logout.php" class="nav-link text-white"><i class="fas fa-sign-out-alt me-2"></i>Keluar</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="content">
        <!-- Page Header -->
        <div class="page-header fade-in">
            <h1><i class="fas fa-users me-3"></i>Pengguna</h1>
            <p class="welcome-text">Kelola dan pantau data pengguna yang terdaftar di sistem Kebun Wiyono.</p>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid fade-in">
            <div class="stat-card primary">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?= $stats['total_users'] ?></div>
                        <div class="stat-label">Total Pengguna</div>
                        <div class="stat-change positive">
                            <i class="fas fa-users me-1"></i>Terdaftar
                        </div>
                    </div>
                    <div class="stat-icon primary">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card success">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?= $stats['complete_profiles'] ?></div>
                        <div class="stat-label">Profil Lengkap</div>
                        <div class="stat-change positive">
                            <i class="fas fa-check-circle me-1"></i><?= round(($stats['complete_profiles']/$stats['total_users'])*100, 1) ?>%
                        </div>
                    </div>
                    <div class="stat-icon success">
                        <i class="fas fa-user-check"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card warning">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?= $stats['new_today'] ?></div>
                        <div class="stat-label">Daftar Hari Ini</div>
                        <div class="stat-change positive">
                            <i class="fas fa-calendar-day me-1"></i><?= date('d M Y') ?>
                        </div>
                    </div>
                    <div class="stat-icon warning">
                        <i class="fas fa-user-plus"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card info">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?= $stats['new_this_week'] ?></div>
                        <div class="stat-label">Daftar Minggu Ini</div>
                        <div class="stat-change positive">
                            <i class="fas fa-calendar-week me-1"></i>7 hari terakhir
                        </div>
                    </div>
                    <div class="stat-icon info">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users Table -->
        <div class="table-card fade-in">
            <div class="table-header">
                <h3 class="table-title"><i class="fas fa-list me-2"></i>Daftar Pengguna</h3>
                <div class="table-filters">
                    <form method="GET" class="d-flex gap-3 align-items-center">
                        <div class="search-box">
                            <input type="text" name="search" placeholder="Cari pengguna..." value="<?= htmlspecialchars($search) ?>" class="form-control">
                            <button type="submit" class="search-btn">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                        <select name="status" class="filter-select" onchange="this.form.submit()">
                            <option value="">Semua Status</option>
                            <option value="complete" <?= $filter_status == 'complete' ? 'selected' : '' ?>>Profil Lengkap</option>
                            <option value="incomplete" <?= $filter_status == 'incomplete' ? 'selected' : '' ?>>Profil Belum Lengkap</option>
                        </select>
                        <?php if (!empty($search) || !empty($filter_status)): ?>
                            <a href="?" class="btn btn-light btn-sm">
                                <i class="fas fa-times me-1"></i>Reset
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Pengguna</th>
                            <th>Kontak</th>
                            <th>Alamat</th>
                            <th>Status</th>
                            <th>Terdaftar</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($usersResult) > 0): ?>
                            <?php while ($user = mysqli_fetch_assoc($usersResult)): ?>
                                <?php
                                $isComplete = !empty($user['nama_lengkap']) && !empty($user['telepon']) && !empty($user['alamat']);
                                $avatar = !empty($user['nama_lengkap']) ? strtoupper(substr($user['nama_lengkap'], 0, 1)) : strtoupper(substr($user['username'], 0, 1));
                                ?>
                                <tr>
                                    <td>
                                        <div class="user-info">
                                            <div class="user-avatar">
                                                <?= $avatar ?>
                                            </div>
                                            <div class="user-details">
                                                <h6><?= htmlspecialchars($user['nama_lengkap'] ?: $user['username']) ?></h6>
                                                <small>@<?= htmlspecialchars($user['username']) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?= htmlspecialchars($user['email']) ?></strong><br>
                                            <small class="text-muted">
                                                <?= $user['telepon'] ? htmlspecialchars($user['telepon']) : 'Belum diisi' ?>
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?php if (!empty($user['alamat'])): ?>
                                                <?= htmlspecialchars(substr($user['alamat'], 0, 50)) ?><?= strlen($user['alamat']) > 50 ? '...' : '' ?><br>
                                                <?= htmlspecialchars($user['kota']) ?>, <?= htmlspecialchars($user['provinsi']) ?>
                                            <?php else: ?>
                                                <em>Belum diisi</em>
                                            <?php endif; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= $isComplete ? 'status-complete' : 'status-incomplete' ?>">
                                            <?= $isComplete ? 'Lengkap' : 'Belum Lengkap' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?= date('d M Y', strtotime($user['created_at'])) ?><br>
                                            <?= date('H:i', strtotime($user['created_at'])) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <button class="action-btn btn-view" 
                                                    title="Lihat Detail" 
                                                    onclick="viewUser(<?= $user['id'] ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="action-btn btn-delete" 
                                                    title="Hapus Pengguna" 
                                                    onclick="confirmDelete(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fas fa-users fa-3x mb-3"></i>
                                        <h5>Tidak ada pengguna ditemukan</h5>
                                        <p>Belum ada pengguna yang terdaftar atau sesuai dengan filter pencarian.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination-wrapper">
                    <div class="pagination-info">
                        Menampilkan <?= ($offset + 1) ?> - <?= min($offset + $limit, $totalUsers) ?> dari <?= $totalUsers ?> pengguna
                    </div>
                    <nav>
                        <ul class="pagination mb-0">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= ($page - 1) ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($filter_status) ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php
                            $start = max(1, $page - 2);
                            $end = min($totalPages, $page + 2);
                            for ($i = $start; $i <= $end; $i++):
                            ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($filter_status) ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= ($page + 1) ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($filter_status) ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
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
            
        });
        
        function viewUser(userId) {
            // Menggunakan AJAX untuk mengambil data pengguna
            fetch(`get_user_detail.php?id=${userId}`)
                .then(response => response.text()) // jangan langsung .json()
                .then(text => {
                    console.log('RESPON SERVER RAW:', text);
                    const data = JSON.parse(text); // parse manual

                    try {
                        const data = JSON.parse(text); // parsing manual
                        if (data.success) {
                            const user = data.user;
                            const url = `detail_pengguna.php?id=${encodeURIComponent(user.id)}`;
                            window.location.href = url;
                        } else {
                            alert('Gagal: ' + data.message);
                        }
                    } catch (err) {
                        console.error('JSON parse error:', err.message);
                        alert('Respon tidak valid:\n' + text); // tampilkan error sebenarnya
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    alert('Terjadi kesalahan saat mengambil data pengguna: ' + error.message);
                });

        }

        // Function untuk konfirmasi hapus pengguna
        function confirmDelete(userId, username) {
            if (confirm(`Apakah Anda yakin ingin menghapus pengguna "${username}"?\n\nTindakan ini tidak dapat dibatalkan!`)) {
                deleteUser(userId);
            }
        }

        // Function untuk menghapus pengguna
        function deleteUser(userId) {
            fetch('delete_user.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${userId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Pengguna berhasil dihapus');
                        location.reload(); // Refresh halaman
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat menghapus pengguna');
                });
        }
        
 </script>
</body>
</html>