<?php
session_start();
require_once '../config/koneksi.php';

// Proteksi Halaman
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// --- Ambil Data User untuk Topbar (Foto Profil) ---
$stmt_user = $pdo->prepare("SELECT nama, foto FROM pengguna WHERE id_pengguna = :id");
$stmt_user->execute([':id' => $_SESSION['id_pengguna']]);
$user_top = $stmt_user->fetch();
$foto_top = (!empty($user_top['foto']) && file_exists("../assets/foto_profil/" . $user_top['foto'])) 
            ? "../assets/foto_profil/" . $user_top['foto'] 
            : "https://ui-avatars.com/api/?name=" . urlencode($user_top['nama']) . "&background=1a56db&color=fff";

// --- Mengambil Data Statistik dari Database (PDO) ---
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM lembar_kerja");
    $total_dokumen = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM pengguna");
    $total_pengguna = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM lembar_kerja WHERE status_qc = 'Belum Diperiksa'");
    $belum_diperiksa = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM hasil_pencocokan WHERE status_temuan = 'Belum Diperbaiki'");
    $temuan_aktif = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT log_aktivitas.*, pengguna.nama FROM log_aktivitas JOIN pengguna ON log_aktivitas.id_pengguna = pengguna.id_pengguna ORDER BY waktu DESC LIMIT 5");
    $aktivitas_terbaru = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Error mengambil data: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - BPS QC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/sidebar_admin.php'; ?>

    <div class="main-content">
        <!-- Topbar dengan Notifikasi & Foto Profil -->
        <div class="topbar">
            <h5 class="mb-0 fw-bold">Dashboard Admin</h5>
            <div class="d-flex align-items-center">
                
                <!-- FITUR NOTIFIKASI REAL-TIME -->
                <div class="dropdown me-3">
                    <div class="position-relative" style="cursor: pointer;" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-bell fs-5 text-muted"></i>
                        <span id="notif-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display:none; font-size: 10px;">0</span>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end shadow" id="notif-list" style="width: 300px;">
                        <li><a class="dropdown-item text-muted text-center" href="#">Memuat notifikasi...</a></li>
                    </ul>
                </div>
                <!-- END NOTIFIKASI -->

                <!-- FOTO PROFIL & NAMA -->
                <div class="d-flex align-items-center" style="cursor: pointer;">
                    <img src="<?= $foto_top ?>" class="rounded-circle me-2" width="35" height="35" style="object-fit: cover; border: 2px solid #e2e8f0;">
                    <span class="fw-semibold" style="font-size: 14px;"><?= htmlspecialchars($user_top['nama']) ?> <i class="bi bi-chevron-down ms-1" style="font-size: 12px;"></i></span>
                </div>
            </div>
        </div>

        <p class="text-muted mb-4">Selamat datang, <?= htmlspecialchars($user_top['nama']) ?></p>

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1" style="font-size: 13px;">Total Dokumen</p>
                        <h3 class="fw-bold mb-0"><?= $total_dokumen ?></h3>
                        <small class="text-success" style="font-size: 12px;"><i class="bi bi-arrow-up"></i> 12% dari bulan lalu</small>
                    </div>
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-file-earmark-text"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1" style="font-size: 13px;">Total Pengguna</p>
                        <h3 class="fw-bold mb-0"><?= $total_pengguna ?></h3>
                        <small class="text-success" style="font-size: 12px;"><i class="bi bi-arrow-up"></i> 8% dari bulan lalu</small>
                    </div>
                    <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="bi bi-people"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1" style="font-size: 13px;">Belum Diperiksa</p>
                        <h3 class="fw-bold mb-0"><?= $belum_diperiksa ?></h3>
                        <small class="text-danger" style="font-size: 12px;"><i class="bi bi-arrow-down"></i> 3% dari bulan lalu</small>
                    </div>
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-hourglass-split"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1" style="font-size: 13px;">Temuan Aktif</p>
                        <h3 class="fw-bold mb-0"><?= $temuan_aktif ?></h3>
                        <small class="text-danger" style="font-size: 12px;"><i class="bi bi-arrow-down"></i> 3% dari bulan lalu</small>
                    </div>
                    <div class="stat-icon bg-danger bg-opacity-10 text-danger"><i class="bi bi-exclamation-triangle"></i></div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-8">
                <div class="stat-card" style="height: 100%;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0">Status QC Dokumen</h6>
                    </div>
                    <div style="position: relative; height:250px; width:100%">
                        <canvas id="chartStatusQC"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card" style="height: 100%;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0">Aktivitas Terbaru</h6>
                        <a href="monitoring.php" class="text-decoration-none" style="font-size: 12px;">Lihat Semua <i class="bi bi-arrow-right"></i></a>
                    </div>
                    <div class="list-group list-group-flush">
                        <?php foreach($aktivitas_terbaru as $log): ?>
                        <div class="list-group-item px-0 py-3 border-0 border-bottom">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1 fw-semibold" style="font-size: 14px;"><?= htmlspecialchars($log['nama']) ?></h6>
                                <small class="text-muted" style="font-size: 12px;"><?= date('H:i', strtotime($log['waktu'])) ?></small>
                            </div>
                            <p class="mb-1 text-muted" style="font-size: 13px;"><?= htmlspecialchars($log['jenis_aktivitas']) ?></p>
                            <small class="text-muted" style="font-size: 12px;"><i class="bi bi-box me-1"></i> <?= htmlspecialchars($log['objek']) ?></small>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('chartStatusQC').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Selesai', 'Dalam Proses', 'Belum Diperiksa'],
                datasets: [{
                    data: [60, 14, 11],
                    backgroundColor: ['#10b981', '#3b82f6', '#f59e0b'],
                    borderWidth: 0, hoverOffset: 4
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'right', labels: { font: { family: 'Poppins', size: 12 }, usePointStyle: true, padding: 20 } } },
                cutout: '75%'
            }
        });
    </script>
</body>
</html>