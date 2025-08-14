<?php
// File: userFeature/konfirmasi_pesanan.php
session_start();
include '../config.php';

// Function to redirect with error message
function redirectWithError($message = '')
{
    if ($message) {
        $_SESSION['error_message'] = $message;
    }
    header("Location: belanjaProduk.php");
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Validate order_id parameter
if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    redirectWithError("ID pesanan tidak valid.");
}

// Sanitize and validate order_id
$order_id_raw = trim($_GET['order_id']);
if (!ctype_digit($order_id_raw)) {
    redirectWithError("ID pesanan harus berupa angka.");
}

$order_id = (int)$order_id_raw;
$user_id = (int)$_SESSION['user_id'];

// Validate that both IDs are positive integers
if ($order_id <= 0 || $user_id <= 0) {
    redirectWithError("Parameter tidak valid.");
}

// Check database connection
if (!$conn) {
    die("Koneksi database gagal.");
}

// Get order that belongs ONLY to current user - VERY STRICT CHECK
$stmt = $conn->prepare("SELECT p.* FROM pesanan p WHERE p.id = ? AND p.user_id = ? LIMIT 1");
if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param("ii", $order_id, $user_id);
$success = $stmt->execute();

if (!$success) {
    $stmt->close();
    redirectWithError("Terjadi kesalahan sistem.");
}

$result = $stmt->get_result();
$order = $result->fetch_assoc();
$stmt->close();

// STRICT: If no order found, redirect immediately
if (!$order || empty($order)) {
    redirectWithError("Pesanan tidak ditemukan atau Anda tidak memiliki akses.");
}

// ADDITIONAL SECURITY: Double check that user_id matches
if ((int)$order['user_id'] !== $user_id) {
    redirectWithError("Akses ditolak.");
}

// Handle upload bukti pembayaran
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_bukti'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Token keamanan tidak valid.");
    }

    // Revalidate order ownership before processing upload
    $recheck_stmt = $conn->prepare("SELECT id FROM pesanan WHERE id = ? AND user_id = ? LIMIT 1");
    $recheck_stmt->bind_param("ii", $order_id, $user_id);
    $recheck_stmt->execute();
    $recheck_result = $recheck_stmt->get_result();

    if ($recheck_result->num_rows === 0) {
        $recheck_stmt->close();
        die("Akses ditolak pada saat upload.");
    }
    $recheck_stmt->close();

    // Process file upload
    if (!isset($_FILES["bukti_pembayaran"]) || $_FILES["bukti_pembayaran"]["error"] !== UPLOAD_ERR_OK) {
        echo "<script>alert('Error uploading file.');</script>";
    } else {
        $targetDir = "../fitur/bukti_pembayaran/";

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $fileName = $_FILES["bukti_pembayaran"]["name"];
        $fileSize = $_FILES["bukti_pembayaran"]["size"];
        $fileTmpName = $_FILES["bukti_pembayaran"]["tmp_name"];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowed = ["jpg", "jpeg", "png", "gif"];

        if (!in_array($fileExt, $allowed)) {
            echo "<script>alert('Format file tidak didukung.');</script>";
        } elseif ($fileSize > 5 * 1024 * 1024) {
            echo "<script>alert('Ukuran file terlalu besar.');</script>";
        } else {
            $newFileName = 'bukti_' . $user_id . '_' . $order_id . '_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $fileExt;
            $targetFilePath = $targetDir . $newFileName;

            $imageInfo = getimagesize($fileTmpName);
            if ($imageInfo === false) {
                echo "<script>alert('File bukan gambar yang valid.');</script>";
            } else {
                if (move_uploaded_file($fileTmpName, $targetFilePath)) {
                    // Triple check before database update
                    $updateStmt = $conn->prepare("UPDATE pesanan SET bukti_pembayaran = ?, status = 'Menunggu Verifikasi' WHERE id = ? AND user_id = ?");
                    $updateStmt->bind_param("sii", $newFileName, $order_id, $user_id);

                    if ($updateStmt->execute() && $updateStmt->affected_rows > 0) {
                        $updateStmt->close();
                        header("Location: konfirmasi_pesanan.php?order_id=$order_id&success=1");
                        exit();
                    } else {
                        $updateStmt->close();
                        unlink($targetFilePath);
                        echo "<script>alert('Gagal menyimpan ke database.');</script>";
                    }
                } else {
                    echo "<script>alert('Gagal mengupload file.');</script>";
                }
            }
        }
    }
}

