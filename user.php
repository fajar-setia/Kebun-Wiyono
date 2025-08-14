<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: index.php");
  exit;
}
include 'config.php';
$query = mysqli_query($conn, "SELECT * FROM produk");
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kebun Wiyono - Perkebunan Terbaik Se-Kabupaten Karimun</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Lilita+One&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=David+Libre&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css">
  <link rel="stylesheet" href="assets/css/user/user.css">
</head>

<body>
  <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container">
      <a class="navbar-brand" href="#">KEBUN WIYONO</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item">
            <a class="nav-link active" aria-current="page" href="user.php">Beranda</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#tentang-kami">Tentang Kami</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#metode-tumpang-sari">Metode</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#produk">Produk</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="userFeature/belanjaProduk.php">Belanja</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="userFeature/kontakKami.php">Kontak Kami</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="userFeature/keranjang.php">
              <i class="fas fa-shopping-cart"></i>
            </a>
          </li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="fas fa-user-circle"></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
              <li><a class="dropdown-item" href="userFeature/profile.php">Profil Saya</a></li>
              <li><a class="dropdown-item" href="userFeature/pesanan-saya.php">Pesanan Saya</a></li>
              <li>
                <hr class="dropdown-divider">
              </li>
              <li><a class="dropdown-item" href="logout.php">Logout</a></li>
            </ul>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <div class="hero-section">
    <div class="container hero-content">
      <h1 class="fade-up">KEBUN WIYONO</h1>
      <p class="lead fade-up">Perkebunan Terbaik Se-Kabupaten Karimun dengan Produk Organik Premium</p>
      <a href="#produk" class="btn btn-lg hero-btn fade-up">Lihat Produk Kami</a>
    </div>
  </div>

  <section id="tentang-kami" class="py-5">
    <div class="container feature-container fade-up">
      <h2 class="section-title">TENTANG KAMI</h2>
      <div class="row align-items-center">
        <div class="col-lg-6 mb-4 mb-lg-0">
          <img src="../assets/perkebunan2.jpg" class="img-fluid rounded-lg shadow" alt="Kebun Wiyono">
        </div>
        <div class="col-lg-6">
          <div class="about-text">
            <p>Perkebunan kami menghadirkan keseimbangan antara tradisi dan inovasi dalam budidaya tanaman. Dengan tanah subur dan metode pertanian berkelanjutan, kami memastikan setiap hasil panen memiliki kualitas terbaik.</p>

            <p>Dari buah segar hingga komoditas unggulan, kami berkomitmen untuk memberikan produk yang alami, sehat, dan bernilai tinggi. Teknologi modern kami mendukung efisiensi dalam proses pertanian, sambil tetap menjaga kelestarian lingkungan.</p>

            <p>Selain menghasilkan produk berkualitas, kami juga mengedepankan edukasi dan transparansi kepada pelanggan. Website ini menjadi jendela bagi Anda untuk mengenal lebih dekat teknik budidaya, keunggulan produk, serta nilai keberlanjutan yang kami junjung tinggi.</p>

            <div class="mt-4">
              <div class="row">
                <div class="col-6 col-md-4 mb-3">
                  <div class="d-flex align-items-center">
                    <i class="fas fa-leaf fa-2x text-success me-3"></i>
                    <div>
                      <h6 class="mb-0 fw-bold">100% Organik</h6>
                      <small class="text-muted">Tanpa bahan kimia</small>
                    </div>
                  </div>
                </div>
                <div class="col-6 col-md-4 mb-3">
                  <div class="d-flex align-items-center">
                    <i class="fas fa-truck fa-2x text-success me-3"></i>
                    <div>
                      <h6 class="mb-0 fw-bold">Pengiriman Cepat</h6>
                      <small class="text-muted">Kesegaran terjamin</small>
                    </div>
                  </div>
                </div>
                <div class="col-6 col-md-4 mb-3">
                  <div class="d-flex align-items-center">
                    <i class="fas fa-seedling fa-2x text-success me-3"></i>
                    <div>
                      <h6 class="mb-0 fw-bold">Berkelanjutan</h6>
                      <small class="text-muted">Ramah lingkungan</small>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section id="metode-tumpang-sari" class="py-5">
    <div class="container feature-container">
      <h2 class="section-title">METODE PERKEBUNAN KAMI</h2>
      <div class="row g-4 metode">
        <div class="col-md-6 fade-up slide-in-left">
          <div class="metode-card card h-100">
            <div class="card-body">
              <h5 class="card-title">Metode Tumpang Sari</h5>
              <p class="card-text">Tumpang sari perkebunan adalah praktik menanam beragam jenis tanaman perkebunan atau semusim secara berdampingan. Tujuannya adalah memanfaatkan lahan secara maksimal, menangkap lebih banyak cahaya, air, dan nutrisi dari tanah.</p>

              <p class="card-text">Dengan memilih tanaman yang saling melengkapi (beda kebutuhan, akar, tinggi), petani bisa mendapatkan hasil panen yang lebih banyak dan beragam dibandingkan menanam satu jenis saja.</p>

              <p class="card-text">Selain meningkatkan hasil, tumpang sari juga membantu mengurangi hama penyakit secara alami, menyuburkan tanah, dan menekan gulma. Contohnya, menanam pisang di antara barisan kopi atau jagung saat kelapa sawit masih muda.</p>

              <div class="mt-4">
                <span class="badge bg-success rounded-pill py-2 px-3 me-2 mb-2">Efisiensi Lahan</span>
                <span class="badge bg-success rounded-pill py-2 px-3 me-2 mb-2">Peningkatan Hasil Panen</span>
                <span class="badge bg-success rounded-pill py-2 px-3 me-2 mb-2">Pengendalian Hama Alami</span>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-6 fade-up slide-in-right">
          <div class="metode-image-card">
            <img src="../assets/metode_tumpang_sari.jpg" class="w-100 h-100 object-fit-cover">
          </div>
        </div>
      </div>
    </div>
  </section>

  <section id="produk" class="py-5">
    <div class="container feature-container">
      <h2 class="section-title">PRODUK UNGGULAN KAMI</h2>
      <div class="row">
        <?php
        $delay = 0;
        while ($row = mysqli_fetch_assoc($query)) {
          $delay += 100;
        ?>
          <div class="col-md-6 col-lg-4 mb-4 fade-up" style="transition-delay: <?= $delay ?>ms">
            <div class="product-card">
              <div class="product-img-wrapper">
                <a href="userFeature/detailProduk.php?id=<?= $row['id']; ?>">
                  <img src="fitur/gambarProduk/<?= $row['gambar']; ?>" alt="<?= $row['nama_produk']; ?>">
                  <div class="product-overlay"></div>
                </a>
              </div>
              <div class="card-body">
                <h5 class="card-title"><?= $row['nama_produk']; ?></h5>
                <p class="card-text"><?= $row['deskripsi']; ?></p>
                <div class="price-tag">
                  Rp<?= number_format($row['harga']); ?> /kg
                </div>
                <div class="product-actions">
                  <a href="userFeature/detailProduk.php?id=<?= $row['id']; ?>" class="btn btn-detail">
                    <i class="fas fa-eye"></i> Detail
                  </a>
                </div>
              </div>
            </div>
          </div>
        <?php } ?>
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
          <h5 class="footer-heading">KEBUN WIYONO</h5>
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
            <li><a href="#produk"><i class="fas fa-angle-right me-2"></i>Buah Naga</a></li>
            <li><a href="#produk"><i class="fas fa-angle-right me-2"></i>Durian</a></li>
            <li><a href="#produk"><i class="fas fa-angle-right me-2"></i>Alpukat</a></li>
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
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
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