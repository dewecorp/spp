<?php
session_start();
include '../config/config.php';

if (!isset($_SESSION['login'])) {
    header("Location: " . base_url('auth/login.php'));
    exit;
}

if (!isset($_GET['nisn']) || !isset($_GET['id_kelas'])) {
    die("Parameter tidak lengkap");
}

$nisn = $_GET['nisn'];
$id_kelas = $_GET['id_kelas'];

// Get Data Siswa
$q_siswa = mysqli_query($koneksi, "SELECT siswa.*, kelas.nama_kelas FROM siswa JOIN kelas ON siswa.id_kelas = kelas.id_kelas WHERE siswa.nisn = '$nisn'");
$d_siswa = mysqli_fetch_assoc($q_siswa);
$nama_kelas = $d_siswa['nama_kelas'];

require '../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Info Siswa
$sheet->setCellValue('A1', 'LAPORAN TAGIHAN SISWA');
$sheet->setCellValue('A2', 'Nama Siswa');
$sheet->setCellValue('B2', ': ' . $d_siswa['nama']);
$sheet->setCellValue('A3', 'Kelas');
$sheet->setCellValue('B3', ': ' . $nama_kelas);
$sheet->setCellValue('A4', 'NISN');
$sheet->setCellValue('B4', ': ' . $d_siswa['nisn']);

// Header Tabel
$row = 6;
$sheet->setCellValue('A' . $row, 'No');
$sheet->setCellValue('B' . $row, 'Jenis Pembayaran');
$sheet->setCellValue('C' . $row, 'Tipe');
$sheet->setCellValue('D' . $row, 'Nominal / Tagihan');
$sheet->setCellValue('E' . $row, 'Status Pembayaran');

// Style Header
$sheet->getStyle('A'.$row.':E'.$row)->getFont()->setBold(true);
$sheet->getColumnDimension('B')->setWidth(25);
$sheet->getColumnDimension('D')->setWidth(20);
$sheet->getColumnDimension('E')->setWidth(50);

$row++;
$no = 1;

$q_jb = mysqli_query($koneksi, "SELECT * FROM jenis_bayar ORDER BY tipe_bayar ASC, nama_pembayaran ASC");
$months = ['Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni'];

// Calculate current month index (relative to school year starting July)
$current_month_num = date('n'); // 1-12
$limit_index = ($current_month_num >= 7) ? $current_month_num - 7 : $current_month_num + 5;

while ($jb = mysqli_fetch_assoc($q_jb)) {
    // Filter by Class
    if (!empty($jb['tagihan_kelas'])) {
        $allowed_kelas = array_map('trim', explode(',', $jb['tagihan_kelas']));
        if (!in_array($nama_kelas, $allowed_kelas)) {
            continue;
        }
    }

    $sheet->setCellValue('A' . $row, $no++);
    $sheet->setCellValue('B' . $row, $jb['nama_pembayaran']);
    $sheet->setCellValue('C' . $row, $jb['tipe_bayar']);
    $sheet->setCellValue('D' . $row, $jb['nominal']);
    
    $status_text = "";
    
    if ($jb['tipe_bayar'] == 'Bulanan') {
        $q_bayar = mysqli_query($koneksi, "SELECT bulan_bayar FROM pembayaran WHERE nisn='$nisn' AND id_jenis_bayar='" . $jb['id_jenis_bayar'] . "'");
        $paid_months = [];
        while ($r = mysqli_fetch_assoc($q_bayar)) {
            if (!empty($r['bulan_bayar'])) {
                $ms = array_map('trim', explode(',', $r['bulan_bayar']));
                $paid_months = array_merge($paid_months, $ms);
            }
        }
        
        $status_parts = [];
        foreach ($months as $index => $m) {
            if ($index > $limit_index) continue; // Skip future months

            if (in_array($m, $paid_months)) {
                $status_parts[] = "[V] " . $m;
            } else {
                $status_parts[] = "[X] " . $m;
            }
        }
        $status_text = implode(", ", $status_parts);
        
    } else {
        $q_total = mysqli_query($koneksi, "SELECT SUM(jumlah_bayar) as total FROM pembayaran WHERE nisn='$nisn' AND id_jenis_bayar='" . $jb['id_jenis_bayar'] . "'");
        $d_total = mysqli_fetch_assoc($q_total);
        $total_bayar = $d_total['total'] ?? 0;
        $sisa = $jb['nominal'] - $total_bayar;
        
        if ($sisa <= 0) {
            $status_text = "LUNAS";
        } else {
            $status_text = "Kurang: Rp " . number_format($sisa, 0, ',', '.');
        }
    }
    
    $sheet->setCellValue('E' . $row, $status_text);
    $sheet->getStyle('E' . $row)->getAlignment()->setWrapText(true);
    
    $row++;
}

$writer = new Xlsx($spreadsheet);
$filename = 'Tagihan_' . str_replace(' ', '_', $d_siswa['nama']) . '_' . date('YmdHis') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="'. $filename .'"');
header('Cache-Control: max-age=0');

$writer->save('php://output');
?>