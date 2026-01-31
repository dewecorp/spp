<?php
include '../template/header.php';
include '../template/sidebar.php';

$siswa = null;
if (isset($_GET['nisn'])) {
    $nisn = $_GET['nisn'];
    $query = mysqli_query($koneksi, "SELECT siswa.*, kelas.nama_kelas FROM siswa JOIN kelas ON siswa.id_kelas = kelas.id_kelas WHERE nisn='$nisn'");
    $siswa = mysqli_fetch_assoc($query);
}

// Proses Bayar
if (isset($_POST['bayar'])) {
    $id_petugas = $_SESSION['id_pengguna'];
    $nisn = $_POST['nisn'];
    $tgl_bayar = date('Y-m-d');
    $bulan_bayar = $_POST['bulan_bayar']; // Optional, jika jenis bayar bulanan
    $tahun_bayar = $_POST['tahun_bayar']; // Optional
    $id_jenis_bayar = $_POST['id_jenis_bayar'];
    $jumlah_bayar = $_POST['jumlah_bayar'];
    $ket = $_POST['ket'];

    $query = mysqli_query($koneksi, "INSERT INTO pembayaran (id_petugas, nisn, tgl_bayar, bulan_bayar, tahun_bayar, id_jenis_bayar, jumlah_bayar, ket) VALUES ('$id_petugas', '$nisn', '$tgl_bayar', '$bulan_bayar', '$tahun_bayar', '$id_jenis_bayar', '$jumlah_bayar', '$ket')");

    if ($query) {
        echo "<script>
            Swal.fire({
                title: 'Berhasil',
                text: 'Pembayaran berhasil disimpan',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                window.location='transaksi.php?nisn=$nisn';
            });
        </script>";
    } else {
        echo "<script>Swal.fire('Gagal', 'Pembayaran gagal disimpan', 'error');</script>";
    }
}

$jenis_bayar = mysqli_query($koneksi, "SELECT * FROM jenis_bayar ORDER BY tahun_ajaran DESC");
?>

<div class="row">
    <div class="col-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Transaksi Pembayaran</h4>
                <form action="" method="get">
                    <div class="input-group mb-3">
                        <input type="text" name="nisn" class="form-control" placeholder="Masukkan NISN Siswa" value="<?= isset($_GET['nisn']) ? $_GET['nisn'] : '' ?>" required>
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="submit">Cari Siswa</button>
                        </div>
                    </div>
                </form>

                <?php if ($siswa) : ?>
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Data Siswa</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <th>NISN</th>
                                    <td><?= $siswa['nisn'] ?></td>
                                </tr>
                                <tr>
                                    <th>Nama</th>
                                    <td><?= $siswa['nama'] ?></td>
                                </tr>
                                <tr>
                                    <th>Kelas</th>
                                    <td><?= $siswa['nama_kelas'] ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5>Form Pembayaran</h5>
                            <form action="" method="post">
                                <input type="hidden" name="nisn" value="<?= $siswa['nisn'] ?>">
                                <div class="form-group">
                                    <label>Jenis Pembayaran</label>
                                    <select name="id_jenis_bayar" class="form-control" id="jenis_bayar" required onchange="updateNominal()">
                                        <option value="">-- Pilih --</option>
                                        <?php while ($jb = mysqli_fetch_assoc($jenis_bayar)) : ?>
                                            <option value="<?= $jb['id_jenis_bayar'] ?>" data-nominal="<?= $jb['nominal'] ?>">
                                                <?= $jb['nama_pembayaran'] ?> (<?= $jb['tahun_ajaran'] ?>)
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="form-group row">
                                    <div class="col-md-6">
                                        <label>Bulan (Opsional)</label>
                                        <select name="bulan_bayar" class="form-control">
                                            <option value="">-- Pilih Bulan --</option>
                                            <?php
                                            $bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                                            foreach ($bulan as $bln) {
                                                echo "<option value='$bln'>$bln</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Tahun (Opsional)</label>
                                        <input type="number" name="tahun_bayar" class="form-control" value="<?= date('Y') ?>">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Jumlah Bayar</label>
                                    <input type="number" name="jumlah_bayar" id="jumlah_bayar" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Keterangan</label>
                                    <textarea name="ket" class="form-control"></textarea>
                                </div>
                                <button type="submit" name="bayar" class="btn btn-success btn-block">Bayar</button>
                            </form>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <h5>Riwayat Pembayaran Siswa Ini</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Tanggal</th>
                                            <th>Jenis Pembayaran</th>
                                            <th>Bulan/Tahun</th>
                                            <th>Jumlah</th>
                                            <th>Petugas</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $no = 1;
                                        $hist = mysqli_query($koneksi, "SELECT pembayaran.*, jenis_bayar.nama_pembayaran, pengguna.nama_lengkap FROM pembayaran JOIN jenis_bayar ON pembayaran.id_jenis_bayar = jenis_bayar.id_jenis_bayar JOIN pengguna ON pembayaran.id_petugas = pengguna.id_pengguna WHERE pembayaran.nisn = '$nisn' ORDER BY tgl_bayar DESC");
                                        while ($h = mysqli_fetch_assoc($hist)) :
                                        ?>
                                            <tr>
                                                <td><?= $no++ ?></td>
                                                <td><?= date('d/m/Y', strtotime($h['tgl_bayar'])) ?></td>
                                                <td><?= $h['nama_pembayaran'] ?></td>
                                                <td><?= $h['bulan_bayar'] ?> <?= $h['tahun_bayar'] ?></td>
                                                <td>Rp <?= number_format($h['jumlah_bayar'], 0, ',', '.') ?></td>
                                                <td><?= $h['nama_lengkap'] ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                <?php elseif (isset($_GET['nisn'])) : ?>
                    <div class="alert alert-danger">Data siswa tidak ditemukan!</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    function updateNominal() {
        var select = document.getElementById('jenis_bayar');
        var nominal = select.options[select.selectedIndex].getAttribute('data-nominal');
        if (nominal) {
            document.getElementById('jumlah_bayar').value = nominal;
        } else {
            document.getElementById('jumlah_bayar').value = '';
        }
    }
</script>

<?php include '../template/footer.php'; ?>
