<?php
session_start();
require '../config.php';

try {
  $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
  ]);
} catch (PDOException $e) {
  die("Koneksi DB gagal: " . $e->getMessage());
}

/** Generate nomor pesanan unik */
function generate_order_no(PDO $pdo): string
{
  do {
    $nomor = 'OFF-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
    $stmt = $pdo->prepare("SELECT 1 FROM pesanan WHERE nomor_pesanan = :n LIMIT 1");
    $stmt->execute([':n' => $nomor]);
    $exists = $stmt->fetchColumn();
  } while ($exists);
  return $nomor;
}

// Hapus pesanan beserta itemnya
if (isset($_GET['delete'])) {
  $id = (int) $_GET['delete'];
  $pdo->prepare("DELETE FROM pesanan_item WHERE pesanan_id = :id")->execute([':id' => $id]);
  $pdo->prepare("DELETE FROM pesanan WHERE id = :id AND sumber_transaksi = 'offline'")->execute([':id' => $id]);
  $_SESSION['msg'] = "Pesanan #$id dihapus.";
  header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));
  exit;
}

// Ambil daftar produk
$produk = $pdo->query("SELECT id, nama_produk FROM produk ORDER BY nama_produk ASC")
  ->fetchAll(PDO::FETCH_ASSOC);

// Simpan pesanan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nama     = trim($_POST['nama_penerima'] ?? '');
  $telepon  = trim($_POST['telepon'] ?? '');
  $alamat   = trim($_POST['alamat'] ?? '');
  $catatan  = trim($_POST['catatan'] ?? '');
  $metode   = trim($_POST['metode_pembayaran'] ?? '');
  $produkId = isset($_POST['produk_id']) ? (int) $_POST['produk_id'] : null;
  $berat    = isset($_POST['berat']) ? (float) $_POST['berat'] : 0;
  $harga    = isset($_POST['harga']) ? (float) $_POST['harga'] : 0;

  if ($nama === '' || $telepon === '' || $metode === '' || !$produkId || $berat <= 0 || $harga <= 0) {
    $_SESSION['err'] = "Lengkapi semua field wajib & pilih produk.";
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
  }

  $subtotal = $harga * $berat;
  $nomor    = generate_order_no($pdo);

  try {
    $pdo->beginTransaction();

    // Insert ke pesanan (sementara total 0)
    $ins = $pdo->prepare("
      INSERT INTO pesanan (
        user_id, nomor_pesanan, nama_penerima, telepon, alamat, catatan,
        metode_pembayaran, total_harga, total_bayar,
        status, sumber_transaksi, tanggal_pesanan
      ) VALUES (
        :user_id, :nomor, :nama, :telp, :alamat, :catatan,
        :metode, 0, 0,
        :status, 'offline', NOW()
      )
    ");
    $ins->execute([
      ':user_id' => 1,
      ':nomor' => $nomor,
      ':nama' => $nama,
      ':telp' => $telepon,
      ':alamat' => $alamat,
      ':catatan' => $catatan,
      ':metode' => $metode,
      ':status' => 'Selesai'
    ]);
    $pesananId = $pdo->lastInsertId();

    // Insert item produk (pakai berat + harga manual)
    $insItem = $pdo->prepare("
      INSERT INTO pesanan_item (pesanan_id, produk_id, berat, harga, subtotal)
      VALUES (:pid,:prod,:berat,:harga,:sub)
    ");
    $insItem->execute([
      ':pid' => $pesananId,
      ':prod' => $produkId,
      ':berat' => $berat,
      ':harga' => $harga,
      ':sub' => $subtotal
    ]);

    // Update total di pesanan
    $pdo->prepare("UPDATE pesanan SET total_harga=:t, total_bayar=:t WHERE id=:id")
      ->execute([':t' => $subtotal, ':id' => $pesananId]);

    $pdo->commit();
    $_SESSION['msg'] = "Pesanan {$nomor} disimpan.";
  } catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['err'] = "Gagal simpan: " . $e->getMessage();
  }

  header("Location: " . $_SERVER['REQUEST_URI']);
  exit;
}

