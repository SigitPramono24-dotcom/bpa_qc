<?php
session_start();
require_once '../config/koneksi.php';

// Panggil Autoload Composer untuk PhpSpreadsheet
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// Proteksi Halaman
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'petugas') {
    header("Location: ../auth/login.php");
    exit;
}

$pesan = '';
$tipe_pesan = '';

// --- Logika Proses Unggah File & Simpan Data ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_dokumen = trim($_POST['nama_dokumen']);
    $jenis = $_POST['jenis'];
    $kegiatan = trim($_POST['kegiatan_statistik']);
    $periode = trim($_POST['periode']);
    $versi = trim($_POST['versi']);
    $id_pengguna = $_SESSION['id_pengguna'];

    // Validasi input wajib
    if (empty($nama_dokumen) || empty($kegiatan) || empty($periode)) {
        $pesan = "Harap lengkapi semua kolom bertanda bintang (*).";
        $tipe_pesan = "danger";
    } else {
        // Proses Upload File
        $file_name = $_FILES['file_dokumen']['name'];
        $file_size = $_FILES['file_dokumen']['size'];
        $file_tmp  = $_FILES['file_dokumen']['tmp_name'];
        $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        $allowed_ext = ['pdf', 'xls', 'xlsx', 'csv'];
        
        // Jika user mengunggah file
        if (!empty($file_name)) {
            if (!in_array($file_ext, $allowed_ext)) {
                $pesan = "Format file tidak didukung. Gunakan PDF, XLS, XLSX, atau CSV.";
                $tipe_pesan = "danger";
            } elseif ($file_size > 10 * 1024 * 1024) { // Max 10MB
                $pesan = "Ukuran file terlalu besar. Maksimal 10MB.";
                $tipe_pesan = "danger";
            } else {
                // Generate nama file unik agar tidak bentrok
                $new_file_name = uniqid('doc_') . '.' . $file_ext;
                $upload_dir = '../assets/uploads/';
                
                // Buat folder jika belum ada
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                if (move_uploaded_file($file_tmp, $upload_dir . $new_file_name)) {
                    
                    // ========================================================
                    // LOGIKA BARU: BACA FILE EXCEL UNTUK MENDAPATKAN JUMLAH BARIS
                    // ========================================================
                    $total_data_excel = 0;
                    if (in_array($file_ext, ['xls', 'xlsx'])) {
                        try {
                            $spreadsheet = IOFactory::load($upload_dir . $new_file_name);
                            $worksheet = $spreadsheet->getActiveSheet();
                            
                            // Menghitung baris tertinggi yang terisi
                            $highestRow = $worksheet->getHighestRow();
                            
                            // Asumsi baris 1 adalah Header, jadi jumlah data = highestRow - 1
                            $total_data_excel = ($highestRow > 1) ? ($highestRow - 1) : 0;
                            
                        } catch (Exception $e) {
                            // Jika gagal membaca (misal file corrupt), biarkan 0
                            $total_data_excel = 0; 
                        }
                    }
                    // ========================================================

                    // Insert ke Database menggunakan PDO
                    try {
                        // PERHATIKAN: Ada tambahan kolom :total_data di query
                        $query = "INSERT INTO lembar_kerja (nama_dokumen, jenis, kegiatan_statistik, periode, versi, file_dokumen, id_pengguna, status_qc, total_data) 
                                  VALUES (:nama, :jenis, :kegiatan, :periode, :versi, :file, :id_user, 'Belum Diperiksa', :total_data)";
                        $stmt = $pdo->prepare($query);
                        $stmt->execute([
                            ':nama' => $nama_dokumen,
                            ':jenis' => $jenis,
                            ':kegiatan' => $kegiatan,
                            ':periode' => $periode,
                            ':versi' => $versi,
                            ':file' => $new_file_name,
                            ':id_user' => $id_pengguna,
                            ':total_data' => $total_data_excel // Menyimpan hasil hitung Excel
                        ]);
                        
                        // Catat log aktivitas
                        $log_stmt = $pdo->prepare("INSERT INTO log_aktivitas (id_pengguna, peran, jenis_aktivitas, objek) VALUES (:id, 'Petugas', 'Unggah Dokumen', :objek)");
                        $log_stmt->execute([':id' => $id_pengguna, ':objek' => $nama_dokumen]);

                        $pesan = "Dokumen berhasil diunggah! Sistem mendeteksi <b>$total_data_excel baris data</b> di dalam file.";
                        $tipe_pesan = "success";
                    } catch (PDOException $e) {
                        $pesan = "Gagal menyimpan ke database: " . $e->getMessage();
                        $tipe_pesan = "danger";
                    }
                } else {
                    $pesan = "Gagal mengunggah file ke server.";
                    $tipe_pesan = "danger";
                }
            }
        } else {
            $pesan = "Harap pilih file dokumen yang akan diunggah.";
            $tipe_pesan = "danger";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Unggah Data - BPS QC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Custom style untuk Dropzone */
        .dropzone {
            border: 2px dashed #ced4da;
            border-radius: 10px;
            padding: 40px 20px;
            text-align: center;
            background-color: #f8f9fa;
            cursor: pointer;
            transition: 0.3s;
            position: relative;
        }
        .dropzone:hover, .dropzone.dragover {
            border-color: #1a56db;
            background-color: #f0f4ff;
        }
        .dropzone input[type="file"] {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0; left: 0;
            opacity: 0;
            cursor: pointer;
        }
        .stepper {
            display: flex;
            flex-direction: column;
            gap: 20px;
            font-size: 13px;
            color: #6c757d;
        }
        .step-item {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .step-circle {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        .step-item.active .step-circle {
            background: #1a56db;
            color: white;
        }
        .step-item.active {
            color: #1a56db;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar_petugas.php'; ?>

    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <h5 class="mb-0 fw-bold">Unggah dan Impor Data</h5>
            <div class="d-flex align-items-center">
                <i class="bi bi-bell me-3 fs-5 text-muted"></i>
                <div class="d-flex align-items-center">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['nama']) ?>&background=1a56db&color=fff" class="rounded-circle me-2" width="35">
                    <span class="fw-semibold" style="font-size: 14px;"><?= $_SESSION['nama'] ?></span>
                </div>
            </div>
        </div>

        <p class="text-muted mb-4">Unggah file lembar kerja atau publikasi dan lengkapi metadatanya.</p>

        <!-- Alert Pesan -->
        <?php if(!empty($pesan)): ?>
            <div class="alert alert-<?= $tipe_pesan ?> alert-dismissible fade show" role="alert">
                <?= $pesan ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="row">
                <!-- Kolom Kiri: Area Upload & Stepper -->
                <div class="col-md-7 mb-4">
                    <div class="stat-card h-100">
                        <h6 class="fw-bold mb-3">File Dokumen</h6>
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="dropzone" id="dropzone">
                                    <input type="file" name="file_dokumen" id="fileInput" accept=".pdf,.xls,.xlsx,.csv">
                                    <i class="bi bi-cloud-arrow-up text-primary" style="font-size: 40px;"></i>
                                    <p class="mb-1 mt-2 fw-semibold">Drag & drop file di sini atau klik untuk memilih file</p>
                                    <small class="text-muted">Format: .pdf, .xls, .xlsx, .csv (Maks. 10MB)</small>
                                    <div id="fileNameDisplay" class="mt-2 fw-bold text-primary"></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="stepper mt-2">
                                    <div class="step-item active">
                                        <div class="step-circle">1</div> Unggah File
                                    </div>
                                    <div class="step-item">
                                        <div class="step-circle">2</div> Pemetakan Kolom
                                    </div>
                                    <div class="step-item">
                                        <div class="step-circle">3</div> Pratinjau Data
                                    </div>
                                    <div class="step-item">
                                        <div class="step-circle">4</div> Impor Data
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kolom Kanan: Form Metadata -->
                <div class="col-md-5 mb-4">
                    <div class="stat-card h-100">
                        <h6 class="fw-bold mb-3">Metadata Dokumen</h6>
                        
                        <div class="mb-3">
                            <label class="form-label">Nama Dokumen <span class="text-danger">*</span></label>
                            <input type="text" name="nama_dokumen" class="form-control" placeholder="Contoh: Lembar Kerja Indikator A" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Jenis Dokumen <span class="text-danger">*</span></label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="jenis" id="jenis1" value="Lembar Kerja" checked>
                                    <label class="form-check-label" for="jenis1">Lembar Kerja</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="jenis" id="jenis2" value="Publikasi">
                                    <label class="form-check-label" for="jenis2">Publikasi</label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Kegiatan Statistik <span class="text-danger">*</span></label>
                            <select name="kegiatan_statistik" class="form-select" required>
                                <option value="">Pilih kegiatan statistik</option>
                                <option value="Penduduk">Penduduk</option>
                                <option value="Ketenagakerjaan">Ketenagakerjaan</option>
                                <option value="Pendidikan">Pendidikan</option>
                                <option value="Kesehatan">Kesehatan</option>
                                <option value="Ekonomi">Ekonomi</option>
                            </select>
                        </div>

                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label">Periode <span class="text-danger">*</span></label>
                                <select name="periode" class="form-select" required>
                                    <option value="2024">2024</option>
                                    <option value="2025" selected>2025</option>
                                    <option value="2026">2026</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Versi</label>
                                <input type="text" name="versi" class="form-control" value="v1.0">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Keterangan</label>
                            <textarea name="keterangan" class="form-control" rows="2" placeholder="Catatan tambahan (opsional)"></textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary w-100" style="background-color: #1a56db;">
                                <i class="bi bi-upload me-1"></i> Unggah & Simpan
                            </button>
                            <button type="reset" class="btn btn-outline-secondary w-50">Batal</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script untuk menampilkan nama file saat dipilih
        const fileInput = document.getElementById('fileInput');
        const fileNameDisplay = document.getElementById('fileNameDisplay');
        const dropzone = document.getElementById('dropzone');

        fileInput.addEventListener('change', function() {
            if (this.files && this.files.length > 0) {
                fileNameDisplay.textContent = "File terpilih: " + this.files[0].name;
                dropzone.style.borderColor = '#10b981'; // Ubah border jadi hijau
            }
        });

        // Efek visual saat file di-drag
        ['dragenter', 'dragover'].forEach(eventName => {
            dropzone.addEventListener(eventName, (e) => {
                e.preventDefault();
                dropzone.classList.add('dragover');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, (e) => {
                e.preventDefault();
                dropzone.classList.remove('dragover');
            }, false);
        });
    </script>
</body>
</html>