<?php
session_start();
require_once '../config/koneksi.php';
require '../vendor/autoload.php'; // Panggil library Composer

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

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
// MEMBUAT FILE EXCEL DENGAN PHPSPREADSHEET
// ==========================================
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// 1. Set Judul Laporan
$sheet->setCellValue('A1', 'BADAN PUSAT STATISTIK KOTA MEDAN');
$sheet->setCellValue('A2', 'LAPORAN AKHIR QUALITY CONTROL DATA STATISTIK');
$sheet->setCellValue('A3', 'Periode: ' . (!empty($filter_periode) ? $filter_periode : 'Semua Periode'));
$sheet->setCellValue('A4', 'Tanggal Cetak: ' . date('d F Y'));

// Merge Cell untuk Judul
$sheet->mergeCells('A1:G1');
$sheet->mergeCells('A2:G2');
$sheet->mergeCells('A3:G3');
$sheet->mergeCells('A4:G4');

// Styling Judul
$sheet->getStyle('A1:G2')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A1:G4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// 2. Set Header Tabel
$headerRow = 6;
$sheet->setCellValue('A' . $headerRow, 'No');
$sheet->setCellValue('B' . $headerRow, 'Nama Dokumen');
$sheet->setCellValue('C' . $headerRow, 'Jenis');
$sheet->setCellValue('D' . $headerRow, 'Periode');
$sheet->setCellValue('E' . $headerRow, 'Total Data');
$sheet->setCellValue('F' . $headerRow, 'Total Temuan');
$sheet->setCellValue('G' . $headerRow, 'Status');

// Styling Header Tabel
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1A56DB']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];
$sheet->getStyle('A' . $headerRow . ':G' . $headerRow)->applyFromArray($headerStyle);

// 3. Isi Data
$rowNum = $headerRow + 1;
$no = 1;
foreach ($data as $row) {
    $sheet->setCellValue('A' . $rowNum, $no++);
    $sheet->setCellValue('B' . $rowNum, $row['nama_dokumen']);
    $sheet->setCellValue('C' . $rowNum, $row['jenis']);
    $sheet->setCellValue('D' . $rowNum, $row['periode']);
    $sheet->setCellValue('E' . $rowNum, $row['total_data'] ?? 0);
    $sheet->setCellValue('F' . $rowNum, $row['total_temuan'] ?? 0);
    $sheet->setCellValue('G' . $rowNum, 'Disetujui');
    $rowNum++;
}

// Styling Body Tabel (Border)
$lastRow = $rowNum - 1;
if ($lastRow >= $headerRow + 1) {
    $sheet->getStyle('A' . ($headerRow + 1) . ':G' . $lastRow)->applyFromArray([
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ]);
}

// 4. Auto Size Kolom
foreach (range('A', 'G') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// 5. Set Header HTTP untuk Download
$filename = 'Laporan_QC_BPS_' . (!empty($filter_periode) ? $filter_periode : 'Semua') . '_' . date('Ymd') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// 6. Simpan ke Output
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>