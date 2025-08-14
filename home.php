<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
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
  <title>Beranda</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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

    .navbar-brand {
      font-family: 'Lilita One', serif;
      font-weight: bold;
      font-size: 1.8rem;
      color: white !important;
      letter-spacing: 1px;
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

    .navbar-dark .navbar-toggler {
      border-color: transparent;
    }

    .navbar-toggler:focus {
      box-shadow: none;
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
      background-image: url('assets/perkebunan2.jpg');
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

    .hero-btn {
      padding: 12px 30px;
      font-size: 1rem;
      text-transform: uppercase;
      letter-spacing: 1px;
      font-weight: 600;
      border-radius: 50px;
      transition: all 0.3s ease;
      background-color: var(--primary-color);
      border-color: var(--primary-color);
    }

    .hero-btn:hover {
      background-color: var(--dark-color);
      border-color: var(--dark-color);
      transform: translateY(-3px);
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
    }

    .section-title {
      position: relative;
      margin-bottom: 60px;
      padding-bottom: 20px;
      text-align: center;
    }

    .section-title::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 50%;
      transform: translateX(-50%);
      width: 80px;
      height: 3px;
      background-color: var(--primary-color);
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

    .about-text {
      font-size: 1.1rem;
      line-height: 1.8;
      text-align: justify;
    }

    .metode-card {
      position: relative;
      overflow: hidden;
      border: none;
      border-radius: 16px;
      box-shadow: var(--box-shadow);
      transition: all 0.3s ease;
    }

    .metode-card:hover {
      transform: translateY(-10px);
    }

    .metode-card .card-body {
      padding: 2rem;
    }

    .metode-card h5 {
      font-size: 1.8rem;
      margin-bottom: 1.5rem;
      color: var(--dark-color);
    }

    .metode-card p {
      font-size: 1.05rem;
      line-height: 1.8;
    }

    .metode-image-card {
      overflow: hidden;
      border-radius: 16px;
      box-shadow: var(--box-shadow);
      width: 100%;
      height: auto;
      object-fit: contain;
      margin-top: 150px;
    }

    .metode-image-card img {
      transition: transform 0.8s ease;
      height: 100%;
    }

    .metode-image-card:hover img {
      transform: scale(1.05);
    }

    .product-card {
      border: none;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: var(--box-shadow);
      transition: all 0.4s ease;
      height: 100%;
    }

    .product-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
    }

    .product-img-wrapper {
      height: 250px;
      overflow: hidden;
      position: relative;
    }

    .product-card img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.6s ease;
    }

    .product-card:hover img {
      transform: scale(1.08);
    }

    .product-overlay {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(to bottom, rgba(0, 0, 0, 0), rgba(0, 0, 0, 0.6));
      opacity: 0;
      transition: opacity 0.3s ease;
    }

    .product-card:hover .product-overlay {
      opacity: 1;
    }

    .product-card .card-body {
      padding: 1.5rem;
    }

    .product-card .card-title {
      font-size: 1.3rem;
      font-weight: 600;
      margin-bottom: 0.75rem;
      color: var(--text-dark);
    }

    .product-card .card-text {
      font-size: 0.95rem;
      color: #666;
      margin-bottom: 1rem;
      height: 3rem;
      overflow: hidden;
      text-overflow: ellipsis;
      display: -webkit-box;
      /* -webkit-line-clamp: 2; */
      -webkit-box-orient: vertical;
    }

    .price-tag {
      display: inline-block;
      padding: 5px 12px;
      background-color: var(--light-color);
      color: var(--dark-color);
      font-weight: 700;
      border-radius: 50px;
      margin-bottom: 1rem;
      font-size: 1.1rem;
    }

    .product-actions {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .quantity-control {
      display: flex;
      align-items: center;
      border: 1px solid #ddd;
      border-radius: 50px;
      overflow: hidden;
    }

    .quantity-btn {
      background: none;
      border: none;
      width: 32px;
      height: 32px;
      font-size: 0.9rem;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .quantity-btn:hover {
      background-color: #f0f0f0;
    }

    .quantity-display {
      padding: 0 10px;
      min-width: 32px;
      text-align: center;
      font-weight: 600;
    }

    .btn-detail {
      padding: 8px 20px;
      font-size: 0.9rem;
      font-weight: 600;
      border-radius: 50px;
      background-color: var(--primary-color);
      border: none;
      transition: all 0.3s ease;
    }

    .btn-detail:hover {
      background-color: var(--dark-color);
      transform: translateY(-2px);
      box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
    }

    .btn-detail i {
      margin-right: 5px;
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

    /* Animation Classes */
    .fade-up {
      opacity: 0;
      transform: translateY(30px);
      transition: opacity 0.6s ease, transform 0.6s ease;
    }

    .fade-up.active {
      opacity: 1;
      transform: translateY(0);
    }

    .fade-in {
      opacity: 0;
      transition: opacity 0.6s ease;
    }

    .fade-in.active {
      opacity: 1;
    }

    .slide-in-right {
      opacity: 0;
      transform: translateX(50px);
      transition: opacity 0.6s ease, transform 0.6s ease;
    }

    .slide-in-right.active {
      opacity: 1;
      transform: translateX(0);
    }

    .slide-in-left {
      opacity: 0;
      transform: translateX(-50px);
      transition: opacity 0.6s ease, transform 0.6s ease;
    }

    .slide-in-left.active {
      opacity: 1;
      transform: translateX(0);
    }

    /* Responsive Adjustments */
    @media (max-width: 991.98px) {
      .hero-section h1 {
        font-size: 3.5rem;
      }

      .hero-section .lead {
        font-size: 1.2rem;
      }

      .navbar-collapse {
        background-color: var(--primary-color);
        padding: 20px;
        border-radius: 10px;
        margin-top: 15px;
      }
    }

    @media (max-width: 767.98px) {
      .hero-section h1 {
        font-size: 2.8rem;
      }

      .hero-section {
        padding: 80px 0;
      }

      .section-title {
        margin-bottom: 40px;
      }

      .feature-container {
        padding: 40px 20px;
      }
    }

    @media (max-width: 575.98px) {
      .hero-section h1 {
        font-size: 2.2rem;
      }

      .hero-section .lead {
        font-size: 1rem;
      }

      .hero-section {
        padding: 60px 0;
        min-height: 60vh;
      }

      .product-card .card-title {
        font-size: 1.1rem;
      }
    }
  </style>
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
            <a class="nav-link active" aria-current="page" href="home.php">Beranda</a>
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
            <a class="nav-link" href="fitur/lihat_produk.php">Produk</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="fitur/kontakKami.php">Kontak Kami</a>
          </li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="fas fa-user-circle"></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
              <li><a class="dropdown-item" href="fitur/pesanan.php">Pesanan</a></li>
              <li><a class="dropdown-item" href="fitur/proses_tambah_produk.php">Tambah Produk</a></li>
              <li><a class="dropdown-item" href="#">Hapus Produk</a></li>
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
      <h1 class="fade-up">Kebun Wiyono</h1>
      <p class="lead fade-up">Perkebunan Terbaik Se-Kabupaten Karimun dengan Produk Organik Premium</p>
    </div>
  </div>

  <section id="tentang-kami" class="py-5">
    <div class="container feature-container fade-up">
      <h2 class="section-title">Tentang Kami</h2>
      <div class="row align-items-center">
        <div class="col-lg-6 mb-4 mb-lg-0">
          <img src="../tugasMPTI/assets/perkebunan2.jpg" class="img-fluid rounded-lg shadow" alt="Kebun Wiyono">
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
      <h2 class="section-title">Metode Perkebunan Kami</h2>
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
            <img src="../tugasMPTI/assets/metode_tumpang_sari.jpg" class="w-100 h-100 object-fit-cover">
          </div>
        </div>
      </div>
    </div>
  </section>

   <section id="produk" class="py-5">
    <div class="container feature-container">
      <h2 class="section-title">Produk Unggulan Kami</h2>
      <div class="row">
        <?php
        $delay = 0;
        while ($row = mysqli_fetch_assoc($query)) {
          $delay += 100;
        ?>
          <div class="col-md-6 col-lg-4 mb-4 fade-up" style="transition-delay: <?= $delay ?>ms">
            <div class="product-card">
              <div class="product-img-wrapper">
                <a href="fitur/lihat_produk.php?= $row['id']; ?>">
                  <img src="fitur/gambarProduk/<?= $row['gambar']; ?>" alt="<?= $row['nama_produk']; ?>">
                  <div class="product-overlay"></div>
                </a>
              </div>
              <div class="card-body">
                <h5 class="card-title"><?= $row['nama_produk']; ?></h5>
                <p class="card-text"><?= $row['deskripsi']; ?></p>
                <div class="price-tag">
                  Rp<?= number_format($row['harga']); ?>
                </div>
                <div class="product-actions">
                  <a href="fitur/detail_produk.php $row['id']; ?>" class="btn btn-primary btn-detail">
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