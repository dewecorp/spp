<?php
$title = 'Laporan Pembayaran';
include '../template/header.php';
include '../template/sidebar.php';

$id_kelas = isset($_GET['id_kelas']) ? $_GET['id_kelas'] : '';
?>
<link rel="stylesheet" href="<?= base_url('assets/vendors/select2/select2.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/vendors/select2-bootstrap-theme/select2-bootstrap.min.css') ?>">
<style><?php include __DIR__ . '/../assets/css/select2-kelas-filter.css'; ?></style>

<div class="row">
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Filter Laporan</h4>
                <form action="" method="get">
                    <div class="row">
                        <div class="col-lg-6 col-xl-5">
                            <div class="form-group">
                                <label class="fw-semibold" for="id_kelas_laporan">Pilih Kelas</label>
                                <select id="id_kelas_laporan" name="id_kelas" class="form-control select2 select2-filter-laporan" style="width: 100%;" required onchange="this.form.submit()" data-placeholder="-- Pilih Kelas --">
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
                        <div class="col-lg-6 col-xl-7 d-flex align-items-end">
                            <?php if (!empty($id_kelas)): ?>
                                <a href="cetak_semua.php?id_kelas=<?= $id_kelas ?>" target="_blank" class="btn btn-primary btn-icon-text mb-3 mb-md-0">
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

<?php include '../template/footer.php'; ?>
<script src="<?= base_url('assets/vendors/select2/select2.min.js') ?>"></script>
<script>
    $(document).ready(function() {
        if ($('.select2-filter-laporan').length) {
            $('.select2-filter-laporan').select2({
                theme: 'bootstrap',
                width: '100%',
                placeholder: $('.select2-filter-laporan').data('placeholder') || '-- Pilih Kelas --',
                allowClear: false
            });

            $('#id_kelas_laporan').on('select2:open', function () {
                $('.select2-dropdown').last().addClass('select2-kelas-filter-dropdown');
            });
            $('#id_kelas_laporan').on('select2:close', function () {
                $('.select2-dropdown').removeClass('select2-kelas-filter-dropdown');
            });
        }
    });
</script>
