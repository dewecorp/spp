<?php
include '../config/config.php';

$nisn = $_GET['nisn'];
$q_siswa = mysqli_query($koneksi, "SELECT * FROM siswa JOIN kelas ON siswa.id_kelas = kelas.id_kelas WHERE nisn = '$nisn'");
$d_siswa = mysqli_fetch_assoc($q_siswa);

if (!$d_siswa) {
    die("Data siswa tidak ditemukan!");
}

// Ambil info sekolah
$q_info = mysqli_query($koneksi, "SELECT * FROM pengaturan LIMIT 1");
$d_info = mysqli_fetch_assoc($q_info);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Laporan Pembayaran - <?= $d_siswa['nama'] ?></title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { 
            text-align: center; 
            margin-bottom: 20px; 
            border-bottom: 2px solid #000; 
            padding-bottom: 10px; 
            position: relative;
            min-height: 80px;
        }
        .header img {
            position: absolute;
            left: 0;
            top: 0;
            height: 80px;
            width: auto;
        }
        .header h2 { margin: 0; padding-top: 15px; }
        .header p { margin: 5px 0; }
        .info-siswa { margin-bottom: 20px; width: 100%; }
        .info-siswa td { padding: 3px; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .table th, .table td { border: 1px solid #000; padding: 5px; }
        .table th { background-color: #f0f0f0; }
        .badge-success { color: green; font-weight: bold; }
        .badge-danger { color: red; font-weight: bold; }
        .badge-warning { color: orange; font-weight: bold; }
        @media print {
            @page { margin: 10mm; }
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="header">
        <?php if (!empty($d_info['logo'])): ?>
            <img src="../assets/images/<?= $d_info['logo'] ?>" alt="Logo">
        <?php endif; ?>
        <h2><?= strtoupper($d_info['nama_sekolah']) ?></h2>
        <h3>LAPORAN STATUS PEMBAYARAN SISWA</h3>
    </div>

    <table class="info-siswa">
        <tr>
            <td width="100">NISN</td>
            <td width="300">: <?= $d_siswa['nisn'] ?></td>
            <td width="100">Kelas</td>
            <td>: <?= $d_siswa['nama_kelas'] ?></td>
        </tr>
        <tr>
            <td>Nama Siswa</td>
            <td>: <?= $d_siswa['nama'] ?></td>
            <td>Tahun Ajaran</td>
            <td>: <?= $d_info['tahun_ajaran'] ?></td>
        </tr>
    </table>

    <?php
    $id_kelas_siswa = $d_siswa['id_kelas'];
    $q_jenis = mysqli_query($koneksi, "SELECT * FROM jenis_bayar ORDER BY tipe_bayar ASC");
    
    while ($d_jenis = mysqli_fetch_assoc($q_jenis)) {
        $applies = true;
        if (!empty($d_jenis['tagihan_kelas'])) {
            $kelas_ids = explode(',', $d_jenis['tagihan_kelas']);
            if (!in_array($id_kelas_siswa, $kelas_ids)) {
                $applies = false;
            }
        }

        if ($applies) {
    ?>
            <div style="margin-bottom: 20px; page-break-inside: avoid;">
                <h4><?= $d_jenis['nama_pembayaran'] ?> (Rp. <?= number_format($d_jenis['nominal'], 0, ',', '.') ?>)</h4>
                
                <?php if ($d_jenis['tipe_bayar'] == 'Bulanan') { ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Bulan</th>
                                <th>Status</th>
                                <th>Tanggal Bayar</th>
                                <th>Jumlah</th>
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
                                
                                $status = $d_bayar ? 'Lunas' : 'Belum Bayar';
                                $tgl = $d_bayar ? date('d/m/Y', strtotime($d_bayar['tgl_bayar'])) : '-';
                                $jml = $d_bayar ? number_format($d_bayar['jumlah_bayar'], 0, ',', '.') : '-';
                            ?>
                                <tr>
                                    <td><?= $bln ?></td>
                                    <td><?= $status ?></td>
                                    <td><?= $tgl ?></td>
                                    <td><?= $jml ?></td>
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
                            <td width="150">Total Tagihan</td>
                            <td>Rp. <?= number_format($d_jenis['nominal'], 0, ',', '.') ?></td>
                        </tr>
                        <tr>
                            <td>Total Dibayar</td>
                            <td>Rp. <?= number_format($total_bayar, 0, ',', '.') ?></td>
                        </tr>
                        <tr>
                            <td>Sisa Tagihan</td>
                            <td>Rp. <?= number_format($sisa > 0 ? $sisa : 0, 0, ',', '.') ?></td>
                        </tr>
                        <tr>
                            <td>Status</td>
                            <td><?= $status_lunas ?></td>
                        </tr>
                    </table>
                <?php } ?>
            </div>
    <?php
        }
    }
    ?>
    
    <div style="margin-top: 30px; float: right; text-align: center;">
        <p>Jepara, <?= date('d F Y') ?></p>
        <p>Bendahara</p>
        <br><br><br>
        <p><b><?= $d_info['nama_bendahara'] ?></b></p>
    </div>

</body>
</html>