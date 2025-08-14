<?php
session_start();
require '../config.php';

// Cek login
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Validasi ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "ID tidak valid";
    exit;
}

$userId = (int)$_GET['id'];

// Ambil data pengguna dari DB
$query = "SELECT * FROM pengguna WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);
mysqli_close($conn);

if (!$user) {
    echo "Pengguna tidak ditemukan";
    exit;
}

// Status profil lengkap?
$requiredFields = ['nama_lengkap', 'telepon', 'alamat', 'email'];
$completedFields = 0;
foreach ($requiredFields as $field) {
    if (!empty($user[$field])) {
        $completedFields++;
    }
}
$completionPercentage = ($completedFields / count($requiredFields)) * 100;
$isComplete = $completionPercentage == 100;

// Format tanggal lahir
$tanggalLahir = '';
if ($user['tanggal_lahir']) {
    $tanggalLahir = date('d F Y', strtotime($user['tanggal_lahir']));
}

// Format jenis kelamin
$jenisKelamin = '';
if ($user['jenis_kelamin'] == 'L') {
    $jenisKelamin = 'Laki-laki';
} elseif ($user['jenis_kelamin'] == 'P') {
    $jenisKelamin = 'Perempuan';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pengguna - <?= htmlspecialchars($user['username']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            border-radius: 15px 15px 0 0;
        }
        .profile-img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .profile-img-placeholder {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 4px solid white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .info-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .info-card:hover {
            transform: translateY(-5px);
        }
        .info-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }
        .progress-thin {
            height: 8px;
            border-radius: 4px;
        }
        .badge-custom {
            font-size: 0.9em;
            padding: 0.5em 1em;
            border-radius: 20px;
        }
        .btn-custom {
            border-radius: 25px;
            padding: 10px 25px;
            transition: all 0.3s ease;
        }
        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .section-title {
            font-size: 1.3em;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: #2c3e50;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 0.5rem;
        }
        .data-row {
            padding: 1rem 0;
            border-bottom: 1px solid #f8f9fa;
        }
        .data-row:last-child {
            border-bottom: none;
        }
        .data-label {
            font-weight: 600;
            color: #495057;
        }
        .data-value {
            color: #6c757d;
        }
        .empty-value {
            color: #adb5bd;
            font-style: italic;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <a href="pengguna.php" class="btn btn-outline-secondary btn-custom">
                    <i class="fas fa-arrow-left me-2"></i>Kembali ke Daftar Pengguna
                </a>
            </div>
        </div>

        <div class="row">
            <!-- Profile Card -->
            <div class="col-lg-4 mb-4">
                <div class="card info-card">
                    <div class="profile-header text-center">
                        <div class="mb-3">
                            <?php if ($user['foto_profil']) : ?>
                                <img src="/uploads/foto_profil/<?= htmlspecialchars($user['foto_profil']) ?>"
                                    alt="Foto Profil" class="profile-img">
                            <?php else : ?>
                                <div class="profile-img-placeholder">
                                    <i class="fas fa-user fa-3x text-muted"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <h4 class="mb-2"><?= htmlspecialchars($user['username']) ?></h4>
                        <p class="mb-0 opacity-75">
                            <?= $user['nama_lengkap'] ? htmlspecialchars($user['nama_lengkap']) : 'Nama belum diisi' ?>
                        </p>
                    </div>
                    <div class="card-body">
                        <!-- Status Profil -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="data-label">Kelengkapan Profil</span>
                                <span class="badge badge-custom <?= $isComplete ? 'bg-success' : 'bg-warning' ?>">
                                    <?= round($completionPercentage) ?>%
                                </span>
                            </div>
                            <div class="progress progress-thin">
                                <div class="progress-bar <?= $isComplete ? 'bg-success' : 'bg-warning' ?>" 
                                     style="width: <?= $completionPercentage ?>%"></div>
                            </div>
                            <small class="text-muted">
                                <?= $completedFields ?> dari <?= count($requiredFields) ?> field terisi
                            </small>
                        </div>

                        <!-- Quick Info -->
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="border-end">
                                    <h6 class="text-muted mb-1">Bergabung</h6>
                                    <small><?= date('M Y', strtotime($user['created_at'])) ?></small>
                                </div>
                            </div>
                            <div class="col-6">
                                <h6 class="text-muted mb-1">Status</h6>
                                <small>
                                    <span class="badge <?= $isComplete ? 'bg-success' : 'bg-warning' ?>">
                                        <?= $isComplete ? 'Aktif' : 'Perlu Dilengkapi' ?>
                                    </span>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detail Information -->
            <div class="col-lg-8">
                <div class="card info-card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle me-2 text-primary"></i>
                            Informasi Detail
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Informasi Dasar -->
                        <div class="section-title">
                            <i class="fas fa-user me-2"></i>Informasi Dasar
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="data-row">
                                    <div class="data-label">Username</div>
                                    <div class="data-value"><?= htmlspecialchars($user['username']) ?></div>
                                </div>
                                <div class="data-row">
                                    <div class="data-label">Nama Lengkap</div>
                                    <div class="<?= $user['nama_lengkap'] ? 'data-value' : 'empty-value' ?>">
                                        <?= $user['nama_lengkap'] ? htmlspecialchars($user['nama_lengkap']) : 'Belum diisi' ?>
                                    </div>
                                </div>
                                <div class="data-row">
                                    <div class="data-label">Email</div>
                                    <div class="<?= $user['email'] ? 'data-value' : 'empty-value' ?>">
                                        <?= $user['email'] ? htmlspecialchars($user['email']) : 'Belum diisi' ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="data-row">
                                    <div class="data-label">Telepon</div>
                                    <div class="<?= $user['telepon'] ? 'data-value' : 'empty-value' ?>">
                                        <?= $user['telepon'] ? htmlspecialchars($user['telepon']) : 'Belum diisi' ?>
                                    </div>
                                </div>
                                <div class="data-row">
                                    <div class="data-label">Tanggal Lahir</div>
                                    <div class="<?= $tanggalLahir ? 'data-value' : 'empty-value' ?>">
                                        <?= $tanggalLahir ?: 'Belum diisi' ?>
                                    </div>
                                </div>
                                <div class="data-row">
                                    <div class="data-label">Jenis Kelamin</div>
                                    <div class="<?= $jenisKelamin ? 'data-value' : 'empty-value' ?>">
                                        <?= $jenisKelamin ?: 'Belum diisi' ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Informasi Alamat -->
                        <div class="section-title mt-4">
                            <i class="fas fa-map-marker-alt me-2"></i>Informasi Alamat
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="data-row">
                                    <div class="data-label">Alamat</div>
                                    <div class="<?= $user['alamat'] ? 'data-value' : 'empty-value' ?>">
                                        <?= $user['alamat'] ? htmlspecialchars($user['alamat']) : 'Belum diisi' ?>
                                    </div>
                                </div>
                                <div class="data-row">
                                    <div class="data-label">Kota</div>
                                    <div class="<?= $user['kota'] ? 'data-value' : 'empty-value' ?>">
                                        <?= $user['kota'] ? htmlspecialchars($user['kota']) : 'Belum diisi' ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="data-row">
                                    <div class="data-label">Provinsi</div>
                                    <div class="<?= $user['provinsi'] ? 'data-value' : 'empty-value' ?>">
                                        <?= $user['provinsi'] ? htmlspecialchars($user['provinsi']) : 'Belum diisi' ?>
                                    </div>
                                </div>
                                <div class="data-row">
                                    <div class="data-label">Kode Pos</div>
                                    <div class="<?= $user['kode_pos'] ? 'data-value' : 'empty-value' ?>">
                                        <?= $user['kode_pos'] ? htmlspecialchars($user['kode_pos']) : 'Belum diisi' ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Informasi Sistem -->
                        <div class="section-title mt-4">
                            <i class="fas fa-cog me-2"></i>Informasi Sistem
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="data-row">
                                    <div class="data-label">Tanggal Daftar</div>
                                    <div class="data-value">
                                        <i class="fas fa-calendar-plus me-2 text-success"></i>
                                        <?= date('d F Y, H:i', strtotime($user['created_at'])) ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="data-row">
                                    <div class="data-label">Terakhir Diupdate</div>
                                    <div class="data-value">
                                        <i class="fas fa-calendar-edit me-2 text-warning"></i>
                                        <?= $user['updated_at'] ? date('d F Y, H:i', strtotime($user['updated_at'])) : 'Belum pernah diupdate' ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="card info-card mt-4">
                    <div class="card-body">
                        <div class="d-flex flex-wrap gap-2">
                            <a href="edit_pengguna.php?id=<?= $user['id'] ?>" class="btn btn-primary btn-custom">
                                <i class="fas fa-edit me-2"></i>Edit Pengguna
                            </a>
                            <button class="btn btn-info btn-custom" onclick="printProfile()">
                                <i class="fas fa-print me-2"></i>Cetak Profil
                            </button>
                            <button class="btn btn-success btn-custom" onclick="exportProfile()">
                                <i class="fas fa-download me-2"></i>Export Data
                            </button>
                            <?php if (!$isComplete): ?>
                            <button class="btn btn-warning btn-custom" onclick="sendReminder()">
                                <i class="fas fa-envelope me-2"></i>Kirim Pengingat
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function printProfile() {
            window.print();
        }

        function exportProfile() {
            // Implementasi export data
            alert('Fitur export akan segera tersedia');
        }

        function sendReminder() {
            if (confirm('Kirim pengingat untuk melengkapi profil?')) {
                // Implementasi kirim pengingat
                alert('Pengingat berhasil dikirim');
            }
        }

        // Hover effect untuk cards
        document.querySelectorAll('.info-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>
</html>