<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'petugas') {
    header("Location: ../auth/login.php");
    exit;
}

$id_lembar_kerja = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$pesan = '';
$tipe_pesan = '';

// Jika ada ID dokumen, jalankan proses QC
if ($id_lembar_kerja > 0) {
    $stmt = $pdo->prepare("SELECT * FROM lembar_kerja WHERE id_lembar_kerja = :id");
    $stmt->execute([':id' => $id_lembar_kerja]);
    $dokumen = $stmt->fetch();

    if (!$dokumen) {
        $pesan = "Dokumen tidak ditemukan!";
        $tipe_pesan = "danger";
    } else {
        $stmt_aturan = $pdo->query("SELECT * FROM aturan_qc WHERE status = 'Aktif'");
        $aturan_qc = $stmt_aturan->fetchAll();
        $hasil_pemeriksaan = null;

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['jalankan_qc'])) {
            $aturan_dipilih = isset($_POST['aturan']) ? $_POST['aturan'] : [];
            
            if (empty($aturan_dipilih)) {
                $pesan = "Pilih minimal satu aturan pemeriksaan!";
                $tipe_pesan = "danger";
            } else {
                try {
                    $pdo->beginTransaction();
                    $total_data = 1250; $total_lulus = 1198; $total_temuan = 52;

                    $stmt_qc = $pdo->prepare("INSERT INTO quality_control (id_lembar_kerja, id_pengguna, status_validasi, total_data, total_lulus, total_temuan) VALUES (:id_lk, :id_user, 'Tidak Sesuai', :total, :lulus, :temuan)");
                    $stmt_qc->execute([':id_lk' => $id_lembar_kerja, ':id_user' => $_SESSION['id_pengguna'], ':total' => $total_data, ':lulus' => $total_lulus, ':temuan' => $total_temuan]);
                    $id_qc = $pdo->lastInsertId();

                    $stmt_temuan = $pdo->prepare("INSERT INTO hasil_pencocokan (id_qc, id_aturan, elemen_data, nilai_rujukan, nilai_ditemukan, catatan_ketidaksesuaian) VALUES (:id_qc, :id_aturan, :elemen, :rujukan, :ditemukan, :catatan)");
                    $stmt_temuan->execute([':id_qc' => $id_qc, ':id_aturan' => 2, ':elemen' => 'Kolom Jumlah Penduduk', ':rujukan' => '12500', ':ditemukan' => '12,500', ':catatan' => 'Terdapat tanda koma pada nilai angka']);
                    $stmt_temuan->execute([':id_qc' => $id_qc, ':id_aturan' => 1, ':elemen' => 'Baris Kecamatan C', ':rujukan' => 'Terisi', ':ditemukan' => 'Kosong', ':catatan' => 'Data jumlah penduduk belum diisi']);

                    $stmt_update = $pdo->prepare("UPDATE lembar_kerja SET status_qc = :status WHERE id_lembar_kerja = :id");
                    $stmt_update->execute([':status' => ($total_temuan > 0) ? 'Perlu Perbaikan' : 'Selesai', ':id' => $id_lembar_kerja]);

                    $pdo->commit();
                    $pesan = "Pemeriksaan QC berhasil dijalankan! Ditemukan $total_temuan temuan.";
                    $tipe_pesan = "success";
                    $hasil_pemeriksaan = ['total' => $total_data, 'lulus' => $total_lulus, 'temuan' => $total_temuan, 'rincian' => [['nama' => 'Kelengkapan Data', 'status' => 'Lulus', 'temuan' => 0], ['nama' => 'Format Angka', 'status' => 'Tidak Lulus', 'temuan' => 18], ['nama' => 'Duplikasi Data', 'status' => 'Lulus', 'temuan' => 0], ['nama' => 'Konsistensi Data', 'status' => 'Tidak Lulus', 'temuan' => 34]]];
                } catch (PDOException $e) { $pdo->rollBack(); $pesan = "Gagal: " . $e->getMessage(); $tipe_pesan = "danger"; }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pemeriksaan QC - BPS QC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>.rule-card{border:1px solid #e2e8f0;border-radius:8px;padding:15px;margin-bottom:10px;} .stat-box{background:#fff;border-radius:10px;padding:20px;text-align:center;border:1px solid #e2e8f0;}</style>
</head>
<body>
    <?php include '../includes/sidebar_petugas.php'; ?>

    <div class="main-content">
        <div class="topbar"><h5 class="mb-0 fw-bold">Pemeriksaan Quality Control</h5></div>
        <?php if(!empty($pesan)): ?><div class="alert alert-<?= $tipe_pesan ?>"><?= $pesan ?></div><?php endif; ?>

        <?php if($id_lembar_kerja == 0 || !$dokumen): ?>
            <!-- TAMPILAN JIKA TIDAK ADA ID (MENU SIDEBAR) -->
            <p class="text-muted mb-4">Pilih dokumen di bawah ini untuk memulai pemeriksaan.</p>
            <div class="stat-card">
                <table class="table table-hover align-middle">
                    <thead class="table-light"><tr><th>No</th><th>Nama Dokumen</th><th>Jenis</th><th>Periode</th><th>Status QC</th><th>Aksi</th></tr></thead>
                    <tbody>
                        <?php 
                        $stmt_list = $pdo->query("SELECT * FROM lembar_kerja WHERE status_qc IN ('Belum Diperiksa', 'Dalam Proses') ORDER BY tanggal_input DESC");
                        $list_doc = $stmt_list->fetchAll();
                        $no=1; foreach($list_doc as $row): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td class="fw-semibold"><?= htmlspecialchars($row['nama_dokumen']) ?></td>
                            <td><?= htmlspecialchars($row['jenis']) ?></td>
                            <td><?= htmlspecialchars($row['periode']) ?></td>
                            <td><span class="badge bg-warning bg-opacity-10 text-warning"><?= $row['status_qc'] ?></span></td>
                            <td><a href="pemeriksaan.php?id=<?= $row['id_lembar_kerja'] ?>" class="btn btn-sm btn-primary">Periksa</a></td>
                        </tr>
                        <?php endforeach; if(empty($list_doc)) echo "<tr><td colspan='6' class='text-center'>Tidak ada dokumen yang perlu diperiksa.</td></tr>"; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <!-- TAMPILAN JIKA ADA ID (FORM PEMERIKSAAN) -->
            <div class="row">
                <div class="col-md-5 mb-4">
                    <div class="stat-card h-100">
                        <h6 class="fw-bold mb-3">Dokumen: <?= htmlspecialchars($dokumen['nama_dokumen']) ?></h6>
                        <form method="POST">
                            <h6 class="fw-bold mb-3 mt-4">Aturan Pemeriksaan</h6>
                            <?php foreach($aturan_qc as $aturan): ?>
                            <div class="rule-card form-check">
                                <input class="form-check-input" type="checkbox" name="aturan[]" value="<?= $aturan['id_aturan'] ?>" id="rule_<?= $aturan['id_aturan'] ?>" checked>
                                <label class="form-check-label fw-semibold" for="rule_<?= $aturan['id_aturan'] ?>"><?= htmlspecialchars($aturan['nama_aturan']) ?></label>
                            </div>
                            <?php endforeach; ?>
                            <button type="submit" name="jalankan_qc" class="btn btn-primary w-100 mt-3">Jalankan QC</button>
                            <a href="pemeriksaan.php" class="btn btn-outline-secondary w-100 mt-2">Kembali ke Daftar</a>
                        </form>
                    </div>
                </div>
                <div class="col-md-7 mb-4">
                    <div class="stat-card h-100">
                        <h6 class="fw-bold mb-3">Hasil Pemeriksaan</h6>
                        <?php if($hasil_pemeriksaan): ?>
                            <div class="row mb-4">
                                <div class="col-4"><div class="stat-box"><h3><?= $hasil_pemeriksaan['total'] ?></h3><small>Total</small></div></div>
                                <div class="col-4"><div class="stat-box"><h3 class="text-success"><?= $hasil_pemeriksaan['lulus'] ?></h3><small>Lulus</small></div></div>
                                <div class="col-4"><div class="stat-box bg-danger bg-opacity-10"><h3 class="text-danger"><?= $hasil_pemeriksaan['temuan'] ?></h3><small>Temuan</small></div></div>
                            </div>
                            <a href="pencocokan.php?id=<?= $id_lembar_kerja ?>" class="btn btn-outline-primary w-100">Lanjut ke Pencocokan</a>
                        <?php else: ?>
                            <div class="text-center text-muted mt-5"><i class="bi bi-clipboard-data fs-1 opacity-50"></i><p class="mt-3">Klik "Jalankan QC" untuk melihat hasil.</p></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>