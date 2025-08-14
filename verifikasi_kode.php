<?php
// Koneksi ke database
$conn = new mysqli("localhost", "fajar", "strongpassword", "project_db");
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$message = '';
$alertType = '';

// Tangani form jika metode POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $otp = $_POST['otp'];

    // Periksa kode OTP di database
    $query = $conn->prepare("SELECT * FROM reset_tokens WHERE email = ? AND token = ? AND expires_at > NOW()");
    $query->bind_param("ss", $email, $otp);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows > 0) {
        // OTP valid
        $message = "Kode verifikasi valid. Anda dapat melanjutkan untuk reset password.";
        $alertType = "success";

        // Hapus token setelah berhasil
        $delete = $conn->prepare("DELETE FROM reset_tokens WHERE email = ?");
        $delete->bind_param("s", $email);
        $delete->execute();

        // Redirect ke halaman reset password
        header("Location: reset_password.php?email=" . urlencode($email));
        exit;
    } else {
        $message = "Verification code is incorrect or has expired.";
        $alertType = "danger";
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Kode</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
     <link rel="stylesheet" href="assets/css/lupaPass.css">
</head>
<body class="bg-light">
    <div class="container d-flex justify-content-center align-items-center vh-100">
        <div class="card shadow-lg" style="width: 100%; max-width: 400px;">
            <div class="card-body">
                <h3 class="text-center mb-4">Verifikasi Kode</h3>
                
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?= $alertType ?>" role="alert">
                        <?= $message ?>
                    </div>
                <?php endif; ?>

                <form action="" method="POST">
                    <input type="hidden" name="email" value="<?= htmlspecialchars($_GET['email'] ?? '') ?>">
                    <div class="mb-3">
                        <label for="otp" class="form-label">Kode Verifikasi</label>
                        <input type="text" class="form-control" id="otp" name="otp" placeholder="Masukkan kode verifikasi" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Verifikasi</button>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
