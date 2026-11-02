<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$pesan = ''; $tipe_pesan = '';

// --- PROSES TAMBAH ATURAN ---
if (isset($_POST['tambah_aturan'])) {
    $nama = trim($_POST['nama_aturan']);
    $jenis = trim($_POST['jenis_aturan']);
    $deskripsi = trim($_POST['deskripsi']);
    $status = $_POST['status'];

    try {
        $stmt = $pdo->prepare("INSERT INTO aturan_qc (nama_aturan, jenis_aturan, deskripsi, status) VALUES (:nama, :jenis, :deskripsi, :status)");
        $stmt->execute([':nama'=>$nama, ':jenis'=>$jenis, ':deskripsi'=>$deskripsi, ':status'=>$status]);
        $pesan = "Aturan QC berhasil ditambahkan!"; $tipe_pesan = "success";
    } catch (PDOException $e) { $pesan = "Gagal: " . $e->getMessage(); $tipe_pesan = "danger"; }
}

// --- PROSES EDIT ATURAN ---
if (isset($_POST['edit_aturan'])) {
    $id = $_POST['id_aturan'];
    $nama = trim($_POST['nama_aturan']);
    $jenis = trim($_POST['jenis_aturan']);
    $deskripsi = trim($_POST['deskripsi']);
    $status = $_POST['status'];

    try {
        $stmt = $pdo->prepare("UPDATE aturan_qc SET nama_aturan=:nama, jenis_aturan=:jenis, deskripsi=:deskripsi, status=:status WHERE id_aturan=:id");
        $stmt->execute([':nama'=>$nama, ':jenis'=>$jenis, ':deskripsi'=>$deskripsi, ':status'=>$status, ':id'=>$id]);
        $pesan = "Aturan QC berhasil diperbarui!"; $tipe_pesan = "success";
    } catch (PDOException $e) { $pesan = "Gagal: " . $e->getMessage(); $tipe_pesan = "danger"; }
}

// --- PROSES HAPUS ATURAN ---
if (isset($_GET['hapus'])) {
    $id_hapus = (int)$_GET['hapus'];
    try {
        $stmt = $pdo->prepare("DELETE FROM aturan_qc WHERE id_aturan = :id");
        $stmt->execute([':id' => $id_hapus]);
        $pesan = "Aturan QC berhasil dihapus!"; $tipe_pesan = "success";
    } catch (PDOException $e) { $pesan = "Gagal menghapus: " . $e->getMessage(); $tipe_pesan = "danger"; }
}

