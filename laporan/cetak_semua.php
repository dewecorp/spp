<?php
include '../config/config.php';

$id_kelas = $_GET['id_kelas'];
$q_kelas = mysqli_query($koneksi, "SELECT * FROM kelas WHERE id_kelas = '$id_kelas'");
$d_kelas = mysqli_fetch_assoc($q_kelas);

if (!$d_kelas) {
    die("Data kelas tidak ditemukan!");
}

// Ambil info sekolah
$q_info = mysqli_query($koneksi, "SELECT * FROM pengaturan LIMIT 1");
$d_info = mysqli_fetch_assoc($q_info);

// Ambil semua siswa di kelas ini
$q_siswa_all = mysqli_query($koneksi, "SELECT * FROM siswa WHERE id_kelas = '$id_kelas' ORDER BY nama ASC");

// Data Tanggal
$bulan_indo = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April', '05' => 'Mei', '06' => 'Juni',
    '07' => 'Juli', '08' => 'Agustus', '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];
$tgl_cetak = date('d') . ' ' . $bulan_indo[date('m')] . ' ' . date('Y');

?>
<!DOCTYPE html>
<html>
<head>
    <title>Cetak Semua Laporan - <?= $d_kelas['nama_kelas'] ?></title>
    <style>
        @page { size: 215mm 330mm; margin: 5mm; }
        body { font-family: Arial, sans-serif; font-size: 10px; margin: 0; padding: 0; }
        
        .container-grid {
            width: 100%;
            overflow: hidden;
        }

        .bill-wrapper {
            float: left;
            width: 48%;
            margin: 0.5%;
            border: 1px solid #999;
            padding: 5px;
            box-sizing: border-box;
            page-break-inside: avoid;
            position: relative;
            margin-bottom: 10px;
        }

        .header { 
            text-align: center; 
            margin-bottom: 5px; 
            position: relative;
            min-height: 40px;
            border-bottom: 1px solid #000;
            padding-bottom: 5px;
        }
        .header img {
            position: absolute;
            left: 0;
            top: 0;
            max-height: 40px;
            max-width: 40px;
        }
        .header h2 { font-size: 12px; margin: 0; padding-top: 5px; }
        .header h3 { font-size: 10px; margin: 2px 0; }
        
        .info-siswa { margin-bottom: 5px; }
        .info-siswa table { width: 100%; font-size: 10px; }
        .info-siswa td { padding: 1px; }

        .payment-section { margin-bottom: 5px; }
        .payment-section h4 { margin: 2px 0; font-size: 10px; background: #eee; padding: 2px; }

        .table { width: 100%; border-collapse: collapse; margin-bottom: 5px; }
        .table th, .table td { border: 1px solid #ccc; padding: 2px; font-size: 9px; }
        .table th { background-color: #f0f0f0; text-align: center; }
        .table td { text-align: center; }
        
        .signature { margin-top: 10px; text-align: center; font-size: 10px; page-break-inside: avoid; }
        
        .text-left { text-align: left !important; }
        .text-right { text-align: right !important; }
    </style>
</head>
<body onload="window.print()">

    <div class="container-grid">
    <?php
    while ($d_siswa = mysqli_fetch_assoc($q_siswa_all)) {
        $nisn = $d_siswa['nisn'];
        $nama_kelas = $d_kelas['nama_kelas'];
    ?>
    <div class="bill-wrapper">
        <div class="header">
            <?php if (!empty($d_info['logo'])): ?>
                <img src="../assets/images/<?= $d_info['logo'] ?>" alt="Logo">
            <?php endif; ?>
            <h2><?= strtoupper($d_info['nama_sekolah']) ?></h2>
            <h3>LAPORAN PEMBAYARAN SISWA</h3>
        </div>

        <div class="info-siswa">
            <table>
                <tr>
                    <td width="15%">NISN</td>
                    <td width="35%">: <?= $d_siswa['nisn'] ?></td>
                    <td width="15%">Kelas</td>
                    <td width="35%">: <?= $nama_kelas ?></td>
                </tr>
                <tr>
                    <td>Nama</td>
                    <td>: <?= $d_siswa['nama'] ?></td>
                    <td>Th. Ajaran</td>
                    <td>: <?= $d_info['tahun_ajaran'] ?></td>
                </tr>
            </table>
        </div>

        <?php
        $id_kelas_siswa = $d_siswa['id_kelas'];
        $q_jenis = mysqli_query($koneksi, "SELECT * FROM jenis_bayar ORDER BY tipe_bayar ASC");
        
        $has_data = false;
        while ($d_jenis = mysqli_fetch_assoc($q_jenis)) {
            $applies = true;
            if (!empty($d_jenis['tagihan_kelas'])) {
                $kelas_ids = explode(',', $d_jenis['tagihan_kelas']);
                if (!in_array($id_kelas_siswa, $kelas_ids)) {
                    $applies = false;
                }
            }

            if ($applies) {
                $has_data = true;
        ?>
                <div class="payment-section">
                    <h4><?= $d_jenis['nama_pembayaran'] ?> (Rp. <?= number_format($d_jenis['nominal'], 0, ',', '.') ?>)</h4>
                    
                    <?php if ($d_jenis['tipe_bayar'] == 'Bulanan') { ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Bln</th>
                                    <th>Status</th>
                                    <th>Tgl Bayar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $bulan = ['Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni'];
                                
                                // Calculate current month index (relative to school year starting July)
                                $current_month_num = date('n'); // 1-12
                                $limit_index = ($current_month_num >= 7) ? $current_month_num - 7 : $current_month_num + 5;
                                
                                foreach ($bulan as $index => $bln) {
                                    if ($index > $limit_index) continue; // Skip future months
                                    
                                    $q_bayar = mysqli_query($koneksi, "SELECT * FROM pembayaran WHERE nisn = '$nisn' AND id_jenis_bayar = '" . $d_jenis['id_jenis_bayar'] . "' AND bulan_bayar = '$bln'");
                                    $d_bayar = mysqli_fetch_assoc($q_bayar);
                                    
                                    $status = $d_bayar ? 'Lunas' : '-';
                                    $tgl = $d_bayar ? date('d/m/y', strtotime($d_bayar['tgl_bayar'])) : '-';
                                ?>
                                    <tr>
                                        <td class="text-left"><?= $bln ?></td>
                                        <td><?= $status ?></td>
                                        <td><?= $tgl ?></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    <?php } else { 
                        $q_total_bayar = mysqli_query($koneksi, "SELECT SUM(jumlah_bayar) as total FROM pembayaran WHERE nisn = '$nisn' AND id_jenis_bayar = '" . $d_jenis['id_jenis_bayar'] . "'");
                        $d_total = mysqli_fetch_assoc($q_total_bayar);
                        $total_bayar = $d_total['total'] ?? 0;
                        $sisa = $d_jenis['nominal'] - $total_bayar;
                        $status_lunas = ($sisa <= 0) ? 'Lunas' : 'Belum Lunas';
                    ?>
                        <table class="table">
                            <tr>
                                <td class="text-left">Dibayar</td>
                                <td class="text-right">Rp. <?= number_format($total_bayar, 0, ',', '.') ?></td>
                            </tr>
                            <tr>
                                <td class="text-left">Sisa</td>
                                <td class="text-right">Rp. <?= number_format($sisa > 0 ? $sisa : 0, 0, ',', '.') ?></td>
                            </tr>
                            <tr>
                                <td class="text-left">Status</td>
                                <td class="text-right"><b><?= $status_lunas ?></b></td>
                            </tr>
                        </table>
                    <?php } ?>
                </div>
        <?php
            }
        }
        
        if (!$has_data) {
            echo "<p style='text-align:center; font-style:italic;'>Tidak ada tagihan untuk kelas ini.</p>";
        }
        ?>
        
        <div class="signature">
            <p>Jepara, <?= $tgl_cetak ?></p>
            <p>Bendahara</p>
            <br><br>
            <p><b><?= $d_info['nama_bendahara'] ?></b></p>
        </div>
    </div>
    <?php } ?>
    </div>

</body>
</html>