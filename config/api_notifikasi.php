<?php
session_start();
require_once 'koneksi.php';

header('Content-Type: application/json');

if (!isset($_SESSION['role'])) {
    echo json_encode(['count' => 0, 'items' => []]);
    exit;
}

$role = $_SESSION['role'];
$count = 0;
$items = [];

try {
    if ($role == 'admin') {
        // Admin: Notifikasi jika ada user baru (dalam 24 jam terakhir)
        $stmt = $pdo->query("SELECT nama, created_at FROM pengguna WHERE created_at >= NOW() - INTERVAL 1 DAY ORDER BY created_at DESC LIMIT 5");
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $count = count($items);
    } elseif ($role == 'petugas') {
        // Petugas: Notifikasi jika ada dokumen baru yang statusnya "Belum Diperiksa"
        $stmt = $pdo->query("SELECT nama_dokumen AS nama, tanggal_input AS created_at FROM lembar_kerja WHERE status_qc = 'Belum Diperiksa' ORDER BY tanggal_input DESC LIMIT 5");
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $count = count($items);
    } elseif ($role == 'pimpinan') {
        // Pimpinan: Notifikasi jika ada dokumen yang butuh di-review
        $stmt = $pdo->query("SELECT nama_dokumen AS nama, tanggal_input AS created_at FROM lembar_kerja WHERE status_qc = 'Selesai' AND status_review = 'Menunggu Review' ORDER BY tanggal_input DESC LIMIT 5");
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $count = count($items);
    }

    echo json_encode(['count' => $count, 'items' => $items]);
} catch (Exception $e) {
    echo json_encode(['count' => 0, 'items' => []]);
}
?>