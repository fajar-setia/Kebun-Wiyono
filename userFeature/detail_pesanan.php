<?php
session_start();
include '../config.php';

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Ambil ID pesanan dari URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: pesananSaya.php");
    exit();
}

$pesanan_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Query untuk mengambil detail pesanan
$query = "SELECT p.*, u.nama_lengkap, u.email 
          FROM pesanan p 
          JOIN pengguna u ON p.user_id = u.id 
          WHERE p.id = ? AND p.user_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $pesanan_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: pesananSaya.php");
    exit();
}

$pesanan = $result->fetch_assoc();

// Query untuk mengambil detail produk dalam pesanan
$query_items = "SELECT pd.nama_produk, pd.harga, pd.gambar, pd.deskripsi, po.quantity 
                FROM pesanan_item po 
                JOIN produk pd ON po.produk_id = pd.id 
                WHERE po.pesanan_id = ?";

$stmt_items = $conn->prepare($query_items);
$stmt_items->bind_param("i", $pesanan_id);
$stmt_items->execute();
$items_result = $stmt_items->get_result();

$items = [];
while ($row = $items_result->fetch_assoc()) {
    $items[] = $row;
}

// Status tracking data
$status_steps = [
    'pending' => ['label' => 'Pesanan Diterima', 'icon' => 'fas fa-clipboard-check', 'active' => false],
    'diproses' => ['label' => 'Sedang Diproses', 'icon' => 'fas fa-cogs', 'active' => false],
    'dikirim' => ['label' => 'Sedang Dikirim', 'icon' => 'fas fa-truck', 'active' => false],
    'selesai' => ['label' => 'Pesanan Selesai', 'icon' => 'fas fa-check-circle', 'active' => false]
];

// Set active status
$current_status = $pesanan['status'];
$status_order = ['pending', 'diproses', 'dikirim', 'selesai'];
$current_index = array_search($current_status, $status_order);