// Get order items - also with strict user validation
$itemsStmt = $conn->prepare("
    SELECT pi.*, p.nama_produk, p.gambar 
    FROM pesanan_item pi
    JOIN produk p ON pi.produk_id = p.id
    JOIN pesanan ps ON pi.pesanan_id = ps.id
    WHERE pi.pesanan_id = ? AND ps.user_id = ?
");
$itemsStmt->bind_param("ii", $order_id, $user_id);
$itemsStmt->execute();
$items_result = $itemsStmt->get_result();
$itemsStmt->close();

// Format the order date
$order_date = date('d F Y, H:i', strtotime($order['created_at'] ?? date('Y-m-d H:i:s')));

// Get payment instructions
$payment_instructions = [];
switch ($order['metode_pembayaran']) {
    case 'transfer_bank':
        $payment_instructions = [
            'Silakan transfer ke rekening berikut:',
            'BNI: 2003141116 a.n. Jhoifha Winola'
        ];
        $payment_method_name = 'Transfer Bank';
        break;
    case 'e_wallet':
        $payment_instructions = [
            'Silakan transfer ke e-wallet berikut:',
            'DANA: 081363010946 (Jhoifha Winola)'
        ];
        $payment_method_name = 'E-Wallet';
        break;
    case 'cod':
        $payment_instructions = [
            'Anda memilih metode pembayaran di tempat (COD).',
            'Siapkan uang pas saat kurir tiba.',
            'Pesanan akan diproses dan dikirim sesuai alamat pengiriman.'
        ];
        $payment_method_name = 'Bayar di Tempat (COD)';
        break;
    default:
        $payment_method_name = 'Tidak Diketahui';
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Show success message
if (isset($_GET['success']) && $_GET['success'] == '1') {
    echo "<script>alert('Bukti pembayaran berhasil diupload!');</script>";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Pesanan - Kebun Wiyono</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lilita+One&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=David+Libre&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/user/konfirmasi_pesanan.css">
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
        <div class="feature-container">
            <div class="text-center mb-4">
                <i class="fas fa-check-circle success-icon"></i>
                <h2>Pesanan Berhasil Dibuat!</h2>
                <p class="lead">Terima kasih telah berbelanja di Kebun Wiyono.</p>
            </div>

            <div class="info-box">
                <p class="mb-0">Status pesanan Anda saat ini adalah <strong>Menunggu Pembayaran</strong>. Silakan lakukan pembayaran sesuai instruksi di bawah untuk memproses pesanan Anda.</p>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="order-card">
                        <h4>Detail Pesanan</h4>
                        <div class="order-details">
                            <table class="w-100">
                                <tr>
                                    <td>Nomor Pesanan</td>
                                    <td><?= $order['nomor_pesanan'] ?></td>
                                </tr>
                                <tr>
                                    <td>Tanggal Pesanan</td>
                                    <td><?= $order_date ?></td>
                                </tr>
                                <tr>
                                    <td>Status</td>
                                    <td><span class="badge bg-warning text-dark"><?= $order['status'] ?></span></td>
                                </tr>
                                <tr>
                                    <td>Metode Pembayaran</td>
                                    <td><?= $payment_method_name ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="order-card">
                        <h4>Informasi Pengiriman</h4>
                        <div class="order-details">
                            <table class="w-100">
                                <tr>
                                    <td>Nama Penerima</td>
                                    <td><?= $order['nama_penerima'] ?></td>
                                </tr>
                                <tr>
                                    <td>Telepon</td>
                                    <td><?= $order['telepon'] ?></td>
                                </tr>
                                <tr>
                                    <td>Alamat</td>
                                    <td><?= $order['alamat'] ?>, <?= $order['kota'] ?>, <?= $order['kode_pos'] ?></td>
                                </tr>
                                <?php if (!empty($order['catatan'])): ?>
                                    <tr>
                                        <td>Catatan</td>
                                        <td><?= $order['catatan'] ?></td>
                                    </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="order-card mt-4">
                <h4>Produk yang Dipesan</h4>
                <div class="product-list">
                    <?php
                    $total_items = 0;
                    while ($item = $items_result->fetch_assoc()):
                        $total_items += $item['quantity'];
                    ?>
                        <div class="product-item">
                            <div class="product-info">
                                <img src="../fitur/gambarProduk/<?= $item['gambar']; ?>" class="product-img" alt="<?= $item['nama_produk']; ?>">
                                <div>
                                    <h6><?= $item['nama_produk']; ?></h6>
                                    <p class="text-muted mb-0"><?= $item['quantity']; ?> x Rp<?= number_format($item['harga']); ?></p>
                                </div>
                            </div>
                            <div class="product-price">
                                Rp<?= number_format($item['subtotal']); ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>

                <div class="row mt-4">
                    <div class="col-md-6 ms-auto">
                        <table class="table">
                            <tr>
                                <td>Subtotal (<?= $total_items ?> items)</td>
                                <td class="text-end">Rp<?= number_format($order['total_harga']); ?></td>
                            </tr>
                            <tr>
                                <td>Ongkos Kirim</td>
                                <td class="text-end">Rp<?= number_format($order['ongkir']); ?></td>
                            </tr>
                            <tr class="fw-bold">
                                <td>Total</td>
                                <td class="text-end">Rp<?= number_format($order['total_bayar']); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="payment-instructions">
                <h4>Instruksi Pembayaran</h4>
                <ul>
                    <?php foreach ($payment_instructions as $instruction): ?>
                        <li><?= $instruction ?></li>
                    <?php endforeach; ?>
                </ul>

                <?php if ($order['metode_pembayaran'] != 'cod'): ?>
                    <div class="mt-3">
                        <p><strong>Jumlah yang harus dibayar: Rp<?= number_format($order['total_bayar']); ?></strong></p>
                        <p>Mohon transfer tepat sampai nominal rupiah terakhir untuk memudahkan verifikasi pembayaran.</p>
                    </div>

                    <!-- Upload Bukti Pembayaran Section -->
                    <div class="upload-section mt-4 p-4" style="background: #f8f9fa; border-radius: 10px; border: 2px dashed #dee2e6;">
                        <?php if (empty($order['bukti_pembayaran'])): ?>
                            <h5 class="mb-3"><i class="fas fa-upload me-2"></i>Upload Bukti Pembayaran</h5>
                            <p class="text-muted mb-3">Setelah melakukan pembayaran, silakan upload bukti transfer di bawah ini:</p>

                            <form method="POST" enctype="multipart/form-data" id="uploadForm">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

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
                                <p class="text-muted">Status: <span class="badge bg-info"><?= $order['status'] ?></span></p>
                                <p>Terima kasih! Bukti pembayaran Anda sedang dalam proses verifikasi.</p>

                                <!-- Show uploaded proof -->
                                <div class="mt-3">
                                    <img src="../fitur/bukti_pembayaran/<?= $order['bukti_pembayaran'] ?>"
                                        alt="Bukti Pembayaran"
                                        style="max-width: 300px; max-height: 200px; border-radius: 8px; border: 1px solid #ddd;">
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                <?php else: ?>
                    <div class="mt-3">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Pembayaran COD</strong><br>
                            Tidak perlu upload bukti pembayaran. Siapkan uang pas saat kurir tiba.
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="mt-4 text-center no-print">
                <a href="javascript:window.print()" class="btn btn-outline me-2"><i class="fas fa-print me-2"></i>Cetak Pesanan</a>
                <a href="belanjaProduk.php" class="btn btn-lanjut"><i class="fas fa-shopping-bag me-2"></i>Lanjutkan Belanja</a>
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
        document.getElementById('bukti_pembayaran').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('preview');
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    } else {
        preview.style.display = 'none';
    }
});

// Validasi form
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