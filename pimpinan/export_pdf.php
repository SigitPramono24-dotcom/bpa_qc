<?php
session_start();
require_once '../config/koneksi.php';
require '../vendor/autoload.php'; // Panggil library Composer

use Dompdf\Dompdf;
use Dompdf\Options;

// Proteksi Halaman
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'pimpinan') {
    header("Location: ../auth/login.php");
    exit;
}

// --- Logika Filter Periode ---
$filter_periode = isset($_GET['periode']) ? $_GET['periode'] : '';

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
$data = $stmt->fetchAll();

// ==========================================
// MEMBUAT HTML UNTUK DOMPDF
// ==========================================
$html = '
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .header h3 { margin: 0; font-size: 16px; }
        .header h4 { margin: 5px 0; font-size: 14px; }
        .header p { margin: 0; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #555; padding: 8px; text-align: left; }
        th { background-color: #1a56db; color: white; text-align: center; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .ttd { margin-top: 50px; width: 100%; }
        .ttd td { border: none; text-align: center; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h3>BADAN PUSAT STATISTIK KOTA MEDAN</h3>
        <h4>LAPORAN AKHIR QUALITY CONTROL DATA STATISTIK</h4>
        <p>Periode: ' . (!empty($filter_periode) ? $filter_periode : 'Semua Periode') . ' | Tanggal Cetak: ' . date('d F Y') . '</p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="35%">Nama Dokumen</th>
                <th width="15%">Jenis</th>
                <th width="10%">Periode</th>
                <th width="15%">Total Data</th>
                <th width="10%">Temuan</th>
                <th width="10%">Status</th>
            </tr>
        </thead>
        <tbody>';

if (count($data) > 0) {
    $no = 1;
    foreach ($data as $row) {
        $html .= '
            <tr>
                <td class="text-center">' . $no++ . '</td>
                <td>' . htmlspecialchars($row['nama_dokumen']) . '</td>
                <td>' . htmlspecialchars($row['jenis']) . '</td>
                <td class="text-center">' . htmlspecialchars($row['periode']) . '</td>
                <td class="text-right">' . number_format($row['total_data'] ?? 0, 0, ',', '.') . '</td>
                <td class="text-right" style="color: ' . (($row['total_temuan'] ?? 0) > 0 ? 'red' : 'green') . ';">
                    ' . number_format($row['total_temuan'] ?? 0, 0, ',', '.') . '
                </td>
                <td class="text-center">Disetujui</td>
            </tr>';
    }
} else {
    $html .= '<tr><td colspan="7" class="text-center">Belum ada data laporan yang disetujui.</td></tr>';
}

$html .= '
        </tbody>
    </table>

    <!-- Bagian Tanda Tangan -->
    <table class="ttd">
        <tr>
            <td width="50%">
                <p>Mengetahui,<br>Kepala BPS Kota Medan</p>
                <br><br><br>
                <p><strong>' . $_SESSION['nama'] . '</strong></p>
            </td>
            <td width="50%">
                <p>Medan, ' . date('d F Y') . '<br>Petugas QC</p>
                <br><br><br>
                <p><strong>Petugas Statistik</strong></p>
            </td>
        </tr>
    </table>
</body>
</html>';

// ==========================================
// PROSES RENDER PDF DENGAN DOMPDF
// ==========================================
// Set opsi Dompdf agar bisa load gambar/CSS eksternal jika perlu
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Output PDF ke browser (Attachment = 1 artinya otomatis download)
$filename = 'Laporan_QC_BPS_' . (!empty($filter_periode) ? $filter_periode : 'Semua') . '_' . date('Ymd') . '.pdf';
$dompdf->stream($filename, array("Attachment" => 1));
exit;
?>