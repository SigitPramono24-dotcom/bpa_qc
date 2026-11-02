<?php
session_start();
require_once '../config/koneksi.php';

// Proteksi Halaman
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'pimpinan') {
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

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM quality_control");
    $total_diperiksa = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT SUM(total_lulus) as lulus, SUM(total_data) as total FROM quality_control");
    $data_kesesuaian = $stmt->fetch();
    $tingkat_kesesuaian = ($data_kesesuaian['total'] > 0) ? round(($data_kesesuaian['lulus'] / $data_kesesuaian['total']) * 100) : 0;

    $stmt = $pdo->query("SELECT COUNT(*) FROM hasil_pencocokan WHERE status_temuan != 'Selesai'");
    $temuan_aktif = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM lembar_kerja WHERE status_qc = 'Selesai' AND status_review = 'Menunggu Review'");
    $menunggu_review = $stmt->fetchColumn();

    $chart_labels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun'];
    $chart_data = [85, 88, 90, 87, 92, 95]; 

    $stmt = $pdo->query("SELECT * FROM lembar_kerja WHERE status_qc = 'Selesai' AND status_review = 'Menunggu Review' ORDER BY tanggal_input DESC LIMIT 5");
    $dokumen_review = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Error mengambil data: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Pimpinan - BPS QC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/sidebar_pimpinan.php'; ?>

    <div class="main-content">
        <!-- Topbar dengan Notifikasi & Foto Profil -->
        <div class="topbar">
            <h5 class="mb-0 fw-bold">Dashboard Pimpinan</h5>
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
                        <p class="text-muted mb-1" style="font-size: 13px;">Dokumen Diperiksa</p>
                        <h3 class="fw-bold mb-0"><?= $total_diperiksa ?></h3>
                        <small class="text-muted" style="font-size: 12px;">dari 50 dokumen</small>
                    </div>
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-file-earmark-check"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1" style="font-size: 13px;">Tingkat Kesesuaian</p>
                        <h3 class="fw-bold mb-0"><?= $tingkat_kesesuaian ?>%</h3>
                        <small class="text-success" style="font-size: 12px;"><i class="bi bi-arrow-up"></i> 5% dari bulan lalu</small>
                    </div>
                    <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-graph-up-arrow"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1" style="font-size: 13px;">Temuan Aktif</p>
                        <h3 class="fw-bold mb-0"><?= $temuan_aktif ?></h3>
                        <small class="text-danger" style="font-size: 12px;">belum selesai</small>
                    </div>
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-exclamation-triangle"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1" style="font-size: 13px;">Menunggu Review</p>
                        <h3 class="fw-bold mb-0"><?= $menunggu_review ?></h3>
                        <small class="text-info" style="font-size: 12px;">laporan QC</small>
                    </div>
                    <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="bi bi-inbox"></i></div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-8">
                <div class="stat-card" style="height: 100%;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0">Progres Pemeriksaan</h6>
                        <a href="monitoring.php" class="text-decoration-none" style="font-size: 12px;">Lihat Semua <i class="bi bi-arrow-right"></i></a>
                    </div>
                    <div style="position: relative; height:250px; width:100%">
                        <canvas id="chartTren"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card" style="height: 100%;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0">Aktivitas Terbaru</h6>
                    </div>
                    <div class="list-group list-group-flush">
                        <div class="list-group-item px-0 py-3 border-0 border-bottom">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1 fw-semibold" style="font-size: 14px;">Review oleh Pimpinan</h6>
                                <small class="text-muted" style="font-size: 12px;">10:30</small>
                            </div>
                            <p class="mb-0 text-muted" style="font-size: 13px;">Dokumen Publikasi A disetujui</p>
                        </div>
                        <div class="list-group-item px-0 py-3 border-0 border-bottom">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1 fw-semibold" style="font-size: 14px;">Tindak Lanjut QC</h6>
                                <small class="text-muted" style="font-size: 12px;">09:15</small>
                            </div>
                            <p class="mb-0 text-muted" style="font-size: 13px;">Perbaikan data Lembar Kerja B</p>
                        </div>
                        <div class="list-group-item px-0 py-3 border-0">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1 fw-semibold" style="font-size: 14px;">Unggah Dokumen Baru</h6>
                                <small class="text-muted" style="font-size: 12px;">08:45</small>
                            </div>
                            <p class="mb-0 text-muted" style="font-size: 13px;">Lembar Kerja Indikator C diunggah</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <h6 class="fw-bold mb-3">Dokumen Menunggu Review</h6>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr><th>No</th><th>Nama Dokumen</th><th>Jenis</th><th>Periode</th><th>Status QC</th><th class="text-center">Aksi</th></tr>
                    </thead>
                    <tbody>
                        <?php if(count($dokumen_review) > 0): ?>
                            <?php $no = 1; foreach($dokumen_review as $row): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td class="fw-semibold"><?= htmlspecialchars($row['nama_dokumen']) ?></td>
                                <td><?= htmlspecialchars($row['jenis']) ?></td>
                                <td><?= htmlspecialchars($row['periode']) ?></td>
                                <td><span class="badge bg-success bg-opacity-10 text-success px-2 py-1"><?= $row['status_qc'] ?></span></td>
                                <td class="text-center">
                                    <a href="review.php?id=<?= $row['id_lembar_kerja'] ?>" class="btn btn-sm btn-outline-primary" style="font-size: 12px;">Review</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">Tidak ada dokumen yang menunggu review saat ini.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctxLine = document.getElementById('chartTren').getContext('2d');
        let gradient = ctxLine.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(26, 86, 219, 0.5)');   
        gradient.addColorStop(1, 'rgba(26, 86, 219, 0.0)');

        new Chart(ctxLine, {
            type: 'line',
            data: {
                labels: <?= json_encode($chart_labels) ?>,
                datasets: [{
                    label: 'Tingkat Kesesuaian (%)',
                    data: <?= json_encode($chart_data) ?>,
                    borderColor: '#1a56db', backgroundColor: gradient, borderWidth: 2,
                    pointBackgroundColor: '#fff', pointBorderColor: '#1a56db', pointBorderWidth: 2, pointRadius: 4,
                    fill: true, tension: 0.4
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: false, min: 50, max: 100, grid: { borderDash: [5, 5] } }, x: { grid: { display: false } } }
            }
        });
    </script>
</body>
</html>