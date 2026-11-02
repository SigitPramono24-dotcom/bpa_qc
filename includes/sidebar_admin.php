<?php $page = basename($_SERVER['PHP_SELF']); ?>
<div class="sidebar">
    <div class="logo-area">
        <img src="../assets/img/logo-bps.png" alt="Logo BPS">
        <div><h5>BPS Kota Medan</h5><span>Sistem QC Statistik</span></div>
    </div>
    <div class="menu-label">MENU ADMIN</div>
    <ul class="nav flex-column">
        <li class="nav-item"><a class="nav-link <?= ($page == 'index.php') ? 'active' : '' ?>" href="index.php"><i class="bi bi-grid-1x2"></i> Dashboard Admin</a></li>
        <li class="nav-item"><a class="nav-link <?= ($page == 'pengguna.php') ? 'active' : '' ?>" href="pengguna.php"><i class="bi bi-people"></i> Manajemen Pengguna</a></li>
        <li class="nav-item"><a class="nav-link <?= ($page == 'dokumen.php') ? 'active' : '' ?>" href="dokumen.php"><i class="bi bi-file-earmark-text"></i> Manajemen Dokumen</a></li>
        <li class="nav-item"><a class="nav-link <?= ($page == 'aturan_qc.php') ? 'active' : '' ?>" href="aturan_qc.php"><i class="bi bi-gear"></i> Pengaturan Aturan QC</a></li>
        <li class="nav-item"><a class="nav-link <?= ($page == 'monitoring.php') ? 'active' : '' ?>" href="monitoring.php"><i class="bi bi-activity"></i> Monitoring Aktivitas</a></li>
        <li class="nav-item"><a class="nav-link <?= ($page == 'laporan.php') ? 'active' : '' ?>" href="laporan.php"><i class="bi bi-file-earmark-bar-graph"></i> Laporan Sistem</a></li>
        <li class="nav-item"><a class="nav-link" href="../profil/index.php"><i class="bi bi-person-circle"></i> Profil Saya</a></li>
    </ul>
    <div class="mt-auto position-absolute bottom-0 w-100 p-3">
        <a href="../auth/logout.php" class="btn btn-outline-light w-100" style="font-size: 14px;"><i class="bi bi-box-arrow-right me-2"></i> Logout</a>
    </div>
</div>
<script>
document.addEventListener("DOMContentLoaded", function() {
    function cekNotifikasi() {
        fetch('../config/api_notifikasi.php').then(r => r.json()).then(data => {
            let badge = document.getElementById('notif-badge');
            if (badge) { badge.style.display = data.count > 0 ? 'block' : 'none'; badge.innerText = data.count; }
            let list = document.getElementById('notif-list');
            if (list) { list.innerHTML = data.items.length > 0 ? data.items.map(i => `<li><a class="dropdown-item" href="#"><small class="text-muted d-block">${i.nama}</small></a></li>`).join('') : '<li><a class="dropdown-item text-muted text-center" href="#">Tidak ada notifikasi</a></li>'; }
        });
    }
    cekNotifikasi(); setInterval(cekNotifikasi, 5000);
});
</script>