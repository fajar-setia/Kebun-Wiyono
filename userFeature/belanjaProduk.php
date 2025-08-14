<?php
session_start();
require '../config.php';

if (!isset($_SESSION['user_id'])) { // pakai 'id' sesuai session yang kamu simpan saat login user
  header("Location: ../index.php"); // arahkan ke halaman login utama
  exit;
}
// Ambil semua produk
$result = $conn->query("SELECT p.*, k.nama_kategori 
                        FROM produk p
                        LEFT JOIN kategori k ON p.id_kategori = k.id_kategori
                        ORDER BY p.id DESC");

// Ambil semua kategori untuk filter
$kategori_result = $conn->query("SELECT * FROM kategori");
$kategoris = [];
while ($kategori = $kategori_result->fetch_assoc()) {
    $kategoris[] = $kategori;
}

// Filter by kategori jika ada
$filter_kategori = isset($_GET['kategori']) ? intval($_GET['kategori']) : 0;
if ($filter_kategori > 0) {
    $result = $conn->query("SELECT p.*, k.nama_kategori 
                          FROM produk p
                          LEFT JOIN kategori k ON p.id_kategori = k.id_kategori
                          WHERE p.id_kategori = $filter_kategori
                          ORDER BY p.id DESC");
}

// Search by nama produk jika ada
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
if (!empty($search)) {
    $result = $conn->query("SELECT p.*, k.nama_kategori 
                          FROM produk p
                          LEFT JOIN kategori k ON p.id_kategori = k.id_kategori
                          WHERE p.nama_produk LIKE '%$search%'
                          ORDER BY p.id DESC");
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Belanja Produk - Kebun Wiyono</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lilita+One&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=David+Libre&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css">
    <link rel="stylesheet" href="../assets/css/user/BelanjaPrroduk.css">
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
                    <li class="nav-item"><a class="nav-link active" href="belanjaProduk.php">Belanja</a></li>
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

    <section class="hero-section">
        <div class="container">
            <h1 class="fade-up">TEMUKAN PRODUK SEGAR DARI KEBUN</h1>
            <p class="lead fade-up">Pilihan buah dan sayur organik berkualitas tinggi langsung dari kebun ke meja Anda. Dijamin segar dan bermanfaat untuk kesehatan.</p>
        </div>
    </section>

    <div class="container feature-container">
        <div class="filter-section ">
            <div class="row g-3">
                <div class="col-md-6">
                    <form action="belanjaProduk.php" method="GET" class="search-form">
                        <input type="text" class="form-control" placeholder="Cari produk..." name="search" value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="search-btn">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
                <div class="col-md-6">
                    <form action="belanjaProduk.php" method="GET" id="filterForm">
                        <select class="form-select" name="kategori" onchange="document.getElementById('filterForm').submit()">
                            <option value="0">Semua Kategori</option>
                            <?php foreach ($kategoris as $kategori): ?>
                                <option value="<?= $kategori['id_kategori'] ?>" <?= $filter_kategori == $kategori['id_kategori'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($kategori['nama_kategori']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
            </div>
        </div>

        <?php if ($result->num_rows == 0): ?>
            <div class="empty-state">
                <i class="fas fa-shopping-basket"></i>
                <h3>Tidak ada produk ditemukan</h3>
                <p>Coba kata kunci pencarian lain atau pilih kategori berbeda</p>
                <a href="belanjaProduk.php" class="btn btn-outline mt-3">Lihat Semua Produk</a>
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4 mb-4">
                <?php while ($produk = $result->fetch_assoc()): ?>
                    <div class="col-md-6 col-lg-4 mb-4 fade-up" style="transition-delay: <?= $delay ?>ms">
                        <div class="card product-card">
                            <div class="product-img-container">
                                <img src="../fitur/gambarProduk/<?= htmlspecialchars($produk['gambar']) ?>" class="card-img-top" alt="<?= htmlspecialchars($produk['nama_produk']) ?>">
                                <?php if (!empty($produk['nama_kategori'])): ?>
                                    <span class="product-category"><?= htmlspecialchars($produk['nama_kategori']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?= htmlspecialchars($produk['nama_produk']) ?></h5>
                                <p class="card-text"><?= htmlspecialchars($produk['deskripsi']) ?></p>
                                <div class="d-flex justify-content-between align-items-center mt-auto">
                                    <span class="product-price">Rp <?= number_format($produk['harga'], 0, ',', '.') ?> /kg</span>
                                    <a href="detailProduk.php?id=<?= $produk['id'] ?>" class="btn btn-sm btn-detail">
                                        <i class="fas fa-eye me-1"></i> Detail
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="scroll-top">
        <i class="fas fa-arrow-up"></i>
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

            // Scroll to top button visibility
            const scrollTopBtn = document.querySelector('.scroll-top');
            if (window.scrollY > 300) {
                scrollTopBtn.classList.add('active');
            } else {
                scrollTopBtn.classList.remove('active');
            }

            // Animations on scroll
            document.querySelectorAll('.fade-up, .fade-in, .slide-in-left, .slide-in-right').forEach(element => {
                const position = element.getBoundingClientRect();
                // If element is in viewport
                if (position.top < window.innerHeight - 150) {
                    element.classList.add('active');
                }
            });
        });

        // Scroll to top functionality
        document.querySelector('.scroll-top').addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // Smooth scrolling for anchor links in navbar
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();

                const targetId = this.getAttribute('href');
                if (targetId === '#') return;

                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    const navbarHeight = document.querySelector('.navbar').offsetHeight;
                    const targetPosition = targetElement.getBoundingClientRect().top + window.pageYOffset - navbarHeight;

                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });

                    // Close mobile menu if open
                    const navbarToggler = document.querySelector('.navbar-toggler');
                    const navbarCollapse = document.querySelector('.navbar-collapse');
                    if (navbarCollapse.classList.contains('show')) {
                        navbarToggler.click();
                    }
                }
            });
        });

        // Product Quantity Controls
        function increaseQuantity(id) {
            var quantityElement = document.getElementById('quantity' + id);
            var currentQuantity = parseInt(quantityElement.textContent);
            quantityElement.textContent = currentQuantity + 1;
        }

        function decreaseQuantity(id) {
            var quantityElement = document.getElementById('quantity' + id);
            var currentQuantity = parseInt(quantityElement.textContent);
            if (currentQuantity > 1) {
                quantityElement.textContent = currentQuantity - 1;
            }
        }

        function addToCart(id) {
            var quantity = parseInt(document.getElementById('quantity' + id).textContent);
            window.location.href = 'userFeature/keranjang.php?action=add&id=' + id + '&qty=' + quantity;
        }

        // Activate animations on elements already in viewport on page load
        window.addEventListener('DOMContentLoaded', function() {
            // Trigger the scroll event to initialize animations
            window.dispatchEvent(new Event('scroll'));

            // Activate hero section animations
            setTimeout(function() {
                document.querySelectorAll('.hero-content .fade-up').forEach((element, index) => {
                    setTimeout(() => {
                        element.classList.add('active');
                    }, 300 * index);
                });
            }, 300);
        });
    </script>
</body>

</html>
<?php $conn->close(); ?>