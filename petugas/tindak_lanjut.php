<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'petugas') { header("Location: ../auth/login.php"); exit; }

$id_pencocokan = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$temuan = null; $pesan = ''; $tipe_pesan = '';

// Proses Simpan Tindak Lanjut
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan_tindak_lanjut'])) {
    $tindak_lanjut = trim($_POST['tindak_lanjut']);
    $status_baru = $_POST['status_temuan'];
    $id_post = (int)$_POST['id_pencocokan'];

    if (empty($tindak_lanjut)) {
        $pesan = "Catatan tindak lanjut tidak boleh kosong!"; $tipe_pesan = "danger";
    } else {
        try {
            $stmt_update = $pdo->prepare("UPDATE hasil_pencocokan SET tindak_lanjut = :tindak, status_temuan = :status, tanggal_selesai = NOW() WHERE id_pencocokan = :id");
            $stmt_update->execute([':tindak' => $tindak_lanjut, ':status' => $status_baru, ':id' => $id_post]);
            $pesan = "Tindak lanjut berhasil disimpan!"; $tipe_pesan = "success";
        } catch (PDOException $e) { $pesan = "Gagal: " . $e->getMessage(); $tipe_pesan = "danger"; }
    }
}

if ($id_pencocokan > 0) {
    $stmt = $pdo->prepare("SELECT hp.*, lk.nama_dokumen, aq.nama_aturan FROM hasil_pencocokan hp JOIN quality_control qc ON hp.id_qc = qc.id_qc JOIN lembar_kerja lk ON qc.id_lembar_kerja = lk.id_lembar_kerja LEFT JOIN aturan_qc aq ON hp.id_aturan = aq.id_aturan WHERE hp.id_pencocokan = :id");
    $stmt->execute([':id' => $id_pencocokan]);
    $temuan = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tindak Lanjut - BPS QC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/sidebar_petugas.php'; ?>
    <div class="main-content">
        <div class="topbar"><h5 class="mb-0 fw-bold">Tindak Lanjut dan Verifikasi</h5></div>
        <?php if(!empty($pesan)): ?><div class="alert alert-<?= $tipe_pesan ?>"><?= $pesan ?></div><?php endif; ?>

        <?php if($id_pencocokan == 0 || !$temuan): ?>
            <!-- TAMPILAN DAFTAR TEMUAN -->
            <p class="text-muted mb-4">Pilih temuan di bawah ini untuk melakukan tindak lanjut.</p>
            <div class="stat-card">
                <table class="table table-hover align-middle">
                    <thead class="table-light"><tr><th>ID Temuan</th><th>Dokumen</th><th>Masalah</th><th>Status</th><th>Aksi</th></tr></thead>
                    <tbody>
                        <?php 
                        $stmt_list = $pdo->query("SELECT hp.*, lk.nama_dokumen FROM hasil_pencocokan hp JOIN quality_control qc ON hp.id_qc = qc.id_qc JOIN lembar_kerja lk ON qc.id_lembar_kerja = lk.id_lembar_kerja ORDER BY hp.id_pencocokan DESC");
                        $list_temuan = $stmt_list->fetchAll();
                        foreach($list_temuan as $row): ?>
                        <tr>
                            <td>#F-<?= str_pad($row['id_pencocokan'], 3, '0', STR_PAD_LEFT) ?></td>
                            <td class="fw-semibold"><?= htmlspecialchars($row['nama_dokumen']) ?></td>
                            <td><?= htmlspecialchars($row['catatan_ketidaksesuaian']) ?></td>
                            <td>
                                <span class="badge bg-<?= ($row['status_temuan']=='Selesai')?'success':(($row['status_temuan']=='Menunggu Verifikasi')?'primary':'warning') ?> bg-opacity-10 text-<?= ($row['status_temuan']=='Selesai')?'success':(($row['status_temuan']=='Menunggu Verifikasi')?'primary':'warning') ?>">
                                    <?= $row['status_temuan'] ?>
                                </span>
                            </td>
                            <td><a href="tindak_lanjut.php?id=<?= $row['id_pencocokan'] ?>" class="btn btn-sm btn-primary">Proses</a></td>
                        </tr>
                        <?php endforeach; if(empty($list_temuan)) echo "<tr><td colspan='5' class='text-center'>Tidak ada temuan.</td></tr>"; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <!-- FORM TINDAK LANJUT -->
            <div class="row">
                <div class="col-md-7 mb-4">
                    <div class="stat-card h-100">
                        <h6 class="fw-bold mb-4">Form Tindak Lanjut</h6>
                        <form method="POST">
                            <input type="hidden" name="id_pencocokan" value="<?= $temuan['id_pencocokan'] ?>">
                            <div class="mb-3">
                                <label class="form-label">ID Temuan</label>
                                <input type="text" class="form-control bg-light" value="#F-<?= str_pad($temuan['id_pencocokan'], 3, '0', STR_PAD_LEFT) ?>" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Tindakan Perbaikan <span class="text-danger">*</span></label>
                                <textarea name="tindak_lanjut" class="form-control" rows="3" required><?= htmlspecialchars($temuan['tindak_lanjut'] ?? '') ?></textarea>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Status Temuan <span class="text-danger">*</span></label>
                                <select name="status_temuan" class="form-select" required>
                                    <option value="Belum Diperbaiki" <?= ($temuan['status_temuan'] == 'Belum Diperbaiki') ? 'selected' : '' ?>>Belum Diperbaiki</option>
                                    <option value="Menunggu Verifikasi" <?= ($temuan['status_temuan'] == 'Menunggu Verifikasi') ? 'selected' : '' ?>>Menunggu Verifikasi</option>
                                    <option value="Selesai" <?= ($temuan['status_temuan'] == 'Selesai') ? 'selected' : '' ?>>Selesai</option>
                                </select>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" name="simpan_tindak_lanjut" class="btn btn-primary w-50">Simpan</button>
                                <a href="tindak_lanjut.php" class="btn btn-outline-secondary w-50">Kembali</a>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="col-md-5 mb-4">
                    <div class="stat-card h-100 bg-light">
                        <h6 class="fw-bold mb-4">Informasi Temuan</h6>
                        <p><small class="text-muted d-block">Dokumen</small> <strong><?= htmlspecialchars($temuan['nama_dokumen']) ?></strong></p>
                        <p><small class="text-muted d-block">Aturan Dilanggar</small> <strong class="text-danger"><?= htmlspecialchars($temuan['nama_aturan'] ?? 'Umum') ?></strong></p>
                        <p><small class="text-muted d-block">Nilai Rujukan</small> <strong class="text-success"><?= htmlspecialchars($temuan['nilai_rujukan']) ?></strong></p>
                        <p><small class="text-muted d-block">Nilai Ditemukan</small> <strong class="text-danger"><?= htmlspecialchars($temuan['nilai_ditemukan']) ?></strong></p>
                        <p><small class="text-muted d-block">Catatan Sistem</small> <strong><?= htmlspecialchars($temuan['catatan_ketidaksesuaian']) ?></strong></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>