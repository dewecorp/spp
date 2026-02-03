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

// Ambil siswa di kelas ini
$q_siswa = mysqli_query($koneksi, "SELECT * FROM siswa WHERE id_kelas = '$id_kelas' ORDER BY nama ASC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Laporan Kelas - <?= $d_kelas['nama_kelas'] ?></title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .header h2 { margin: 0; }
        .header p { margin: 5px 0; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .table th, .table td { border: 1px solid #000; padding: 4px; }
        .table th { background-color: #f0f0f0; text-align: center; }
        .page-break { page-break-after: always; }
        .status-lunas { font-weight: bold; }
        .status-belum { color: red; }
        @media print {
            @page { margin: 10mm; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="header">
        <h2><?= strtoupper($d_info['nama_sekolah']) ?></h2>
        <p><?= $d_info['alamat_sekolah'] ?></p>
        <h3>REKAPITULASI PEMBAYARAN KELAS <?= strtoupper($d_kelas['nama_kelas']) ?></h3>
    </div>

    <?php
    // Kita buat tabel matriks: Baris = Siswa, Kolom = Jenis Pembayaran
    // Ambil semua jenis pembayaran yang berlaku untuk kelas ini
    $jenis_bayar_valid = [];
    $q_jenis = mysqli_query($koneksi, "SELECT * FROM jenis_bayar ORDER BY tipe_bayar ASC");
    while ($r = mysqli_fetch_assoc($q_jenis)) {
        $applies = true;
        if (!empty($r['tagihan_kelas'])) {
            $kelas_ids = explode(',', $r['tagihan_kelas']);
            if (!in_array($id_kelas, $kelas_ids)) {
                $applies = false;
            }
        }
        if ($applies) {
            $jenis_bayar_valid[] = $r;
        }
    }
    ?>

    <table class="table">
        <thead>
            <tr>
                <th width="30">No</th>
                <th>Nama Siswa</th>
                <?php foreach ($jenis_bayar_valid as $jb) { ?>
                    <th><?= $jb['nama_pembayaran'] ?></th>
                <?php } ?>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            while ($d_siswa = mysqli_fetch_assoc($q_siswa)) {
                echo "<tr>";
                echo "<td align='center'>$no</td>";
                echo "<td>" . $d_siswa['nama'] . "</td>";
                
                foreach ($jenis_bayar_valid as $jb) {
                    echo "<td align='center'>";
                    if ($jb['tipe_bayar'] == 'Bulanan') {
                        // Hitung berapa bulan sudah bayar
                        $q_count = mysqli_query($koneksi, "SELECT COUNT(*) as jml FROM pembayaran WHERE nisn = '" . $d_siswa['nisn'] . "' AND id_jenis_bayar = '" . $jb['id_jenis_bayar'] . "'");
                        $d_count = mysqli_fetch_assoc($q_count);
                        $jml_bulan = $d_count['jml'];
                        
                        if ($jml_bulan == 12) {
                            echo "<span class='status-lunas'>LUNAS</span>";
                        } else {
                            echo "<span class='status-belum'>$jml_bulan / 12 Bln</span>";
                        }
                    } else {
                        // Cicilan
                        $q_bayar = mysqli_query($koneksi, "SELECT SUM(jumlah_bayar) as total FROM pembayaran WHERE nisn = '" . $d_siswa['nisn'] . "' AND id_jenis_bayar = '" . $jb['id_jenis_bayar'] . "'");
                        $d_bayar = mysqli_fetch_assoc($q_bayar);
                        $total = $d_bayar['total'] ?? 0;
                        $sisa = $jb['nominal'] - $total;
                        
                        if ($sisa <= 0) {
                            echo "<span class='status-lunas'>LUNAS</span>";
                        } else {
                            echo "<span class='status-belum'>Kurang Rp. " . number_format($sisa, 0, ',', '.') . "</span>";
                        }
                    }
                    echo "</td>";
                }
                
                echo "</tr>";
                $no++;
            }
            ?>
        </tbody>
    </table>

    <div style="margin-top: 30px; float: right; text-align: center;">
        <p><?= $d_info['alamat_sekolah'] ?>, <?= date('d F Y') ?></p>
        <p>Bendahara Sekolah</p>
        <br><br><br>
        <p><b><?= $d_info['nama_bendahara'] ?></b></p>
    </div>

</body>
</html>