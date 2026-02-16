<?php
include '../config/config.php';

if (!isset($_GET['no_transaksi'])) {
    echo "No Transaksi tidak ditemukan.";
    exit;
}

$no_transaksi = $_GET['no_transaksi'];

// Fetch Transaction Data
$query = mysqli_query($koneksi, "SELECT 
                                    p.*,
                                    s.nama as nama_siswa,
                                    k.nama_kelas,
                                    jb.nama_pembayaran,
                                    jb.tipe_bayar
                                 FROM pembayaran p
                                 JOIN siswa s ON p.nisn = s.nisn
                                 JOIN kelas k ON s.id_kelas = k.id_kelas
                                 JOIN jenis_bayar jb ON p.id_jenis_bayar = jb.id_jenis_bayar
                                 WHERE p.no_transaksi = '$no_transaksi'");

if (mysqli_num_rows($query) == 0) {
    echo "Data transaksi tidak ditemukan.";
    exit;
}

// Fetch first row for header info
$data = [];
while ($row = mysqli_fetch_assoc($query)) {
    $data[] = $row;
}

$header = $data[0];

// Fetch School Settings
$q_setting = mysqli_query($koneksi, "SELECT * FROM pengaturan WHERE id_pengaturan = 1");
$setting = mysqli_fetch_assoc($q_setting);
$nama_bendahara = $setting['nama_bendahara'] ?? 'Bendahara';
$nama_sekolah = $setting['nama_sekolah'] ?? '';
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Bukti Pembayaran - <?= $no_transaksi ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12pt;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            position: relative;
            min-height: 100px;
        }
        .header img {
            position: absolute;
            left: 0;
            top: 0;
            max-height: 80px;
            max-width: 80px;
        }
        .header h2, .header h3, .header p {
            margin: 2px;
        }
        .header-content {
            margin-left: 90px; /* Adjust based on logo width + gap */
            text-align: center;
        }
        .info-table {
            width: 100%;
            margin-bottom: 20px;
        }
        .info-table td {
            padding: 5px;
            vertical-align: top;
        }
        .transaksi-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .transaksi-table th, .transaksi-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        .transaksi-table th {
            background-color: #f0f0f0;
        }
        .total-row {
            font-weight: bold;
        }
        .footer {
            margin-top: 50px;
            text-align: right;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <?php if (!empty($setting['logo'])): ?>
            <img src="../assets/images/<?= $setting['logo'] ?>" alt="Logo Sekolah">
        <?php endif; ?>
        <div class="header-content">
            <h2><?= strtoupper($setting['nama_sekolah']) ?></h2>
            <p><?= $setting['alamat_sekolah'] ?></p>
            <h3>BUKTI PEMBAYARAN</h3>
        </div>
    </div>

    <table class="info-table">
        <tr>
            <td width="150">No. Transaksi</td>
            <td width="10">:</td>
            <td><?= $header['no_transaksi'] ?></td>
            <td width="150">Tanggal</td>
            <td width="10">:</td>
            <td><?= date('d/m/Y', strtotime($header['tgl_bayar'])) ?></td>
        </tr>
        <tr>
            <td>NISN</td>
            <td>:</td>
            <td><?= $header['nisn'] ?></td>
            <td>Petugas</td>
            <td>:</td>
            <td>Admin</td> <!-- Bisa ambil dari session/join petugas jika ada -->
        </tr>
        <tr>
            <td>Nama Siswa</td>
            <td>:</td>
            <td><?= $header['nama_siswa'] ?></td>
            <td>Kelas</td>
            <td>:</td>
            <td><?= $header['nama_kelas'] ?></td>
        </tr>
    </table>

    <table class="transaksi-table">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th>Jenis Pembayaran</th>
                <th>Keterangan</th>
                <th width="20%">Jumlah Bayar</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            $total = 0;
            foreach ($data as $d): 
                $total += $d['jumlah_bayar'];
                $ket = '';
                if ($d['tipe_bayar'] == 'Bulanan') {
                    $ket = "Bulan: " . $d['bulan_bayar'];
                } else {
                    $ket = "Cicilan ke-" . $d['cicilan_ke'];
                }
            ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= $d['nama_pembayaran'] ?></td>
                <td><?= $ket ?></td>
                <td style="text-align: right;">Rp <?= number_format($d['jumlah_bayar'], 0, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="3" style="text-align: right;">Total Pembayaran</td>
                <td style="text-align: right;">Rp <?= number_format($total, 0, ',', '.') ?></td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <p><?= $tgl_cetak ?></p>
        <p>Bendahara</p>
        <?php $qr_src_bendahara = generate_qr_bendahara($nama_bendahara, $nama_sekolah, 60); ?>
        <img src="<?= $qr_src_bendahara ?>" alt="QR Bendahara" style="width:60px;height:60px;margin:6px 0;">
        <p>( <?= $setting['nama_bendahara'] ?> )</p>
    </div>

    <div class="no-print" style="margin-top: 20px; text-align: center;">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px; cursor: pointer;">Cetak Bukti</button>
    </div>

    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>
