<?php
session_start();
require_once '../config/koneksi.php';

// Proteksi Halaman
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Ambil data log aktivitas beserta nama pengguna
$query = "SELECT log_aktivitas.*, pengguna.nama 
          FROM log_aktivitas 
          JOIN pengguna ON log_aktivitas.id_pengguna = pengguna.id_pengguna 
          ORDER BY log_aktivitas.waktu DESC";
$stmt = $pdo->query($query);
$log_list = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Monitoring Aktivitas - BPS QC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/sidebar_admin.php'; ?>

    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <h5 class="mb-0 fw-bold">Monitoring Aktivitas</h5>
            <div class="d-flex align-items-center">
                <i class="bi bi-bell me-3 fs-5 text-muted"></i>
                <div class="d-flex align-items-center">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['nama']) ?>&background=1a56db&color=fff" class="rounded-circle me-2" width="35">
                    <span class="fw-semibold" style="font-size: 14px;"><?= $_SESSION['nama'] ?></span>
                </div>
            </div>
        </div>

        <p class="text-muted mb-4">Lihat aktivitas pengguna dalam sistem.</p>

        <div class="stat-card">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Waktu</th>
                            <th>Pengguna</th>
                            <th>Peran</th>
                            <th>Jenis Aktivitas</th>
                            <th>Objek</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($log_list) > 0): ?>
                            <?php $no = 1; foreach($log_list as $row): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($row['waktu'])) ?></td>
                                <td class="fw-semibold"><?= htmlspecialchars($row['nama']) ?></td>
                                <td>
                                    <?php 
                                        $badge = 'secondary';
                                        if($row['peran'] == 'Admin') $badge = 'primary';
                                        elseif($row['peran'] == 'Petugas') $badge = 'info';
                                        elseif($row['peran'] == 'Pimpinan') $badge = 'warning';
                                    ?>
                                    <span class="badge bg-<?= $badge ?> bg-opacity-10 text-<?= $badge ?> px-2 py-1"><?= $row['peran'] ?></span>
                                </td>
                                <td><?= htmlspecialchars($row['jenis_aktivitas']) ?></td>
                                <td><?= htmlspecialchars($row['objek'] ?? '-') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada aktivitas yang tercatat.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>