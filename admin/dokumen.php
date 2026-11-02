<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$pesan = ''; $tipe_pesan = '';

// --- PROSES TAMBAH DOKUMEN ---
if (isset($_POST['tambah_dokumen'])) {
    $nama = trim($_POST['nama_dokumen']);
    $jenis = $_POST['jenis'];
    $kegiatan = trim($_POST['kegiatan_statistik']);
    $periode = trim($_POST['periode']);
    $versi = trim($_POST['versi']);
    $id_user = $_SESSION['id_pengguna'];

    try {
        $stmt = $pdo->prepare("INSERT INTO lembar_kerja (nama_dokumen, jenis, kegiatan_statistik, periode, versi, id_pengguna, status_qc) VALUES (:nama, :jenis, :kegiatan, :periode, :versi, :id_user, 'Belum Diperiksa')");
        $stmt->execute([':nama'=>$nama, ':jenis'=>$jenis, ':kegiatan'=>$kegiatan, ':periode'=>$periode, ':versi'=>$versi, ':id_user'=>$id_user]);
        $pesan = "Dokumen berhasil ditambahkan!"; $tipe_pesan = "success";
    } catch (PDOException $e) { $pesan = "Gagal menambah: " . $e->getMessage(); $tipe_pesan = "danger"; }
}

// --- PROSES EDIT DOKUMEN ---
if (isset($_POST['edit_dokumen'])) {
    $id = $_POST['id_lembar_kerja'];
    $nama = trim($_POST['nama_dokumen']);
    $jenis = $_POST['jenis'];
    $kegiatan = trim($_POST['kegiatan_statistik']);
    $periode = trim($_POST['periode']);
    $versi = trim($_POST['versi']);
    $status_qc = $_POST['status_qc'];

    try {
        $stmt = $pdo->prepare("UPDATE lembar_kerja SET nama_dokumen=:nama, jenis=:jenis, kegiatan_statistik=:kegiatan, periode=:periode, versi=:versi, status_qc=:status WHERE id_lembar_kerja=:id");
        $stmt->execute([':nama'=>$nama, ':jenis'=>$jenis, ':kegiatan'=>$kegiatan, ':periode'=>$periode, ':versi'=>$versi, ':status'=>$status_qc, ':id'=>$id]);
        $pesan = "Dokumen berhasil diperbarui!"; $tipe_pesan = "success";
    } catch (PDOException $e) { $pesan = "Gagal memperbarui: " . $e->getMessage(); $tipe_pesan = "danger"; }
}

// --- PROSES HAPUS DOKUMEN ---
if (isset($_GET['hapus'])) {
    $id_hapus = (int)$_GET['hapus'];
    try {
        $stmt = $pdo->prepare("DELETE FROM lembar_kerja WHERE id_lembar_kerja = :id");
        $stmt->execute([':id' => $id_hapus]);
        $pesan = "Dokumen berhasil dihapus!"; $tipe_pesan = "success";
    } catch (PDOException $e) { $pesan = "Gagal menghapus: " . $e->getMessage(); $tipe_pesan = "danger"; }
}

// --- LOGIKA FILTER ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_jenis = isset($_GET['jenis']) ? $_GET['jenis'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';

$query = "SELECT lembar_kerja.*, pengguna.nama AS nama_penginput FROM lembar_kerja JOIN pengguna ON lembar_kerja.id_pengguna = pengguna.id_pengguna WHERE 1=1";
$params = [];
if (!empty($search)) { $query .= " AND lembar_kerja.nama_dokumen LIKE :search"; $params[':search'] = "%$search%"; }
if (!empty($filter_jenis)) { $query .= " AND lembar_kerja.jenis = :jenis"; $params[':jenis'] = $filter_jenis; }
if (!empty($filter_status)) { $query .= " AND lembar_kerja.status_qc = :status"; $params[':status'] = $filter_status; }
$query .= " ORDER BY lembar_kerja.tanggal_input DESC";

