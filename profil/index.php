<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['role'])) { header("Location: ../auth/login.php"); exit; }

// Ambil data user terbaru dari database
$stmt = $pdo->prepare("SELECT * FROM pengguna WHERE id_pengguna = :id");
$stmt->execute([':id' => $_SESSION['id_pengguna']]);
$user = $stmt->fetch();

// Cek apakah user punya foto, jika tidak pakai avatar default
$foto_path = "../assets/foto_profil/" . $user['foto'];
$foto_default = "https://ui-avatars.com/api/?name=" . urlencode($user['nama']) . "&background=1a56db&color=fff&size=150";
$foto_src = (!empty($user['foto']) && file_exists($foto_path)) ? $foto_path : $foto_default;

$dashboard_link = ($_SESSION['role'] == 'admin') ? '../admin/index.php' : (($_SESSION['role'] == 'petugas') ? '../petugas/index.php' : '../pimpinan/index.php');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Profil Saya - BPS QC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .profile-img-container { position: relative; width: 150px; height: 150px; margin: 0 auto 20px auto; }
        .profile-img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; border: 4px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .upload-btn { position: absolute; bottom: 0; right: 0; background: #1a56db; color: white; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; border: 3px solid #fff; transition: 0.3s; }
        .upload-btn:hover { background: #1545b5; transform: scale(1.1); }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="fw-bold mb-0">Pengaturan Profil</h4>
                    <a href="<?= $dashboard_link ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Kembali ke Dashboard</a>
                </div>

                <!-- Kartu Foto Profil -->
                <div class="card shadow-sm border-0 mb-4 text-center">
                    <div class="card-body py-4">
                        <form action="update_data.php" method="POST" enctype="multipart/form-data" id="formFoto">
                            <div class="profile-img-container">
                                <img src="<?= $foto_src ?>" alt="Foto Profil" class="profile-img" id="previewFoto">
                                <label for="fileFoto" class="upload-btn" title="Ganti Foto">
                                    <i class="bi bi-camera"></i>
                                </label>
                                <input type="file" name="foto" id="fileFoto" class="d-none" accept="image/*" onchange="previewImage(event); this.form.submit();">
                            </div>
                        </form>
                        <h5 class="fw-bold mb-1"><?= htmlspecialchars($user['nama']) ?></h5>
                        <p class="text-muted mb-0"><i class="bi bi-person-badge me-1"></i> <?= ucfirst($user['role']) ?> | <?= htmlspecialchars($user['email']) ?></p>
                    </div>
                </div>

                <div class="row">
                    <!-- Form Edit Data Diri -->
                    <div class="col-md-6 mb-4">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-header bg-primary text-white"><h6 class="mb-0"><i class="bi bi-person-lines-fill me-2"></i>Data Diri</h6></div>
                            <div class="card-body">
                                <form action="update_data.php" method="POST">
                                    <div class="mb-3">
                                        <label class="form-label">Nama Lengkap</label>
                                        <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($user['nama']) ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Username</label>
                                        <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($user['username']) ?>" readonly>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                                    </div>
                                    <button type="submit" name="update_profil" class="btn btn-primary w-100">Simpan Data</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Form Ganti Password -->
                    <div class="col-md-6 mb-4">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-header bg-danger text-white"><h6 class="mb-0"><i class="bi bi-shield-lock me-2"></i>Ganti Password</h6></div>
                            <div class="card-body">
                                <form action="update_password.php" method="POST">
                                    <div class="mb-3">
                                        <label class="form-label">Password Lama</label>
                                        <input type="password" name="password_lama" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Password Baru</label>
                                        <input type="password" name="password_baru" class="form-control" minlength="6" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Konfirmasi Password Baru</label>
                                        <input type="password" name="konfirmasi" class="form-control" minlength="6" required>
                                    </div>
                                    <button type="submit" class="btn btn-danger w-100">Ganti Password</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function previewImage(event) {
            var reader = new FileReader();
            reader.onload = function(){
                var output = document.getElementById('previewFoto');
                output.src = reader.result;
            };
            reader.readAsDataURL(event.target.files[0]);
        }
    </script>
</body>
</html>