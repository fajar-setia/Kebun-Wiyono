<?php
// File: userFeature/checkout.php
session_start();
include '../config.php';

// Include PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


// Add these lines at the beginning of your file to include PHPMailer
require '../PHPMailer-master/src/Exception.php';
require '../PHPMailer-master/src/PHPMailer.php';
require '../PHPMailer-master/src/SMTP.php';

if (!isset($_SESSION['user_id'])) { // pakai 'id' sesuai session yang kamu simpan saat login user
  header("Location: ../index.php"); // arahkan ke halaman login utama
  exit;
}

// Redirect if cart is empty
if (empty($_SESSION['cart'])) {
    header("Location: keranjang.php");
    exit;
}

// Function to send email notification to admin
function sendOrderNotificationEmail($order_id, $order_number, $nama_penerima, $total_bayar, $items, $conn) {
    // Create a new PHPMailer instance
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // Change to your SMTP host
        $mail->SMTPAuth   = true;
        $mail->Username   = 'setiafajar935@gmail.com'; // Change to your email
        $mail->Password   = 'axux wtmx aqya doun'; // Change to your email password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Recipients
        $mail->setFrom('setiafajar935@gmail.com', 'Pembeli');
        $mail->addAddress('setiafajar935@gmail.com', 'Admin Kebun Wiyono'); // Add a recipient
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = "Pesanan Baru: {$order_number}";
        
        // Create email body
        $body = "<h2>Pesanan Baru Telah Diterima</h2>";
        $body .= "<p><strong>No. Pesanan:</strong> {$order_number}</p>";
        $body .= "<p><strong>Nama Penerima:</strong> {$nama_penerima}</p>";
        $body .= "<p><strong>Total Bayar:</strong> Rp" . number_format($total_bayar) . "</p>";
        
        // Item details
        $body .= "<h3>Detail Item:</h3>";
        $body .= "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>";
        $body .= "<tr><th>Produk</th><th>Jumlah</th><th>Harga</th><th>Subtotal</th></tr>";
        
        foreach ($items as $item) {
            $body .= "<tr>";
            $body .= "<td>{$item['nama_produk']}</td>";
            $body .= "<td>{$item['quantity']}</td>";
            $body .= "<td>Rp" . number_format($item['harga']) . "</td>";
            $body .= "<td>Rp" . number_format($item['harga'] * $item['quantity']) . "</td>";
            $body .= "</tr>";
        }
        
        $body .= "</table>";
        
        // Add link to admin page
        // $body .= "<p><a href='http://yourdomain.com/admin/detail_pesanan.php?id={$order_id}'>Lihat Detail Pesanan di Dashboard Admin</a></p>";
        
        $mail->Body = $body;
        $mail->AltBody = "Pesanan baru telah diterima: {$order_number}";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error sending email: {$mail->ErrorInfo}");
        return false;
    }
}

// Process checkout form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $nama_penerima = mysqli_real_escape_string($conn, $_POST['nama_penerima']);
    $telepon = mysqli_real_escape_string($conn, $_POST['telepon']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $kota = mysqli_real_escape_string($conn, $_POST['kota']);
    $kode_pos = mysqli_real_escape_string($conn, $_POST['kode_pos']);
    $catatan = mysqli_real_escape_string($conn, $_POST['catatan']);
    $metode_pembayaran = mysqli_real_escape_string($conn, $_POST['metode_pembayaran']);
    
    // Get user ID from session (assuming user is logged in)
    $user_id = $_SESSION['user_id']; // Make sure you have a user_id stored in session
    
    // Calculate total price
    $total_harga = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total_harga += $item['harga'] * $item['quantity'];
    }
    
    // Add shipping cost (you can adjust this based on your business logic)
    $ongkir = 20000;
    $total_bayar = $total_harga + $ongkir;
    
    // Generate order number
    $order_number = 'KB' . date('YmdHis') . rand(100, 999);
    
    // Insert order into the database
    $query = "INSERT INTO pesanan (user_id, nomor_pesanan, nama_penerima, telepon, alamat, kota, kode_pos, catatan, metode_pembayaran, total_harga, ongkir, total_bayar, status) 
              VALUES ('$user_id', '$order_number', '$nama_penerima', '$telepon', '$alamat', '$kota', '$kode_pos', '$catatan', '$metode_pembayaran', '$total_harga', '$ongkir', '$total_bayar', 'Menunggu Pembayaran')";
    
    if (mysqli_query($conn, $query)) {
        $order_id = mysqli_insert_id($conn);
        
        // Insert order items
        foreach ($_SESSION['cart'] as $item) {
            $product_id = $item['id'];
            $quantity = $item['quantity'];
            $harga = $item['harga'];
            $subtotal = $harga * $quantity;
            
            $item_query = "INSERT INTO pesanan_item (pesanan_id, produk_id, quantity, harga, subtotal) 
                          VALUES ('$order_id', '$product_id', '$quantity', '$harga', '$subtotal')";
            mysqli_query($conn, $item_query);
        }
        
        // Send email notification to admin
        $email_sent = sendOrderNotificationEmail($order_id, $order_number, $nama_penerima, $total_bayar, $_SESSION['cart'], $conn);
        
        // Clear the cart
        $_SESSION['cart'] = [];
        
        // Store email status in session
        $_SESSION['email_notification'] = $email_sent ? 'success' : 'failed';
        
        // Redirect to order confirmation page
        header("Location: konfirmasi_pesanan.php?order_id=$order_id");
        exit;
    } else {
        $error_message = "Terjadi kesalahan saat memproses pesanan. Silakan coba lagi.";
    }
}

