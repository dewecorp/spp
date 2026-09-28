<?php
include '../config/config.php';

if (!isset($_GET['nisn'])) {
    die("NISN tidak ditemukan!");
}

$nisn = mysqli_real_escape_string($koneksi, $_GET['nisn']);
$q_siswa = mysqli_query($koneksi, "SELECT s.*, k.nama_kelas FROM siswa s JOIN kelas k ON s.id_kelas = k.id_kelas WHERE s.nisn = '$nisn'");
$d_siswa = mysqli_fetch_assoc($q_siswa);

if (!$d_siswa) {
    die("Data siswa tidak ditemukan!");
}

if (kelas_adalah_alumni($d_siswa['nama_kelas'] ?? '')) {
    die("Kelas Alumni tidak memiliki laporan tahun ajaran berjalan.");
}

// Ambil info sekolah
$q_info = mysqli_query($koneksi, "SELECT * FROM pengaturan LIMIT 1");
$d_info = mysqli_fetch_assoc($q_info);
$nama_bendahara = $d_info['nama_bendahara'] ?? 'Bendahara';
$nama_sekolah = $d_info['nama_sekolah'] ?? '';
$alamat_sekolah = $d_info['alamat_sekolah'] ?? '';
$tahun_ajaran_laporan = trim((string)($d_info['tahun_ajaran'] ?? get_tahun_ajaran_aktif($koneksi)));
$bulan_indo = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April', '05' => 'Mei', '06' => 'Juni',
    '07' => 'Juli', '08' => 'Agustus', '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];
