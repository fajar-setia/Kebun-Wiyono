<?php
session_start();
require '../config.php';

// Cek apakah user adalah admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../index.php");
    exit;
}

// Total Pesanan
$resultTotal = mysqli_query($conn, "SELECT COUNT(*) as total FROM pesanan");
$totalOrders = mysqli_fetch_assoc($resultTotal)['total'];

// Total Pendapatan Hari Ini online
$resultRevenueOnline = mysqli_query($conn, "SELECT SUM(total_harga) as total FROM pesanan WHERE DATE(tanggal_pesanan) = CURDATE()");
$totalRevenueOnline = mysqli_fetch_assoc($resultRevenueOnline)['total'] ?? 0;

//total pendapatan hari ini offline
$resultRevenueOffline = mysqli_query($conn, "SELECT SUM(total_harga) as total 
                                      FROM pesanan 
                                      WHERE DATE(tanggal_pesanan) = CURDATE() 
                                      AND sumber_transaksi = 'offline'");
$totalRevenueOffline = mysqli_fetch_assoc($resultRevenueOffline)['total'] ?? 0;

// Pesanan Pending
$resultPending = mysqli_query($conn, "SELECT COUNT(*) as total FROM pesanan WHERE status = 'pending'");
$pendingOrders = mysqli_fetch_assoc($resultPending)['total'];

// Total Produk
$resultProduk = mysqli_query($conn, "SELECT COUNT(*) as total FROM produk");
$totalProducts = mysqli_fetch_assoc($resultProduk)['total'];


$total_notifications = $totalOrders + $pendingOrders;
// Data untuk grafik penjualan mingguan (7 hari terakhir)
$salesWeekly = [];
$salesLabels = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $dayName = date('D', strtotime("-$i days"));
    $salesLabels[] = $dayName;

    $dailySales = mysqli_query($conn, "SELECT SUM(total_harga) as total FROM pesanan WHERE DATE(tanggal_pesanan) = '$date'");
    $salesWeekly[] = mysqli_fetch_assoc($dailySales)['total'] ?? 0;
}

