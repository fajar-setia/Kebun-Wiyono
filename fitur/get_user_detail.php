<?php
ob_start(); // Buffer output
session_start();
header('Content-Type: application/json');

require '../config.php';

if (!isset($_SESSION['admin_id'])) {
  echo json_encode(['success' => false, 'message' => 'Unauthorized']);
  exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  echo json_encode(['success' => false, 'message' => 'ID pengguna tidak valid']);
  exit;
}

$userId = (int)$_GET['id'];
$query = "SELECT * FROM pengguna WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);

if (!$stmt) {
  echo json_encode(['success' => false, 'message' => 'Gagal mempersiapkan statement']);
  exit;
}

mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($user = mysqli_fetch_assoc($result)) {
  echo json_encode(['success' => true, 'user' => $user]);
} else {
  echo json_encode(['success' => false, 'message' => 'Pengguna tidak ditemukan']);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
exit;
