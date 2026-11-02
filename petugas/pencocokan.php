<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'petugas') { header("Location: ../auth/login.php"); exit; }

$id_lembar_kerja = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$dokumen = null; $qc_terakhir = null; $temuan = [];

if ($id_lembar_kerja > 0) {
    $stmt = $pdo->prepare("SELECT * FROM lembar_kerja WHERE id_lembar_kerja = :id");
    $stmt->execute([':id' => $id_lembar_kerja]);
    $dokumen = $stmt->fetch();

    if ($dokumen) {
        $stmt_qc = $pdo->prepare("SELECT * FROM quality_control WHERE id_lembar_kerja = :id ORDER BY id_qc DESC LIMIT 1");
        $stmt_qc->execute([':id' => $id_lembar_kerja]);
        $qc_terakhir = $stmt_qc->fetch();
        if ($qc_terakhir) {
            $stmt_temuan = $pdo->prepare("SELECT hp.*, aq.nama_aturan FROM hasil_pencocokan hp LEFT JOIN aturan_qc aq ON hp.id_aturan = aq.id_aturan WHERE hp.id_qc = :id_qc");
            $stmt_temuan->execute([':id_qc' => $qc_terakhir['id_qc']]);
            $temuan = $stmt_temuan->fetchAll();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pencocokan Lembar Kerja - BPS QC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/sidebar_petugas.php'; ?>
    <div class="main-content">
        <div class="topbar"><h5 class="mb-0 fw-bold">Pencocokan Lembar Kerja</h5></div>
        
        <?php if($id_lembar_kerja == 0 || !$dokumen): ?>
            <p class="text-muted mb-4">Pilih dokumen di bawah ini untuk melihat hasil pencocokan.</p>
            <div class="stat-card">
                <table class="table table-hover align-middle">
                    <thead class="table-light"><tr><th>No</th><th>Nama Dokumen</th><th>Periode</th><th>Status QC</th><th>Aksi</th></tr></thead>
                    <tbody>
                        <?php 
                        $stmt_list = $pdo->query("SELECT * FROM lembar_kerja WHERE status_qc IN ('Dalam Proses', 'Perlu Perbaikan', 'Selesai') ORDER BY tanggal_input DESC");
                        $list_doc = $stmt_list->fetchAll();
                        $no=1; foreach($list_doc as $row): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td class="fw-semibold"><?= htmlspecialchars($row['nama_dokumen']) ?></td>
                            <td><?= htmlspecialchars($row['periode']) ?></td>
                            <td><span class="badge bg-info bg-opacity-10 text-info"><?= $row['status_qc'] ?></span></td>
                            <td><a href="pencocokan.php?id=<?= $row['id_lembar_kerja'] ?>" class="btn btn-sm btn-outline-primary">Lihat Detail</a></td>
                        </tr>
                        <?php endforeach; if(empty($list_doc)) echo "<tr><td colspan='5' class='text-center'>Belum ada dokumen yang di-QC.</td></tr>"; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <!-- Konten Pencocokan (Sama seperti sebelumnya) -->
            <div class="stat-card mb-4">
                <div class="row g-3">
                    <div class="col-md-6"><label>Lembar Kerja</label><input type="text" class="form-control bg-light" value="<?= htmlspecialchars($dokumen['nama_dokumen']) ?>" readonly></div>
                    <div class="col-md-6"><label>Publikasi Acuan</label><input type="text" class="form-control bg-light" value="Publikasi Statistik <?= $dokumen['periode'] ?>" readonly></div>
                </div>
            </div>
            <div class="row mb-4">
                <div class="col-md-4"><div class="stat-card text-center"><h4><?= $qc_terakhir ? $qc_terakhir['total_data'] : '0' ?></h4><small>Total Data</small></div></div>
                <div class="col-md-4"><div class="stat-card text-center"><h4 class="text-primary"><?= $qc_terakhir ? $qc_terakhir['total_lulus'] : '0' ?></h4><small>Lulus</small></div></div>
                <div class="col-md-4"><div class="stat-card text-center bg-danger bg-opacity-10"><h4 class="text-danger"><?= $qc_terakhir ? $qc_terakhir['total_temuan'] : '0' ?></h4><small>Temuan</small></div></div>
            </div>
            <div class="stat-card">
                <table class="table table-hover align-middle">
                    <thead class="table-light"><tr><th>Wilayah</th><th>Lembar Kerja</th><th>Publikasi</th><th>Selisih</th><th>Status</th></tr></thead>
                    <tbody>
                        <tr><td>Kota Medan</td><td>2.500.000</td><td>2.500.000</td><td>0</td><td><span class="badge bg-success">Sesuai</span></td></tr>
                        <tr><td>Kec. Medan Baru</td><td>150.000</td><td>149.500</td><td class="text-danger">-500</td><td><span class="badge bg-danger">Temuan</span></td></tr>
                    </tbody>
                </table>
                <div class="mt-3 d-flex justify-content-end gap-2">
                    <a href="pencocokan.php" class="btn btn-outline-secondary">Kembali</a>
                    <a href="index.php" class="btn btn-primary">Selesai</a>
                </div>
            </div>
            <?php if(!empty($temuan)): ?>
            <div class="stat-card mt-4">
                <h6 class="fw-bold text-danger mb-3">Catatan Temuan Sistem</h6>
                <table class="table table-sm table-bordered">
                    <thead class="table-light"><tr><th>Aturan</th><th>Elemen</th><th>Rujukan</th><th>Ditemukan</th><th>Catatan</th></tr></thead>
                    <tbody>
                        <?php foreach($temuan as $t): ?>
                        <tr><td><?= htmlspecialchars($t['nama_aturan'] ?? 'Umum') ?></td><td><?= htmlspecialchars($t['elemen_data']) ?></td><td><?= htmlspecialchars($t['nilai_rujukan']) ?></td><td class="text-danger"><?= htmlspecialchars($t['nilai_ditemukan']) ?></td><td><?= htmlspecialchars($t['catatan_ketidaksesuaian']) ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>