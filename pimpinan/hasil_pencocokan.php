<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'pimpinan') {
    header("Location: ../auth/login.php");
    exit;
}

// --- Logika Filter ---
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';

$query = "SELECT hp.*, lk.nama_dokumen, lk.periode 
          FROM hasil_pencocokan hp
          JOIN quality_control qc ON hp.id_qc = qc.id_qc
          JOIN lembar_kerja lk ON qc.id_lembar_kerja = lk.id_lembar_kerja
          WHERE 1=1";
$params = [];

if (!empty($filter_status)) {
    $query .= " AND hp.status_temuan = :status";
    $params[':status'] = $filter_status;
}
$query .= " ORDER BY hp.id_pencocokan DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$hasil = $stmt->fetchAll();

// Hitung ringkasan (simulasi dari data yang ada)
$total_seluruh = 125; // Simulasi total data keseluruhan
$total_temuan = count($hasil);
$total_lulus = $total_seluruh - $total_temuan;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Hasil Pencocokan - BPS QC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/sidebar_pimpinan.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <h5 class="mb-0 fw-bold">Hasil Pencocokan</h5>
            <div class="d-flex align-items-center">
                <i class="bi bi-bell me-3 fs-5 text-muted"></i>
                <div class="d-flex align-items-center">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['nama']) ?>&background=1a56db&color=fff" class="rounded-circle me-2" width="35">
                    <span class="fw-semibold" style="font-size: 14px;"><?= $_SESSION['nama'] ?></span>
                </div>
            </div>
        </div>

        <p class="text-muted mb-4">Lihat rincian hasil nilai, indikator yang bermasalah, dan dokumen yang lengkap.</p>

        <!-- Kartu Ringkasan -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <h3 class="text-success fw-bold"><?= $total_lulus ?></h3>
                    <small class="text-muted">Sesuai</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <h3 class="text-warning fw-bold"><?= $total_temuan ?></h3>
                    <small class="text-muted">Selisih</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <h3 class="text-danger fw-bold">0</h3>
                    <small class="text-muted">Tanpa Pasangan</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <h3 class="text-secondary fw-bold">0</h3>
                    <small class="text-muted">Belum Lengkap</small>
                </div>
            </div>
        </div>

        <!-- Tabel Hasil -->
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0">Hasil Pencocokan Data</h6>
                <form method="GET" class="d-flex gap-2">
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Semua Status</option>
                        <option value="Belum Diperbaiki" <?= ($filter_status == 'Belum Diperbaiki') ? 'selected' : '' ?>>Belum Diperbaiki</option>
                        <option value="Menunggu Verifikasi" <?= ($filter_status == 'Menunggu Verifikasi') ? 'selected' : '' ?>>Menunggu Verifikasi</option>
                        <option value="Selesai" <?= ($filter_status == 'Selesai') ? 'selected' : '' ?>>Selesai</option>
                    </select>
                </form>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Dokumen</th>
                            <th>Elemen Data</th>
                            <th>Nilai Rujukan</th>
                            <th>Nilai Ditemukan</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($hasil) > 0): ?>
                            <?php $no = 1; foreach($hasil as $row): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td>
                                    <span class="d-block fw-semibold"><?= htmlspecialchars($row['nama_dokumen']) ?></span>
                                    <small class="text-muted">Periode: <?= $row['periode'] ?></small>
                                </td>
                                <td><?= htmlspecialchars($row['elemen_data']) ?></td>
                                <td class="text-success"><?= htmlspecialchars($row['nilai_rujukan']) ?></td>
                                <td class="text-danger"><?= htmlspecialchars($row['nilai_ditemukan']) ?></td>
                                <td>
                                    <?php 
                                        $badge = 'secondary';
                                        if($row['status_temuan'] == 'Selesai') $badge = 'success';
                                        elseif($row['status_temuan'] == 'Menunggu Verifikasi') $badge = 'primary';
                                        elseif($row['status_temuan'] == 'Belum Diperbaiki') $badge = 'warning';
                                    ?>
                                    <span class="badge bg-<?= $badge ?> bg-opacity-10 text-<?= $badge ?> px-2 py-1"><?= $row['status_temuan'] ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">Tidak ada data pencocokan.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>