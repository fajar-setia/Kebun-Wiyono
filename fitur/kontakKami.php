<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

require '../config.php'; // Menghubungkan ke database
// Query untuk mengambil semua data barang yang ada

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kontak Kami</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lilita+One&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css">
    <style>
        :root {
            --primary-color: #4CAF50;
            --secondary-color: #388E3C;
            --accent-color: #8BC34A;
            --dark-color: #2E7D32;
            --light-color: #F1F8E9;
            --text-dark: #212121;
            --text-light: #FAFAFA;
            --box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #FAFAFA;
            color: var(--text-dark);
            overflow-x: hidden;
        }

        h1,
        h2,
        h3,
        h4,
        h5 {
            font-family: 'Playfair Display', serif;
            font-weight: 600;
        }

        .navbar {
            font-family: 'Poppins', sans-serif;
            background-color: var(--primary-color);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 15px 0;
            transition: all 0.3s ease;
        }

        .navbar.scrolled {
            padding: 10px 0;
            background-color: rgba(76, 175, 80, 0.97);
            backdrop-filter: blur(10px);
        }

        .nav-link {
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 1px;
            padding: 10px 15px !important;
            margin: 0 3px;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            color: var(--light-color) !important;
            transform: translateY(-2px);
        }

        .navbar-brand {
            font-family: 'Lilita One', serif;
            font-weight: bold;
            font-size: 1.8rem;
            color: white !important;
            letter-spacing: 1px;
        }

        .navbar-dark .navbar-toggler {
            border-color: transparent;
        }

        .navbar-toggler:focus {
            box-shadow: none;
        }

         .feature-container {
            background-color: white;
            padding: 60px 40px;
            border-radius: 16px;
            box-shadow: var(--box-shadow);
            margin: 50px auto;
            /* Tengah secara horizontal */
            max-width: 1140px;
            /* Batasi lebar agar tidak terlalu melebar */
            position: relative;
            overflow: hidden;
        }

        .feature-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
        }

        .hero-section {
            position: relative;
            background-color: #000;
            color: white;
            padding: 120px 0;
            text-align: center;
            min-height: 80vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .hero-section::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('../assets/perkebunan2.jpg');
            background-repeat: no-repeat;
            background-position: center;
            background-size: cover;
            background-attachment: fixed;
            z-index: 0;
            filter: brightness(40%);
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .hero-section h1 {
            font-size: 4.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
            letter-spacing: 1px;
        }

        .hero-section .lead {
            font-size: 1.5rem;
            font-weight: 400;
            margin-bottom: 30px;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.5);
        }

        .filter-section {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }

        .page-footer {
            background-color: #2c3e50;
            color: white;
            padding: 4rem 0 2rem;
            margin-top: 5rem;
            position: relative;
        }

        .page-footer::before {
            content: '';
            position: absolute;
            top: -50px;
            left: 0;
            width: 100%;
            height: 50px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1440 100'%3E%3Cpath fill='%232c3e50' d='M0,0 C320,100 420,0 740,50 C1060,100 1360,0 1440,30 L1440,320 L0,320 L0,0 Z'%3E%3C/path%3E%3C/svg%3E");
            background-size: cover;
        }

        .footer-heading {
            font-family: 'Lilita One', serif;
            font-size: 1.8rem;
            margin-bottom: 20px;
            color: var(--accent-color);
        }

        .footer-text {
            line-height: 1.8;
            margin-bottom: 20px;
        }

        .footer-links {
            margin-bottom: 30px;
        }

        .footer-links h5 {
            color: var(--accent-color);
            font-size: 1.3rem;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .footer-links ul li {
            margin-bottom: 15px;
        }

        .footer-links a {
            color: #ddd;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .footer-links a:hover {
            color: var(--accent-color);
            transform: translateX(5px);
        }

        .contact-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .contact-icon {
            width: 35px;
            height: 35px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }

        .footer-copyright {
            padding-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            font-size: 0.9rem;
            color: #aaa;
        }

        .scroll-top {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 50px;
            height: 50px;
            background-color: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            cursor: pointer;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 999;
        }

        .scroll-top.active {
            opacity: 1;
            visibility: visible;
        }

        .scroll-top:hover {
            background-color: var(--dark-color);
            transform: translateY(-5px);
        }

        .fade-up {
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.6s ease, transform 0.6s ease;
        }

        .fade-up.active {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
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
                    <li class="nav-item"><a class="nav-link active" href="kontakKami.php">Kontak Kami</a></li>
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
                            <li><a class="dropdown-item" href="#">Profil Saya</a></li>
                            <li><a class="dropdown-item" href="#">Pesanan Saya</a></li>
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
            <h1 class="fade-up">KONTAK KAMI</h1>
            <p class="lead fade-up">Pilihan buah dan sayur organik berkualitas tinggi langsung dari kebun ke meja Anda. Dijamin segar dan bermanfaat untuk kesehatan.</p>
        </div>
    </section>

    <!-- SECTION: Kontak Kami -->
<section id="kontak" class="py-5 feature-container">
  <div class="container">
    <div class="row gy-4">
      <!-- Form Kontak -->
      <div class="col-lg-6">
        <h2 class="mb-4">Hubungi Kami</h2>
        <p class="mb-4">Ada pertanyaan, saran, atau kerjasama? Silakan isi form di bawah, kami akan segera merespons.</p>
        <form action="proses_kontak.php" method="POST">
          <div class="mb-3">
            <label for="nama" class="form-label">Nama Lengkap</label>
            <input type="text" class="form-control" id="nama" name="nama" placeholder="Nama Anda" required>
          </div>
          <div class="mb-3">
            <label for="email" class="form-label">Alamat Email</label>
            <input type="email" class="form-control" id="email" name="email" placeholder="email@contoh.com" required>
          </div>
          <div class="mb-3">
            <label for="subjek" class="form-label">Subjek</label>
            <input type="text" class="form-control" id="subjek" name="subjek" placeholder="Subjek Pesan" required>
          </div>
          <div class="mb-4">
            <label for="pesan" class="form-label">Pesan Anda</label>
            <textarea class="form-control" id="pesan" name="pesan" rows="5" placeholder="Tulis pesan di sini..." required></textarea>
          </div>
          <button type="submit" class="btn btn-success px-4">Kirim Pesan</button>
        </form>
      </div>
      <!-- Peta & Info Kontak -->
      <div class="col-lg-6">
        <h2 class="mb-4">Lokasi & Kontak</h2>
        <!-- Google Maps Embed -->
        <div class="mb-4" style="border:1px solid #ddd; border-radius:8px; overflow:hidden;">
          <iframe
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3958.1234567890123!2d110.1234567!3d-7.1234567!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e7a1a2b3c4d5e6f%3A0xabcdef1234567890!2sKebun%20Wiyono!5e0!3m2!1sid!2sid!4v1612345678901!5m2!1sid!2sid"
            width="100%" height="300" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
        </div>
        <!-- Detail Kontak -->
        <ul class="list-unstyled">
          <li class="d-flex mb-3">
            <div class="me-3 text-success fs-4">
              <i class="fas fa-map-marker-alt"></i>
            </div>
            <div>
              <strong>Alamat:</strong><br>
              Jl. Raya Kebun No.123, TG Balai Karimun, Kepulauan Riau
            </div>
          </li>
          <li class="d-flex mb-3">
            <div class="me-3 text-success fs-4">
              <i class="fas fa-phone"></i>
            </div>
            <div>
              <strong>Telepon:</strong><br>
              +62 823 4567 8910
            </div>
          </li>
          <li class="d-flex mb-3">
            <div class="me-3 text-success fs-4">
              <i class="fas fa-envelope"></i>
            </div>
            <div>
              <strong>Email:</strong><br>
              <a href="mailto:info@kebunwiyono.com">info@kebunwiyono.com</a>
            </div>
          </li>
          <li class="d-flex">
            <div class="me-3 text-success fs-4">
              <i class="fas fa-clock"></i>
            </div>
            <div>
              <strong>Jam Operasional:</strong><br>
              Senin – Sabtu, 08:00 – 17:00
            </div>
          </li>
        </ul>
        <!-- Ikon Sosial Media -->
        <div class="mt-4">
          <a href="#" class="me-3 fs-4 text-secondary"><i class="fab fa-facebook-f"></i></a>
          <a href="#" class="me-3 fs-4 text-secondary"><i class="fab fa-instagram"></i></a>
          <a href="#" class="me-3 fs-4 text-secondary"><i class="fab fa-twitter"></i></a>
          <a href="#" class="fs-4 text-secondary"><i class="fab fa-youtube"></i></a>
        </div>
      </div>
    </div>
  </div>
</section>


    <div class="scroll-top">
        <i class="fas fa-arrow-up"></i>
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
<?php
// Menutup koneksi
$conn->close();
?>