if ($current_status !== 'dibatalkan') {
    for ($i = 0; $i <= $current_index; $i++) {
        if (isset($status_steps[$status_order[$i]])) {
            $status_steps[$status_order[$i]]['active'] = true;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Detail Pesanan #<?= $pesanan['id'] ?> - Kebun Wiyono</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Lilita+One&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=David+Libre&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/user/detail_pesanan.css">
</head>

<body>
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
            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
              <i class="fas fa-user-circle"></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="profile.php">Profil Saya</a></li>
              <li><a class="dropdown-item" href="pesananSaya.php">Pesanan Saya</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
            </ul>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <div class="container feature-container">
    <!-- Header Pesanan -->
    <div class="order-header">
      <div class="order-meta">
        <div>
          <h2>Pesanan #<?= $pesanan['id'] ?></h2>
          <p class="mb-0">
            <i class="fas fa-calendar me-2"></i>
            <?= date('d F Y, H:i', strtotime($pesanan['tanggal_pesanan'])) ?>
          </p>
        </div>
        <div>
          <?php
          $status_class = '';
          $status_text = '';
          switch($pesanan['status']) {
            case 'pending':
              $status_class = 'status-pending';
              $status_text = 'Menunggu Konfirmasi';
              break;
            case 'diproses':
              $status_class = 'status-diproses';
              $status_text = 'Sedang Diproses';
              break;
            case 'dikirim':
              $status_class = 'status-dikirim';
              $status_text = 'Sedang Dikirim';
              break;
            case 'selesai':
              $status_class = 'status-selesai';
              $status_text = 'Pesanan Selesai';
              break;
            case 'dibatalkan':
              $status_class = 'status-dibatalkan';
              $status_text = 'Dibatalkan';
              break;
          }
          ?>
          <span class="status-badge <?= $status_class ?>"><?= $status_text ?></span>
        </div>
      </div>
    </div>

    <!-- Status Dibatalkan -->
    <?php if ($pesanan['status'] === 'dibatalkan'): ?>
      <div class="cancelled-badge">
        <i class="fas fa-times-circle me-2"></i>
        <strong>Pesanan ini telah dibatalkan</strong>
      </div>
    <?php endif; ?>

    <!-- Tracking Status -->
    <?php if ($pesanan['status'] !== 'dibatalkan'): ?>
      <div class="tracking-container">
        <h5><i class="fas fa-route me-2"></i>Status Pesanan</h5>
        <div class="tracking-steps">
          <?php foreach ($status_steps as $status => $step): ?>
            <div class="tracking-step">
              <div class="step-icon <?= $step['active'] ? 'active' : '' ?>">
                <i class="<?= $step['icon'] ?>"></i>
              </div>
              <div class="step-label <?= $step['active'] ? 'active' : '' ?>">
                <?= $step['label'] ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <div class="row">
      <!-- Detail Produk -->
      <div class="col-lg-8">
        <div class="info-section">
          <h5><i class="fas fa-box me-2"></i>Detail Produk</h5>
          <?php foreach ($items as $item): ?>
            <div class="product-item">
              <img src="../fitur/gambarProduk/<?= $item['gambar'] ?>" 
                   class="product-img" 
                   alt="<?= $item['nama_produk'] ?>">
              <div class="product-info">
                <div class="product-name"><?= $item['nama_produk'] ?></div>
                <div class="product-desc"><?= substr($item['deskripsi'], 0, 100) ?>...</div>
                <div class="product-price">Rp<?= number_format($item['harga']) ?></div>
              </div>
              <div class="product-quantity">
                <span class="quantity-badge">x<?= $item['quantity'] ?></span>
              </div>
              <div class="product-subtotal">
                <div class="subtotal-amount">
                  Rp<?= number_format($item['harga'] * $item['quantity']) ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Info Pengiriman & Kontak -->
      <div class="col-lg-4">
        <div class="info-section">
          <h5><i class="fas fa-map-marker-alt me-2"></i>Alamat Pengiriman</h5>
          <p class="mb-2"><strong><?= $pesanan['nama_lengkap'] ?></strong></p>
          <p class="mb-2"><?= nl2br(htmlspecialchars($pesanan['alamat'])) ?></p>
          <p class="mb-0">
            <i class="fas fa-phone me-2"></i>
            <?= $pesanan['telepon'] ?>
          </p>
        </div>

        <div class="info-section">
          <h5><i class="fas fa-envelope me-2"></i>Kontak</h5>
          <p class="mb-0">
            <i class="fas fa-envelope me-2"></i>
            <?= $pesanan['email'] ?>
          </p>
        </div>

        <!-- Total -->
        <div class="total-section">
          <h5 class="mb-3">Ringkasan Pembayaran</h5>
          <div class="d-flex justify-content-between mb-2">
            <span>Subtotal:</span>
            <span>Rp<?= number_format($pesanan['total_harga']) ?></span>
          </div>
          <div class="d-flex justify-content-between mb-2">
            <span>Ongkos Kirim:</span>
            <span>Gratis</span>
          </div>
          <hr>
          <div class="d-flex justify-content-between">
            <strong>Total:</strong>
            <strong class="total-amount">Rp<?= number_format($pesanan['total_harga']) ?></strong>
          </div>
        </div>
      </div>
    </div>

    <!-- Action Buttons -->
    <div class="action-buttons">
      <a href="pesanan-saya.php" class="btn-custom btn-outline-custom">
        <i class="fas fa-arrow-left me-2"></i>Kembali
      </a>
      
      <?php if ($pesanan['status'] === 'pending'): ?>
        <button class="btn-custom btn-danger-custom" onclick="cancelOrder(<?= $pesanan['id'] ?>)">
          <i class="fas fa-times me-2"></i>Batalkan Pesanan
        </button>
      <?php endif; ?>
      
      <?php if ($pesanan['status'] === 'selesai'): ?>
        <button class="btn-custom btn-primary-custom" onclick="showReviewModal(<?= $pesanan['id'] ?>)">
          <i class="fas fa-star me-2"></i>Beri Ulasan
        </button>
      <?php endif; ?>
      
      <button class="btn-custom btn-primary-custom" onclick="printOrder()">
        <i class="fas fa-print me-2"></i>Cetak
      </button>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function cancelOrder(orderId) {
      if (confirm('Apakah Anda yakin ingin membatalkan pesanan ini?')) {
        fetch('cancelOrder.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            order_id: orderId
          })
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            alert('Pesanan berhasil dibatalkan');
            location.reload();
          } else {
            alert('Gagal membatalkan pesanan: ' + data.message);
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Terjadi kesalahan saat membatalkan pesanan');
        });
      }
    }

    function showReviewModal(orderId) {
      alert('Menampilkan form review untuk pesanan #' + orderId);
      // Implementasi modal review
    }

    function printOrder() {
      window.print();
    }

    // Smooth scroll untuk navbar
    window.addEventListener('scroll', function() {
      const navbar = document.querySelector('.navbar');
      if (window.scrollY > 100) {
        navbar.classList.add('scrolled');
      } else {
        navbar.classList.remove('scrolled');
      }
    });
  </script>
</body>

</html>