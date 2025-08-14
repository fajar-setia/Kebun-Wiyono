<?php
date_default_timezone_set("Asia/Jakarta");

// Memasukkan PHPMailer
require './PHPMailer-master/src/Exception.php';
require './PHPMailer-master/src/PHPMailer.php';
require './PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];

    // Koneksi ke database
    $conn = new mysqli("localhost", "fajar", "strongpassword", "project_db");
    if ($conn->connect_error) {
        die("Koneksi gagal: " . $conn->connect_error);
    }

    // Cek apakah email ada
    $query = $conn->prepare("SELECT * FROM pengguna WHERE email = ?");
    $query->bind_param("s", $email);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows > 0) {
        // Email ditemukan
        $otp = rand(100000, 999999); // Membuat kode OTP 6 digit
        $expiresAt = date("Y-m-d H:i:s", strtotime("+10 minutes")); // Kode berlaku 10 menit

        // Simpan OTP ke database
        $insert = $conn->prepare("INSERT INTO reset_tokens (email, token, expires_at) VALUES (?, ?, ?)");
        $insert->bind_param("sss", $email, $otp, $expiresAt);
        $insert->execute();

        // Kirim email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'setiafajar935@gmail.com'; // Ganti dengan email Anda
            $mail->Password = 'qvpb hvlc mauo irhy';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('setiafajar935@gmail.com', 'KebunWiyono');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Kode Verifikasi';
            $mail->Body    = "Kode verifikasi Anda adalah <b>$otp</b>. Berlaku selama 10 menit.";

            $mail->send();

            // Redirect ke halaman verifikasi
            header("Location: verifikasi_kode.php?email=" . urlencode($email));
            exit;
        } catch (Exception $e) {
            $message = "Gagal mengirim email. Pesan kesalahan: " . $mail->ErrorInfo;
            $alertType = "danger";
        }
    } else {
        $message = "Email tidak terdaftar.";
        $alertType = "warning";
    }

    $conn->close();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
     <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/lupaPass.css">
</head>
<body>
    <div class="main-container">
        <div class="floating-shapes">
            <div class="shape"></div>
            <div class="shape"></div>
            <div class="shape"></div>
            <div class="shape"></div>
            <div class="shape"></div>
        </div>
        <div class="glass-container">
            <div class="illustration-section">
                <div class="logo-container">
                    <div class="logo">
                        <i class="bi bi-layers"></i>
                    </div>
                </div>
                <div class="welcome-text">
                    <h1>Welcome..</h1>
                    <p>jangan sampai lupa lagi ya...</p>
                </div>
            </div>
            <div class="lupaPass-section">
                <div class="lupaPass-card">
                    <div class="lupaPass-header">
                        <h2>lupa password</h2>
                        <?php if (!empty($message)): ?>
                            <div class="alert alert-<?= $alertType ?> mt-3" role="alert">
                                <?= $message ?>
                            </div>
                        <?php endif; ?>
                        <form action="" method="POST" class="lupaPass">
                            <div class="form-group">
                                <div class="form-floating">
                                    <label for="email" class="form-label"></label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                            </div>
                            <button type="submit" class="lupaPass-btn">
                                <span class="btn-text">Reset</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="">
        document.addEventListener('DOMContentLoaded', function () {
    const lupaPass = document.getElementsByClassName('lupaPass');

    if (lupaPass) {
        lupaPass.addEventListener('submit', function (e) {
            e.preventDefault();

            const submitBtn = document.querySelector('.lupaPass-btn');
            const btnText = document.querySelector('.btn-text');

            // Add loading state
            submitBtn.classList.add('loading');
            btnText.textContent = 'Signing In...';
            submitBtn.disabled = true;

            setTimeout(() => {
                submitBtn.classList.remove('loading');
                btnText.textContent = 'Sign In';
                submitBtn.disabled = false;
                
                lupaPass.submit(); // Kirim form ke PHP
            }, 2000);
        });
    }

    // Enhanced input focus effects
    document.querySelectorAll('.form-floating input').forEach(input => {
        input.addEventListener('focus', function () {
            this.parentElement.classList.add('focused');
        });

        input.addEventListener('blur', function () {
            this.parentElement.classList.remove('focused');
        });
    });

    // Floating shapes animation enhancement
    document.querySelectorAll('.shape').forEach((shape, index) => {
        shape.style.animationDuration = (4 + Math.random() * 4) + 's';
        shape.style.animationDelay = (Math.random() * 2) + 's';
    });

    // Add subtle parallax effect to floating shapes
    document.addEventListener('mousemove', (e) => {
        const shapes = document.querySelectorAll('.shape');
        const mouseX = e.clientX / window.innerWidth;
        const mouseY = e.clientY / window.innerHeight;

        shapes.forEach((shape, index) => {
            const speed = (index + 1) * 0.3; // Dikurangi dari 0.5 untuk efek yang lebih halus
            const x = (mouseX - 0.5) * speed;
            const y = (mouseY - 0.5) * speed;

            shape.style.transform = `translate(${x}px, ${y}px)`;
        });
    });
});
    </script>
</body>

</html>