// Calculate totals
$total = 0;
foreach ($_SESSION['cart'] as $item) {
    $total += $item['harga'] * $item['quantity'];
}
$ongkir = 20000; // Fixed shipping cost, you can make this dynamic
$total_bayar = $total + $ongkir;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Kebun Wiyono</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lilita+One&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=David+Libre&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css">
    <link rel="stylesheet" href="../assets/css/user/checkout.css">
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

    <div class="container" style="margin-top: 120px;">
        <div class="row">
            <div class="col-lg-8">
                <div class="feature-container">
                    <h2 class="mb-4">Checkout</h2>
                    
                    <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?= $error_message ?>
                    </div>
                    <?php endif; ?>
                    
                    <form action="checkout.php" method="post">
                        <div class="form-section mb-4">
                            <h3 class="section-title">Informasi Pengiriman</h3>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nama_penerima" class="form-label">Nama Penerima*</label>
                                    <input type="text" class="form-control" id="nama_penerima" name="nama_penerima" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="telepon" class="form-label">Nomor Telepon*</label>
                                    <input type="tel" class="form-control" id="telepon" name="telepon" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="alamat" class="form-label">Alamat Lengkap*</label>
                                <textarea class="form-control" id="alamat" name="alamat" rows="3" required></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="kota" class="form-label">Kota/Kabupaten*</label>
                                    <input type="text" class="form-control" id="kota" name="kota" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="kode_pos" class="form-label">Kode Pos*</label>
                                    <input type="text" class="form-control" id="kode_pos" name="kode_pos" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="catatan" class="form-label">Catatan Pesanan</label>
                                <textarea class="form-control" id="catatan" name="catatan" rows="2" placeholder="Misal: Tolong kirim pagi hari, dsb."></textarea>
                            </div>
                        </div>
                        
                        <div class="form-section mb-4">
                            <h3 class="section-title">Metode Pembayaran</h3>
                            
                            <div class="payment-method selected" onclick="selectPayment(this, 'transfer_bank')">
                                <input type="radio" name="metode_pembayaran" value="transfer_bank" id="transfer_bank" checked style="display: none;">
                                <div class="payment-header">
                                    <div>
                                        <h5 class="mb-0">Transfer Bank</h5>
                                        <p class="text-muted mb-0">BCA, BNI, Mandiri, BRI</p>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <span class="payment-check"><i class="fas fa-check-circle fa-lg"></i></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="payment-method mt-3" onclick="selectPayment(this, 'e_wallet')">
                                <input type="radio" name="metode_pembayaran" value="e_wallet" id="e_wallet" style="display: none;">
                                <div class="payment-header">
                                    <div>
                                        <h5 class="mb-0">E-Wallet</h5>
                                        <p class="text-muted mb-0">GoPay, OVO, Dana, LinkAja</p>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <span class="payment-check"><i class="fas fa-check-circle fa-lg"></i></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="payment-method mt-3" onclick="selectPayment(this, 'cod')">
                                <input type="radio" name="metode_pembayaran" value="cod" id="cod" style="display: none;">
                                <div class="payment-header">
                                    <div>
                                        <h5 class="mb-0">Bayar di Tempat (COD)</h5>
                                        <p class="text-muted mb-0">Hanya tersedia untuk pengiriman dalam kota</p>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <span class="payment-check"><i class="fas fa-check-circle fa-lg"></i></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">Buat Pesanan</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="feature-container order-summary">
                    <h3 class="section-title">Ringkasan Pesanan</h3>
                    
                    <div class="product-list">
                        <?php foreach ($_SESSION['cart'] as $item): ?>
                        <div class="product-item">
                            <div class="product-info">
                                <img src="../fitur/gambarProduk/<?= $item['gambar']; ?>" class="product-img" alt="<?= $item['nama_produk']; ?>">
                                <div>
                                    <h6><?= $item['nama_produk']; ?></h6>
                                    <p class="text-muted mb-0"><?= $item['quantity']; ?> x Rp<?= number_format($item['harga']); ?></p>
                                </div>
                            </div>
                            <div class="product-price">
                                Rp<?= number_format($item['harga'] * $item['quantity']); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="price-details mt-4">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal</span>
                            <span>Rp<?= number_format($total); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Ongkos Kirim</span>
                            <span>Rp<?= number_format($ongkir); ?></span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between fw-bold">
                            <span>Total</span>
                            <span>Rp<?= number_format($total_bayar); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="feature-container">
                    <h3 class="section-title">Petunjuk Pembayaran</h3>
                    <ol class="mb-0">
                        <li>Pastikan semua data pengiriman sudah benar</li>
                        <li>Pilih metode pembayaran yang diinginkan</li>
                        <li>Klik tombol "Buat Pesanan" untuk melanjutkan</li>
                        <li>Setelah melakukan pembayaran, konfirmasi melalui halaman "Pesanan Saya"</li>
                        <li>Tim kami akan memproses pesanan setelah pembayaran dikonfirmasi</li>
                    </ol>
                </div>
            </div>
        </div>
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
        function selectPayment(element, id) {
            // Remove selected class from all payment methods
            document.querySelectorAll('.payment-method').forEach(item => {
                item.classList.remove('selected');
            });
            
            // Add selected class to clicked payment method
            element.classList.add('selected');
            
            // Check the radio button
            document.getElementById(id).checked = true;
        }
        
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