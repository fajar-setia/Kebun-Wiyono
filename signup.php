<?php
// Konfigurasi database
$host = 'localhost'; // Host database (biasanya 'localhost')
$user = 'fajar';      // Nama pengguna database
$pass = 'strongpassword';          // Kata sandi database (kosong jika menggunakan XAMPP tanpa password)
$db = 'website_project'; // Nama database yang Anda gunakan

// Membuat koneksi ke database
$conn = new mysqli($host, $user, $pass, $db);

// Memeriksa koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Inisialisasi variabel untuk pesan kesalahan
$usernameError = "";
$emailError = "";
$passwordError = "";
$confirmPasswordError = "";
$successMessage = "";


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    // Validasi input
    if (empty($username)) {
        $usernameError = "Username diperlukan!";
    }
    if (empty($email)) {
        $emailError = "Email diperlukan!";
    }
    if (empty($password)) {
        $passwordError = "Password diperlukan!";
    }
    if (empty($confirmPassword)) {
        $confirmPasswordError = "Konfirmasi password diperlukan!";
    } elseif ($password !== $confirmPassword) {
        $confirmPasswordError = "Password tidak cocok!";
    }

    // Jika tidak ada kesalahan, simpan ke database
    if (empty($usernameError) && empty($emailError) && empty($passwordError) && empty($confirmPasswordError)) {
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Menyimpan data ke database
        $stmt = $conn->prepare("INSERT INTO pengguna (username, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss",$username , $email, $hashedPassword);

        if ($stmt->execute()) {
            $successMessage = "Pendaftaran berhasil! Silakan <a href='index.php'>Login</a>";
        } else {
            $emailError = "Error: " . $stmt->error; // Jika ada kesalahan saat eksekusi
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
    <title>Modern Login - Welcome Back</title>
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
                    <h1>Welcome Back!</h1>
                    <p>Enter your credentials to access your account and continue your journey with us.</p>
                </div>
            </div>

            <!-- Right Section - Login Form -->
            <div class="login-section">
                <div class="login-card">
                    <div class="login-header">
                        <h2>Sign up</h2>
                        <p>Access your account</p>
                        <?php if (!empty($message)): ?>
                            <div class="alert alert-<?= $alertType ?>" role="alert">
                                <?= $message ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <form class="loginForm" method="post">
                        <div class="form-group">
                            <div class="form-floating">
                                <input type="text" id="email" name="email" placeholder=" " required>
                                <label for="floatingInput">Username</label>
                                <i class="bi bi-person input-icon"></i></i>
                                 <!-- <div class="text-danger"><?php echo $usernameError; ?></div> -->
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="form-floating">
                                <input type="email" id="email" name="email" placeholder=" " required>
                                <label for="email">Email Address</label>
                                <i class="bi bi-envelope input-icon"></i>
                                 <!-- <div class="text-danger"><?php echo $emailError; ?></div> -->
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="form-floating">
                                <input type="password" id="password" name="password" placeholder=" " required>
                                <label for="password">Password</label>
                                 <!-- <div class="text-danger"><?php echo $passwordError; ?></div> -->
                                <i class="bi bi-lock input-icon"></i>
                                <button type="button" class="password-toggle" onclick="togglePassword()">
                                    <i class="bi bi-eye" id="toggleIcon"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="form-floating">
                                <input type="password" id="password" name="confirm_password" placeholder=" " required>
                                <label for="password">Password</label>
                                 <!-- <div class="text-danger"><?php echo $confirmPasswordError; ?></div> -->
                                <i class="bi bi-lock input-icon"></i>
                                <button type="button" class="password-toggle" onclick="togglePassword()">
                                    <i class="bi bi-eye" id="toggleIcon"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-links">
                            <label class="d-flex align-items-center">
                                <input type="checkbox" class="me-2"> Remember me
                            </label>
                            <a href="lupa_password.php" class="forgot-link">Forgot Password?</a>
                        </div>

                        <button type="submit" class="login-btn">
                            <span class="btn-text">Sign In</span>
                        </button>

                        <div class="divider">
                            <span>or continue with</span>
                        </div>

                        <div class="social-buttons">
                            <a href="#" class="social-btn">
                                <i class="bi bi-google"></i>
                                Google
                            </a>
                            <a href="#" class="social-btn">
                                <i class="bi bi-facebook"></i>
                                Facebook
                            </a>
                        </div>

                        <div class="text-center mt-4 create-account">
                            <p class="mb-0" style="color: var(--text-secondary);">
                               Already have an account?
                                <a href="index.php">Login</a>
                            </p>
                        </div>
                    </form>
                    <div class="text-center text-success mt-3"><?php echo $successMessage; ?></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/login.js"></script>
</body>

</html>