// Ambil data aturan QC
$stmt = $pdo->query("SELECT * FROM aturan_qc ORDER BY id_aturan ASC");
$aturan = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pengaturan Aturan QC - BPS QC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/sidebar_admin.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <h5 class="mb-0 fw-bold">Pengaturan Aturan QC</h5>
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
                <h5 class="fw-bold mb-1">Kelola aturan pemeriksaan data</h5>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahAturan" style="background-color: #1a56db; border-radius: 8px;">
                <i class="bi bi-plus-lg me-1"></i> Tambah Aturan
            </button>
        </div>

        <?php if(!empty($pesan)): ?>
            <div class="alert alert-<?= $tipe_pesan ?> alert-dismissible fade show"><?= $pesan ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <div class="stat-card">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No</th><th>Nama Aturan</th><th>Jenis</th><th>Deskripsi</th><th>Status</th><th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($aturan) > 0): ?>
                            <?php $no = 1; foreach($aturan as $row): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td class="fw-semibold"><?= htmlspecialchars($row['nama_aturan']) ?></td>
                                <td><?= htmlspecialchars($row['jenis_aturan']) ?></td>
                                <td class="text-muted" style="font-size: 14px;"><?= htmlspecialchars($row['deskripsi']) ?></td>
                                <td>
                                    <span class="badge bg-<?= ($row['status'] == 'Aktif') ? 'success' : 'danger' ?> bg-opacity-10 text-<?= ($row['status'] == 'Aktif') ? 'success' : 'danger' ?> px-3 py-2"><?= $row['status'] ?></span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-light text-info" data-bs-toggle="modal" data-bs-target="#modalLihatAturan<?= $row['id_aturan'] ?>" title="Lihat"><i class="bi bi-eye"></i></button>
                                    <button class="btn btn-sm btn-light text-primary" data-bs-toggle="modal" data-bs-target="#modalEditAturan<?= $row['id_aturan'] ?>" title="Edit"><i class="bi bi-pencil-square"></i></button>
                                    <a href="aturan_qc.php?hapus=<?= $row['id_aturan'] ?>" class="btn btn-sm btn-light text-danger" onclick="return confirm('Yakin ingin menghapus aturan ini?');" title="Hapus"><i class="bi bi-trash"></i></a>

                                    <!-- Modal Lihat -->
                                    <div class="modal fade text-start" id="modalLihatAturan<?= $row['id_aturan'] ?>" tabindex="-1">
                                        <div class="modal-dialog"><div class="modal-content">
                                            <div class="modal-header"><h5 class="modal-title">Detail Aturan QC</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                            <div class="modal-body">
                                                <p><strong>Nama:</strong> <?= htmlspecialchars($row['nama_aturan']) ?></p>
                                                <p><strong>Jenis:</strong> <?= htmlspecialchars($row['jenis_aturan']) ?></p>
                                                <p><strong>Deskripsi:</strong> <?= htmlspecialchars($row['deskripsi']) ?></p>
                                                <p><strong>Status:</strong> <?= $row['status'] ?></p>
                                            </div>
                                        </div></div>
                                    </div>

                                    <!-- Modal Edit -->
                                    <div class="modal fade text-start" id="modalEditAturan<?= $row['id_aturan'] ?>" tabindex="-1">
                                        <div class="modal-dialog"><div class="modal-content">
                                            <form method="POST">
                                                <div class="modal-header"><h5 class="modal-title">Edit Aturan QC</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="id_aturan" value="<?= $row['id_aturan'] ?>">
                                                    <div class="mb-2"><label>Nama Aturan</label><input type="text" name="nama_aturan" class="form-control" value="<?= htmlspecialchars($row['nama_aturan']) ?>" required></div>
                                                    <div class="mb-2"><label>Jenis Aturan</label><input type="text" name="jenis_aturan" class="form-control" value="<?= htmlspecialchars($row['jenis_aturan']) ?>" required></div>
                                                    <div class="mb-2"><label>Deskripsi</label><textarea name="deskripsi" class="form-control" rows="3"><?= htmlspecialchars($row['deskripsi']) ?></textarea></div>
                                                    <div class="mb-2"><label>Status</label>
                                                        <select name="status" class="form-select">
                                                            <option value="Aktif" <?= ($row['status'] == 'Aktif') ? 'selected' : '' ?>>Aktif</option>
                                                            <option value="Nonaktif" <?= ($row['status'] == 'Nonaktif') ? 'selected' : '' ?>>Nonaktif</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="modal-footer"><button type="submit" name="edit_aturan" class="btn btn-primary">Simpan Perubahan</button></div>
                                            </form>
                                        </div></div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada aturan QC.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Aturan -->
    <div class="modal fade" id="modalTambahAturan" tabindex="-1">
        <div class="modal-dialog"><div class="modal-content">
            <form method="POST">
                <div class="modal-header"><h5 class="modal-title">Tambah Aturan QC Baru</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-2"><label>Nama Aturan</label><input type="text" name="nama_aturan" class="form-control" required></div>
                    <div class="mb-2"><label>Jenis Aturan</label><input type="text" name="jenis_aturan" class="form-control" placeholder="Contoh: Validasi Kolom" required></div>
                    <div class="mb-2"><label>Deskripsi</label><textarea name="deskripsi" class="form-control" rows="3"></textarea></div>
                    <div class="mb-2"><label>Status</label>
                        <select name="status" class="form-select">
                            <option value="Aktif">Aktif</option>
                            <option value="Nonaktif">Nonaktif</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer"><button type="submit" name="tambah_aturan" class="btn btn-primary">Simpan Aturan</button></div>
            </form>
        </div></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>