// Ambil daftar pesanan offline + itemnya
$pesanan = $pdo->query("
    SELECT 
      p.*, 
      GROUP_CONCAT(DISTINCT pr.nama_produk SEPARATOR ', ') AS items,
      COALESCE(SUM(pi.berat),0) AS total_berat
    FROM pesanan p
    LEFT JOIN pesanan_item pi ON pi.pesanan_id=p.id
    LEFT JOIN produk pr ON pr.id=pi.produk_id
    WHERE p.sumber_transaksi='offline'
    GROUP BY p.id
    ORDER BY p.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Statistik
$st = $pdo->query("SELECT COUNT(*) total, COALESCE(SUM(total_bayar),0) pendapatan
                   FROM pesanan WHERE sumber_transaksi='offline'")
  ->fetch(PDO::FETCH_ASSOC);

$resultPending = mysqli_query($conn, "SELECT COUNT(*) as total FROM pesanan WHERE status = 'pending'");
$pendingOrders = mysqli_fetch_assoc($resultPending)['total'];
$resultTotal = mysqli_query($conn, "SELECT COUNT(*) as total FROM pesanan");
$totalOrders = mysqli_fetch_assoc($resultTotal)['total'];
$total_notifications = $totalOrders + $pendingOrders;
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>pembukuan Ofline | Kebun Wiyono</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/admin/pembukuan_Offline.css">
</head>

<body>
  <!-- Toggle Button -->
  <button class="btn btn-success d-md-none position-fixed" style="top: 1rem; left: 1rem; z-index: 1100;" id="toggleSidebar">
    <i class="fas fa-bars"></i>
  </button>

  <div class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <h4><i class="fas fa-seedling me-2"></i>Kebun Wiyono</h4>
      <small>Panel Admin</small>
    </div>
    <ul class="nav flex-column sidebar-menu">
      <li class="nav-item"><a href="dashboard.php" class="nav-link text-white"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
      <li class="nav-item"><a href="proses_tambah_produk.php" class="nav-link text-white"><i class="fas fa-leaf me-2"></i>Produk</a></li>
      <li class="nav-item"><a href="digitalisasi_Pembukuan_offline.php" class="nav-link text-white active"><i class="fas fa-book me-2 active"></i>Pembukuan Offline</a></li>
      <li class="nav-item"><a href="kategori.php" class="nav-link text-white "><i class="fas fa-tags me-2"></i>Kategori</a></li>
      <li class="nav-item">
        <a href="pesanan.php" class="nav-link text-white">
          <i class="fas fa-shopping-cart me-2"></i>Pesanan
          <?php if ($total_notifications > 0): ?>
            <span class="badge bg-warning ms-2"><?= $total_notifications ?></span>
          <?php endif; ?>
        </a>
      </li>
      <li class="nav-item"><a href="pengguna.php" class="nav-link text-white"><i class="fas fa-users me-2"></i>Pengguna</a></li>
      <li class="nav-item"><a href="pengaturan.php" class="nav-link text-white"><i class="fas fa-cog me-2"></i>Pengaturan</a></li>
      <li class="nav-item"><a href="../logout.php" class="nav-link text-white"><i class="fas fa-sign-out-alt me-2"></i>Keluar</a></li>
    </ul>
  </div>

  <div class="content">
    <div class="page-header fade-in">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1><i class="fas fa-tags me-3"></i>Pembukuan Offline</h1>
          <p class="welcome-text">Kelola pembukuan offline penjualan untuk mengorganisir toko Anda dengan lebih baik.</p>
        </div>
      </div>
    </div>

    <?php if (!empty($_SESSION['msg'])): ?>
      <div class="msg ok"><?= htmlspecialchars($_SESSION['msg']) ?></div>
    <?php unset($_SESSION['msg']);
    endif; ?>
    <?php if (!empty($_SESSION['err'])): ?>
      <div class="msg err"><?= htmlspecialchars($_SESSION['err']) ?></div>
    <?php unset($_SESSION['err']);
    endif; ?>

    <div class="stats-grid fade-in">
      <div class="stat-card primary">
        <div class="stat-header">
          <div>
            <div class="stat-value"><?= (int)$st['total'] ?></div>
            <div class="stat-label">Total Pesanan</div>
            <div class="stat-change positive">
              <i class="fas fa-shopping-cart me-1"></i>pesanan
            </div>
          </div>
          <div class="stat-icon primary">
            <i class="fas fa-shopping-cart"></i>
          </div>
        </div>
      </div>
      <div class="stat-card primary">
        <div class="stat-header">
          <div>
            <div class="stat-value">Rp <?= number_format((float)$st['pendapatan'], 0, ',', '.') ?></div>
            <div class="stat-label">Total Pendapatan</div>
            <div class="stat-change positive">
              <i class="fas fa-money-bill-wave me-1"></i>hari ini
            </div>
          </div>
          <div class="stat-icon primary">
            <i class="fas fa-money-bill-wave"></i>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <h3>Tambah Pesanan</h3>
      <form method="post" class="order-form">

        <div class="form-group">
          <label for="nama_penerima">Nama Penerima <span class="required">*</span></label>
          <input type="text" id="nama_penerima" name="nama_penerima" required>
        </div>

        <div class="form-group">
          <label for="telepon">Telepon <span class="required">*</span></label>
          <input type="text" id="telepon" name="telepon" required>
        </div>

        <div class="form-group">
          <label for="alamat">Alamat <span class="required">*</span></label>
          <textarea id="alamat" name="alamat" rows="2" required></textarea>
        </div>

        <div class="row">
          <div class="form-group">
            <label for="produk_id">Produk <span class="required">*</span></label>
            <select id="produk_id" name="produk_id" required>
              <option value="">-- Pilih Produk --</option>
              <?php foreach ($produk as $p): ?>
                <option value="<?= $p['id'] ?>">
                  <?= htmlspecialchars($p['nama_produk']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label for="berat">Berat (Kg) <span class="required">*</span></label>
            <input type="number" id="berat" name="berat" min="0.1" step="0.01" value="1" required>
          </div>
        </div>

        <div class="row">
          <div class="form-group">
            <label for="metode_pembayaran">Metode Pembayaran <span class="required">*</span></label>
            <select id="metode_pembayaran" name="metode_pembayaran" required>
              <option value="">-- Pilih Metode --</option>
              <option>Cash</option>
              <option>Transfer</option>
              <option>QRIS</option>
            </select>
          </div>
          <div class="form-group">
            <label for="harga">Harga per Kg (Rp) <span class="required">*</span></label>
            <input type="number" id="harga" name="harga" min="100" step="100" required>
          </div>
        </div>

        <div class="form-group">
          <label for="catatan">Catatan</label>
          <textarea id="catatan" name="catatan" rows="2"></textarea>
        </div>

        <button type="submit" class="btn-submit">Simpan</button>
      </form>

      <div class="small"><span class="required">*</span> Wajib diisi</div>
    </div>


    <div class="card">
      <h3>Daftar Pesanan Offline</h3>
      <table>
        <thead>
          <tr>
            <th>Nomor</th>
            <th>Nama</th>
            <th>Telepon</th>
            <th>Items</th>
            <th>Total Harga</th>
            <th>Total Berat</th>
            <th>Status</th>
            <th>Tanggal</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$pesanan): ?>
            <tr>
              <td colspan="9">Belum ada data.</td>
            </tr>
            <?php else: foreach ($pesanan as $row): ?>
              <tr>
                <td><?= htmlspecialchars($row['nomor_pesanan']) ?></td>
                <td><?= htmlspecialchars($row['nama_penerima']) ?></td>
                <td><?= htmlspecialchars($row['telepon']) ?></td>
                <td><?= htmlspecialchars($row['items']) ?></td>
                <td>Rp <?= number_format($row['total_harga'], 0, ',', '.') ?></td>
                <td><?= number_format($row['total_berat'], 2, ',', '.') ?> Kg</td>
                <td><?= htmlspecialchars($row['status']) ?></td>
                <td><?= htmlspecialchars($row['tanggal_pesanan']) ?></td>
                <td class="actions"><a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Hapus pesanan ini?')">Hapus</a></td>
              </tr>
          <?php endforeach;
          endif; ?>
        </tbody>
      </table>
    </div>

  </div>
</body>

</html>