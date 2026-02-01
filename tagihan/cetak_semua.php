<?php
session_start();
include '../config/config.php';

if (!isset($_SESSION['login'])) {
    header("Location: " . base_url('auth/login.php'));
    exit;
}

if (!isset($_GET['id_kelas'])) {
    die("Parameter tidak lengkap");
}

$id_kelas = $_GET['id_kelas'];

// Get Data Kelas
$q_kelas = mysqli_query($koneksi, "SELECT nama_kelas FROM kelas WHERE id_kelas = '$id_kelas'");
$d_kelas = mysqli_fetch_assoc($q_kelas);
$nama_kelas = $d_kelas['nama_kelas'];

// Get Data Siswa
$q_siswa = mysqli_query($koneksi, "SELECT * FROM siswa WHERE id_kelas = '$id_kelas' ORDER BY nama ASC");

// Get Jenis Bayar
$jb_data = [];
$q_jb = mysqli_query($koneksi, "SELECT * FROM jenis_bayar ORDER BY tipe_bayar ASC, nama_pembayaran ASC");
while ($row = mysqli_fetch_assoc($q_jb)) {
    $jb_data[] = $row;
}

$months = ['Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni'];

// Get School Settings
$q_setting = mysqli_query($koneksi, "SELECT * FROM pengaturan WHERE id_pengaturan = 1");
$d_setting = mysqli_fetch_assoc($q_setting);
$nama_sekolah = $d_setting['nama_sekolah'] ?? 'SMK NEGERI 1 CONTOH';
$nama_bendahara = $d_setting['nama_bendahara'] ?? 'Bendahara Sekolah';
$tahun_ajaran = $d_setting['tahun_ajaran'] ?? '';

// Calculate current month index (relative to school year starting July)
$current_month_num = date('n'); // 1-12
$limit_index = ($current_month_num >= 7) ? $current_month_num - 7 : $current_month_num + 5;

// Date strings
$tgl = date('d');
$bulan_indo = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April', '05' => 'Mei', '06' => 'Juni',
    '07' => 'Juli', '08' => 'Agustus', '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];
$bln = $bulan_indo[date('m')];
$thn = date('Y');
$tanggal_str = $tgl . ' ' . $bln . ' ' . $thn;

