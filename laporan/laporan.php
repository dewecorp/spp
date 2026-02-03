<?php
$title = 'Laporan Pembayaran';
include '../template/header.php';
include '../template/sidebar.php';

$id_kelas = isset($_GET['id_kelas']) ? $_GET['id_kelas'] : '';
?>

<div class="row">
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Filter Laporan</h4>
                <form action="" method="get">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Pilih Kelas</label>
                                <select class="form-control" name="id_kelas" required onchange="this.form.submit()">
                                    <option value="">-- Pilih Kelas --</option>
                                    <?php
                                    $q_kelas = mysqli_query($koneksi, "SELECT * FROM kelas ORDER BY nama_kelas ASC");
                                    while ($d_kelas = mysqli_fetch_assoc($q_kelas)) {
                                        $selected = ($id_kelas == $d_kelas['id_kelas']) ? 'selected' : '';
                                        echo "<option value='" . $d_kelas['id_kelas'] . "' $selected>" . $d_kelas['nama_kelas'] . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-8 d-flex align-items-center">
                            <?php if (!empty($id_kelas)): ?>
                                <a href="cetak_semua.php?v=1&id_kelas=<?= $id_kelas ?>" target="_blank" class="btn btn-primary btn-icon-text mt-3">
                                    <i class="mdi mdi-printer btn-icon-prepend"></i> Cetak Semua Laporan
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php if (!empty($id_kelas)): ?>
        <div class="col-md-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Data Siswa</h4>
                    <div class="table-responsive">
                        <table class="table table-striped" id="table-laporan">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>NISN</th>
                                    <th>Nama Siswa</th>
                                    <th>Kelas</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = 1;
                                $q_siswa = mysqli_query($koneksi, "SELECT * FROM siswa JOIN kelas ON siswa.id_kelas = kelas.id_kelas WHERE siswa.id_kelas = '$id_kelas' ORDER BY nama ASC");
                                if (mysqli_num_rows($q_siswa) > 0) {
                                    while ($d_siswa = mysqli_fetch_assoc($q_siswa)) {
                                ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td><?= $d_siswa['nisn'] ?></td>
                                            <td><?= $d_siswa['nama'] ?></td>
                                            <td><?= $d_siswa['nama_kelas'] ?></td>
                                            <td>
                                                <a href="detail_laporan.php?nisn=<?= $d_siswa['nisn'] ?>" class="btn btn-info btn-sm btn-icon-text">
                                                    <i class="mdi mdi-eye btn-icon-prepend"></i> Detail
                                                </a>
                                                <a href="cetak_laporan.php?nisn=<?= $d_siswa['nisn'] ?>" target="_blank" class="btn btn-warning btn-sm btn-icon-text">
                                                    <i class="mdi mdi-printer btn-icon-prepend"></i> Cetak
                                                </a>
                                            </td>
                                        </tr>
                                <?php
                                    }
                                } else {
                                    echo "<tr><td colspan='5' class='text-center'>Tidak ada data siswa</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    $(document).ready(function() {
        $('#table-laporan').DataTable();
    });
</script>

<?php include '../template/footer.php'; ?>