$tgl_cetak = date('d') . ' ' . $bulan_indo[date('m')] . ' ' . date('Y');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Pembayaran - <?= htmlspecialchars($d_siswa['nama']) ?></title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 15mm;
        }
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 10pt; color: #111; margin: 0; padding: 10px; }
        .header { 
            display: flex; 
            align-items: center; 
            border-bottom: 2px solid #000; 
            padding-bottom: 8px; 
            margin-bottom: 15px; 
        }
        .header img {
            max-height: 60px;
            max-width: 60px;
            margin-right: 15px;
        }
        .header-content {
            flex-grow: 1;
            text-align: center;
        }
        .header-content h2 { margin: 0; font-size: 13pt; font-weight: bold; text-transform: uppercase; }
        .header-content p { margin: 2px 0 0 0; font-size: 9pt; color: #333; }
        .header-content h3 { margin: 6px 0 0 0; font-size: 11pt; text-decoration: underline; }
        
        .info-siswa { width: 100%; border-collapse: collapse; margin-bottom: 15px; font-size: 9.5pt; }
        .info-siswa td { padding: 4px 6px; vertical-align: top; }
        
        .payment-card { margin-bottom: 15px; page-break-inside: avoid; break-inside: avoid; }
        .payment-card h4 { margin: 0 0 6px 0; font-size: 10pt; background: #e9ecef; padding: 6px 10px; border-left: 4px solid #0d6efd; }
        
        .table, .app-data-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 9pt; }
        .table th, .table td, .app-data-table th, .app-data-table td { border: 1px solid #333; padding: 5px 8px; }
        .table th, .app-data-table th { background-color: #f1f3f5; font-weight: bold; text-align: center; }
        
        .badge-lunas { color: #198754; font-weight: bold; }
        .badge-belum { color: #dc3545; font-weight: bold; }
        
        .footer-container { display: flex; justify-content: flex-end; margin-top: 25px; page-break-inside: avoid; break-inside: avoid; }
        .signature { text-align: center; min-width: 180px; font-size: 9pt; }
        .signature p { margin: 2px 0; }
        .signature img { width: 55px; height: 55px; margin: 4px 0; }
        
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="header">
        <?php if (!empty($d_info['logo'])): ?>
            <img src="../assets/images/<?= $d_info['logo'] ?>" alt="Logo">
        <?php endif; ?>
        <div class="header-content">
            <h2><?= strtoupper($d_info['nama_sekolah']) ?></h2>
            <p><?= $alamat_sekolah ?></p>
            <h3>LAPORAN STATUS PEMBAYARAN SISWA</h3>
        </div>
    </div>

    <table class="info-siswa">
        <tr>
            <td width="110"><strong>NISN</strong></td>
            <td width="10">:</td>
            <td width="230"><?= $d_siswa['nisn'] ?></td>
            <td width="110"><strong>Kelas</strong></td>
            <td width="10">:</td>
            <td><?= $d_siswa['nama_kelas'] ?></td>
        </tr>
        <tr>
            <td><strong>Nama Siswa</strong></td>
            <td>:</td>
            <td><strong><?= $d_siswa['nama'] ?></strong></td>
            <td><strong>Tahun Ajaran</strong></td>
            <td>:</td>
            <td><?= htmlspecialchars($tahun_ajaran_laporan, ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
    </table>

    <?php
    $id_kelas_siswa = $d_siswa['id_kelas'];
    $nama_kelas_siswa = $d_siswa['nama_kelas'];
    $q_jenis = mysqli_query($koneksi, "SELECT * FROM jenis_bayar WHERE status = 'Aktif' ORDER BY tipe_bayar ASC");
    
    while ($d_jenis = mysqli_fetch_assoc($q_jenis)) {
        $applies = jenis_bayar_berlaku_untuk_kelas($d_jenis['tagihan_kelas'] ?? '', $id_kelas_siswa, $nama_kelas_siswa);

        if ($applies) {
    ?>
            <div class="payment-card">
                <h4><?= $d_jenis['nama_pembayaran'] ?> (Nominal: Rp <?= number_format($d_jenis['nominal'], 0, ',', '.') ?>)</h4>
                
                <?php if ($d_jenis['tipe_bayar'] == 'Bulanan') { ?>
                    <table class="app-data-table">
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th>Bulan</th>
                                <th>Status</th>
                                <th>Tanggal Bayar</th>
                                <th width="25%">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $bulan = ['Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni'];
                            $paid_by_month = bulanan_map_pembayaran_per_bulan($koneksi, $nisn, $d_jenis['id_jenis_bayar'], $tahun_ajaran_laporan);
                            
                            $no = 1;
                            foreach ($bulan as $bln) {
                                $d_bayar = $paid_by_month[$bln] ?? null;
                                
                                $status = $d_bayar ? '<span class="badge-lunas">Lunas</span>' : '<span class="badge-belum">Belum Bayar</span>';
                                $tgl = $d_bayar ? date('d/m/Y', strtotime($d_bayar['tgl_bayar'])) : '-';
                                $jml = $d_bayar ? 'Rp ' . number_format($d_bayar['jumlah'], 0, ',', '.') : '-';
                            ?>
                                <tr>
                                    <td style="text-align: center;"><?= $no++ ?></td>
                                    <td><?= $bln ?></td>
                                    <td style="text-align: center;"><?= $status ?></td>
                                    <td style="text-align: center;"><?= $tgl ?></td>
                                    <td style="text-align: right;"><?= $jml ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                <?php } else { 
                    $total_bayar = ambil_total_bayar_tersimpan($koneksi, $nisn, $d_jenis['id_jenis_bayar'], $tahun_ajaran_laporan);
                    $sisa = $d_jenis['nominal'] - $total_bayar;
                    $status_lunas = ($sisa <= 0) ? '<span class="badge-lunas">Lunas</span>' : '<span class="badge-belum">Belum Lunas</span>';
                ?>
                    <table class="app-data-table">
                        <tr>
                            <td width="180">Total Tagihan</td>
                            <td width="10">:</td>
                            <td style="text-align: right;">Rp <?= number_format($d_jenis['nominal'], 0, ',', '.') ?></td>
                        </tr>
                        <tr>
                            <td>Total Dibayar</td>
                            <td>:</td>
                            <td style="text-align: right;">Rp <?= number_format($total_bayar, 0, ',', '.') ?></td>
                        </tr>
                        <tr>
                            <td>Sisa Tagihan</td>
                            <td>:</td>
                            <td style="text-align: right; font-weight: bold;">Rp <?= number_format($sisa > 0 ? $sisa : 0, 0, ',', '.') ?></td>
                        </tr>
                        <tr>
                            <td>Status Pembayaran</td>
                            <td>:</td>
                            <td><?= $status_lunas ?></td>
                        </tr>
                    </table>
                <?php } ?>
            </div>
    <?php
        }
    }
    ?>
    
    <div class="footer-container">
        <div class="signature">
            <p><?= $tgl_cetak ?></p>
            <p style="font-weight: bold;">Bendahara,</p>
            <?php $qr_src_bendahara = generate_qr_bendahara($nama_bendahara, $nama_sekolah, 55); ?>
            <img src="<?= $qr_src_bendahara ?>" alt="QR Bendahara">
            <p><u><strong><?= $d_info['nama_bendahara'] ?></strong></u></p>
        </div>
    </div>

</body>
</html>
