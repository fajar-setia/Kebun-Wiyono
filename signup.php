<?php
// Konfigurasi database
$host = 'localhost'; // Host database (biasanya 'localhost')
$user = 'fajar';      // Nama pengguna database
$pass = 'strongpassword';          // Kata sandi database (kosong jika menggunakan XAMPP tanpa password)
$db = 'project_db'; // Nama database yang Anda gunakan

// Membuat koneksi ke database
$conn = new mysqli($host, $user, $pass, $db);

// Memeriksa koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Inisialisasi variabel untuk pesan kesalahan
$usernameError = "";
$namaLengkapError = "";
$emailError = "";
$passwordError = "";
$confirmPasswordError = "";
$successMessage = "";
$message = "";
$alertType = "";

// Inisialisasi variabel untuk form values
$username = "";
$nama_lengkap = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $nama_lengkap = trim($_POST["nama_lengkap"]);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    // Validasi input
    if (empty($username)) {
        $usernameError = "Username diperlukan!";
    } elseif (strlen($username) < 3) {
        $usernameError = "Username minimal 3 karakter!";
    }
    
    if (empty($nama_lengkap)) {
        $namaLengkapError = "Nama lengkap diperlukan!";
    } elseif (strlen($nama_lengkap) < 2) {
        $namaLengkapError = "Nama lengkap minimal 2 karakter!";
    }
    
    if (empty($email)) {
        $emailError = "Email diperlukan!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $emailError = "Format email tidak valid!";
    }
    
    if (empty($password)) {
        $passwordError = "Password diperlukan!";
    } elseif (strlen($password) < 6) {
        $passwordError = "Password minimal 6 karakter!";
    }
    
    if (empty($confirmPassword)) {
        $confirmPasswordError = "Konfirmasi password diperlukan!";
    } elseif ($password !== $confirmPassword) {
        $confirmPasswordError = "Password tidak cocok!";
    }

    // Cek apakah username atau email sudah ada
    if (empty($usernameError) && empty($emailError)) {
        $checkStmt = $conn->prepare("SELECT username, email FROM pengguna WHERE username = ? OR email = ?");
        $checkStmt->bind_param("ss", $username, $email);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows > 0) {
            $existingUser = $result->fetch_assoc();
            if ($existingUser['username'] === $username) {
                $usernameError = "Username sudah digunakan!";
            }
            if ($existingUser['email'] === $email) {
                $emailError = "Email sudah terdaftar!";
            }
        }
        $checkStmt->close();
    }

    // Jika tidak ada kesalahan, simpan ke database
    if (empty($usernameError) && empty($namaLengkapError) && empty($emailError) && empty($passwordError) && empty($confirmPasswordError)) {
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Menyimpan data ke database sesuai struktur tabel
        $stmt = $conn->prepare("INSERT INTO pengguna (username, nama_lengkap, email, password) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $nama_lengkap, $email, $hashedPassword);

        if ($stmt->execute()) {
            $successMessage = "Pendaftaran berhasil! Silakan <a href='index.php'>Login</a>";
            $message = "Pendaftaran berhasil! Silakan login dengan akun Anda.";
            $alertType = "success";
            
            // Reset form values setelah berhasil
            $username = $nama_lengkap = $email = "";
        } else {
            $message = "Terjadi kesalahan saat mendaftar: " . $conn->error;
            $alertType = "danger";
        }

        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Create Account</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Link ke file CSS eksternal -->
    <link rel="stylesheet" href="assets/css/signup.css">
</head>

<body>
    <div class="main-container">
        <!-- Animated Background -->
        <div class="bg-animation">
            <div class="floating-shapes">
                <div class="shape"></div>
                <div class="shape"></div>
                <div class="shape"></div>
                <div class="shape"></div>
                <div class="shape"></div>
            </div>
        </div>

        <!-- Main Glass Container -->
        <div class="glass-container">
            <!-- Left Section - Illustration -->
            <div class="illustration-section">
                <div class="logo-container">
                    <div class="logo">
                        <i class="bi bi-layers"></i>
                    </div>
                </div>
                <div class="welcome-text">
                    <h1>Join Us Today!</h1>
                    <p>Create your account and start your journey with us. It's quick and easy!</p>
                </div>
            </div>

            <!-- Right Section - Signup Form -->
            <div class="login-section">
                <div class="login-card">
                    <div class="login-header">
                        <h2>Sign Up</h2>
                        <p>Create your account</p>
                        <?php if (!empty($message)): ?>
                            <div class="alert alert-<?= $alertType ?>" role="alert">
                                <?= $message ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <form class="loginForm" method="post">
                        <div class="form-group">
                            <div class="form-floating">
                                <input type="text" id="username" name="username" placeholder=" " value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
                                <label for="username">Username</label>
                                <i class="bi bi-person input-icon"></i>
                            </div>
                            <?php if (!empty($usernameError)): ?>
                                <div class="text-danger small mt-1"><?php echo $usernameError; ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <div class="form-floating">
                                <input type="text" id="nama_lengkap" name="nama_lengkap" placeholder=" " value="<?php echo htmlspecialchars($nama_lengkap ?? ''); ?>" required>
                                <label for="nama_lengkap">Nama Lengkap</label>
                                <i class="bi bi-person-badge input-icon"></i>
                            </div>
                            <?php if (!empty($namaLengkapError)): ?>
                                <div class="text-danger small mt-1"><?php echo $namaLengkapError; ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <div class="form-floating">
                                <input type="email" id="email" name="email" placeholder=" " value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                                <label for="email">Email Address</label>
                                <i class="bi bi-envelope input-icon"></i>
                            </div>
                            <?php if (!empty($emailError)): ?>
                                <div class="text-danger small mt-1"><?php echo $emailError; ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <div class="form-floating">
                                <input type="password" id="password" name="password" placeholder=" " required>
                                <label for="password">Password</label>
                                <i class="bi bi-lock input-icon"></i>
                                <button type="button" class="password-toggle" onclick="togglePassword('password', 'toggleIcon1')">
                                    <i class="bi bi-eye" id="toggleIcon1"></i>
                                </button>
                            </div>
                            <?php if (!empty($passwordError)): ?>
                                <div class="text-danger small mt-1"><?php echo $passwordError; ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <div class="form-floating">
                                <input type="password" id="confirm_password" name="confirm_password" placeholder=" " required>
                                <label for="confirm_password">Confirm Password</label>
                                <i class="bi bi-lock input-icon"></i>
                                <button type="button" class="password-toggle" onclick="togglePassword('confirm_password', 'toggleIcon2')">
                                    <i class="bi bi-eye" id="toggleIcon2"></i>
                                </button>
                            </div>
                            <?php if (!empty($confirmPasswordError)): ?>
                                <div class="text-danger small mt-1"><?php echo $confirmPasswordError; ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-links">
                            <label class="d-flex align-items-center">
                                <input type="checkbox" class="me-2" required> 
                                I agree to the <a href="#" class="forgot-link">Terms & Conditions</a>
                            </label>
                        </div>

                        <button type="submit" class="login-btn">
                            <span class="btn-text">Sign Up</span>
                        </button>

                        <div class="divider">
                            <span>or continue with</span>
                        </div>

                        <div class="social-buttons">
                            <a href="#" class="social-btn">
                                <i class="bi bi-google"></i>
                                Google
                            </a>
                            <a href="github-login.php" class="social-btn">
                                <i class="bi bi-github"></i>
                                Github
                            </a>
                        </div>

                        <div class="text-center mt-4 create-account">
                            <p class="mb-0" style="color: var(--text-secondary);">
                               Already have an account?
                                <a href="index.php">Login</a>
                            </p>
                        </div>
                    </form>
                    
                    <?php if (!empty($successMessage)): ?>
                        <div class="text-center text-success mt-3"><?php echo $successMessage; ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/signup.js"></script>
</body>

</html>