$stmt = $pdo->prepare($query); $stmt->execute($params); $dokumen = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen Dokumen - BPS QC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/sidebar_admin.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <h5 class="mb-0 fw-bold">Manajemen Dokumen</h5>
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
                <h5 class="fw-bold mb-1">Kelola lembar kerja dan publikasi data statistik</h5>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahDok" style="background-color: #1a56db; border-radius: 8px;">
                <i class="bi bi-plus-lg me-1"></i> Tambah Dokumen
            </button>
        </div>

        <?php if(!empty($pesan)): ?>
            <div class="alert alert-<?= $tipe_pesan ?> alert-dismissible fade show"><?= $pesan ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <div class="stat-card">
            <form method="GET" class="row mb-4 g-2">
                <div class="col-md-4"><input type="text" name="search" class="form-control" placeholder="Cari nama dokumen..." value="<?= htmlspecialchars($search) ?>"></div>
                <div class="col-md-2">
                    <select name="jenis" class="form-select">
                        <option value="">Jenis: Semua</option>
                        <option value="Lembar Kerja" <?= ($filter_jenis == 'Lembar Kerja') ? 'selected' : '' ?>>Lembar Kerja</option>
                        <option value="Publikasi" <?= ($filter_jenis == 'Publikasi') ? 'selected' : '' ?>>Publikasi</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">Status QC: Semua</option>
                        <option value="Belum Diperiksa" <?= ($filter_status == 'Belum Diperiksa') ? 'selected' : '' ?>>Belum Diperiksa</option>
                        <option value="Dalam Proses" <?= ($filter_status == 'Dalam Proses') ? 'selected' : '' ?>>Dalam Proses</option>
                        <option value="Perlu Perbaikan" <?= ($filter_status == 'Perlu Perbaikan') ? 'selected' : '' ?>>Perlu Perbaikan</option>
                        <option value="Selesai" <?= ($filter_status == 'Selesai') ? 'selected' : '' ?>>Selesai</option>
                    </select>
                </div>
                <div class="col-md-2"><button type="submit" class="btn btn-outline-primary w-100">Filter</button></div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No</th><th>Nama Dokumen</th><th>Jenis</th><th>Kegiatan</th><th>Periode</th><th>Versi</th><th>Status QC</th><th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($dokumen) > 0): ?>
                            <?php $no = 1; foreach($dokumen as $row): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td class="fw-semibold"><?= htmlspecialchars($row['nama_dokumen']) ?></td>
                                <td><?= htmlspecialchars($row['jenis']) ?></td>
                                <td><?= htmlspecialchars($row['kegiatan_statistik']) ?></td>
                                <td><?= htmlspecialchars($row['periode']) ?></td>
                                <td><?= htmlspecialchars($row['versi']) ?></td>
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
                                <td class="text-center">
                                    <!-- Tombol Lihat -->
                                    <button class="btn btn-sm btn-light text-info" data-bs-toggle="modal" data-bs-target="#modalLihatDok<?= $row['id_lembar_kerja'] ?>" title="Lihat"><i class="bi bi-eye"></i></button>
                                    <!-- Tombol Edit -->
                                    <button class="btn btn-sm btn-light text-primary" data-bs-toggle="modal" data-bs-target="#modalEditDok<?= $row['id_lembar_kerja'] ?>" title="Edit"><i class="bi bi-pencil-square"></i></button>
                                    <!-- Tombol Hapus -->
                                    <a href="dokumen.php?hapus=<?= $row['id_lembar_kerja'] ?>" class="btn btn-sm btn-light text-danger" onclick="return confirm('Yakin ingin menghapus dokumen ini? Semua data QC terkait akan ikut terhapus.');" title="Hapus"><i class="bi bi-trash"></i></a>

                                    <!-- Modal Lihat -->
                                    <div class="modal fade text-start" id="modalLihatDok<?= $row['id_lembar_kerja'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header"><h5 class="modal-title">Detail Dokumen</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                                <div class="modal-body">
                                                    <p><strong>Nama:</strong> <?= htmlspecialchars($row['nama_dokumen']) ?></p>
                                                    <p><strong>Jenis:</strong> <?= htmlspecialchars($row['jenis']) ?></p>
                                                    <p><strong>Kegiatan:</strong> <?= htmlspecialchars($row['kegiatan_statistik']) ?></p>
                                                    <p><strong>Periode:</strong> <?= htmlspecialchars($row['periode']) ?></p>
                                                    <p><strong>Versi:</strong> <?= htmlspecialchars($row['versi']) ?></p>
                                                    <p><strong>Status QC:</strong> <?= htmlspecialchars($row['status_qc']) ?></p>
                                                    <p><strong>Diinput oleh:</strong> <?= htmlspecialchars($row['nama_penginput']) ?></p>
                                                    <p><strong>Tanggal Input:</strong> <?= date('d F Y H:i', strtotime($row['tanggal_input'])) ?></p>
                                                    <?php if(!empty($row['total_data'])): ?>
                                                        <p><strong>Total Baris Data Excel:</strong> <?= $row['total_data'] ?></p>
                                                    <?php endif; ?>
                                                    <?php if(!empty($row['file_dokumen'])): ?>
                                                        <p><strong>File:</strong> <a href="../assets/uploads/<?= $row['file_dokumen'] ?>" target="_blank">Download File</a></p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Modal Edit -->
                                    <div class="modal fade text-start" id="modalEditDok<?= $row['id_lembar_kerja'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST">
                                                    <div class="modal-header"><h5 class="modal-title">Edit Dokumen</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                                    <div class="modal-body">
                                                        <input type="hidden" name="id_lembar_kerja" value="<?= $row['id_lembar_kerja'] ?>">
                                                        <div class="mb-2"><label>Nama Dokumen</label><input type="text" name="nama_dokumen" class="form-control" value="<?= htmlspecialchars($row['nama_dokumen']) ?>" required></div>
                                                        <div class="mb-2"><label>Jenis</label>
                                                            <select name="jenis" class="form-select">
                                                                <option value="Lembar Kerja" <?= ($row['jenis'] == 'Lembar Kerja') ? 'selected' : '' ?>>Lembar Kerja</option>
                                                                <option value="Publikasi" <?= ($row['jenis'] == 'Publikasi') ? 'selected' : '' ?>>Publikasi</option>
                                                            </select>
                                                        </div>
                                                        <div class="mb-2"><label>Kegiatan</label><input type="text" name="kegiatan_statistik" class="form-control" value="<?= htmlspecialchars($row['kegiatan_statistik']) ?>" required></div>
                                                        <div class="mb-2"><label>Periode</label><input type="text" name="periode" class="form-control" value="<?= htmlspecialchars($row['periode']) ?>" required></div>
                                                        <div class="mb-2"><label>Versi</label><input type="text" name="versi" class="form-control" value="<?= htmlspecialchars($row['versi']) ?>"></div>
                                                        <div class="mb-2"><label>Status QC</label>
                                                            <select name="status_qc" class="form-select">
                                                                <option value="Belum Diperiksa" <?= ($row['status_qc'] == 'Belum Diperiksa') ? 'selected' : '' ?>>Belum Diperiksa</option>
                                                                <option value="Dalam Proses" <?= ($row['status_qc'] == 'Dalam Proses') ? 'selected' : '' ?>>Dalam Proses</option>
                                                                <option value="Perlu Perbaikan" <?= ($row['status_qc'] == 'Perlu Perbaikan') ? 'selected' : '' ?>>Perlu Perbaikan</option>
                                                                <option value="Selesai" <?= ($row['status_qc'] == 'Selesai') ? 'selected' : '' ?>>Selesai</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer"><button type="submit" name="edit_dokumen" class="btn btn-primary">Simpan Perubahan</button></div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="text-center py-4 text-muted">Tidak ada dokumen ditemukan.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Dokumen -->
    <div class="modal fade" id="modalTambahDok" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header"><h5 class="modal-title">Tambah Dokumen Baru</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="mb-2"><label>Nama Dokumen</label><input type="text" name="nama_dokumen" class="form-control" required></div>
                        <div class="mb-2"><label>Jenis</label>
                            <select name="jenis" class="form-select" required>
                                <option value="Lembar Kerja">Lembar Kerja</option>
                                <option value="Publikasi">Publikasi</option>
                            </select>
                        </div>
                        <div class="mb-2"><label>Kegiatan Statistik</label><input type="text" name="kegiatan_statistik" class="form-control" required></div>
                        <div class="mb-2"><label>Periode</label><input type="text" name="periode" class="form-control" placeholder="Contoh: 2025" required></div>
                        <div class="mb-2"><label>Versi</label><input type="text" name="versi" class="form-control" value="v1.0"></div>
                    </div>
                    <div class="modal-footer"><button type="submit" name="tambah_dokumen" class="btn btn-primary">Simpan Dokumen</button></div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>