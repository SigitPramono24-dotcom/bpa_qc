<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'pimpinan') {
    header("Location: ../auth/login.php");
    exit;
}

// --- Logika Filter ---
$filter_periode = isset($_GET['periode']) ? $_GET['periode'] : '';
$filter_jenis = isset($_GET['jenis']) ? $_GET['jenis'] : '';

$query = "SELECT * FROM lembar_kerja WHERE 1=1";
$params = [];

if (!empty($filter_periode)) {
    $query .= " AND periode = :periode";
    $params[':periode'] = $filter_periode;
}
if (!empty($filter_jenis)) {
    $query .= " AND jenis = :jenis";
    $params[':jenis'] = $filter_jenis;
}

$query .= " ORDER BY tanggal_input DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$dokumen_list = $stmt->fetchAll();

// Hitung total untuk kartu ringkasan
$total_dokumen = count($dokumen_list);
$belum_qc = 0; $dalam_proses = 0; $selesai = 0;

foreach($dokumen_list as $d) {
    if($d['status_qc'] == 'Belum Diperiksa') $belum_qc++;
    elseif($d['status_qc'] == 'Dalam Proses' || $d['status_qc'] == 'Perlu Perbaikan') $dalam_proses++;
    elseif($d['status_qc'] == 'Selesai') $selesai++;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Monitoring QC - BPS QC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .stat-box { background: #fff; border-radius: 10px; padding: 15px; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; }
        .stat-box h3 { font-weight: 700; margin: 0; }
    </style>
</head>
<body>
    <?php include '../includes/sidebar_pimpinan.php'; ?>

    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <h5 class="mb-0 fw-bold">Monitoring Quality Control</h5>
            <div class="d-flex align-items-center">
                <i class="bi bi-bell me-3 fs-5 text-muted"></i>
                <div class="d-flex align-items-center">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['nama']) ?>&background=1a56db&color=fff" class="rounded-circle me-2" width="35">
                    <span class="fw-semibold" style="font-size: 14px;"><?= $_SESSION['nama'] ?></span>
                </div>
            </div>
        </div>

        <p class="text-muted mb-4">Pantau status pemeriksaan dokumen berdasarkan periode, kegiatan statistik, atau jenis dokumen.</p>

        <!-- Filter Form -->
        <div class="stat-card mb-4">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Periode</label>
                    <select name="periode" class="form-select">
                        <option value="">Semua Periode</option>
                        <option value="2024" <?= ($filter_periode == '2024') ? 'selected' : '' ?>>2024</option>
                        <option value="2025" <?= ($filter_periode == '2025') ? 'selected' : '' ?>>2025</option>
                        <option value="2026" <?= ($filter_periode == '2026') ? 'selected' : '' ?>>2026</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Jenis Dokumen</label>
                    <select name="jenis" class="form-select">
                        <option value="">Semua Jenis</option>
                        <option value="Lembar Kerja" <?= ($filter_jenis == 'Lembar Kerja') ? 'selected' : '' ?>>Lembar Kerja</option>
                        <option value="Publikasi" <?= ($filter_jenis == 'Publikasi') ? 'selected' : '' ?>>Publikasi</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100" style="background-color: #1a56db;"><i class="bi bi-funnel me-1"></i> Tampilkan</button>
                </div>
            </form>
        </div>

        <!-- Kartu Ringkasan -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stat-box">
                    <div>
                        <small class="text-muted d-block">Total Dokumen</small>
                        <h3><?= $total_dokumen ?></h3>
                    </div>
                    <i class="bi bi-file-earmark-text text-primary fs-1 opacity-50"></i>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-box">
                    <div>
                        <small class="text-muted d-block">Belum QC</small>
                        <h3 class="text-warning"><?= $belum_qc ?></h3>
                    </div>
                    <i class="bi bi-hourglass text-warning fs-1 opacity-50"></i>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-box">
                    <div>
                        <small class="text-muted d-block">Dalam Proses</small>
                        <h3 class="text-info"><?= $dalam_proses ?></h3>
                    </div>
                    <i class="bi bi-arrow-repeat text-info fs-1 opacity-50"></i>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-box">
                    <div>
                        <small class="text-muted d-block">Selesai</small>
                        <h3 class="text-success"><?= $selesai ?></h3>
                    </div>
                    <i class="bi bi-check-circle text-success fs-1 opacity-50"></i>
                </div>
            </div>
        </div>

        <!-- Grafik -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="stat-card h-100">
                    <h6 class="fw-bold mb-3">Status QC per Periode</h6>
                    <div style="position: relative; height: 250px; width: 100%;">
                        <canvas id="chartMonitoring"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card h-100">
                    <h6 class="fw-bold mb-3">Status QC per Jenis</h6>
                    <div style="position: relative; height: 250px; width: 100%;">
                        <canvas id="chartJenis"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Daftar Dokumen -->
        <div class="stat-card">
            <h6 class="fw-bold mb-3">Daftar Dokumen dan Status QC</h6>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Nama Dokumen</th>
                            <th>Jenis</th>
                            <th>Periode</th>
                            <th>Kegiatan</th>
                            <th>Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($dokumen_list) > 0): ?>
                            <?php $no = 1; foreach($dokumen_list as $row): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td class="fw-semibold"><?= htmlspecialchars($row['nama_dokumen']) ?></td>
                                <td><?= htmlspecialchars($row['jenis']) ?></td>
                                <td><?= htmlspecialchars($row['periode']) ?></td>
                                <td><?= htmlspecialchars($row['kegiatan_statistik']) ?></td>
                                <td>
                                    <?php 
                                        $badge = 'secondary';
                                        if($row['status_qc'] == 'Selesai') $badge = 'success';
                                        elseif($row['status_qc'] == 'Dalam Proses') $badge = 'primary';
                                        elseif($row['status_qc'] == 'Perlu Perbaikan') $badge = 'danger';
                                        elseif($row['status_qc'] == 'Belum Diperiksa') $badge = 'warning';
                                    ?>
                                    <span class="badge bg-<?= $badge ?> bg-opacity-10 text-<?= $badge ?> px-2 py-1"><?= $row['status_qc'] ?></span>
                                </td>
                                <td class="text-center">
                                    <a href="hasil_pencocokan.php?id=<?= $row['id_lembar_kerja'] ?>" class="btn btn-sm btn-outline-primary" style="font-size: 12px;">Lihat</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center py-4 text-muted">Tidak ada data dokumen.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Grafik Batang Bertumpuk (Stacked Bar) per Periode
        const ctxBar = document.getElementById('chartMonitoring').getContext('2d');
        new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun'],
                datasets: [
                    { label: 'Belum QC', data: [5, 8, 6, 10, 8, 12], backgroundColor: '#f59e0b', borderRadius: 4 },
                    { label: 'Dalam Proses', data: [8, 5, 10, 6, 12, 8], backgroundColor: '#3b82f6', borderRadius: 4 },
                    { label: 'Selesai', data: [15, 20, 18, 25, 22, 30], backgroundColor: '#10b981', borderRadius: 4 }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 8 } } },
                scales: { x: { stacked: true, grid: { display: false } }, y: { stacked: true, beginAtZero: true } }
            }
        });

        // Grafik Donat per Jenis
        const ctxDonut = document.getElementById('chartJenis').getContext('2d');
        new Chart(ctxDonut, {
            type: 'doughnut',
            data: {
                labels: ['Lembar Kerja', 'Publikasi', 'Lainnya'],
                datasets: [{
                    data: [<?= $total_dokumen > 0 ? '25, 15, 10' : '0,0,0' ?>], // Simulasi angka
                    backgroundColor: ['#10b981', '#3b82f6', '#f59e0b'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false, cutout: '70%',
                plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } } }
            }
        });
    </script>
</body>
</html>