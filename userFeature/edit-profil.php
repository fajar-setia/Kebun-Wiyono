<?php
session_start();
require '../config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
  header("Location: ../index.php");
  exit;
}

// Fetch user data
$user_id = $_SESSION['user_id'];
$user = null;
$error_message = '';
$success_message = '';

try {
  $sql = "SELECT * FROM pengguna WHERE id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
  } else {
    $error_message = "Data pengguna tidak ditemukan.";
  }
} catch (Exception $e) {
  $error_message = "Error mengambil data pengguna: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nama_lengkap = trim($_POST['nama_lengkap']);
  $telepon = trim($_POST['telepon']);
  $jenis_kelamin = $_POST['jenis_kelamin'];
  $tanggal_lahir = $_POST['tanggal_lahir'];
  $alamat = trim($_POST['alamat']);
  $kota = trim($_POST['kota']);
  $provinsi = trim($_POST['provinsi']);
  $kode_pos = trim($_POST['kode_pos']);
  
  // Validate input data
  $errors = [];
  
  if (empty($nama_lengkap)) {
    $errors[] = "Nama lengkap harus diisi";
  }
  
  if (empty($telepon)) {
    $errors[] = "Nomor telepon harus diisi";
  } elseif (!preg_match('/^[0-9]{10,15}$/', str_replace([' ', '-', '+'], '', $telepon))) {
    $errors[] = "Format nomor telepon tidak valid";
  }
  
  if (empty($alamat)) {
    $errors[] = "Alamat harus diisi";
  }
  
  if (empty($kota)) {
    $errors[] = "Kota harus diisi";
  }
  
  if (empty($provinsi)) {
    $errors[] = "Provinsi harus diisi";
  }
  
  if (empty($kode_pos)) {
    $errors[] = "Kode pos harus diisi";
  } elseif (!preg_match('/^[0-9]{5}$/', $kode_pos)) {
    $errors[] = "Kode pos harus terdiri dari 5 digit angka";
  }
  
  // Handle profile picture upload
  $foto_profil = $user['foto_profil']; // Default to existing photo
  
  if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
    $file_type = $_FILES['foto_profil']['type'];
    $file_size = $_FILES['foto_profil']['size'];
    $max_size = 2 * 1024 * 1024; // 2MB
    
    if (!in_array($file_type, $allowed_types)) {
      $errors[] = "Jenis file tidak diizinkan. Hanya file JPG, JPEG, dan PNG yang diperbolehkan.";
    } elseif ($file_size > $max_size) {
      $errors[] = "Ukuran file terlalu besar. Maksimal ukuran file adalah 2MB.";
    } else {
      $upload_dir = '../uploads/profile/';
      
      // Create directory if not exists
      if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
      }
      
      // Generate unique filename
      $file_extension = pathinfo($_FILES['foto_profil']['name'], PATHINFO_EXTENSION);
      $new_filename = uniqid('profile_') . '.' . $file_extension;
      $upload_path = $upload_dir . $new_filename;
      
      if (move_uploaded_file($_FILES['foto_profil']['tmp_name'], $upload_path)) {
        // If upload successful, delete old profile picture
        if (!empty($user['foto_profil']) && file_exists($upload_dir . $user['foto_profil'])) {
          unlink($upload_dir . $user['foto_profil']);
        }
        $foto_profil = $new_filename;
      } else {
        $errors[] = "Gagal mengunggah foto profil. Silakan coba lagi.";
      }
    }
  }
  
  // If no errors, update user data
  if (empty($errors)) {
    try {
      $update_sql = "UPDATE pengguna SET 
                       nama_lengkap = ?, 
                       telepon = ?, 
                       jenis_kelamin = ?, 
                       tanggal_lahir = ?, 
                       alamat = ?, 
                       kota = ?, 
                       provinsi = ?, 
                       kode_pos = ?,
                       foto_profil = ?
                     WHERE id = ?";
      
      $update_stmt = $conn->prepare($update_sql);
      $update_stmt->bind_param("sssssssssi", 
                       $nama_lengkap, 
                       $telepon, 
                       $jenis_kelamin, 
                       $tanggal_lahir, 
                       $alamat, 
                       $kota, 
                       $provinsi, 
                       $kode_pos,
                       $foto_profil,
                       $user_id);
      
      if ($update_stmt->execute()) {
        $success_message = "Profil berhasil diperbarui!";
        
        // Refresh user data
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
          $user = $result->fetch_assoc();
        }
      } else {
        $error_message = "Gagal memperbarui profil. Silakan coba lagi.";
      }
    } catch (Exception $e) {
      $error_message = "Error memperbarui data: " . $e->getMessage();
    }
  } else {
    $error_message = implode("<br>", $errors);
  }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Profil - Kebun Wiyono</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Lilita+One&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=David+Libre&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css">
  <link rel="stylesheet" href="../assets/css/user/editProfile.css">
</head>

