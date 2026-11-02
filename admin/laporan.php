<?php
session_start();
require_once '../config/koneksi.php';

// Proteksi Halaman
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Hitung Ringkasan
$total_dokumen = $pdo->query("SELECT COUNT(*) FROM lembar_kerja")->fetchColumn();
$total_selesai = $pdo->query("SELECT COUNT(*) FROM lembar_kerja WHERE status_qc = 'Selesai'")->fetchColumn();
$total_revisi  = $pdo->query("SELECT COUNT(*) FROM lembar_kerja WHERE status_qc = 'Perlu Perbaikan'")->fetchColumn();
$total_temuan  = $pdo->query("SELECT COUNT(*) FROM hasil_pencocokan")->fetchColumn();

// Ambil data untuk tabel
$stmt = $pdo->query("SELECT lk.*, p.nama AS nama_petugas 
                     FROM lembar_kerja lk 
                     JOIN pengguna p ON lk.id_pengguna = p.id_pengguna 
                     ORDER BY lk.tanggal_input DESC");
$laporan = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Sistem - BPS QC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/sidebar_admin.php'; ?>

    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <h5 class="mb-0 fw-bold">Laporan Sistem</h5>
            <div class="d-flex align-items-center">
                <i class="bi bi-bell me-3 fs-5 text-muted"></i>
                <div class="d-flex align-items-center">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['nama']) ?>&background=1a56db&color=fff" class="rounded-circle me-2" width="35">
                    <span class="fw-semibold" style="font-size: 14px;"><?= $_SESSION['nama'] ?></span>
                </div>
            </div>
        </div>

        <p class="text-muted mb-4">Rekapitulasi hasil pemeriksaan dan pencocokan data secara keseluruhan.</p>

        <!-- Kartu Ringkasan -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <h3 class="fw-bold text-primary"><?= $total_dokumen ?></h3>
                    <small class="text-muted">Total Dokumen</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <h3 class="fw-bold text-success"><?= $total_selesai ?></h3>
                    <small class="text-muted">Selesai</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <h3 class="fw-bold text-danger"><?= $total_revisi ?></h3>
                    <small class="text-muted">Perlu Perbaikan</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <h3 class="fw-bold text-warning"><?= $total_temuan ?></h3>
                    <small class="text-muted">Total Temuan</small>
                </div>
            </div>
        </div>

        <!-- Tabel Laporan -->
        <div class="stat-card">
            <h6 class="fw-bold mb-3">Daftar Seluruh Dokumen</h6>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Nama Dokumen</th>
                            <th>Petugas Penginput</th>
                            <th>Periode</th>
                            <th>Status QC</th>
                            <th>Status Review</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($laporan) > 0): ?>
                            <?php $no = 1; foreach($laporan as $row): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td class="fw-semibold"><?= htmlspecialchars($row['nama_dokumen']) ?></td>
                                <td><?= htmlspecialchars($row['nama_petugas']) ?></td>
                                <td><?= htmlspecialchars($row['periode']) ?></td>
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
                                <td>
                                    <?php if($row['status_review'] == 'Disetujui'): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success px-2 py-1">Disetujui</span>
                                    <?php elseif($row['status_review'] == 'Ditolak'): ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger px-2 py-1">Ditolak</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary px-2 py-1">Menunggu</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada data dokumen.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>