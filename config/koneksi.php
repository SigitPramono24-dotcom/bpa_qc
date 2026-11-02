<?php
// config/koneksi.php

$host = "localhost";
$dbname = "db_bps_qc";
$username = "root";
$password = ""; // Kosongkan jika menggunakan XAMPP default

try {
    // Membuat koneksi PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // Mengatur mode error PDO agar menampilkan exception jika ada masalah
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Mengatur mode fetch default menjadi array asosiatif (mirip mysqli_fetch_assoc)
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Koneksi ke database gagal: " . $e->getMessage());
}
?>