<body>
  <!-- Navigation Bar -->
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

  <!-- Edit Profile Content -->
  <section id="edit-profile" class="py-5 profile-section feature-container">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-10">
          <h2 class="mb-4 text-center">Edit Profil</h2>
          
          <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
              <?php echo $error_message; ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          <?php endif; ?>
          
          <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show">
              <?php echo $success_message; ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          <?php endif; ?>
          
          <form method="POST" enctype="multipart/form-data" class="card border-0 shadow-sm p-4">
            <div class="text-center mb-4">
              <div class="profile-pic-wrapper">
                <?php
                $fotoProfil = isset($user['foto_profil']) && !empty($user['foto_profil'])
                  ? '../uploads/profile/' . htmlspecialchars($user['foto_profil'], ENT_QUOTES, 'UTF-8')
                  : '../assets/avatar-placeholder.jpg';
                ?>
                <img src="<?= $fotoProfil ?>"
                  id="preview-profile-pic"
                  onerror="this.onerror=null; this.src='https://via.placeholder.com/150';"
                  class="profile-pic"
                  alt="Foto Profil">
                <label for="foto_profil" class="pic-upload-btn">
                  <i class="fas fa-camera"></i>
                </label>
                <input type="file" name="foto_profil" id="foto_profil" accept="image/jpeg, image/png, image/jpg">
              </div>
              <p class="text-muted small">Klik ikon kamera untuk mengganti foto profil</p>
            </div>
            
            <div class="row">
              <div class="col-12">
                <h5 class="mb-3">Informasi Pribadi</h5>
              </div>
              
              <div class="col-md-6 mb-3">
                <label for="nama_lengkap" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" value="<?php echo isset($user['nama_lengkap']) ? htmlspecialchars($user['nama_lengkap']) : ''; ?>" required>
              </div>
              
              <div class="col-md-6 mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" value="<?php echo isset($user['username']) ? htmlspecialchars($user['username']) : ''; ?>" disabled>
                <div class="form-text">Username tidak dapat diubah.</div>
              </div>
              
              <div class="col-md-6 mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" value="<?php echo isset($user['email']) ? htmlspecialchars($user['email']) : ''; ?>" disabled>
                <div class="form-text">Email tidak dapat diubah.</div>
              </div>
              
              <div class="col-md-6 mb-3">
                <label for="telepon" class="form-label">No. Telepon <span class="text-danger">*</span></label>
                <input type="tel" class="form-control" id="telepon" name="telepon" value="<?php echo isset($user['telepon']) ? htmlspecialchars($user['telepon']) : ''; ?>" required>
              </div>
              
              <div class="col-md-6 mb-3">
                <label for="jenis_kelamin" class="form-label">Jenis Kelamin</label>
                <select class="form-select" id="jenis_kelamin" name="jenis_kelamin">
                  <option value="">Pilih Jenis Kelamin</option>
                  <option value="L" <?php echo (isset($user['jenis_kelamin']) && $user['jenis_kelamin'] == 'L') ? 'selected' : ''; ?>>Laki-laki</option>
                  <option value="P" <?php echo (isset($user['jenis_kelamin']) && $user['jenis_kelamin'] == 'P') ? 'selected' : ''; ?>>Perempuan</option>
                </select>
              </div>
              
              <div class="col-md-6 mb-3">
                <label for="tanggal_lahir" class="form-label">Tanggal Lahir</label>
                <input type="date" class="form-control" id="tanggal_lahir" name="tanggal_lahir" value="<?php echo isset($user['tanggal_lahir']) ? $user['tanggal_lahir'] : ''; ?>">
              </div>
              
              <div class="col-12 mt-4">
                <h5 class="mb-3">Alamat Pengiriman</h5>
              </div>
              
              <div class="col-12 mb-3">
                <label for="alamat" class="form-label">Alamat Lengkap <span class="text-danger">*</span></label>
                <textarea class="form-control" id="alamat" name="alamat" rows="3" required><?php echo isset($user['alamat']) ? htmlspecialchars($user['alamat']) : ''; ?></textarea>
              </div>
              
              <div class="col-md-4 mb-3">
                <label for="kota" class="form-label">Kota <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="kota" name="kota" value="<?php echo isset($user['kota']) ? htmlspecialchars($user['kota']) : ''; ?>" required>
              </div>
              
              <div class="col-md-4 mb-3">
                <label for="provinsi" class="form-label">Provinsi <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="provinsi" name="provinsi" value="<?php echo isset($user['provinsi']) ? htmlspecialchars($user['provinsi']) : ''; ?>" required>
              </div>
              
              <div class="col-md-4 mb-3">
                <label for="kode_pos" class="form-label">Kode Pos <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="kode_pos" name="kode_pos" value="<?php echo isset($user['kode_pos']) ? htmlspecialchars($user['kode_pos']) : ''; ?>" required>
              </div>
            </div>
            
            <div class="d-flex justify-content-between mt-4">
              <a href="profile.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Kembali
              </a>
              <button type="submit" class="btn btn-success">
                <i class="fas fa-save me-2"></i>Simpan Perubahan
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
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
            <li><a href="../user.php#tentang-kami"><i class="fas fa-angle-right me-2"></i>Tentang Kami</a></li>
            <li><a href="../user.php#produk"><i class="fas fa-angle-right me-2"></i>Produk</a></li>
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
  <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
  
  <script>
  // Navbar scroll effect
  window.addEventListener('scroll', function () {
    const navbar = document.querySelector('.navbar');
    if (window.scrollY > 50) {
      navbar.classList.add('scrolled');
    } else {
      navbar.classList.remove('scrolled');
    }
  });

  // Preview foto profil saat dipilih
  document.addEventListener('DOMContentLoaded', function () {
    const fileInput = document.getElementById('foto_profil');
    const profilePic = document.querySelector('.profile-pic');

    fileInput.addEventListener('change', function () {
      if (fileInput.files && fileInput.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
          profilePic.src = e.target.result;
        };
        reader.readAsDataURL(fileInput.files[0]);
      }
    });

    // Tombol upload di klik
    const uploadBtn = document.querySelector('.pic-upload-btn');
    uploadBtn.addEventListener('click', function () {
      fileInput.click();
    });
  });
</script>

</body>

</html>