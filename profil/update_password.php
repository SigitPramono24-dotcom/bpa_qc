<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['role'])) { header("Location: ../auth/login.php"); exit; }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $lama = $_POST['password_lama'];
    $baru = $_POST['password_baru'];
    $konfirmasi = $_POST['konfirmasi'];
    $id = $_SESSION['id_pengguna'];

    // Cek user
    $stmt = $pdo->prepare("SELECT password FROM pengguna WHERE id_pengguna = :id");
    $stmt->execute([':id' => $id]);
    $user = $stmt->fetch();

    if (!password_verify($lama, $user['password'])) {
        echo "<script>alert('Password lama salah!'); window.location='index.php';</script>"; exit;
    }

    if ($baru !== $konfirmasi) {
        echo "<script>alert('Konfirmasi password tidak cocok!'); window.location='index.php';</script>"; exit;
    }

    try {
        $hash = password_hash($baru, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE pengguna SET password = :pass WHERE id_pengguna = :id");
        $stmt->execute([':pass' => $hash, ':id' => $id]);
        echo "<script>alert('Password berhasil diubah!'); window.location='index.php';</script>";
    } catch (PDOException $e) {
        echo "<script>alert('Gagal mengubah password: " . $e->getMessage() . "'); window.location='index.php';</script>";
    }
}
?>