<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$pesan = '';
$tipe_pesan = '';

// ==========================================
// PROSES TAMBAH PENGGUNA
// ==========================================
if (isset($_POST['tambah_pengguna'])) {
    $nama = trim($_POST['nama']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash password

    try {
        $stmt = $pdo->prepare("INSERT INTO pengguna (nama, username, password, email, role, status) VALUES (:nama, :username, :password, :email, :role, 'Aktif')");
        $stmt->execute([':nama'=>$nama, ':username'=>$username, ':password'=>$password, ':email'=>$email, ':role'=>$role]);
        $pesan = "Pengguna baru berhasil ditambahkan!"; $tipe_pesan = "success";
    } catch (PDOException $e) {
        $pesan = "Gagal menambah pengguna: " . $e->getMessage(); $tipe_pesan = "danger";
    }
}

// ==========================================
// PROSES EDIT PENGGUNA
// ==========================================
if (isset($_POST['edit_pengguna'])) {
    $id = $_POST['id_pengguna_edit'];
    $nama = trim($_POST['nama_edit']);
    $username = trim($_POST['username_edit']);
    $email = trim($_POST['email_edit']);
    $role = $_POST['role_edit'];
    $status = $_POST['status_edit'];

    try {
        // Jika password diisi, update password juga
        if (!empty($_POST['password_edit'])) {
            $password = password_hash($_POST['password_edit'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE pengguna SET nama=:nama, username=:username, password=:password, email=:email, role=:role, status=:status WHERE id_pengguna=:id");
            $stmt->execute([':nama'=>$nama, ':username'=>$username, ':password'=>$password, ':email'=>$email, ':role'=>$role, ':status'=>$status, ':id'=>$id]);
        } else {
            $stmt = $pdo->prepare("UPDATE pengguna SET nama=:nama, username=:username, email=:email, role=:role, status=:status WHERE id_pengguna=:id");
            $stmt->execute([':nama'=>$nama, ':username'=>$username, ':email'=>$email, ':role'=>$role, ':status'=>$status, ':id'=>$id]);
        }
        $pesan = "Data pengguna berhasil diperbarui!"; $tipe_pesan = "success";
    } catch (PDOException $e) {
        $pesan = "Gagal memperbarui: " . $e->getMessage(); $tipe_pesan = "danger";
    }
}

// ==========================================
// PROSES HAPUS PENGGUNA
// ==========================================
if (isset($_GET['hapus'])) {
    $id_hapus = (int)$_GET['hapus'];
    try {
        $stmt = $pdo->prepare("DELETE FROM pengguna WHERE id_pengguna = :id");
        $stmt->execute([':id' => $id_hapus]);
        $pesan = "Pengguna berhasil dihapus!"; $tipe_pesan = "success";
    } catch (PDOException $e) {
        $pesan = "Gagal menghapus: " . $e->getMessage(); $tipe_pesan = "danger";
    }
}

// --- Logika Pencarian dan Filter ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_role = isset($_GET['role']) ? $_GET['role'] : '';

$query = "SELECT * FROM pengguna WHERE 1=1";
$params = [];
if (!empty($search)) {
    $query .= " AND (nama LIKE :search OR username LIKE :search OR email LIKE :search)";
    $params[':search'] = "%$search%";
}
if (!empty($filter_role)) {
    $query .= " AND role = :role";
    $params[':role'] = $filter_role;
}
$query .= " ORDER BY id_pengguna ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen Pengguna - BPS QC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/sidebar_admin.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <h5 class="mb-0 fw-bold">Manajemen Pengguna</h5>
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
                <h5 class="fw-bold mb-1">Daftar Pengguna</h5>
                <p class="text-muted mb-0" style="font-size: 14px;">Kelola akun pengguna sistem</p>
            </div>
            <!-- Tombol Tambah Memicu Modal -->
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah" style="background-color: #1a56db; border-radius: 8px;">
                <i class="bi bi-plus-lg me-1"></i> Tambah Pengguna
            </button>
        </div>

        <?php if(!empty($pesan)): ?>
            <div class="alert alert-<?= $tipe_pesan ?> alert-dismissible fade show"><?= $pesan ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <div class="stat-card">
            <form method="GET" class="row mb-4 g-2">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Cari nama, username, atau email..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="role" class="form-select">
                        <option value="">Semua Peran</option>
                        <option value="admin" <?= ($filter_role == 'admin') ? 'selected' : '' ?>>Admin</option>
                        <option value="petugas" <?= ($filter_role == 'petugas') ? 'selected' : '' ?>>Petugas</option>
                        <option value="pimpinan" <?= ($filter_role == 'pimpinan') ? 'selected' : '' ?>>Pimpinan</option>
                    </select>
                </div>
                <div class="col-md-2"><button type="submit" class="btn btn-outline-primary w-100">Filter</button></div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No</th><th>Nama Lengkap</th><th>Username</th><th>Email</th><th>Peran</th><th>Status</th><th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($users) > 0): ?>
                            <?php $no = 1; foreach($users as $user): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td class="fw-semibold"><?= htmlspecialchars($user['nama']) ?></td>
                                <td><?= htmlspecialchars($user['username']) ?></td>
                                <td class="text-muted"><?= htmlspecialchars($user['email']) ?></td>
                                <td>
                                    <?php $badge = ($user['role']=='admin')?'primary':(($user['role']=='petugas')?'info':'warning'); ?>
                                    <span class="badge bg-<?= $badge ?> bg-opacity-10 text-<?= $badge ?> px-3 py-2" style="font-weight: 500;"><?= ucfirst($user['role']) ?></span>
                                </td>
                                <td><span class="badge bg-<?= ($user['status']=='Aktif')?'success':'danger' ?> bg-opacity-10 text-<?= ($user['status']=='Aktif')?'success':'danger' ?> px-3 py-2"><?= $user['status'] ?></span></td>
                                <td class="text-center">
                                    <!-- Tombol Lihat (Detail) -->
                                    <button class="btn btn-sm btn-light text-info" data-bs-toggle="modal" data-bs-target="#modalLihat<?= $user['id_pengguna'] ?>" title="Lihat"><i class="bi bi-eye"></i></button>
                                    <!-- Tombol Edit -->
                                    <button class="btn btn-sm btn-light text-primary" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $user['id_pengguna'] ?>" title="Edit"><i class="bi bi-pencil-square"></i></button>
                                    <!-- Tombol Hapus -->
                                    <a href="pengguna.php?hapus=<?= $user['id_pengguna'] ?>" class="btn btn-sm btn-light text-danger" onclick="return confirm('Yakin ingin menghapus pengguna ini?');" title="Hapus"><i class="bi bi-trash"></i></a>

                                    <!-- Modal Lihat Detail -->
                                    <div class="modal fade text-start" id="modalLihat<?= $user['id_pengguna'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header"><h5 class="modal-title">Detail Pengguna</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                                <div class="modal-body">
                                                    <p><strong>Nama:</strong> <?= htmlspecialchars($user['nama']) ?></p>
                                                    <p><strong>Username:</strong> <?= htmlspecialchars($user['username']) ?></p>
                                                    <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                                                    <p><strong>Peran:</strong> <?= ucfirst($user['role']) ?></p>
                                                    <p><strong>Status:</strong> <?= $user['status'] ?></p>
                                                    <p><strong>Terdaftar:</strong> <?= date('d F Y', strtotime($user['created_at'])) ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Modal Edit -->
                                    <div class="modal fade text-start" id="modalEdit<?= $user['id_pengguna'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST">
                                                    <div class="modal-header"><h5 class="modal-title">Edit Pengguna</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                                    <div class="modal-body">
                                                        <input type="hidden" name="id_pengguna_edit" value="<?= $user['id_pengguna'] ?>">
                                                        <div class="mb-2"><label>Nama</label><input type="text" name="nama_edit" class="form-control" value="<?= htmlspecialchars($user['nama']) ?>" required></div>
                                                        <div class="mb-2"><label>Username</label><input type="text" name="username_edit" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" required></div>
                                                        <div class="mb-2"><label>Email</label><input type="email" name="email_edit" class="form-control" value="<?= htmlspecialchars($user['email']) ?>"></div>
                                                        <div class="mb-2"><label>Password Baru (Opsional)</label><input type="password" name="password_edit" class="form-control" placeholder="Biarkan kosong jika tidak diubah"></div>
                                                        <div class="mb-2"><label>Peran</label>
                                                            <select name="role_edit" class="form-select">
                                                                <option value="admin" <?= ($user['role']=='admin')?'selected':'' ?>>Admin</option>
                                                                <option value="petugas" <?= ($user['role']=='petugas')?'selected':'' ?>>Petugas</option>
                                                                <option value="pimpinan" <?= ($user['role']=='pimpinan')?'selected':'' ?>>Pimpinan</option>
                                                            </select>
                                                        </div>
                                                        <div class="mb-2"><label>Status</label>
                                                            <select name="status_edit" class="form-select">
                                                                <option value="Aktif" <?= ($user['status']=='Aktif')?'selected':'' ?>>Aktif</option>
                                                                <option value="Nonaktif" <?= ($user['status']=='Nonaktif')?'selected':'' ?>>Nonaktif</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer"><button type="submit" name="edit_pengguna" class="btn btn-primary">Simpan Perubahan</button></div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center py-4 text-muted">Data pengguna tidak ditemukan.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Pengguna -->
    <div class="modal fade" id="modalTambah" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header"><h5 class="modal-title">Tambah Pengguna Baru</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="mb-2"><label>Nama Lengkap</label><input type="text" name="nama" class="form-control" required></div>
                        <div class="mb-2"><label>Username</label><input type="text" name="username" class="form-control" required></div>
                        <div class="mb-2"><label>Email</label><input type="email" name="email" class="form-control" required></div>
                        <div class="mb-2"><label>Password</label><input type="password" name="password" class="form-control" required></div>
                        <div class="mb-2"><label>Peran</label>
                            <select name="role" class="form-select" required>
                                <option value="">Pilih Peran</option>
                                <option value="admin">Admin</option>
                                <option value="petugas">Petugas</option>
                                <option value="pimpinan">Pimpinan</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="submit" name="tambah_pengguna" class="btn btn-primary">Simpan Pengguna</button></div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>