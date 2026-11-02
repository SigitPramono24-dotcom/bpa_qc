<?php
session_start();
require_once '../config/koneksi.php';

// Proteksi Halaman
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'pimpinan') {
    header("Location: ../auth/login.php");
    exit;
}

// --- Logika Filter ---
$filter_periode = isset($_GET['periode']) ? $_GET['periode'] : '';

// Query Dasar
$query = "SELECT lk.*, qc.total_data, qc.total_temuan 
          FROM lembar_kerja lk 
          LEFT JOIN quality_control qc ON lk.id_lembar_kerja = qc.id_lembar_kerja 
          WHERE lk.status_review = 'Disetujui'";
$params = [];

if (!empty($filter_periode)) {
    $query .= " AND lk.periode = :periode";
    $params[':periode'] = $filter_periode;
}
$query .= " ORDER BY lk.tanggal_input DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$laporan_list = $stmt->fetchAll();

// Hitung Ringkasan Laporan
$total_dokumen = count($laporan_list);
$total_data_seluruh = 0;
$total_temuan_seluruh = 0;

foreach($laporan_list as $row) {
    $total_data_seluruh += $row['total_data'] ?? 0;
    $total_temuan_seluruh += $row['total_temuan'] ?? 0;
}

// Hitung persentase kesesuaian sederhana (jika ada data)
$persentase_kesesuaian = 0;
if ($total_data_seluruh > 0) {
    $persentase_kesesuaian = round((($total_data_seluruh - $total_temuan_seluruh) / $total_data_seluruh) * 100);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Akhir QC - BPS QC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* CSS Khusus untuk Mode Cetak (Print) */
        @media print {
            .sidebar, .topbar, .no-print { display: none !important; }
            .main-content { margin-left: 0 !important; padding: 0 !important; background: white !important; }
            .stat-card { box-shadow: none !important; border: none !important; padding: 0 !important; }
            .print-header { display: block !important; }
            body { background: white !important; }
        }
        .print-header { display: none; }
    </style>
</head>
<body>
    <?php include '../includes/sidebar_pimpinan.php'; ?>

    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar no-print">
            <h5 class="mb-0 fw-bold">Laporan Akhir QC</h5>
            <div class="d-flex align-items-center">
                <i class="bi bi-bell me-3 fs-5 text-muted"></i>
                <div class="d-flex align-items-center">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['nama']) ?>&background=1a56db&color=fff" class="rounded-circle me-2" width="35">
                    <span class="fw-semibold" style="font-size: 14px;"><?= $_SESSION['nama'] ?></span>
                </div>
            </div>
        </div>

        <p class="text-muted mb-4 no-print">Lihat dan unduh laporan akhir pemeriksaan QC yang telah disetujui.</p>

        <!-- Filter & Tombol Ekspor -->
        <div class="stat-card mb-4 no-print">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <form method="GET">
                        <label class="form-label">Filter Periode</label>
                        <select name="periode" class="form-select" onchange="this.form.submit()">
                            <option value="">Semua Periode</option>
                            <option value="2024" <?= ($filter_periode == '2024') ? 'selected' : '' ?>>2024</option>
                            <option value="2025" <?= ($filter_periode == '2025') ? 'selected' : '' ?>>2025</option>
                            <option value="2026" <?= ($filter_periode == '2026') ? 'selected' : '' ?>>2026</option>
                        </select>
                    </form>
                </div>
                <div class="col-md-8 text-end">
                    <!-- Tombol Ekspor Mengarah ke File PHP yang Baru Kita Buat -->
                    <a href="export_excel.php?periode=<?= $filter_periode ?>" class="btn btn-success me-2">
                        <i class="bi bi-file-earmark-excel me-1"></i> Unduh Excel
                    </a>
                    <a href="export_pdf.php?periode=<?= $filter_periode ?>" class="btn btn-danger">
                        <i class="bi bi-file-earmark-pdf me-1"></i> Unduh PDF
                    </a>
                </div>
            </div>
        </div>

        <!-- Konten Laporan -->
        <div class="stat-card">
            <!-- Judul Laporan (Hanya Muncul saat Print / PDF) -->
            <div class="print-header text-center mb-4">
                <h4 class="fw-bold mb-0">BADAN PUSAT STATISTIK KOTA MEDAN</h4>
                <h5 class="fw-bold">LAPORAN AKHIR QUALITY CONTROL DATA STATISTIK</h5>
                <p>Periode: <?= !empty($filter_periode) ? $filter_periode : 'Semua Periode' ?> | Tanggal Cetak: <?= date('d F Y') ?></p>
                <hr>
            </div>

            <h6 class="fw-bold mb-3">Ringkasan Laporan</h6>
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="p-3 bg-light rounded text-center border">
                        <h3 class="fw-bold text-primary mb-0"><?= $total_dokumen ?></h3>
                        <small class="text-muted">Total Dokumen Disetujui</small>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="p-3 bg-light rounded text-center border">
                        <h3 class="fw-bold text-info mb-0"><?= number_format($total_data_seluruh, 0, ',', '.') ?></h3>
                        <small class="text-muted">Total Data Diperiksa</small>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="p-3 bg-light rounded text-center border">
                        <h3 class="fw-bold text-success mb-0"><?= $persentase_kesesuaian ?>%</h3>
                        <small class="text-muted">Tingkat Kesesuaian</small>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="p-3 bg-light rounded text-center border">
                        <h3 class="fw-bold text-danger mb-0"><?= number_format($total_temuan_seluruh, 0, ',', '.') ?></h3>
                        <small class="text-muted">Total Temuan</small>
                    </div>
                </div>
            </div>

            <!-- Tabel Rekap Laporan -->
            <h6 class="fw-bold mb-3 mt-4">Dokumen yang Telah Disetujui</h6>
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Nama Dokumen</th>
                            <th>Jenis</th>
                            <th>Periode</th>
                            <th>Total Data</th>
                            <th>Total Temuan</th>
                            <th>Status Akhir</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($laporan_list) > 0): ?>
                            <?php $no = 1; foreach($laporan_list as $row): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td class="fw-semibold"><?= htmlspecialchars($row['nama_dokumen']) ?></td>
                                <td><?= htmlspecialchars($row['jenis']) ?></td>
                                <td><?= htmlspecialchars($row['periode']) ?></td>
                                <td><?= number_format($row['total_data'] ?? 0, 0, ',', '.') ?></td>
                                <td class="<?= ($row['total_temuan'] > 0) ? 'text-danger fw-bold' : 'text-success' ?>">
                                    <?= number_format($row['total_temuan'] ?? 0, 0, ',', '.') ?>
                                </td>
                                <td>
                                    <span class="badge bg-success">Disetujui</span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center py-4 text-muted">Belum ada laporan yang disetujui untuk periode ini.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Tanda Tangan (Hanya Muncul saat Print) -->
            <div class="row mt-5 print-header">
                <div class="col-6 text-center">
                    <p>Mengetahui,<br>Kepala BPS Kota Medan</p>
                    <br><br><br>
                    <p class="fw-bold text-decoration-underline"><?= $_SESSION['nama'] ?></p>
                </div>
                <div class="col-6 text-center">
                    <p>Medan, <?= date('d F Y') ?><br>Petugas QC</p>
                    <br><br><br>
                    <p class="fw-bold text-decoration-underline">Petugas Statistik</p>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>