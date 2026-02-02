<?php
$title = 'Riwayat Pembayaran';
include '../template/header.php';
include '../template/sidebar.php';
?>

<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Riwayat Pembayaran Global</h4>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Siswa</th>
                                <th>Kelas</th>
                                <th>Tanggal</th>
                                <th>Waktu</th>
                                <th>Jenis Pembayaran</th>
                                <th>Jumlah</th>
                                <th>Petugas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            $query = mysqli_query($koneksi, "SELECT pembayaran.*, siswa.nama, kelas.nama_kelas, jenis_bayar.nama_pembayaran, pengguna.nama_lengkap FROM pembayaran 
                                JOIN siswa ON pembayaran.nisn = siswa.nisn 
                                JOIN kelas ON siswa.id_kelas = kelas.id_kelas
                                JOIN jenis_bayar ON pembayaran.id_jenis_bayar = jenis_bayar.id_jenis_bayar 
                                JOIN pengguna ON pembayaran.id_petugas = pengguna.id_pengguna 
                                ORDER BY created_at DESC");
                            while ($row = mysqli_fetch_assoc($query)) :
                            ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= $row['nama'] ?> <br> <small><?= $row['nisn'] ?></small></td>
                                    <td><?= $row['nama_kelas'] ?></td>
                                    <td><?= date('d/m/Y', strtotime($row['tgl_bayar'])) ?></td>
                                    <td><?= date('H:i', strtotime($row['created_at'])) ?></td>
                                    <td><?= $row['nama_pembayaran'] ?> <br> <small><?= $row['bulan_bayar'] ?> <?= $row['tahun_bayar'] ?></small></td>
                                    <td>Rp <?= number_format($row['jumlah_bayar'], 0, ',', '.') ?></td>
                                    <td><?= $row['nama_lengkap'] ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../template/footer.php'; ?>
