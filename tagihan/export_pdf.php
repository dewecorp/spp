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

// Get School Settings
$q_setting = mysqli_query($koneksi, "SELECT * FROM pengaturan WHERE id_pengaturan = 1");
$d_setting = mysqli_fetch_assoc($q_setting);
$nama_sekolah = $d_setting['nama_sekolah'] ?? 'SMK NEGERI 1 CONTOH';
$nama_bendahara = $d_setting['nama_bendahara'] ?? 'Bendahara Sekolah';
$tahun_ajaran = $d_setting['tahun_ajaran'] ?? '';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Laporan Tagihan - <?= $d_siswa['nama'] ?></title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { 
            text-align: center; 
            margin-bottom: 20px; 
            position: relative;
            min-height: 80px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .header img {
            position: absolute;
            left: 0;
            top: 0;
            max-height: 80px;
            max-width: 80px;
        }
        .header-content {
            margin-left: 90px;
        }
        .header h2, .header h3, .header p { margin: 2px 0; }
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
        
        .signature { margin-top: 30px; float: right; text-align: center; width: 200px; page-break-inside: avoid; break-inside: avoid; }
        
        @media print {
            @page { size: A4; margin: 2cm; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <?php if (!empty($d_setting['logo'])): ?>
            <img src="../assets/images/<?= $d_setting['logo'] ?>" alt="Logo">
        <?php endif; ?>
        <div class="header-content">
            <h2>LAPORAN TAGIHAN SISWA</h2>
            <h3><?= strtoupper($nama_sekolah) ?></h3>
            <p>TAHUN AJARAN <?= strtoupper($tahun_ajaran) ?></p>
        </div>
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
                <th width="25%">Jenis Pembayaran</th>
                <th width="20%">Nominal / Tagihan</th>
                <th>Status Pembayaran</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            $total_tagihan = 0;
            
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

                // Check status before displaying
                $is_fully_paid = false;
                $paid_months = [];
                $total_bayar = 0;
                $sisa = 0;

                if ($jb['tipe_bayar'] == 'Bulanan') {
                    $q_bayar = mysqli_query($koneksi, "SELECT bulan_bayar FROM pembayaran WHERE nisn='$nisn' AND id_jenis_bayar='" . $jb['id_jenis_bayar'] . "'");
                    while ($r = mysqli_fetch_assoc($q_bayar)) {
                        if (!empty($r['bulan_bayar'])) {
                            $ms = array_map('trim', explode(',', $r['bulan_bayar']));
                            $paid_months = array_merge($paid_months, $ms);
                        }
                    }
                    
                    $has_unpaid = false;
                    foreach ($months as $index => $m) {
                        if ($index > $limit_index) continue;
                        if (!in_array($m, $paid_months)) {
                            $has_unpaid = true;
                            break;
                        }
                    }
                    
                    if (!$has_unpaid) {
                        $is_fully_paid = true;
                    }

                } else {
                    $q_total = mysqli_query($koneksi, "SELECT SUM(jumlah_bayar) as total FROM pembayaran WHERE nisn='$nisn' AND id_jenis_bayar='" . $jb['id_jenis_bayar'] . "'");
                    $d_total = mysqli_fetch_assoc($q_total);
                    $total_bayar = $d_total['total'] ?? 0;
                    $sisa = $jb['nominal'] - $total_bayar;
                    
                    if ($sisa <= 0) {
                        $is_fully_paid = true;
                    }
                }

                // Skip if fully paid
                if ($is_fully_paid) {
                    continue;
                }
                
                echo "<tr>";
                echo "<td>" . $no++ . "</td>";
                echo "<td>" . $jb['nama_pembayaran'] . "</td>";
                echo "<td>Rp " . number_format($jb['nominal'], 0, ',', '.') . "</td>";
                echo "<td>";

                if ($jb['tipe_bayar'] == 'Bulanan') {
                    echo '<div style="display: table; width: 100%;">';
                    $counter = 0;
                    echo '<div style="display: table-row;">';
                    foreach ($months as $index => $m) {
                        if ($index > $limit_index) continue; // Skip future months

                        $is_paid = in_array($m, $paid_months);
                        
                        // Skip if paid
                        if ($is_paid) continue;

                        $total_tagihan += $jb['nominal'];

                        $symbol = '&#10006;';
                        $color = 'red';
                        
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
                    if ($sisa > 0) {
                        $total_tagihan += $sisa;
                    }

                    // echo "Sudah Bayar: Rp " . number_format($total_bayar, 0, ',', '.') . "<br>";
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
            <tr>
                <td colspan="3" style="text-align: right; font-weight: bold;">Total Tagihan Belum Dibayar</td>
                <td style="font-weight: bold; color: red;">Rp <?= number_format($total_tagihan, 0, ',', '.') ?></td>
            </tr>
        </tbody>
    </table>

    <div class="signature">
        <?php
        $tgl = date('d');
        $bulan = [
            '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April', '05' => 'Mei', '06' => 'Juni',
            '07' => 'Juli', '08' => 'Agustus', '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
        ];
        $bln = $bulan[date('m')];
        $thn = date('Y');
        $qr_src_bendahara = generate_qr_bendahara($nama_bendahara, $nama_sekolah, 60);
        ?>
        <p><?= $tgl . ' ' . $bln . ' ' . $thn ?></p>
        <p>Bendahara,</p>
        <img src="<?= $qr_src_bendahara ?>" alt="QR Bendahara" style="width:60px;height:60px;margin:6px 0;">
        <p><b><?= $nama_bendahara ?></b></p>
    </div>
</body>
</html>
