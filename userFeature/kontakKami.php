<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

require '../config.php'; // Koneksi ke database

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer-master/src/Exception.php';
require '../PHPMailer-master/src/PHPMailer.php';
require '../PHPMailer-master/src/SMTP.php';

$status_message = "";
$status_success = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama    = $_POST['nama'];
    $email   = $_POST['email'];
    $subjek  = $_POST['subjek'];
    $pesan   = $_POST['pesan'];

    // Simpan ke database
    $stmt = $conn->prepare("INSERT INTO pesan_kontak (nama, email, subjek, pesan) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $nama, $email, $subjek, $pesan);
    $stmt->execute();
    $stmt->close();

    // Kirim email
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'setiafajar935@gmail.com'; // Ganti
        $mail->Password   = 'axux wtmx aqya doun'; // Ganti
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom($email, $nama);
        $mail->addAddress('setiafajar935@gmail.com', 'KebunWiyono');

        $mail->isHTML(true);
        $mail->Subject = $subjek;
        $mail->Body    = "<b>Nama:</b> $nama<br><b>Email:</b> $email<br><b>Pesan:</b><br>$pesan";

        $mail->send();
        $status_message = "Pesan berhasil dikirim!";
        $status_success = true;
    } catch (Exception $e) {
        $status_message = "Pesan gagal dikirim. Mailer Error: {$mail->ErrorInfo}";
        $status_success = false;
    }
}
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
    <link href="https://fonts.googleapis.com/css2?family=David+Libre&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css">
    <link rel="stylesheet" href="../assets/css/user/kontak.css">
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
            <h1 class="fade-up">KONTAK KAMI</h1>
            <p class="lead fade-up">Pilihan buah dan sayur organik berkualitas tinggi langsung dari kebun ke meja Anda. Dijamin segar dan bermanfaat untuk kesehatan.</p>
        </div>
    </section>


    <section id="kontak" class="py-5 feature-container">
        <div class="container">
            <div class="row gy-4">
                <!-- Form Kontak -->
                <div class="col-lg-6">
                    <h2 class="mb-4">Hubungi Kami</h2>
                    <?php if ($status_message): ?>
                        <p class="status-message <?= $status_success ? 'status-success' : 'status-error' ?>">
                            <?= $status_message ?>
                        </p>
                    <?php endif; ?>
                    <p class="mb-4">Ada pertanyaan, saran, atau kerjasama? Silakan isi form di bawah, kami akan segera merespons.</p>
                    <form method="POST" action="kontakKami.php">
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
                            width="100%"
                            height="300"
                            style="border:0"
                            loading="lazy"
                            allowfullscreen
                            referrerpolicy="no-referrer-when-downgrade"
                            src="https://maps.google.com/maps?q=-7.845357919508926,110.39331340175495&z=15&output=embed">
                        </iframe>
                    </div>
                    <!-- Detail Kontak -->
                    <ul class="list-unstyled">
                        <li class="d-flex mb-3">
                            <div class="me-3 text-success fs-4">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div>
                                <strong>Alamat:</strong><br>
                                Jl. Raya Kebun No.123, Karimun, batam
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
                                <a href="mailto:info@kebunwiyono.com">KebunWiyono30@gmail.com</a>
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