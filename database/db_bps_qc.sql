create database db_bps_qc;

use db_bps_qc;

-- 1. Tabel Pengguna (Admin, Petugas, Pimpinan)
CREATE TABLE `pengguna` (
  `id_pengguna` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` enum('admin','petugas','pimpinan') NOT NULL,
  `status` enum('Aktif','Nonaktif') DEFAULT 'Aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_pengguna`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Tabel Lembar Kerja / Dokumen Publikasi
CREATE TABLE `lembar_kerja` (
  `id_lembar_kerja` int(11) NOT NULL AUTO_INCREMENT,
  `nama_dokumen` varchar(255) NOT NULL,
  `jenis` enum('Lembar Kerja','Publikasi') NOT NULL,
  `kegiatan_statistik` varchar(100) NOT NULL,
  `periode` varchar(20) NOT NULL,
  `versi` varchar(10) DEFAULT 'v1.0',
  `file_dokumen` varchar(255) DEFAULT NULL,
  `id_pengguna` int(11) NOT NULL,
  `status_qc` enum('Belum Diperiksa','Dalam Proses','Perlu Perbaikan','Selesai') DEFAULT 'Belum Diperiksa',
  `tanggal_input` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_lembar_kerja`),
  KEY `id_pengguna` (`id_pengguna`),
  CONSTRAINT `lembar_kerja_ibfk_1` FOREIGN KEY (`id_pengguna`) REFERENCES `pengguna` (`id_pengguna`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Tabel Aturan QC (Pengaturan Pemeriksaan)
CREATE TABLE `aturan_qc` (
  `id_aturan` int(11) NOT NULL AUTO_INCREMENT,
  `nama_aturan` varchar(100) NOT NULL,
  `jenis_aturan` varchar(50) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `status` enum('Aktif','Nonaktif') DEFAULT 'Aktif',
  PRIMARY KEY (`id_aturan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Tabel Proses Quality Control
CREATE TABLE `quality_control` (
  `id_qc` int(11) NOT NULL AUTO_INCREMENT,
  `id_lembar_kerja` int(11) NOT NULL,
  `id_pengguna` int(11) NOT NULL,
  `tanggal_periksa` datetime DEFAULT current_timestamp(),
  `status_validasi` enum('Sesuai','Tidak Sesuai') NOT NULL,
  `total_data` int(11) DEFAULT 0,
  `total_lulus` int(11) DEFAULT 0,
  `total_temuan` int(11) DEFAULT 0,
  PRIMARY KEY (`id_qc`),
  KEY `id_lembar_kerja` (`id_lembar_kerja`),
  KEY `id_pengguna` (`id_pengguna`),
  CONSTRAINT `qc_ibfk_1` FOREIGN KEY (`id_lembar_kerja`) REFERENCES `lembar_kerja` (`id_lembar_kerja`) ON DELETE CASCADE,
  CONSTRAINT `qc_ibfk_2` FOREIGN KEY (`id_pengguna`) REFERENCES `pengguna` (`id_pengguna`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Tabel Hasil Pencocokan & Temuan
CREATE TABLE `hasil_pencocokan` (
  `id_pencocokan` int(11) NOT NULL AUTO_INCREMENT,
  `id_qc` int(11) NOT NULL,
  `id_aturan` int(11) DEFAULT NULL,
  `elemen_data` varchar(100) NOT NULL,
  `nilai_rujukan` varchar(100) DEFAULT NULL,
  `nilai_ditemukan` varchar(100) DEFAULT NULL,
  `catatan_ketidaksesuaian` text DEFAULT NULL,
  `status_temuan` enum('Belum Diperbaiki','Menunggu Verifikasi','Selesai') DEFAULT 'Belum Diperbaiki',
  `tindak_lanjut` text DEFAULT NULL,
  `tanggal_selesai` datetime DEFAULT NULL,
  PRIMARY KEY (`id_pencocokan`),
  KEY `id_qc` (`id_qc`),
  CONSTRAINT `pencocokan_ibfk_1` FOREIGN KEY (`id_qc`) REFERENCES `quality_control` (`id_qc`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Tabel Log Aktivitas (Untuk halaman Monitoring Admin)
CREATE TABLE `log_aktivitas` (
  `id_log` int(11) NOT NULL AUTO_INCREMENT,
  `waktu` datetime DEFAULT current_timestamp(),
  `id_pengguna` int(11) NOT NULL,
  `peran` varchar(20) NOT NULL,
  `jenis_aktivitas` varchar(100) NOT NULL,
  `objek` varchar(100) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  PRIMARY KEY (`id_log`),
  KEY `id_pengguna` (`id_pengguna`),
  CONSTRAINT `log_ibfk_1` FOREIGN KEY (`id_pengguna`) REFERENCES `pengguna` (`id_pengguna`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- INSERT DATA DUMMY (Untuk Simulasi Awal)
-- Password asli untuk semua akun di bawah ini adalah: admin123
-- ==========================================

-- Insert Pengguna
INSERT INTO `pengguna` (`nama`, `username`, `password`, `email`, `role`, `status`) VALUES
('Admin Utama', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@bps.go.id', 'admin', 'Aktif'),
('Budi Santoso', 'budi', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'budi@bps.go.id', 'petugas', 'Aktif'),
('Siti Rahmawati', 'siti', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'siti@bps.go.id', 'petugas', 'Aktif'),
('Andi Pratama', 'andi', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'andi@bps.go.id', 'petugas', 'Aktif'),
('Rina Sari', 'rina', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'rina@bps.go.id', 'pimpinan', 'Aktif'),
('Dedi Kurniawan', 'dedi', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'dedi@bps.go.id', 'petugas', 'Nonaktif');

-- Insert Aturan QC (Sesuai desain Pengaturan Aturan QC)
INSERT INTO `aturan_qc` (`nama_aturan`, `jenis_aturan`, `deskripsi`, `status`) VALUES
('Kelengkapan Data', 'Validasi Kolom', 'Memastikan semua kolom wajib terisi', 'Aktif'),
('Format Angka', 'Format Data', 'Memastikan nilai sesuai format numerik', 'Aktif'),
('Duplikasi Data', 'Keunikan Data', 'Mendeteksi baris data yang sama', 'Aktif'),
('Rentang Nilai', 'Validasi Nilai', 'Memastikan nilai dalam rentang yang diizinkan', 'Aktif'),
('Konsistensi Data', 'Pencocokan', 'Membandingkan nilai antar dokumen', 'Nonaktif');

-- Insert Lembar Kerja (Sesuai desain Manajemen Dokumen)
INSERT INTO `lembar_kerja` (`nama_dokumen`, `jenis`, `kegiatan_statistik`, `periode`, `versi`, `id_pengguna`, `status_qc`) VALUES
('Lembar Kerja Indikator A', 'Lembar Kerja', 'Penduduk', '2025', 'v1.0', 2, 'Belum Diperiksa'),
('Publikasi A', 'Publikasi', 'Pendapatan Regional', '2025', 'v1.0', 3, 'Selesai'),
('Lembar Kerja Indikator B', 'Lembar Kerja', 'Ketenagakerjaan', '2024', 'v1.0', 4, 'Perlu Perbaikan'),
('Publikasi Indikator C', 'Publikasi', 'Pendidikan', '2024', 'v1.0', 2, 'Selesai'),
('Lembar Kerja Indikator D', 'Lembar Kerja', 'Kesehatan', '2025', 'v1.0', 3, 'Dalam Proses');

-- Insert Log Aktivitas (Sesuai desain Monitoring Aktivitas)
INSERT INTO `log_aktivitas` (`id_pengguna`, `peran`, `jenis_aktivitas`, `objek`, `keterangan`) VALUES
(2, 'Petugas', 'Unggah Dokumen', 'Lembar Kerja A', 'File berhasil diunggah'),
(3, 'Petugas', 'Jalankan QC', 'Publikasi B', 'QC selesai, 3 temuan'),
(1, 'Admin', 'Tambah Pengguna', '', 'Pengguna baru ditambahkan'),
(5, 'Pimpinan', 'Review Hasil', 'Publikasi A', 'Disetujui dengan catatan'),
(6, 'Petugas', 'Pencocokan Data', 'LK A vs PUB A', '2 selisih ditemukan');