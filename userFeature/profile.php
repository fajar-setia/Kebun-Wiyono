<?php
session_start();
require '../config.php';

// PERBAIKAN: Tambahkan debug dan validasi yang lebih baik
// Debug untuk melihat isi session (hapus setelah testing)
// error_log("Session contents: " . print_r($_SESSION, true));

// Check if user is logged in dengan validasi yang lebih ketat
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id']) || $_SESSION['user_id'] === true) {
  // Clear invalid session
  session_destroy();
  header("Location: ../index.php");
  exit;
}

// PERBAIKAN: Pastikan user_id adalah integer
$user_id = (int)$_SESSION['user_id'];
if ($user_id <= 0) {
  // Invalid user ID
  session_destroy();
  header("Location: ../index.php");
  exit;
}

$user = null;
$error_message = '';

try {
  // PERBAIKAN: Tambahkan debug query
  error_log("Querying user with ID: " . $user_id);

  $sql = "SELECT * FROM pengguna WHERE id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    // Debug: log user data (hapus setelah testing)
    error_log("User found: " . $user['email'] . " - " . ($user['nama_lengkap'] ?? $user['username']));
  } else {
    $error_message = "User data not found for ID: " . $user_id;
    error_log($error_message);

    // User tidak ditemukan, destroy session dan redirect
    session_destroy();
    header("Location: ../index.php");
    exit;
  }
  $stmt->close();
} catch (Exception $e) {
  $error_message = "Error retrieving user data: " . $e->getMessage();
  error_log($error_message);
}

