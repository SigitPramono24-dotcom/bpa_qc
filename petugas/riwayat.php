<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'petugas') {
    header("Location: ../auth/login.php");
    exit;
}

// Ambil riwayat QC beserta nama dokumen dan nama petugas
$query = "SELECT qc.*, lk.nama_dokumen, lk.jenis, p.nama AS nama_petugas 
          FROM quality_control qc
          JOIN lembar_kerja lk ON qc.id_lembar_kerja = lk.id_lembar_kerja
          JOIN pengguna p ON qc.id_pengguna = p.id_pengguna
          ORDER BY qc.tanggal_periksa DESC";
$stmt = $pdo->query($query);
$riwayat = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Riwayat Pemeriksaan - BPS QC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/sidebar_petugas.php'; ?>

    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <h5 class="mb-0 fw-bold">Riwayat Pemeriksaan</h5>
            <div class="d-flex align-items-center">
                <i class="bi bi-bell me-3 fs-5 text-muted"></i>
                <div class="d-flex align-items-center">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['nama']) ?>&background=1a56db&color=fff" class="rounded-circle me-2" width="35">
                    <span class="fw-semibold" style="font-size: 14px;"><?= $_SESSION['nama'] ?></span>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="fw-bold mb-1">Riwayat Pemeriksaan</h5>
                <p class="text-muted mb-0" style="font-size: 14px;">Lihat riwayat hasil QC dan pencocokan sebelumnya</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Tanggal Periksa</th>
                            <th>Nama Dokumen</th>
                            <th>Petugas</th>
                            <th>Total Data</th>
                            <th>Temuan</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($riwayat) > 0): ?>
                            <?php $no = 1; foreach($riwayat as $row): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($row['tanggal_periksa'])) ?></td>
                                <td class="fw-semibold"><?= htmlspecialchars($row['nama_dokumen']) ?></td>
                                <td><?= htmlspecialchars($row['nama_petugas']) ?></td>
                                <td><?= number_format($row['total_data'], 0, ',', '.') ?></td>
                                <td>
                                    <span class="badge bg-<?= ($row['total_temuan'] > 0) ? 'danger' : 'success' ?> bg-opacity-10 text-<?= ($row['total_temuan'] > 0) ? 'danger' : 'success' ?> px-2 py-1">
                                        <?= $row['total_temuan'] ?> Temuan
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="pencocokan.php?id=<?= $row['id_lembar_kerja'] ?>" class="btn btn-sm btn-outline-primary" style="font-size: 12px;">Detail</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center py-4 text-muted">Belum ada riwayat pemeriksaan.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>