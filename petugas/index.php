<?php
session_start();
require_once '../config/koneksi.php';

// Proteksi Halaman
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'petugas') {
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
    $stmt = $pdo->query("SELECT COUNT(*) FROM lembar_kerja WHERE status_qc != 'Selesai'");
    $tugas_pemeriksaan = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM lembar_kerja WHERE status_qc = 'Belum Diperiksa'");
    $belum_diperiksa = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM hasil_pencocokan WHERE status_temuan = 'Belum Diperbaiki'");
    $temuan_aktif = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM hasil_pencocokan WHERE status_temuan = 'Menunggu Verifikasi'");
    $perlu_verifikasi = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT * FROM lembar_kerja ORDER BY tanggal_input DESC LIMIT 3");
    $dokumen_terbaru = $stmt->fetchAll();

    $stmt = $pdo->query("SELECT * FROM lembar_kerja WHERE status_qc != 'Selesai' LIMIT 5");
    $perlu_ditangani = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Petugas - BPS QC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/sidebar_petugas.php'; ?>

    <div class="main-content">
        <!-- Topbar dengan Notifikasi & Foto Profil -->
        <div class="topbar">
            <h5 class="mb-0 fw-bold">Dashboard Petugas QC</h5>
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
                        <p class="text-muted mb-1" style="font-size: 13px;">Tugas Pemeriksaan</p>
                        <h3 class="fw-bold mb-0"><?= $tugas_pemeriksaan ?></h3>
                        <small class="text-muted" style="font-size: 12px;">Dokumen belum selesai</small>
                    </div>
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-clipboard-check"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1" style="font-size: 13px;">Belum Diperiksa</p>
                        <h3 class="fw-bold mb-0"><?= $belum_diperiksa ?></h3>
                        <small class="text-muted" style="font-size: 12px;">Menunggu proses QC</small>
                    </div>
                    <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="bi bi-hourglass-split"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1" style="font-size: 13px;">Temuan Aktif</p>
                        <h3 class="fw-bold mb-0"><?= $temuan_aktif ?></h3>
                        <small class="text-danger" style="font-size: 12px;">Belum diperbaiki</small>
                    </div>
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-exclamation-triangle"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1" style="font-size: 13px;">Perlu Verifikasi</p>
                        <h3 class="fw-bold mb-0"><?= $perlu_verifikasi ?></h3>
                        <small class="text-muted" style="font-size: 12px;">Menunggu review</small>
                    </div>
                    <div class="stat-icon bg-danger bg-opacity-10 text-danger"><i class="bi bi-patch-check"></i></div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-8">
                <div class="stat-card" style="height: 100%;">
                    <h6 class="fw-bold mb-3">Progres Pemeriksaan</h6>
                    <div style="position: relative; height:250px; width:100%">
                        <canvas id="chartProgresPetugas"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card" style="height: 100%;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0">Pekerjaan Terbaru</h6>
                        <a href="riwayat.php" class="text-decoration-none" style="font-size: 12px;">Lihat Semua <i class="bi bi-arrow-right"></i></a>
                    </div>
                    <div class="list-group list-group-flush">
                        <?php foreach($dokumen_terbaru as $doc): ?>
                        <div class="list-group-item px-0 py-3 border-0 border-bottom">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1 fw-semibold" style="font-size: 14px;"><?= htmlspecialchars($doc['nama_dokumen']) ?></h6>
                            </div>
                            <p class="mb-2 text-muted" style="font-size: 12px;"><?= htmlspecialchars($doc['jenis']) ?> • <?= htmlspecialchars($doc['periode']) ?></p>
                            <span class="badge bg-<?= ($doc['status_qc'] == 'Belum Diperiksa') ? 'warning' : 'primary' ?> bg-opacity-10 text-<?= ($doc['status_qc'] == 'Belum Diperiksa') ? 'warning' : 'primary' ?> px-2 py-1"><?= $doc['status_qc'] ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <h6 class="fw-bold mb-3">Dokumen yang Perlu Ditangani</h6>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr><th>No</th><th>Nama Dokumen</th><th>Jenis</th><th>Periode</th><th>Status</th><th class="text-center">Aksi</th></tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; foreach($perlu_ditangani as $row): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td class="fw-semibold"><?= htmlspecialchars($row['nama_dokumen']) ?></td>
                            <td><?= htmlspecialchars($row['jenis']) ?></td>
                            <td><?= htmlspecialchars($row['periode']) ?></td>
                            <td>
                                <?php 
                                    $badge = 'secondary';
                                    if($row['status_qc'] == 'Belum Diperiksa') $badge = 'warning';
                                    elseif($row['status_qc'] == 'Dalam Proses') $badge = 'primary';
                                    elseif($row['status_qc'] == 'Perlu Perbaikan') $badge = 'danger';
                                ?>
                                <span class="badge bg-<?= $badge ?> bg-opacity-10 text-<?= $badge ?> px-2 py-1"><?= $row['status_qc'] ?></span>
                            </td>
                            <td class="text-center">
                                <?php if($row['status_qc'] == 'Belum Diperiksa'): ?>
                                    <a href="pemeriksaan.php?id=<?= $row['id_lembar_kerja'] ?>" class="btn btn-sm btn-primary" style="font-size: 12px;">Periksa</a>
                                <?php else: ?>
                                    <a href="pencocokan.php?id=<?= $row['id_lembar_kerja'] ?>" class="btn btn-sm btn-outline-primary" style="font-size: 12px;">Lihat</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($perlu_ditangani)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">Tidak ada dokumen yang perlu ditangani. Kerja bagus!</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctxBar = document.getElementById('chartProgresPetugas').getContext('2d');
        new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun'],
                datasets: [
                    { label: 'Selesai', data: [12, 19, 15, 25, 22, 30], backgroundColor: '#10b981', borderRadius: 4 },
                    { label: 'Dalam Proses', data: [5, 8, 6, 10, 8, 12], backgroundColor: '#3b82f6', borderRadius: 4 },
                    { label: 'Belum Diperiksa', data: [8, 5, 10, 6, 12, 8], backgroundColor: '#f59e0b', borderRadius: 4 }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 8, font: { family: 'Poppins', size: 12 } } } },
                scales: { y: { beginAtZero: true, grid: { borderDash: [5, 5] } }, x: { grid: { display: false } } }
            }
        });
    </script>
</body>
</html>