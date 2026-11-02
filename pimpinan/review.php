<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'pimpinan') {
    header("Location: ../auth/login.php");
    exit;
}

$pesan = '';
$tipe_pesan = '';

// --- Proses Aksi Setuju / Tolak ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['aksi_review'])) {
    $id_lembar_kerja = (int)$_POST['id_lembar_kerja'];
    $aksi = $_POST['aksi_review']; // 'Disetujui' atau 'Ditolak'
    
    try {
        $stmt_update = $pdo->prepare("UPDATE lembar_kerja SET status_review = :status WHERE id_lembar_kerja = :id");
        $stmt_update->execute([':status' => $aksi, ':id' => $id_lembar_kerja]);
        
        // Log Aktivitas
        $log_stmt = $pdo->prepare("INSERT INTO log_aktivitas (id_pengguna, peran, jenis_aktivitas, objek) VALUES (:id, 'Pimpinan', 'Review Hasil', :objek)");
        $log_stmt->execute([':id' => $_SESSION['id_pengguna'], ':objek' => "Dokumen ID " . $id_lembar_kerja]);

        $pesan = "Dokumen berhasil di-$aksi!";
        $tipe_pesan = "success";
    } catch (PDOException $e) {
        $pesan = "Gagal memproses review: " . $e->getMessage();
        $tipe_pesan = "danger";
    }
}

// --- Logika Filter ---
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'Menunggu Review';

$query = "SELECT lk.*, qc.total_data, qc.total_temuan 
          FROM lembar_kerja lk 
          LEFT JOIN quality_control qc ON lk.id_lembar_kerja = qc.id_lembar_kerja 
          WHERE lk.status_qc = 'Selesai'"; // Hanya tampilkan yang sudah di-QC selesai

if (!empty($filter_status)) {
    $query .= " AND lk.status_review = :status";
    $params[':status'] = $filter_status;
}
$query .= " ORDER BY lk.tanggal_input DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params ?? []);
$dokumen_review = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Review dan Persetujuan - BPS QC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/sidebar_pimpinan.php'; ?>

    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <h5 class="mb-0 fw-bold">Review dan Persetujuan</h5>
            <div class="d-flex align-items-center">
                <i class="bi bi-bell me-3 fs-5 text-muted"></i>
                <div class="d-flex align-items-center">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['nama']) ?>&background=1a56db&color=fff" class="rounded-circle me-2" width="35">
                    <span class="fw-semibold" style="font-size: 14px;"><?= $_SESSION['nama'] ?></span>
                </div>
            </div>
        </div>

        <p class="text-muted mb-4">Periksa hasil pemeriksaan, berikan catatan, dan tentukan keputusan.</p>

        <?php if(!empty($pesan)): ?>
            <div class="alert alert-<?= $tipe_pesan ?> alert-dismissible fade show" role="alert">
                <?= $pesan ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Filter -->
        <div class="stat-card mb-4">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Status Review</label>
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="Menunggu Review" <?= ($filter_status == 'Menunggu Review') ? 'selected' : '' ?>>Menunggu Review</option>
                        <option value="Disetujui" <?= ($filter_status == 'Disetujui') ? 'selected' : '' ?>>Disetujui</option>
                        <option value="Ditolak" <?= ($filter_status == 'Ditolak') ? 'selected' : '' ?>>Ditolak</option>
                        <option value="">Semua Status</option>
                    </select>
                </div>
            </form>
        </div>

        <!-- Tabel Review -->
        <div class="stat-card">
            <h6 class="fw-bold mb-3">Daftar Dokumen</h6>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Nama Dokumen</th>
                            <th>Periode</th>
                            <th>Hasil QC</th>
                            <th>Status Review</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($dokumen_review) > 0): ?>
                            <?php $no = 1; foreach($dokumen_review as $row): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td class="fw-semibold"><?= htmlspecialchars($row['nama_dokumen']) ?></td>
                                <td><?= htmlspecialchars($row['periode']) ?></td>
                                <td>
                                    <?php if($row['total_temuan'] > 0): ?>
                                        <span class="text-danger fw-semibold"><?= $row['total_temuan'] ?> Temuan</span>
                                    <?php else: ?>
                                        <span class="text-success fw-semibold">0 Temuan (Lulus)</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                        $status = $row['status_review'];
                                        $badge = 'secondary';
                                        if($status == 'Disetujui') $badge = 'success';
                                        elseif($status == 'Ditolak') $badge = 'danger';
                                        elseif($status == 'Menunggu Review') $badge = 'warning';
                                    ?>
                                    <span class="badge bg-<?= $badge ?> bg-opacity-10 text-<?= $badge ?> px-2 py-1"><?= $status ?></span>
                                </td>
                                <td class="text-center">
                                    <?php if($row['status_review'] == 'Menunggu Review'): ?>
                                        <!-- Form untuk Setuju / Tolak -->
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin MENYETUJUI dokumen ini?');">
                                            <input type="hidden" name="id_lembar_kerja" value="<?= $row['id_lembar_kerja'] ?>">
                                            <button type="submit" name="aksi_review" value="Disetujui" class="btn btn-sm btn-success" style="font-size: 12px;"><i class="bi bi-check-lg"></i> Setujui</button>
                                        </form>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin MENOLAK dokumen ini?');">
                                            <input type="hidden" name="id_lembar_kerja" value="<?= $row['id_lembar_kerja'] ?>">
                                            <button type="submit" name="aksi_review" value="Ditolak" class="btn btn-sm btn-danger" style="font-size: 12px;"><i class="bi bi-x-lg"></i> Tolak</button>
                                        </form>
                                    <?php else: ?>
                                        <a href="hasil_pencocokan.php?id=<?= $row['id_lembar_kerja'] ?>" class="btn btn-sm btn-outline-primary" style="font-size: 12px;">Lihat Detail</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">Tidak ada dokumen dengan status ini.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>