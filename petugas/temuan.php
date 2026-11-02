<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'petugas') {
    header("Location: ../auth/login.php");
    exit;
}

// --- Logika Filter ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';

$query = "SELECT hp.*, lk.nama_dokumen, aq.nama_aturan 
          FROM hasil_pencocokan hp
          JOIN quality_control qc ON hp.id_qc = qc.id_qc
          JOIN lembar_kerja lk ON qc.id_lembar_kerja = lk.id_lembar_kerja
          LEFT JOIN aturan_qc aq ON hp.id_aturan = aq.id_aturan
          WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (hp.elemen_data LIKE :search OR lk.nama_dokumen LIKE :search)";
    $params[':search'] = "%$search%";
}
if (!empty($filter_status)) {
    $query .= " AND hp.status_temuan = :status";
    $params[':status'] = $filter_status;
}

$query .= " ORDER BY hp.id_pencocokan DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$daftar_temuan = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Temuan - BPS QC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/sidebar_petugas.php'; ?>

    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <h5 class="mb-0 fw-bold">Daftar Temuan</h5>
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
                <h5 class="fw-bold mb-1">Daftar Temuan</h5>
                <p class="text-muted mb-0" style="font-size: 14px;">Kelola seluruh temuan dari hasil QC dan pencocokan</p>
            </div>
        </div>

        <div class="stat-card">
            <!-- Filter & Search -->
            <form method="GET" class="row mb-4 g-2">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Cari nama dokumen atau elemen..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="Belum Diperbaiki" <?= ($filter_status == 'Belum Diperbaiki') ? 'selected' : '' ?>>Belum Diperbaiki</option>
                        <option value="Menunggu Verifikasi" <?= ($filter_status == 'Menunggu Verifikasi') ? 'selected' : '' ?>>Menunggu Verifikasi</option>
                        <option value="Selesai" <?= ($filter_status == 'Selesai') ? 'selected' : '' ?>>Selesai</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100" style="background-color: #1a56db;">Filter</button>
                </div>
            </form>

            <!-- Tabel Daftar Temuan -->
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID Temuan</th>
                            <th>Dokumen</th>
                            <th>Aturan</th>
                            <th>Masalah</th>
                            <th>Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($daftar_temuan) > 0): ?>
                            <?php foreach($daftar_temuan as $row): ?>
                            <tr>
                                <td class="fw-bold text-secondary">#F-<?= str_pad($row['id_pencocokan'], 3, '0', STR_PAD_LEFT) ?></td>
                                <td>
                                    <span class="d-block fw-semibold"><?= htmlspecialchars($row['nama_dokumen']) ?></span>
                                </td>
                                <td><?= htmlspecialchars($row['nama_aturan'] ?? 'Umum') ?></td>
                                <td><?= htmlspecialchars($row['catatan_ketidaksesuaian']) ?></td>
                                <td>
                                    <?php 
                                        $status = $row['status_temuan'];
                                        $badge = 'secondary';
                                        if($status == 'Selesai') $badge = 'success';
                                        elseif($status == 'Menunggu Verifikasi') $badge = 'primary';
                                        elseif($status == 'Belum Diperbaiki') $badge = 'warning';
                                    ?>
                                    <span class="badge bg-<?= $badge ?> bg-opacity-10 text-<?= $badge ?> px-2 py-1" style="font-weight: 500;">
                                        <?= $status ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="tindak_lanjut.php?id=<?= $row['id_pencocokan'] ?>" class="btn btn-sm btn-outline-primary" style="font-size: 12px;">Detail</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">Tidak ada temuan ditemukan.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                <small class="text-muted">Menampilkan <?= count($daftar_temuan) ?> data temuan</small>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>