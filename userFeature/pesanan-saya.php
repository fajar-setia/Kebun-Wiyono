<?php
session_start();
include '../config.php';

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
  header("Location: ../login.php");
  exit();
}

$user_id = $_SESSION['user_id'];

// Query: ambil data pesanan beserta produk-produk di dalamnya
$query = "SELECT 
            p.id AS pesanan_id,
            p.tanggal_pesanan,
            p.total_harga,
            p.status,
            p.alamat,
            p.telepon,
            p.bukti_pembayaran,
            pr.nama_produk,
            pr.harga,
            pr.gambar,
            pi.quantity
          FROM pesanan p
          JOIN pesanan_item pi ON p.id = pi.pesanan_id 
          JOIN produk pr ON pi.produk_id = pr.id 
          WHERE p.user_id = ? 
          ORDER BY p.tanggal_pesanan DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$pesanan_data = [];
$has_orders = false;

if ($result && $result->num_rows > 0) {
  $has_orders = true;
  while ($row = $result->fetch_assoc()) {
    $pesanan_id = $row['pesanan_id'];
    if (!isset($pesanan_data[$pesanan_id])) {
      $pesanan_data[$pesanan_id] = [
        'id' => $pesanan_id,
        'tanggal_pesanan' => $row['tanggal_pesanan'],
        'total_harga' => $row['total_harga'],
        'status' => $row['status'],
        'alamat' => $row['alamat'],
        'telepon' => $row['telepon'],
        'bukti_pembayaran' => $row['bukti_pembayaran'],
        'items' => []
      ];
    }

    // Tambahkan item produk ke dalam pesanan
    $pesanan_data[$pesanan_id]['items'][] = [
      'nama_produk' => $row['nama_produk'],
      'harga' => $row['harga'],
      'quantity' => $row['quantity'],
      'gambar' => $row['gambar']
    ];
  }
}