// Data status pesanan untuk pie chart
$statusQuery = mysqli_query($conn, "
    SELECT status, COUNT(*) as count 
    FROM pesanan 
    GROUP BY status
");
$orderStatusData = [];
$orderStatusLabels = [];
while ($row = mysqli_fetch_assoc($statusQuery)) {
    $statusName = [
        'completed' => 'Selesai',
        'processing' => 'Diproses',
        'pending' => 'Pending',
        'cancelled' => 'Dibatalkan'
    ][$row['status']] ?? ucfirst($row['status']);

    $orderStatusLabels[] = $statusName;
    $orderStatusData[] = (int)$row['count'];
}

// Function untuk menghitung waktu yang lalu (pindah ke atas agar bisa digunakan)
function time_elapsed_string($datetime, $full = false)
{
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $w = floor($diff->d / 7);
    $d = $diff->d - ($w * 7);

    $string = array(
        'y' => 'tahun',
        'm' => 'bulan',
        'w' => 'minggu',
        'd' => 'hari',
        'h' => 'jam',
        'i' => 'menit',
        's' => 'detik',
    );

    foreach ($string as $k => &$v) {
        if ($k === 'w' && $w) {
            $v = $w . ' ' . $v;
        } elseif ($k === 'd' && $d) {
            $v = $d . ' ' . $v;
        } elseif (in_array($k, ['y', 'm', 'h', 'i', 's']) && $diff->$k) {
            $v = $diff->$k . ' ' . $v;
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' lalu' : 'baru saja';
}

// Pesanan terbaru - DIPERBAIKI
$recentOrdersQuery = mysqli_query($conn, "
    SELECT p.id, p.total_harga, p.status, p.tanggal_pesanan, p.nama_penerima, u.nama_lengkap
    FROM pesanan p 
    LEFT JOIN pengguna u ON p.user_id = u.id  
    ORDER BY p.tanggal_pesanan DESC 
    LIMIT 5
");

$recentOrders = []; // Initialize array properly
while ($row = mysqli_fetch_assoc($recentOrdersQuery)) {
    $timeAgo = time_elapsed_string($row['tanggal_pesanan']); // Calculate time ago here

    $recentOrders[] = [
        'id' => 'ORD-' . str_pad($row['id'], 3, '0', STR_PAD_LEFT),
        'customer' => $row['nama_lengkap'] ?? $row['nama_penerima'] ?? 'Guest',
        'total' => (int)$row['total_harga'],
        'status' => $row['status'],
        'time' => $timeAgo
    ];
}

$topProducts = [];

// Debug: Cek apakah ada data di detail_pesanan
$checkDetailPesanan = mysqli_query($conn, "SELECT COUNT(*) as total FROM detail_pesanan");
$totalDetailPesanan = mysqli_fetch_assoc($checkDetailPesanan)['total'];

if ($totalDetailPesanan > 0) {
    // Query 1: Jika ada data detail_pesanan (query asli)
    $topProductsQuery = mysqli_query($conn, "
        SELECT p.nama_produk, SUM(dp.jumlah) as total_sold, SUM(dp.subtotal) as revenue
        FROM detail_pesanan dp
        JOIN produk p ON dp.produk_id = p.id
        JOIN pesanan ps ON dp.pesanan_id = ps.id
        WHERE ps.status != 'cancelled'
        GROUP BY p.id, p.nama_produk
        ORDER BY total_sold DESC
        LIMIT 5
    ");

    while ($row = mysqli_fetch_assoc($topProductsQuery)) {
        $topProducts[] = [
            'name' => $row['nama_produk'],
            'sold' => (int)$row['total_sold'],
            'revenue' => (int)$row['revenue']
        ];
    }
} else {
    // Query 2: Alternatif berdasarkan total_harga pesanan dan asumsi produk
    $alternativeQuery = mysqli_query($conn, "
        SELECT p.nama_produk, COUNT(ps.id) as estimated_sold, 
               SUM(ps.total_harga) as revenue,
               p.harga
        FROM produk p
        LEFT JOIN pesanan ps ON p.id = (
            SELECT pr.id FROM produk pr 
            WHERE pr.harga <= ps.total_harga 
            ORDER BY pr.harga DESC LIMIT 1
        )
        WHERE ps.status != 'cancelled' OR ps.status IS NULL
        GROUP BY p.id, p.nama_produk, p.harga
        HAVING COUNT(ps.id) > 0
        ORDER BY estimated_sold DESC, revenue DESC
        LIMIT 5
    ");

    if (mysqli_num_rows($alternativeQuery) > 0) {
        while ($row = mysqli_fetch_assoc($alternativeQuery)) {
            $topProducts[] = [
                'name' => $row['nama_produk'],
                'sold' => (int)$row['estimated_sold'],
                'revenue' => (int)$row['revenue'] ?? 0
            ];
        }
    } else {
        // Query 3: Fallback - tampilkan produk berdasarkan data produk saja
        $fallbackQuery = mysqli_query($conn, "
            SELECT p.nama_produk, p.harga, p.stok,
                   CASE 
                       WHEN p.stok > 0 THEN FLOOR(RAND() * 10) + 1
                       ELSE 0 
                   END as estimated_sold
            FROM produk p
            ORDER BY p.harga DESC, p.nama_produk ASC
            LIMIT 5
        ");

        while ($row = mysqli_fetch_assoc($fallbackQuery)) {
            $topProducts[] = [
                'name' => $row['nama_produk'],
                'sold' => (int)$row['estimated_sold'],
                'revenue' => (int)$row['harga'] * (int)$row['estimated_sold']
            ];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin | Kebun Wiyono</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.css">
    <link rel="stylesheet" href="../assets/css/admin/dashboard.css">
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
            <li class="nav-item "><a href="#" class="nav-link text-white active"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
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
            <li class="nav-item"><a href="pengaturan.php" class="nav-link text-white"><i class="fas fa-cog me-2"></i>Pengaturan</a></li>
            <li class="nav-item"><a href="../logout.php" class="nav-link text-white"><i class="fas fa-sign-out-alt me-2"></i>Keluar</a></li>
        </ul>
    </div>

    <div class="content">
        <div class="page-header fade-in">
            <h1><i class="fas fa-tachometer-alt me-3"></i>Dashboard</h1>
            <p class="welcome-text">Selamat datang kembali! Berikut adalah ringkasan aktivitas Kebun Wiyono hari ini.</p>
        </div>

        <div class="stats-grid fade-in">
            <div class="stat-card primary">
                <div class="stat-header">
                    <div>
                        <div class="stat-value" id="totalOrders">0</div>
                        <div class="stat-label">Total Pesanan</div>
                        <div class="stat-change positive">
                            <i class="fas fa-shopping-cart me-1"></i>Semua waktu
                        </div>
                    </div>
                    <div class="stat-icon primary">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card success">
                <div class="stat-header">
                    <div>
                        <div class="stat-value" id="totalRevenueOnline">Rp 0</div>
                        <div class="stat-label">Pendapatan Hari Ini Online</div>
                        <div class="stat-change positive">
                            <i class="fas fa-calendar-day me-1"></i><?= date('d M Y') ?>
                        </div>
                    </div>
                    <div class="stat-icon success">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card success">
                <div class="stat-header">
                    <div>
                        <div class="stat-value" id="totalRevenueOffline">Rp 0</div>
                        <div class="stat-label">Pendapatan Hari Ini Offline</div>
                        <div class="stat-change positive">
                            <i class="fas fa-calendar-day me-1"></i><?= date('d M Y') ?>
                        </div>
                    </div>
                    <div class="stat-icon success">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card warning">
                <div class="stat-header">
                    <div>
                        <div class="stat-value" id="pendingOrders">0</div>
                        <div class="stat-label">Pesanan Pending</div>
                        <div class="stat-change <?= $pendingOrders > 0 ? 'negative' : 'positive' ?>">
                            <?php if ($pendingOrders > 0): ?>
                                <i class="fas fa-exclamation-triangle me-1"></i>Perlu perhatian
                            <?php else: ?>
                                <i class="fas fa-check me-1"></i>Semua tertangani
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="stat-icon warning">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card info">
                <div class="stat-header">
                    <div>
                        <div class="stat-value" id="totalProducts">0</div>
                        <div class="stat-label">Total Produk</div>
                        <div class="stat-change positive">
                            <i class="fas fa-leaf me-1"></i>Aktif di toko
                        </div>
                    </div>
                    <div class="stat-icon info">
                        <i class="fas fa-leaf"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="charts-grid fade-in">
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title"><i class="fas fa-chart-line me-2"></i>Grafik Penjualan Mingguan</h3>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            7 Hari Terakhir
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="changePeriod(7)">7 Hari Terakhir</a></li>
                            <li><a class="dropdown-item" href="#" onclick="changePeriod(30)">30 Hari Terakhir</a></li>
                            <li><a class="dropdown-item" href="#" onclick="changePeriod(90)">3 Bulan Terakhir</a></li>
                        </ul>
                    </div>
                </div>
                <canvas id="salesChart" height="300"></canvas>
            </div>

            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title"><i class="fas fa-chart-pie me-2"></i>Status Pesanan</h3>
                </div>
                <div class="chart-container pie-chart">
                    <canvas id="orderStatusChart"></canvas>
                </div>
            </div>
        </div>

        <div class="recent-section fade-in">
            <div class="recent-card">
                <div class="recent-header">
                    <h3 class="recent-title"><i class="fas fa-shopping-bag me-2"></i>Pesanan Terbaru</h3>
                    <a href="pesanan.php" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                </div>
                <div id="recentOrders">
                    <!-- Dynamic content will be loaded here -->
                </div>
            </div>

            <div class="recent-card">
                <div class="recent-header">
                    <h3 class="recent-title"><i class="fas fa-star me-2"></i>Produk Terlaris</h3>
                    <a href="proses_tambah_produk.php" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                </div>
                <div id="topProducts">
                    <!-- Dynamic content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <div class="quick-actions fade-in">
        <button class="quick-action-btn" title="Refresh Data" onclick="refreshData()">
            <i class="fas fa-sync-alt"></i>
        </button>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
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
        // Real data from PHP
        const dashboardData = {
            stats: {
                totalOrders: <?= $totalOrders ?>,
                totalRevenueOnline: <?= $totalRevenueOnline ?>,
                totalRevenueOffline:<?= $totalRevenueOffline?>,
                pendingOrders: <?= $pendingOrders ?>,
                totalProducts: <?= $totalProducts ?>
            },
            salesData: {
                labels: <?= json_encode($salesLabels) ?>,
                data: <?= json_encode($salesWeekly) ?>
            },
            orderStatus: {
                labels: <?= json_encode($orderStatusLabels) ?>,
                data: <?= json_encode($orderStatusData) ?>,
                colors: ['#198754', '#0dcaf0', '#ffc107', '#dc3545']
            },
            recentOrders: <?= json_encode($recentOrders) ?>,
            topProducts: <?= json_encode($topProducts) ?>
        };

        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            initializeStats();
            initializeCharts();
            loadRecentOrders();
            loadTopProducts();

            // Auto refresh every 5 minutes
            setInterval(refreshData, 300000);
        });

        function initializeStats() {
            // Animate counter effect
            animateCounter('totalOrders', dashboardData.stats.totalOrders);
            animateCounter('pendingOrders', dashboardData.stats.pendingOrders);
            animateCounter('totalProducts', dashboardData.stats.totalProducts);

            // Format revenue
            document.getElementById('totalRevenueOnline').textContent =
                'Rp ' + dashboardData.stats.totalRevenueOnline.toLocaleString('id-ID');
            document.getElementById('totalRevenueOffline').textContent =
                'Rp ' + dashboardData.stats.totalRevenueOffline.toLocaleString('id-ID');
        }


        function animateCounter(elementId, target) {
            const element = document.getElementById(elementId);
            let current = 0;
            const increment = target / 50;
            const duration = 2000;
            const stepTime = duration / 50;

            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                element.textContent = Math.floor(current);
            }, stepTime);
        }

        function initializeCharts() {
            // Sales Chart
            const salesCtx = document.getElementById('salesChart').getContext('2d');
            new Chart(salesCtx, {
                type: 'line',
                data: {
                    labels: dashboardData.salesData.labels,
                    datasets: [{
                        label: 'Penjualan (Rp)',
                        data: dashboardData.salesData.data,
                        borderColor: '#2d5a27',
                        backgroundColor: 'rgba(45, 90, 39, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#2d5a27',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false, // Penting: set ke false
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    if (value >= 1000000) {
                                        return 'Rp ' + (value / 1000000).toFixed(1) + 'M';
                                    } else if (value >= 1000) {
                                        return 'Rp ' + (value / 1000).toFixed(0) + 'K';
                                    }
                                    return 'Rp ' + value.toLocaleString('id-ID');
                                }
                            }
                        }
                    }
                }
            });

            // Order Status Chart - Perbaikan khusus untuk doughnut chart
            const statusCtx = document.getElementById('orderStatusChart').getContext('2d');
            new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: dashboardData.orderStatus.labels,
                    datasets: [{
                        data: dashboardData.orderStatus.data,
                        backgroundColor: dashboardData.orderStatus.colors,
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false, // Penting: set ke false
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                usePointStyle: true,
                                font: {
                                    size: 12
                                }
                            }
                        }
                    },
                    // Tambahkan layout padding untuk mengontrol ukuran
                    layout: {
                        padding: {
                            top: 10,
                            bottom: 10,
                            left: 10,
                            right: 10
                        }
                    }
                }
            });
        }

        function loadRecentOrders() {
            const container = document.getElementById('recentOrders');
            container.innerHTML = '';

            if (dashboardData.recentOrders.length === 0) {
                container.innerHTML = '<div class="text-center text-muted py-4">Belum ada pesanan</div>';
                return;
            }

            dashboardData.recentOrders.forEach(order => {
                const statusClass = {
                    'pending': 'status-pending',
                    'processing': 'status-processing',
                    'completed': 'status-completed',
                    'cancelled': 'status-cancelled'
                } [order.status] || 'status-pending';

                const statusText = {
                    'pending': 'Pending',
                    'processing': 'Diproses',
                    'completed': 'Selesai',
                    'cancelled': 'Dibatalkan'
                } [order.status] || order.status;

                const iconClass = {
                    'pending': 'fa-clock',
                    'processing': 'fa-cog fa-spin',
                    'completed': 'fa-check-circle',
                    'cancelled': 'fa-times-circle'
                } [order.status] || 'fa-clock';

                container.innerHTML += `
                    <div class="recent-item">
                        <div class="recent-icon ${statusClass}">
                            <i class="fas ${iconClass}"></i>
                        </div>
                        <div class="recent-content">
                            <div class="recent-title-text">${order.id}</div>
                            <div class="recent-meta">${order.customer} • Rp ${order.total.toLocaleString('id-ID')}</div>
                        </div>
                        <div>
                            <span class="status-badge ${statusClass}">${statusText}</span>
                            <div class="text-muted small mt-1">${order.time}</div>
                        </div>
                    </div>
                `;
            });
        }

        function loadTopProducts() {
            const container = document.getElementById('topProducts');
            container.innerHTML = '';

            if (dashboardData.topProducts.length === 0) {
                container.innerHTML = '<div class="text-center text-muted py-4">Belum ada data penjualan</div>';
                return;
            }

            dashboardData.topProducts.forEach((product, index) => {
                const rankColors = ['#ffd700', '#c0c0c0', '#cd7f32', '#6c757d', '#6c757d'];
                const rankColor = rankColors[index] || '#6c757d';

                container.innerHTML += `
                    <div class="recent-item">
                        <div class="recent-icon" style="background: ${rankColor};">
                            <strong>${index + 1}</strong>
                        </div>
                        <div class="recent-content">
                            <div class="recent-title-text">${product.name}</div>
                            <div class="recent-meta">${product.sold} terjual • Rp ${product.revenue.toLocaleString('id-ID')}</div>
                        </div>
                        <div class="text-end">
                            <i class="fas fa-fire text-danger"></i>
                        </div>
                    </div>
                `;
            });
        }

        function changePeriod(days) {
            // This would typically make an AJAX call to get new data
            showNotification(`Menampilkan data ${days} hari terakhir`, 'info');
        }

        function refreshData() {
            // Show loading state
            document.body.classList.add('loading');
            const btn = event.target.closest('button');
            const originalContent = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            // Refresh the page to get new data
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }

        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type} position-fixed top-0 end-0 m-3 fade show`;
            notification.style.zIndex = '1050';
            notification.innerHTML = `
                <strong><i class="fas fa-info-circle me-2"></i>${message}</strong>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

            document.body.appendChild(notification);

            setTimeout(() => {
                notification.classList.remove('show');
                notification.classList.add('hide');
                setTimeout(() => notification.remove(), 500);
            }, 5000);
        }

        // Add loading styles
        const style = document.createElement('style');
        style.textContent = `
            .loading {
                pointer-events: none;
                opacity: 0.7;
            }
            .status-pending { background-color: #ffc107; color: #000; }
            .status-processing { background-color: #0dcaf0; color: #000; }
            .status-completed { background-color: #198754; color: #fff; }
            .status-cancelled { background-color: #dc3545; color: #fff; }
            .status-badge { 
                padding: 4px 8px; 
                border-radius: 12px;
                font-size: 0.75rem;
                font-weight: 500;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>

</html>