?>
<!DOCTYPE html>
<html>
<head>
    <title>Cetak Tagihan Kelas <?= $nama_kelas ?></title>
    <style>
        @page { size: 215mm 330mm; margin: 5mm; }
        body { font-family: sans-serif; font-size: 10px; margin: 0; padding: 0; }
        
        .container-grid {
            width: 100%;
            overflow: hidden;
        }

        .bill-wrapper {
            float: left;
            width: 48%; /* 2 columns */
            height: 145mm; /* Half page height approx for F4 */
            margin: 0.5%; /* Gap */
            border: 1px solid #999;
            padding: 5px;
            box-sizing: border-box;
            page-break-inside: avoid;
            position: relative;
        }
        
        .header { text-align: center; margin-bottom: 5px; }
        .header h2 { font-size: 14px; margin: 0; }
        .header h3 { font-size: 12px; margin: 2px 0; }
        .header p { font-size: 10px; margin: 0; font-weight: bold; }
        
        .info-siswa { margin-bottom: 5px; }
        .info-siswa table { width: auto; border: none; }
        .info-siswa td { border: none; padding: 0 5px 0 0; font-size: 10px; }
        
        table.data { width: 100%; border-collapse: collapse; margin-top: 2px; }
        table.data th, table.data td { border: 1px solid #000; padding: 2px; text-align: left; vertical-align: top; font-size: 9px; }
        table.data th { background-color: #f2f2f2; }
        
        .text-success { color: green; font-weight: bold; }
        .text-danger { color: red; font-weight: bold; }
        
        .signature { margin-top: 5px; float: right; text-align: center; width: 120px; font-size: 10px; }
        .signature p { margin: 1px 0; }
        
        .page-break { page-break-after: always; clear: both; width: 100%; }
        
        @media print {
            .page-break { page-break-after: always; }
            .bill-wrapper { height: 145mm; overflow: hidden; }
        }
    </style>
</head>
<body onload="window.print()">

<div class="container-grid">

<?php
$counter = 0;
while ($d_siswa = mysqli_fetch_assoc($q_siswa)) {
    $nisn = $d_siswa['nisn'];
    $counter++;
    
    echo '<div class="bill-wrapper">';
    
    // Header
    echo '<div class="header">';
    echo '<h2>LAPORAN TAGIHAN SISWA</h2>';
    echo '<h3>' . strtoupper($nama_sekolah) . '</h3>';
    echo '<p>TAHUN AJARAN ' . strtoupper($tahun_ajaran) . '</p>';
    echo '</div>';
    
    // Info Siswa
    echo '<div class="info-siswa">';
    echo '<table>';
    echo '<tr><td>Nama Siswa</td><td>: ' . $d_siswa['nama'] . '</td></tr>';
    echo '<tr><td>Kelas</td><td>: ' . $nama_kelas . '</td></tr>';
    echo '<tr><td>NISN</td><td>: ' . $d_siswa['nisn'] . '</td></tr>';
    echo '</table>';
    echo '</div>';
    
    // Table
    echo '<table class="data">';
    echo '<thead>';
    echo '<tr>';
    echo '<th width="5%">No</th>';
    echo '<th width="20%">Jenis Pembayaran</th>';
    echo '<th width="15%">Tipe</th>';
    echo '<th width="15%">Nominal / Tagihan</th>';
    echo '<th>Status Pembayaran</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    $no = 1;
    $total_tagihan = 0;
    
    foreach ($jb_data as $jb) {
        // Filter by Class
        if (!empty($jb['tagihan_kelas'])) {
            $allowed_kelas = array_map('trim', explode(',', $jb['tagihan_kelas']));
            if (!in_array($nama_kelas, $allowed_kelas)) {
                continue;
            }
        }
        
        echo "<tr>";
        echo "<td>" . $no++ . "</td>";
        echo "<td>" . $jb['nama_pembayaran'] . "</td>";
        echo "<td>" . $jb['tipe_bayar'] . "</td>";
        echo "<td>Rp " . number_format($jb['nominal'], 0, ',', '.') . "</td>";
        echo "<td>";

        if ($jb['tipe_bayar'] == 'Bulanan') {
            $q_bayar = mysqli_query($koneksi, "SELECT bulan_bayar FROM pembayaran WHERE nisn='$nisn' AND id_jenis_bayar='" . $jb['id_jenis_bayar'] . "'");
            $paid_months = [];
            while ($r = mysqli_fetch_assoc($q_bayar)) {
                if (!empty($r['bulan_bayar'])) {
                    $ms = array_map('trim', explode(',', $r['bulan_bayar']));
                    $paid_months = array_merge($paid_months, $ms);
                }
            }
            
            echo '<div style="display: table; width: 100%;">';
            $month_counter = 0;
            echo '<div style="display: table-row;">';
            foreach ($months as $index => $m) {
                if ($index > $limit_index) continue; // Skip future months

                $is_paid = in_array($m, $paid_months);
                
                if (!$is_paid) {
                    $total_tagihan += $jb['nominal'];
                }

                $symbol = $is_paid ? '&#10004;' : '&#10006;';
                $color = $is_paid ? 'green' : 'red';
                
                echo '<div style="display: table-cell; width: 50%; padding-bottom: 1px; font-size: 9px;">';
                echo '<span style="color: '.$color.';">'.$symbol.'</span> ' . $m;
                echo '</div>';
                
                $month_counter++;
                if ($month_counter % 2 == 0 && $month_counter < 12) {
                    echo '</div><div style="display: table-row;">';
                }
            }
            echo '</div></div>';
            
        } else {
            $q_total = mysqli_query($koneksi, "SELECT SUM(jumlah_bayar) as total FROM pembayaran WHERE nisn='$nisn' AND id_jenis_bayar='" . $jb['id_jenis_bayar'] . "'");
            $d_total = mysqli_fetch_assoc($q_total);
            $total_bayar = $d_total['total'] ?? 0;
            $sisa = $jb['nominal'] - $total_bayar;
            
            if ($sisa > 0) {
                $total_tagihan += $sisa;
            }

            echo "Sudah Bayar: Rp " . number_format($total_bayar, 0, ',', '.') . "<br>";
            if ($sisa <= 0) {
                echo '<span class="text-success">&#10004; LUNAS</span>';
            } else {
                echo '<span class="text-danger">&#10006; Kurang: Rp ' . number_format($sisa, 0, ',', '.') . '</span>';
            }
        }

        echo "</td>";
        echo "</tr>";
    }
    
    echo '<tr>';
    echo '<td colspan="4" style="text-align: right; font-weight: bold;">Total Tagihan Belum Dibayar</td>';
    echo '<td style="font-weight: bold; color: red;">Rp ' . number_format($total_tagihan, 0, ',', '.') . '</td>';
    echo '</tr>';
    
    echo '</tbody>';
    echo '</table>';
    
    // Signature
    echo '<div class="signature">';
    echo '<p>' . $tanggal_str . '</p>';
    echo '<p>Bendahara,</p>';
    echo '<br><br><br>';
    echo '<p><b>' . $nama_bendahara . '</b></p>';
    echo '</div>';
    
    echo '</div>'; // End bill-wrapper
    
    // Page break every 4 items (2x2 grid)
    if ($counter % 4 == 0) {
        echo '<div class="page-break"></div>';
    }
}
?>

</div>

</body>
</html>