// Ambil data pesanan dengan user_id yang benar
$orders = [];
try {
  $order_sql = "SELECT * FROM pesanan WHERE user_id = ? ORDER BY tanggal_pesanan DESC LIMIT 3";
  $stmt = $conn->prepare($order_sql);
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $order_result = $stmt->get_result();

  if ($order_result && $order_result->num_rows > 0) {
    while ($order = $order_result->fetch_assoc()) {
      $orders[] = $order;
    }
  }
  $stmt->close();
} catch (Exception $e) {
  error_log("Error retrieving orders: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profile Saya - Kebun Wiyono</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Lilita+One&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=David+Libre&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css">
  <link rel="stylesheet" href="../assets/css/user/profil.css">
</head>
<style>
  
</style>

<body>
  <!-- Navigation Bar -->
  <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container">
      <a class="navbar-brand" href="#">KEBUN WIYONO</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a class="nav-link" href="../user.php">Beranda</a></li>
          <li class="nav-item"><a class="nav-link" href="belanjaProduk.php">Belanja</a></li>
          <li class="nav-item"><a class="nav-link" href="kontakKami.php">Kontak Kami</a></li>
          <li class="nav-item">
            <a class="nav-link" href="keranjang.php">
              <i class="fas fa-shopping-cart"></i>
            </a>
          </li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="fas fa-user-circle"></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
              <li><a class="dropdown-item" href="profile.php">Profil Saya</a></li>
              <li><a class="dropdown-item" href="pesanan-saya.php">Pesanan Saya</a></li>
              <li>
                <hr class="dropdown-divider">
              </li>
              <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
            </ul>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Profile Content -->
  <section id="profile" class="py-5 profile-section feature-container">
    <div class="container">
      <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger">
          <?php echo $error_message; ?>
        </div>
      <?php endif; ?>

      <div class="row">
        <!-- Left Profile Card -->
        <div class="col-lg-4 mb-4">
          <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body text-center p-5">
              <div class="mb-4">
                <?php
                $fotoProfil = isset($user['foto_profil']) && !empty($user['foto_profil'])
                  ? '../uploads/profile/' . htmlspecialchars($user['foto_profil'], ENT_QUOTES, 'UTF-8')
                  : '../assets/avatar-placeholder.jpg';
                ?>
                <img src="<?= $fotoProfil ?>"
                  onerror="this.onerror=null; this.src='https://via.placeholder.com/150';"
                  class="rounded-circle img-thumbnail"
                  width="150" height="150"
                  alt="Foto Profil">
              </div>
              <h4 class="mb-1">
                <?php echo isset($user['nama_lengkap']) ? htmlspecialchars($user['nama_lengkap']) : htmlspecialchars($user['username']); ?>
              </h4>
              <p class="text-muted mb-4">
                <i class="fas fa-map-marker-alt me-2"></i>
                <?php echo isset($user['kota']) && !empty($user['kota']) ? htmlspecialchars($user['kota']) : "Lokasi belum diatur"; ?>
              </p>
              <div class="d-grid gap-2">
                <a href="edit-profil.php" class="btn btn-success">
                  <i class="fas fa-user-edit me-2"></i>Edit Profil
                </a>
                <a href="ganti-password.php" class="btn btn-outline-success">
                  <i class="fas fa-key me-2"></i>Ganti Password
                </a>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Information Cards -->
        <div class="col-lg-8">
          <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-5">
              <h3 class="card-title mb-4">Informasi Pribadi</h3>

              <div class="mb-4">
                <div class="table-responsive">
                  <table class="table table-borderless">
                    <tbody>
                      <tr>
                        <td style="width: 35%;" class="fw-medium text-muted ps-0">Nama Lengkap</td>
                        <td class="text-dark">
                          <?php echo isset($user['nama_lengkap']) && !empty($user['nama_lengkap']) ? htmlspecialchars($user['nama_lengkap']) : "Belum diisi"; ?>
                        </td>
                      </tr>
                      <tr>
                        <td class="fw-medium text-muted ps-0">Username</td>
                        <td class="text-dark">
                          <?php echo isset($user['username']) ? htmlspecialchars($user['username']) : "Belum diisi"; ?>
                        </td>
                      </tr>
                      <tr>
                        <td class="fw-medium text-muted ps-0">Email</td>
                        <td class="text-dark">
                          <?php echo isset($user['email']) ? htmlspecialchars($user['email']) : "Belum diisi"; ?>
                        </td>
                      </tr>
                      <tr>
                        <td class="fw-medium text-muted ps-0">No. Telepon</td>
                        <td class="text-dark">
                          <?php echo isset($user['telepon']) && !empty($user['telepon']) ? htmlspecialchars($user['telepon']) : "Belum diisi"; ?>
                        </td>
                      </tr>
                      <tr>
                        <td class="fw-medium text-muted ps-0">Tanggal Lahir</td>
                        <td class="text-dark">
                          <?php echo isset($user['tanggal_lahir']) && !empty($user['tanggal_lahir']) ? date('d F Y', strtotime($user['tanggal_lahir'])) : "Belum diisi"; ?>
                        </td>
                      </tr>
                      <tr>
                        <td class="fw-medium text-muted ps-0">Jenis Kelamin</td>
                        <td class="text-dark">
                          <?php
                          if (isset($user['jenis_kelamin']) && !empty($user['jenis_kelamin'])) {
                            echo $user['jenis_kelamin'] == 'L' ? "Laki-laki" : "Perempuan";
                          } else {
                            echo "Belum diisi";
                          }
                          ?>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <h3 class="card-title mb-4">Alamat Pengiriman</h3>
              <div class="mb-4">
                <?php if (isset($user['alamat']) && !empty($user['alamat'])): ?>
                  <div class="table-responsive">
                    <table class="table table-borderless">
                      <tbody>
                        <tr>
                          <td style="width: 35%;" class="fw-medium text-muted ps-0">Alamat Lengkap</td>
                          <td class="text-dark">
                            <?php echo htmlspecialchars($user['alamat']); ?>
                          </td>
                        </tr>
                        <tr>
                          <td class="fw-medium text-muted ps-0">Kota</td>
                          <td class="text-dark">
                            <?php echo isset($user['kota']) && !empty($user['kota']) ? htmlspecialchars($user['kota']) : "Belum diisi"; ?>
                          </td>
                        </tr>
                        <tr>
                          <td class="fw-medium text-muted ps-0">Provinsi</td>
                          <td class="text-dark">
                            <?php echo isset($user['provinsi']) && !empty($user['provinsi']) ? htmlspecialchars($user['provinsi']) : "Belum diisi"; ?>
                          </td>
                        </tr>
                        <tr>
                          <td class="fw-medium text-muted ps-0">Kode Pos</td>
                          <td class="text-dark">
                            <?php echo isset($user['kode_pos']) && !empty($user['kode_pos']) ? htmlspecialchars($user['kode_pos']) : "Belum diisi"; ?>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                <?php else: ?>
                  <div class="empty-state">
                    <i class="fas fa-home text-muted"></i>
                    <p class="text-muted mb-3">Anda belum mengatur alamat pengiriman</p>
                    <a href="edit-profil.php" class="btn btn-sm btn-success">Tambahkan Alamat</a>
                  </div>
                <?php endif; ?>
              </div>

              <div class="mt-4">
                <h3 class="card-title mb-4">Aktivitas Terbaru</h3>
                <div class="list-group list-group-flush">
                  <?php if (!empty($orders)): ?>
                    <?php foreach ($orders as $order): ?>
                      <a href="detail_pesanan.php?id=<?php echo $order['id']; ?>" class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                          <h6 class="mb-1">Pesanan #<?php echo htmlspecialchars($order['nomor_pesanan']); ?></h6>
                          <small><?php echo date('d M Y', strtotime($order['tanggal_pesanan'])); ?></small>
                        </div>
                        <p class="mb-1">Status:
                          <span class="badge bg-<?php
                                                echo ($order['status'] == 'selesai' ? 'success' : ($order['status'] == 'dikirim' ? 'primary' : ($order['status'] == 'diproses' ? 'warning' : ($order['status'] == 'dibatalkan' ? 'danger' : 'secondary'))));
                                                ?>">
                            <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                          </span>
                        </p>
                        <small>Total: Rp <?php echo number_format($order['total_harga'], 0, ',', '.'); ?></small>
                      </a>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <div class="empty-state">
                      <i class="fas fa-shopping-bag text-muted"></i>
                      <p class="text-muted mb-3">Belum ada pesanan</p>
                      <a href="belanjaProduk.php" class="btn btn-sm btn-success">Mulai Belanja</a>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="page-footer">
    <div class="container">
      <div class="row">
        <div class="col-lg-4 col-md-6 mb-4 mb-md-0">
          <h5 class="footer-heading">Kebun Wiyono</h5>
          <p class="footer-text">Menyediakan produk organik segar langsung dari kebun untuk kesehatan dan kebaikan Anda. Kami berkomitmen untuk kualitas terbaik dan kelestarian lingkungan.</p>
        </div>

        <div class="col-lg-2 col-md-6 mb-4 mb-md-0">
          <h5 class="footer-links">Links</h5>
          <ul class="list-unstyled">
            <li><a href="../user.php"><i class="fas fa-angle-right me-2"></i>Beranda</a></li>
            <li><a href="../user.php#tentang-kami"><i class="fas fa-angle-right me-2"></i>Tentang Kami</a></li>
            <li><a href="../user.php#produk"><i class="fas fa-angle-right me-2"></i>Produk</a></li>
            <li><a href="belanjaProduk.php"><i class="fas fa-angle-right me-2"></i>Belanja</a></li>
            <li><a href="kontakKami.php"><i class="fas fa-angle-right me-2"></i>Kontak</a></li>
          </ul>
        </div>

        <div class="col-lg-3 col-md-6 mb-4 mb-md-0">
          <h5 class="footer-links">Kategori Buah</h5>
          <ul class="list-unstyled">
            <li><a href="#"><i class="fas fa-angle-right me-2"></i>Buah Naga</a></li>
            <li><a href="#"><i class="fas fa-angle-right me-2"></i>Durian</a></li>
            <li><a href="#"><i class="fas fa-angle-right me-2"></i>Alpukat</a></li>
          </ul>
        </div>

        <div class="col-lg-3 col-md-6 mb-4 mb-md-0">
          <h5 class="footer-links">Kontak Kami</h5>
          <ul class="list-unstyled">
            <li class="contact-item">
              <div class="contact-icon">
                <i class="fas fa-map-marker-alt"></i>
              </div>
              <div>Jl. Raya Kebun No. 123, Karimun</div>
            </li>
            <li class="contact-item">
              <div class="contact-icon">
                <i class="fas fa-phone"></i>
              </div>
              <div>+62 823 4567 8910</div>
            </li>
            <li class="contact-item">
              <div class="contact-icon">
                <i class="fas fa-envelope"></i>
              </div>
              <div>info@kebunwiyono.com</div>
            </li>
            <li class="contact-item">
              <div class="contact-icon">
                <i class="fas fa-clock"></i>
              </div>
              <div>Senin - Sabtu: 08:00 - 17:00</div>
            </li>
          </ul>
        </div>
      </div>

      <div class="footer-copyright mt-4">
        <p>&copy; <?= date('Y') ?> Kebun Wiyono. Hak Cipta Dilindungi. Dibuat dengan <i class="fas fa-heart text-danger"></i> untuk para petani Indonesia.</p>
      </div>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
  <script>
    // Navbar scroll effect
    window.addEventListener('scroll', function() {
      const navbar = document.querySelector('.navbar');
      if (window.scrollY > 50) {
        navbar.classList.add('scrolled');
      } else {
        navbar.classList.remove('scrolled');
      }
    });
  </script>
</body>

</html>
<?php $conn->close(); ?>