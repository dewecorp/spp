<?php
$title = 'Cetak Tagihan';
include '../template/header.php';
include '../template/sidebar.php';

// Include Select2 CSS
?>
<link rel="stylesheet" href="<?= base_url('assets/vendors/select2/select2.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/vendors/select2-bootstrap-theme/select2-bootstrap.min.css') ?>">

<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Filter Tagihan Siswa</h4>
                <form action="" method="get">
                    <div class="row">
                        <div class="col-md-5">
                            <div class="form-group">
                                <label>Pilih Kelas</label>
                                <select name="id_kelas" id="id_kelas" class="form-control select2" style="width: 100%;" required>
                                    <option value="">-- Pilih Kelas --</option>
                                    <?php
                                    $q_kelas = mysqli_query($koneksi, "SELECT * FROM kelas ORDER BY nama_kelas ASC");
                                    while ($k = mysqli_fetch_assoc($q_kelas)) {
                                        $selected = (isset($_GET['id_kelas']) && $_GET['id_kelas'] == $k['id_kelas']) ? 'selected' : '';
                                        echo '<option value="' . $k['id_kelas'] . '" ' . $selected . '>' . $k['nama_kelas'] . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
if (isset($_GET['id_kelas'])) {
    $id_kelas = $_GET['id_kelas'];
    $q_siswa = mysqli_query($koneksi, "SELECT * FROM siswa WHERE id_kelas = '$id_kelas' ORDER BY nama ASC");
    $d_kelas = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT nama_kelas FROM kelas WHERE id_kelas = '$id_kelas'"));
?>

<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title mb-0">Data Siswa Kelas: <?= $d_kelas['nama_kelas'] ?></h4>
                    <a href="cetak_semua.php?id_kelas=<?= $id_kelas ?>" class="btn btn-primary" target="_blank">
                        <i class="mdi mdi-printer"></i> Cetak Tagihan Semua Siswa
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th width="5%">No</th>
                                <th>NISN</th>
                                <th>Nama Siswa</th>
                                <th width="15%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            while ($s = mysqli_fetch_assoc($q_siswa)) {
                            ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= $s['nisn'] ?></td>
                                    <td><?= $s['nama'] ?></td>
                                    <td>
                                        <a href="tagihan_detail.php?nisn=<?= $s['nisn'] ?>&id_kelas=<?= $id_kelas ?>" class="btn btn-info btn-sm">
                                            <i class="mdi mdi-eye"></i> Lihat Tagihan
                                        </a>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
}
?>

<?php include '../template/footer.php'; ?>
<!-- Select2 JS -->
<script src="<?= base_url('assets/vendors/select2/select2.min.js') ?>"></script>
<script>
    $(document).ready(function() {
        $('.select2').select2({
            theme: "bootstrap",
            width: '100%'
        });

        // Auto submit form when class is selected
        $('#id_kelas').on('change', function() {
            if ($(this).val()) {
                $(this).closest('form').submit();
            }
        });
    });
</script>