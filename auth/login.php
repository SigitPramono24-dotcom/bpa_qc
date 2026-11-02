<?php
session_start();
require_once '../config/koneksi.php';

// Jika sudah login, redirect ke halaman masing-masing
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] == 'admin') header("Location: ../admin/index.php");
    elseif ($_SESSION['role'] == 'petugas') header("Location: ../petugas/index.php");
    elseif ($_SESSION['role'] == 'pimpinan') header("Location: ../pimpinan/index.php");
    exit;
}

$error = '';

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Username dan Password wajib diisi!";
    } else {
        try {
            // Menggunakan PDO Prepared Statement (Aman dari SQL Injection)
            $stmt = $pdo->prepare("SELECT * FROM pengguna WHERE username = :username");
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch();

            // Verifikasi password (menggunakan password_verify karena di database di-hash)
            if ($user && password_verify($password, $user['password'])) {
                if ($user['status'] == 'Nonaktif') {
                    $error = "Akun Anda telah dinonaktifkan. Hubungi Admin.";
                } else {
                    // Set Session
                    $_SESSION['id_pengguna'] = $user['id_pengguna'];
                    $_SESSION['nama'] = $user['nama'];
                    $_SESSION['role'] = $user['role'];

                    // Redirect berdasarkan role
                    if ($user['role'] == 'admin') header("Location: ../admin/index.php");
                    elseif ($user['role'] == 'petugas') header("Location: ../petugas/index.php");
                    elseif ($user['role'] == 'pimpinan') header("Location: ../pimpinan/index.php");
                    exit;
                }
            } else {
                $error = "Username atau Password salah!";
            }
        } catch (PDOException $e) {
            $error = "Terjadi kesalahan sistem: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem QC BPS Medan</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f6f9;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }

        .login-container {
            width: 900px;
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            display: flex;
            min-height: 550px;
        }

        /* Sisi Kiri (Branding) */
        .login-left {
            width: 50%;
            background: linear-gradient(135deg, #0b1a4a 0%, #1a56db 100%);
            color: #fff;
            padding: 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            position: relative;
        }

        .login-left::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: url('https://www.transparenttextures.com/patterns/cubes.png'); /* Pattern halus */
            opacity: 0.1;
        }

        .login-left img {
            width: 130px;
            margin-bottom: 25px;
            position: relative;
            z-index: 1;
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.3));
        }

        .login-left h3 {
            font-weight: 700;
            font-size: 24px;
            margin-bottom: 15px;
            position: relative;
            z-index: 1;
        }

        .login-left p {
            font-size: 14px;
            color: #e2e8f0;
            line-height: 1.6;
            position: relative;
            z-index: 1;
        }

        /* Sisi Kanan (Form) */
        .login-right {
            width: 50%;
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-right h4 {
            font-weight: 700;
            color: #0b1a4a;
            margin-bottom: 5px;
        }

        .login-right p.subtitle {
            color: #718096;
            font-size: 14px;
            margin-bottom: 30px;
        }

        .form-label {
            font-weight: 500;
            font-size: 14px;
            color: #4a5568;
        }

        .input-group-text {
            background-color: #f8f9fa;
            border-right: none;
            color: #a0aec0;
        }

        .form-control {
            border-left: none;
            padding: 12px 15px;
            background-color: #f8f9fa;
            font-size: 14px;
        }

        .form-control:focus {
            box-shadow: none;
            border-color: #ced4da;
            background-color: #fff;
        }

        .input-group:focus-within .input-group-text,
        .input-group:focus-within .form-control {
            border-color: #1a56db;
        }

        .btn-login {
            background-color: #1a56db;
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s;
        }

        .btn-login:hover {
            background-color: #1545b5;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26, 86, 219, 0.3);
        }

        /* Responsif untuk layar kecil (HP) */
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
                width: 90%;
                min-height: auto;
            }
            .login-left, .login-right {
                width: 100%;
                padding: 40px 30px;
            }
            .login-left img {
                width: 100px;
            }
        }
    </style>
</head>
<body>

    <div class="login-container">
        <!-- Sisi Kiri (Logo & Branding) -->
        <div class="login-left">
            <img src="../assets/img/logo-bps.png" alt="Logo BPS">
            <h3>BPS Kota Medan</h3>
            <p>Sistem Informasi Quality Control & Pencocokan Lembar Kerja Publikasi Data Statistik</p>
        </div>

        <!-- Sisi Kanan (Form Login) -->
        <div class="login-right">
            <h4>Selamat Datang</h4>
            <p class="subtitle">Silakan masuk dengan akun Anda untuk melanjutkan.</p>

            <!-- Menampilkan Pesan Error jika ada -->
            <?php if(!empty($error)): ?>
                <div class="alert alert-danger py-2" style="font-size: 14px;">
                    <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" name="username" class="form-control" placeholder="Masukkan username" required autofocus>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" id="password" class="form-control" placeholder="Masukkan password" required>
                        <span class="input-group-text" style="cursor: pointer; border-left: none;" onclick="togglePassword()">
                            <i class="bi bi-eye-slash" id="toggleIcon"></i>
                        </span>
                    </div>
                </div>
                
                <button type="submit" name="login" class="btn btn-primary btn-login w-100 text-white">
                    Masuk ke Sistem <i class="bi bi-arrow-right ms-2"></i>
                </button>
            </form>
        </div>
    </div>

    <!-- Script untuk Toggle Show/Hide Password -->
    <script>
        function togglePassword() {
            var passwordInput = document.getElementById('password');
            var toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('bi-eye-slash');
                toggleIcon.classList.add('bi-eye');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('bi-eye');
                toggleIcon.classList.add('bi-eye-slash');
            }
        }
    </script>
</body>
</html>