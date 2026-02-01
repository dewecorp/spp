<?php
session_start();
include '../config/config.php';

if (!isset($_SESSION['login'])) {
    header("Location: " . base_url('auth/login.php'));
    exit;
}

// Using simple HTML table for PDF to avoid complex library setup if not already present
// But usually in these templates, there's a preferred way. I'll use a standard approach.

$query = mysqli_query($koneksi, "SELECT pembayaran.*, siswa.nama AS nama_siswa, kelas.nama_kelas, jenis_bayar.nama_pembayaran, jenis_bayar.tipe_bayar 
                                 FROM pembayaran 
                                 JOIN siswa ON pembayaran.nisn = siswa.nisn 
                                 JOIN kelas ON siswa.id_kelas = kelas.id_kelas 
                                 JOIN jenis_bayar ON pembayaran.id_jenis_bayar = jenis_bayar.id_jenis_bayar 
                                 ORDER BY pembayaran.tgl_bayar DESC");

?>
<!DOCTYPE html>
<html>
<head>
    <title>Export PDF - Transaksi Pembayaran</title>
    <style>
        body { font-family: sans-serif; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        h2 { text-align: center; }
    </style>
</head>
<body onload="window.print()">
    <h2>LAPORAN TRANSAKSI PEMBAYARAN</h2>
    <p>Tanggal Cetak: <?= date('d/m/Y H:i:s') ?></p>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Siswa</th>
                <th>Kelas</th>
                <th>Jenis Bayar</th>
                <th>Cicilan Ke</th>
                <th>Nominal</th>
                <th>Tanggal</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            while($row = mysqli_fetch_assoc($query)) :
            ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= $row['nama_siswa'] ?></td>
                <td><?= $row['nama_kelas'] ?></td>
                <td><?= $row['nama_pembayaran'] ?></td>
                <td><?= ($row['tipe_bayar'] == 'Cicilan' ? $row['cicilan_ke'] : '-') ?></td>
                <td>Rp <?= number_format($row['jumlah_bayar'], 0, ',', '.') ?></td>
                <td><?= date('d/m/Y', strtotime($row['tgl_bayar'])) ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>