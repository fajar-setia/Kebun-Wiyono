<?php
// File: userFeature/keranjang.php
session_start();
include '../config.php';

// Initialize the cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Add product to cart when "Add to Cart" button is clicked
if (isset($_GET['action']) && $_GET['action'] == 'add') {
    $product_id = $_GET['id'];
    $quantity = $_GET['qty'];
    
    // Check if product exists
    $query = mysqli_query($conn, "SELECT * FROM produk WHERE id = $product_id");
    $product = mysqli_fetch_assoc($query);
    
    if ($product) {
        // Check if the product is already in the cart
        $product_exists = false;
        foreach ($_SESSION['cart'] as $key => $item) {
            if ($item['id'] == $product_id) {
                $_SESSION['cart'][$key]['quantity'] += $quantity;
                $product_exists = true;
                break;
            }
        }
        
        // If the product doesn't exist in the cart, add it
        if (!$product_exists) {
            $_SESSION['cart'][] = [
                'id' => $product_id,
                'nama_produk' => $product['nama_produk'],
                'harga' => $product['harga'],
                'gambar' => $product['gambar'],
                'quantity' => $quantity
            ];
        }
        
        // Redirect back with success message
        header("Location: keranjang.php?status=added");
        exit;
    }
}

// Remove item from cart
if (isset($_GET['action']) && $_GET['action'] == 'remove') {
    $product_id = $_GET['id'];
    
    foreach ($_SESSION['cart'] as $key => $item) {
        if ($item['id'] == $product_id) {
            unset($_SESSION['cart'][$key]);
            break;
        }
    }
    
    // Re-index the array
    $_SESSION['cart'] = array_values($_SESSION['cart']);
    
    // Redirect back to cart
    header("Location: keranjang.php?status=removed");
    exit;
}

// Update quantity
if (isset($_GET['action']) && $_GET['action'] == 'update') {
    $product_id = $_GET['id'];
    $quantity = $_GET['qty'];
    
    foreach ($_SESSION['cart'] as $key => $item) {
        if ($item['id'] == $product_id) {
            $_SESSION['cart'][$key]['quantity'] = $quantity;
            break;
        }
    }
    
    // Redirect back to cart
    header("Location: keranjang.php?status=updated");
    exit;
}

// Clear the cart
if (isset($_GET['action']) && $_GET['action'] == 'clear') {
    $_SESSION['cart'] = [];
    header("Location: keranjang.php?status=cleared");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lilita+One&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=David+Libre&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css">
    <link rel="stylesheet" href="../assets/css/user/keranjang.css">
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
                        <a class="nav-link active" href="keranjang.php">
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

    <div class="container feature-container">
        <h2 class="mb-4">Keranjang Belanja</h2>
        
        <?php if (isset($_GET['status'])): ?>
            <?php if ($_GET['status'] === 'added'): ?>
                <div class="alert alert-success" role="alert">
                    Produk berhasil ditambahkan ke keranjang.
                </div>
            <?php elseif ($_GET['status'] === 'removed'): ?>
                <div class="alert alert-warning" role="alert">
                    Produk berhasil dihapus dari keranjang.
                </div>
            <?php elseif ($_GET['status'] === 'updated'): ?>
                <div class="alert alert-info" role="alert">
                    Jumlah produk berhasil diperbarui.
                </div>
            <?php elseif ($_GET['status'] === 'cleared'): ?>
                <div class="alert alert-warning" role="alert">
                    Keranjang belanja berhasil dikosongkan.
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php if (empty($_SESSION['cart'])): ?>
            <div class="empty-cart">
                <h3>Keranjang Belanja Kosong</h3>
                <p>Yuk mulai belanja produk dari Kebun Wiyono!</p>
                <a href="belanjaProduk.php" class="btn btn-belanja mt-3">Belanja Sekarang</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Harga</th>
                            <th>Jumlah</th>
                            <th>Subtotal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total = 0;
                        foreach ($_SESSION['cart'] as $item): 
                            $subtotal = $item['harga'] * $item['quantity'];
                            $total += $subtotal;
                        ?>
                            <tr class="cart-item">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="../fitur/gambarProduk/<?= $item['gambar']; ?>" class="product-img me-3" alt="<?= $item['nama_produk']; ?>">
                                        <span><?= $item['nama_produk']; ?></span>
                                    </div>
                                </td>
                                <td>Rp<?= number_format($item['harga']); ?></td>
                                <td>
                                    <form action="keranjang.php" method="get" class="quantity-control">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="id" value="<?= $item['id']; ?>">
                                        <button type="button" class="quantity-btn" onclick="decreaseQty(this)">-</button>
                                        <input type="number" name="qty" value="<?= $item['quantity']; ?>" min="1" class="quantity-input" onchange="this.form.submit()">
                                        <button type="button" class="quantity-btn" onclick="increaseQty(this)">+</button>
                                    </form>
                                </td>
                                <td>Rp<?= number_format($subtotal); ?></td>
                                <td>
                                    <a href="keranjang.php?action=remove&id=<?= $item['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus produk ini dari keranjang?')">Hapus</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td colspan="3" class="text-end fw-bold">Total:</td>
                            <td class="fw-bold">Rp<?= number_format($total); ?></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="d-flex justify-content-between mt-4">
                <a href="keranjang.php?action=clear" class="btn btn-warning" onclick="return confirm('Yakin ingin mengosongkan keranjang?')">Kosongkan Keranjang</a>
                <a href="checkout.php" class="btn btn-success">Checkout</a>
            </div>
        <?php endif; ?>
    </div>

     <footer class="page-footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4 mb-md-0">
                    <h5 class="footer-heading">Kebun Wiyono</h5>
                    <p class="footer-text">Menyediakan produk organik segar langsung dari kebun untuk kesehatan dan kebaikan Anda. Kami berkomitmen untuk kualitas terbaik dan kelestarian lingkungan.</p>
                    <div class="social-icons mt-4">
                        <a href="#" class="me-3"><i class="fab fa-facebook-f fa-lg text-white"></i></a>
                        <a href="#" class="me-3"><i class="fab fa-instagram fa-lg text-white"></i></a>
                        <a href="#" class="me-3"><i class="fab fa-twitter fa-lg text-white"></i></a>
                        <a href="#" class="me-3"><i class="fab fa-youtube fa-lg text-white"></i></a>
                    </div>
                </div>

                <div class="col-lg-2 col-md-6 mb-4 mb-md-0">
                    <h5 class="footer-links">Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="user.php"><i class="fas fa-angle-right me-2"></i>Beranda</a></li>
                        <li><a href="#tentang-kami"><i class="fas fa-angle-right me-2"></i>Tentang Kami</a></li>
                        <li><a href="#produk"><i class="fas fa-angle-right me-2"></i>Produk</a></li>
                        <li><a href="userFeature/belanjaProduk.php"><i class="fas fa-angle-right me-2"></i>Belanja</a></li>
                        <li><a href="userFeature/fitur3.php"><i class="fas fa-angle-right me-2"></i>Kontak</a></li>
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
        function decreaseQty(button) {
            const input = button.nextElementSibling;
            const currentValue = parseInt(input.value);
            if (currentValue > 1) {
                input.value = currentValue - 1;
                input.form.submit();
            }
        }

        function increaseQty(button) {
            const input = button.previousElementSibling;
            input.value = parseInt(input.value) + 1;
            input.form.submit();
        }
    </script>
</body>
</html>