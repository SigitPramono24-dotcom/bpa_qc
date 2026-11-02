<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['role'])) { header("Location: ../auth/login.php"); exit; }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_SESSION['id_pengguna'];
    
    // --- LOGIKA UPLOAD FOTO ---
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $filename = $_FILES['foto']['name'];
        $filetype = $_FILES['foto']['type'];
        $filesize = $_FILES['foto']['size'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed)) {
            echo "<script>alert('Format foto harus JPG, JPEG, atau PNG!'); window.location='index.php';</script>"; exit;
        }
        if ($filesize > 2 * 1024 * 1024) { // Max 2MB
            echo "<script>alert('Ukuran foto maksimal 2MB!'); window.location='index.php';</script>"; exit;
        }

        // Generate nama file unik
        $new_foto = uniqid('user_') . '.' . $ext;
        $upload_dir = '../assets/foto_profil/';
        
        if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }
        
        if (move_uploaded_file($_FILES['foto']['tmp_name'], $upload_dir . $new_foto)) {
            // Hapus foto lama jika ada
            $stmt = $pdo->prepare("SELECT foto FROM pengguna WHERE id_pengguna = :id");
            $stmt->execute([':id' => $id]);
            $old_foto = $stmt->fetchColumn();
            if (!empty($old_foto) && file_exists($upload_dir . $old_foto)) {
                unlink($upload_dir . $old_foto);
            }

            // Update kolom foto
            $stmt = $pdo->prepare("UPDATE pengguna SET foto = :foto WHERE id_pengguna = :id");
            $stmt->execute([':foto' => $new_foto, ':id' => $id]);
            
            echo "<script>alert('Foto profil berhasil diperbarui!'); window.location='index.php';</script>"; exit;
        }
    }

    // --- LOGIKA UPDATE DATA DIRI ---
    if (isset($_POST['update_profil'])) {
        $nama = trim($_POST['nama']);
        $email = trim($_POST['email']);

        $stmt = $pdo->prepare("UPDATE pengguna SET nama = :nama, email = :email WHERE id_pengguna = :id");
        $stmt->execute([':nama' => $nama, ':email' => $email, ':id' => $id]);
        $_SESSION['nama'] = $nama;
        
        echo "<script>alert('Data diri berhasil diperbarui!'); window.location='index.php';</script>"; exit;
    }
}
?>