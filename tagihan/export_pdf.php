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

$q_jb = mysqli_query($koneksi, "SELECT * FROM jenis_bayar ORDER BY tipe_bayar ASC, nama_pembayaran ASC");
$months = ['Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni'];

?>
<!DOCTYPE html>
<html>
<head>
    <title>Laporan Tagihan - <?= $d_siswa['nama'] ?></title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .info-siswa { margin-bottom: 20px; }
        .info-siswa table { width: auto; border: none; }
        .info-siswa td { border: none; padding: 2px 10px 2px 0; }
        
        table.data { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.data th, table.data td { border: 1px solid #000; padding: 8px; text-align: left; vertical-align: top; }
        table.data th { background-color: #f2f2f2; }
        
        .status-grid { display: flex; flex-wrap: wrap; }
        .status-item { width: 33%; margin-bottom: 5px; }
        
        .text-success { color: green; font-weight: bold; }
        .text-danger { color: red; font-weight: bold; }
        
        @media print {
            @page { size: A4; margin: 2cm; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <h2>LAPORAN TAGIHAN SISWA</h2>
        <h3><?= strtoupper($nama_sekolah ?? 'SMK NEGERI 1 CONTOH') ?></h3>
    </div>

    <div class="info-siswa">
        <table>
            <tr>
                <td>Nama Siswa</td>
                <td>: <?= $d_siswa['nama'] ?></td>
            </tr>
            <tr>
                <td>Kelas</td>
                <td>: <?= $nama_kelas ?></td>
            </tr>
            <tr>
                <td>NISN</td>
                <td>: <?= $d_siswa['nisn'] ?></td>
            </tr>
        </table>
    </div>

    <table class="data">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="20%">Jenis Pembayaran</th>
                <th width="15%">Tipe</th>
                <th width="15%">Nominal / Tagihan</th>
                <th>Status Pembayaran</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            
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
                    $counter = 0;
                    echo '<div style="display: table-row;">';
                    foreach ($months as $index => $m) {
                        if ($index > $limit_index) continue; // Skip future months

                        $is_paid = in_array($m, $paid_months);
                        $symbol = $is_paid ? '&#10004;' : '&#10006;';
                        $color = $is_paid ? 'green' : 'red';
                        
                        echo '<div style="display: table-cell; width: 25%; padding-bottom: 5px;">';
                        echo '<span style="color: '.$color.';">'.$symbol.'</span> ' . $m;
                        echo '</div>';
                        
                        $counter++;
                        if ($counter % 4 == 0 && $counter < 12) {
                            echo '</div><div style="display: table-row;">';
                        }
                    }
                    echo '</div></div>';
                    
                } else {
                    $q_total = mysqli_query($koneksi, "SELECT SUM(jumlah_bayar) as total FROM pembayaran WHERE nisn='$nisn' AND id_jenis_bayar='" . $jb['id_jenis_bayar'] . "'");
                    $d_total = mysqli_fetch_assoc($q_total);
                    $total_bayar = $d_total['total'] ?? 0;
                    $sisa = $jb['nominal'] - $total_bayar;
                    
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
            ?>
        </tbody>
    </table>
</body>
</html>