// Ambil beberapa produk terbaru untuk ditampilkan di halaman kosong
$produk_terbaru = [];
if (!$has_orders) {
  $query_produk = "SELECT 
                    k.id, 
                    p.nama_produk, 
                    p.harga, 
                    p.gambar 
                  FROM 
                    keranjang k
                  JOIN 
                    produk p ON k.id = p.id
                  WHERE 
                    k.jumlah = p.harga + k.jumlah;";
  $result_produk = $conn->query($query_produk);
  if ($result_produk && $result_produk->num_rows > 0) {
    while ($row = $result_produk->fetch_assoc()) {
      $produk_terbaru[] = $row;
    }
  }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_bukti'])) {
  $targetDir = "../fitur/bukti_pembayaran/";
  $fileName = basename($_FILES["bukti_pembayaran"]["name"]);
  $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
  $allowed = ["jpg", "jpeg", "png", "gif"];

  // Ambil ID pesanan dari input hidden
  $pesanan_id = intval($_POST['order_id']);

  if (in_array($fileExt, $allowed) && $_FILES["bukti_pembayaran"]["size"] <= 5 * 1024 * 1024) {
    $newFileName = 'bukti_' . time() . '_' . rand(1000, 9999) . '.' . $fileExt;
    $targetFilePath = $targetDir . $newFileName;

    if (move_uploaded_file($_FILES["bukti_pembayaran"]["tmp_name"], $targetFilePath)) {
      $stmt = $conn->prepare("UPDATE pesanan SET bukti_pembayaran = ?, status = 'Menunggu Verifikasi' WHERE id = ?");
      $stmt->bind_param("si", $newFileName, $pesanan_id);

      if ($stmt->execute()) {
        header("Location: pesanan-saya.php");
        exit;
      } else {
        echo "<script>alert('Gagal menyimpan data ke database.');</script>";
      }
    } else {
      echo "<script>alert('Gagal mengupload file.');</script>";
    }
  } else {
    echo "<script>alert('Format file tidak didukung atau ukuran terlalu besar (maks 5MB).');</script>";
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pesanan Saya - Kebun Wiyono</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Lilita+One&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=David+Libre&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css">
  <link rel="stylesheet" href="../assets/css/user/pesanan-saya.css">
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
            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="fas fa-user-circle"></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
              <li><a class="dropdown-item" href="profile.php">Profil Saya</a></li>
              <li><a class="dropdown-item active" href="pesananSaya.php">Pesanan Saya</a></li>
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

  <div class="container feature-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2>Pesanan Saya</h2>
      <div class="text-muted">
        <i class="fas fa-box"></i> Total <?= count($pesanan_data) ?> Pesanan
      </div>
    </div>

    <?php if (empty($pesanan_data)): ?>
      <div class="empty-orders">
        <i class="fas fa-shopping-bag fa-4x text-muted mb-3"></i>
        <h3>Belum Ada Pesanan</h3>
        <p>Anda belum melakukan pemesanan apapun. Yuk mulai belanja produk segar dari Kebun Wiyono!</p>
        <a href="belanjaProduk.php" class="btn btn-primary mt-3">
          <i class="fas fa-shopping-cart me-2"></i>Belanja Sekarang
        </a>
      </div>
    <?php else: ?>
      <?php foreach ($pesanan_data as $pesanan): ?>
        <div class="order-card">
          <div class="order-header">
            <div>
              <div class="order-id">Pesanan #<?= $pesanan['id'] ?></div>
              <div class="order-date">
                <i class="fas fa-calendar me-1"></i>
                <?= date('d M Y, H:i', strtotime($pesanan['tanggal_pesanan'])) ?>
              </div>
            </div>
            <div>
              <?php
              $status_class = '';
              switch ($pesanan['status']) {
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
                  $status_text = 'Selesai';
                  break;
                case 'dibatalkan':
                  $status_class = 'status-dibatalkan';
                  $status_text = 'Dibatalkan';
                  break;
                default:
                  $status_class = 'status-pending';
                  $status_text = ucfirst($pesanan['status']);
              }
              ?>
              <span class="status-badge <?= $status_class ?>"><?= $status_text ?></span>
            </div>
          </div>

          <div class="order-items">
            <?php foreach ($pesanan['items'] as $item): ?>
              <div class="item-row">
                <img src="../fitur/gambarProduk/<?= $item['gambar'] ?>"
                  class="product-img"
                  alt="<?= $item['nama_produk'] ?>">
                <div class="item-info">
                  <div class="item-name"><?= $item['nama_produk'] ?></div>
                  <div class="item-price">Rp<?= number_format($item['harga']) ?> per item</div>
                </div>
                <div class="item-quantity">x<?= $item['quantity'] ?></div>
              </div>
            <?php endforeach; ?>
          </div>

          <div class="row mt-3">
            <div class="col-md-6">
              <small class="text-muted">
                <i class="fas fa-map-marker-alt me-1"></i>
                <strong>Alamat Pengiriman:</strong><br>
                <?= nl2br(htmlspecialchars($pesanan['alamat'])) ?>
              </small>
            </div>
            <div class="col-md-6">
              <small class="text-muted">
                <i class="fas fa-phone me-1"></i>
                <strong>No. Telepon:</strong> <?= $pesanan['telepon'] ?>
              </small>
            </div>
          </div>
          
          <div class="upload-section mt-4 p-4" style="background: #f8f9fa; border-radius: 10px; border: 2px dashed #dee2e6;">
            <?php if (empty($pesanan['bukti_pembayaran'])): ?>
              <h5 class="mb-3"><i class="fas fa-upload me-2"></i>Upload Bukti Pembayaran</h5>
              <p class="text-muted mb-3">Setelah melakukan pembayaran, silakan upload bukti transfer di bawah ini:</p>

              <form method="POST" enctype="multipart/form-data" id="uploadForm">
                <input type="hidden" name="order_id" value="<?= $pesanan['id'] ?>">

                <div class="mb-3">
                  <label for="bukti_pembayaran" class="form-label">Pilih File Bukti Pembayaran</label>
                  <input type="file" class="form-control" id="bukti_pembayaran" name="bukti_pembayaran"
                    accept="image/jpeg,image/jpg,image/png,image/gif" required>
                  <small class="form-text text-muted">Format: JPG, PNG, GIF. Maksimal: 5MB</small>
                </div>

                <div class="mb-3">
                  <img id="preview" src="" alt="Preview" style="display: none; max-width: 300px; max-height: 200px; border-radius: 8px;">
                </div>

                <button type="submit" name="upload_bukti" class="btn btn-success">
                  <i class="fas fa-upload me-2"></i>Upload Bukti Pembayaran
                </button>
              </form>
            <?php else: ?>
              <div class="text-center">
                <i class="fas fa-check-circle text-success" style="font-size: 48px;"></i>
                <h5 class="text-success mt-3">Bukti Pembayaran Sudah Diupload</h5>
                <p class="text-muted">Status: <span class="badge bg-info"><?= $pesanan['status'] ?></span></p>
                <p>Terima kasih! Bukti pembayaran Anda sedang dalam proses verifikasi.</p>

                <!-- Show uploaded proof -->
                <div class="mt-3">
                  <img src="../fitur/bukti_pembayaran/<?= $pesanan['bukti_pembayaran'] ?>"
                    alt="Bukti Pembayaran"
                    style="max-width: 300px; max-height: 200px; border-radius: 8px; border: 1px solid #ddd;">
                </div>
              </div>
            <?php endif; ?>
          </div>

          <div class="order-total">
            <div class="total-amount">
              Total: Rp<?= number_format($pesanan['total_harga']) ?>
            </div>
          </div>

          <div class="order-actions">
            <?php if ($pesanan['status'] === 'selesai'): ?>
              <button class="btn btn-outline-primary btn-sm me-2"
                onclick="showReviewModal(<?= $pesanan['id'] ?>)">
                <i class="fas fa-star me-1"></i>Beri Ulasan
              </button>
            <?php endif; ?>

            <?php if ($pesanan['status'] === 'pending'): ?>
              <button class="btn btn-outline-danger btn-sm me-2"
                onclick="cancelOrder(<?= $pesanan['id'] ?>)">
                <i class="fas fa-times me-1"></i>Batalkan
              </button>
            <?php endif; ?>

            <button class="btn btn-detail btn-sm"
              onclick="showOrderDetail(<?= $pesanan['id'] ?>)">
              <i class="fas fa-eye me-1"></i>Detail
            </button>
          </div>
        </div>
      <?php endforeach; ?>

      <!-- Pagination jika diperlukan -->
      <div class="d-flex justify-content-center mt-4">
        <nav aria-label="Pagination">
          <!-- Tambahkan pagination di sini jika pesanan banyak -->
        </nav>
      </div>
    <?php endif; ?>
  </div>

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
            <li><a href="#tentang-kami"><i class="fas fa-angle-right me-2"></i>Tentang Kami</a></li>
            <li><a href="#produk"><i class="fas fa-angle-right me-2"></i>Produk</a></li>
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
  <script>
    function showOrderDetail(orderId) {
      window.location.href = 'detail_pesanan.php?id=' + orderId;

    }

    function cancelOrder(orderId) {
      if (confirm('Apakah Anda yakin ingin membatalkan pesanan ini?')) {
        // AJAX request untuk membatalkan pesanan
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
      // Implementasi untuk menampilkan modal review
      alert('Menampilkan form review untuk pesanan #' + orderId);
      // Anda bisa membuat modal untuk review dan rating
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
    
    document.getElementById('uploadForm').addEventListener('submit', function(e) {
    const fileInput = document.getElementById('bukti_pembayaran');
    const file = fileInput.files[0];
    
    if (!file) {
        e.preventDefault();
        alert('Silakan pilih file bukti pembayaran terlebih dahulu!');
        return false;
    }
    
    // Validasi ukuran file (5MB = 5 * 1024 * 1024 bytes)
    if (file.size > 5 * 1024 * 1024) {
        e.preventDefault();
        alert('Ukuran file terlalu besar! Maksimal 5MB.');
        return false;
    }
    
    // Validasi tipe file
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    if (!allowedTypes.includes(file.type)) {
        e.preventDefault();
        alert('Tipe file tidak didukung! Hanya menerima JPG, PNG, atau GIF.');
        return false;
    }
    
    // Konfirmasi upload
    return confirm('Apakah Anda yakin ingin mengupload bukti pembayaran ini?');
});
  </script>
</body>

</html>