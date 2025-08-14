<?php

use Google\Service\Oauth2 as Google_Service_Oauth2;
session_start();  // Memulai sesi

if (isset($_POST['login'])) {
    // Hapus semua data session lama
    $_SESSION = array(); // Clear session array
    session_unset(); // Unset all session variables
    session_destroy(); // Destroy session
    
    // Mulai session baru
    session_start();
    session_regenerate_id(true); // Regenerate session ID untuk keamanan
    
    // Debug: log session cleanup
    error_log("Session cleaned. New Session ID: " . session_id());
}

require 'config.php';
require 'vendor/autoload.php';
require_once 'vendor/autoload.php';

$emailError = "";
$passwordError = "";
$successMessage = "";
$loginError = "";

//login with google
$client_id = '28317463013-41hi6aqp8rc68c51pegl3b7a8qcss88b.apps.googleusercontent.com';
$client_secret = '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';
$redirect_uri = 'https://kebunkita.shop/index.php';

$client = new Google_Client();

$client->setClientId($client_id);
$client->setClientSecret($client_secret);
$client->setRedirectUri($redirect_uri);
$client->addScope('email');
$client->addScope('profile');


//untuk google
if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    if (!isset($token['error'])) {
        $client->setAccessToken($token['access_token']);

        $service = new Google_Service_Oauth2($client);
        $profile = $service->userinfo->get();

        $g_name = $profile['name'];
        $g_email = $profile['email'];
        $g_id = $profile['id'];

        // Cek apakah email sudah ada di pengguna (manual)
        $stmt = $conn->prepare("SELECT * FROM pengguna WHERE email = ?");
        $stmt->bind_param("s", $g_email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            // Email sudah ada → update google_id
            $stmt_update = $conn->prepare("UPDATE pengguna SET google_id = ? WHERE email = ?");
            $stmt_update->bind_param("ss", $g_id, $g_email);
            $stmt_update->execute();

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $g_email;
            $_SESSION['user_name'] = $user['nama_lengkap'];
            header("Location: user.php");
            exit();
        } else {
            // Email belum ada → insert user baru
            $stmt_insert = $conn->prepare("INSERT INTO pengguna (nama_lengkap, email, google_id) VALUES (?, ?, ?)");
            $stmt_insert->bind_param("sss", $g_name, $g_email, $g_id);

            if ($stmt_insert->execute()) {
                $new_user_id = $conn->insert_id;
                $_SESSION['user_id'] = $new_user_id;
                $_SESSION['user_email'] = $g_email;
                $_SESSION['user_name'] = $g_name;
                header("Location: user.php");
                exit();
            } else {
                echo "<script>alert('Gagal membuat akun Google.');</script>";
            }
        }
    } else {
        echo "<script>alert('Gagal login dengan Google.');</script>";
    }
}

// Ambil pesan dan alertType dari session
if (isset($_SESSION['message']) && isset($_SESSION['alertType'])) {
    $message = $_SESSION['message'];
    $alertType = $_SESSION['alertType'];

    // Hapus pesan dari session setelah ditampilkan
    unset($_SESSION['message']);
    unset($_SESSION['alertType']);
}

// Mengatasi pengiriman form
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email']) && isset($_POST['password'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    if (empty($email)) {
        $emailError = "Email diperlukan!";
    }
    if (empty($password)) {
        $passwordError = "Password diperlukan!";
    }

    if (empty($emailError) && empty($passwordError)) {
        // Cek user biasa
        $stmt = $conn->prepare("SELECT * FROM pengguna WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password'])) {
                // PERBAIKAN: Simpan user ID yang sebenarnya, bukan true
                $_SESSION['user_id'] = $user['id']; // Bukan true!
                $_SESSION['user_email'] = $email;
                $_SESSION['user_name'] = $user['nama_lengkap'] ?? $user['username'];
                
                $stmt->close();
                $conn->close();
                header("Location: user.php");
                exit();
            }
        }
        $stmt->close();

        // Cek admin
        $stmt = $conn->prepare("SELECT * FROM admin WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $admResult = $stmt->get_result();

        if ($admin = $admResult->fetch_assoc()) {
            if (password_verify($password, $admin['password'])) {
                $_SESSION['admin_id'] = true; 
                $_SESSION['admin_role'] = $admin['role'];
                $_SESSION['admin_email'] = $email;
                
                $stmt->close();
                $conn->close();
                header("Location: fitur/dashboard.php");
                exit();
            }
        } else {
            $emailError = "Email dan password salah";
            echo "<script>alert('hai , " . $emailError . "!');</script>";
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
    <link rel="stylesheet" href="assets/css/login.css">
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
                        <h2>Sign In</h2>
                        <p>Access your account</p>
                        <?php if (!empty($message)): ?>
                            <div class="alert alert-<?= $alertType ?>" role="alert">
                                <?= $message ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <form class="loginForm" method="POST">
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

                        <div class="form-links">
                            <label class="d-flex align-items-center">
                                <input type="checkbox" class="me-2"> Remember me
                            </label>
                            <a href="lupa_password.php" class="forgot-link">Forgot Password?</a>
                        </div>

                        <button type="submit" class="login-btn" name="login">
                            <span class="btn-text">Sign In</span>
                        </button>

                        <div class="divider">
                            <span>or continue with</span>
                        </div>

                        <div class="social-buttons">
                            <a href="<?= $client->createAuthUrl(); ?>" class="social-btn">
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
                                Don't have an account?
                                <a href="signup.php">Create Account</a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/login.js"></script>

</body>

</html>