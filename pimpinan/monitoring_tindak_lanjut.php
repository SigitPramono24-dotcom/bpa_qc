<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'pimpinan') {
    header("Location: ../auth/login.php");
    exit;
}

// --- Mengambil Data Tindak Lanjut ---
$stmt = $pdo->query("SELECT hp.*, lk.nama_dokumen, p.nama AS nama_petugas 
                     FROM hasil_pencocokan hp
                     JOIN quality_control qc ON hp.id_qc = qc.id_qc
                     JOIN lembar_kerja lk ON qc.id_lembar_kerja = lk.id_lembar_kerja
                     LEFT JOIN pengguna p ON qc.id_pengguna = p.id_pengguna
                     ORDER BY hp.tanggal_selesai DESC, hp.id_pencocokan DESC");
$tindak_lanjut = $stmt->fetchAll();

// Hitung Ringkasan Status
$total_temuan = count($tindak_lanjut);
$belum_selesai = 0; $menunggu = 0; $selesai = 0;

foreach($tindak_lanjut as $row) {
    if($row['status_temuan'] == 'Belum Diperbaiki') $belum_selesai++;
    elseif($row['status_temuan'] == 'Menunggu Verifikasi') $menunggu++;
    elseif($row['status_temuan'] == 'Selesai') $selesai++;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Monitoring Tindak Lanjut - BPS QC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/sidebar_pimpinan.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <h5 class="mb-0 fw-bold">Monitoring Tindak Lanjut</h5>
            <div class="d-flex align-items-center">
                <i class="bi bi-bell me-3 fs-5 text-muted"></i>
                <div class="d-flex align-items-center">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['nama']) ?>&background=1a56db&color=fff" class="rounded-circle me-2" width="35">
                    <span class="fw-semibold" style="font-size: 14px;"><?= $_SESSION['nama'] ?></span>
                </div>
            </div>
        </div>

        <p class="text-muted mb-4">Pantau status perbaikan temuan, penanggung jawab, dan waktu penyelesaiannya.</p>

        <!-- Kartu Ringkasan -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stat-card d-flex align-items-center justify-content-between border-start border-danger border-4">
                    <div>
                        <p class="text-muted mb-1" style="font-size: 13px;">Belum Selesai</p>
                        <h3 class="fw-bold mb-0 text-danger"><?= $belum_selesai ?></h3>
                    </div>
                    <i class="bi bi-exclamation-circle text-danger fs-2 opacity-50"></i>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card d-flex align-items-center justify-content-between border-start border-warning border-4">
                    <div>
                        <p class="text-muted mb-1" style="font-size: 13px;">Menunggu Verifikasi</p>
                        <h3 class="fw-bold mb-0 text-warning"><?= $menunggu ?></h3>
                    </div>
                    <i class="bi bi-hourglass-split text-warning fs-2 opacity-50"></i>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card d-flex align-items-center justify-content-between border-start border-success border-4">
                    <div>
                        <p class="text-muted mb-1" style="font-size: 13px;">Selesai</p>
                        <h3 class="fw-bold mb-0 text-success"><?= $selesai ?></h3>
                    </div>
                    <i class="bi bi-check-circle text-success fs-2 opacity-50"></i>
                </div>
            </div>
        </div>

        <!-- Tabel Tindak Lanjut -->
        <div class="stat-card">
            <h6 class="fw-bold mb-3">Daftar Tindak Lanjut</h6>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID Temuan</th>
                            <th>Dokumen</th>
                            <th>Penanggung Jawab</th>
                            <th>Tanggal Temuan</th>
                            <th>Lama</th>
                            <th>Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($tindak_lanjut) > 0): ?>
                            <?php foreach($tindak_lanjut as $row): 
                                // Hitung lama hari (simulasi sederhana)
                                $tgl_mulai = new DateTime($row['tanggal_periksa'] ?? 'now');
                                $tgl_selesai = $row['tanggal_selesai'] ? new DateTime($row['tanggal_selesai']) : new DateTime();
                                $lama = $tgl_mulai->diff($tgl_selesai)->days;
                            ?>
                            <tr>
                                <td class="fw-bold text-secondary">#F-<?= str_pad($row['id_pencocokan'], 3, '0', STR_PAD_LEFT) ?></td>
                                <td>
                                    <span class="d-block fw-semibold"><?= htmlspecialchars($row['nama_dokumen']) ?></span>
                                    <small class="text-muted"><?= htmlspecialchars($row['elemen_data']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($row['nama_petugas'] ?? 'Petugas') ?></td>
                                <td><?= date('d/m/Y', strtotime($row['tanggal_periksa'] ?? 'now')) ?></td>
                                <td><?= $lama ?> hari</td>
                                <td>
                                    <?php 
                                        $badge = 'secondary';
                                        if($row['status_temuan'] == 'Selesai') $badge = 'success';
                                        elseif($row['status_temuan'] == 'Menunggu Verifikasi') $badge = 'primary';
                                        elseif($row['status_temuan'] == 'Belum Diperbaiki') $badge = 'danger';
                                    ?>
                                    <span class="badge bg-<?= $badge ?> bg-opacity-10 text-<?= $badge ?> px-2 py-1"><?= $row['status_temuan'] ?></span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-secondary" style="font-size: 12px;" data-bs-toggle="modal" data-bs-target="#modalDetail<?= $row['id_pencocokan'] ?>">Lihat</button>
                                    
                                    <!-- Modal Detail -->
                                    <div class="modal fade" id="modalDetail<?= $row['id_pencocokan'] ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered text-start">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Detail Tindak Lanjut #F-<?= str_pad($row['id_pencocokan'], 3, '0', STR_PAD_LEFT) ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p><strong>Dokumen:</strong> <?= htmlspecialchars($row['nama_dokumen']) ?></p>
                                                    <p><strong>Masalah:</strong> <?= htmlspecialchars($row['catatan_ketidaksesuaian']) ?></p>
                                                    <p><strong>Tindakan Perbaikan:</strong> <?= htmlspecialchars($row['tindak_lanjut'] ?? 'Belum ada tindakan') ?></p>
                                                    <p><strong>Tanggal Selesai:</strong> <?= $row['tanggal_selesai'] ? date('d/m/Y H:i', strtotime($row['tanggal_selesai'])) : '-' ?></p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center py-4 text-muted">Belum ada data tindak lanjut.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>