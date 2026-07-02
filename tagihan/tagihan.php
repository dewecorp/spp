<?php
$title = 'Cetak Tagihan';
include '../template/header.php';
include '../template/sidebar.php';

// Include Select2 CSS
?>
<link rel="stylesheet" href="<?= base_url('assets/vendors/select2/select2.min.css') ?>">
<style><?php include __DIR__ . '/../assets/css/select2-kelas-filter.css'; ?></style>

<div class="mb-6 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
    <h4 class="mb-5 text-base font-extrabold tracking-normal text-slate-950">Filter Tagihan Siswa</h4>
    <form action="" method="get">
        <div class="grid items-end gap-4 md:grid-cols-[minmax(240px,320px)_auto]">
            <div class="min-w-0">
                <label for="id_kelas" class="mb-2 block text-sm font-bold text-slate-700">Pilih Kelas</label>
                <select name="id_kelas" id="id_kelas" class="app-control select2 filter-kelas" style="width: 100%;" required onchange="this.form.submit()">
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
            <?php if (isset($_GET['id_kelas']) && $_GET['id_kelas'] !== ''): ?>
                <div class="flex gap-3 md:pb-0">
                    <a href="cetak_semua.php?id_kelas=<?= $_GET['id_kelas'] ?>" class="app-button app-button-primary app-button-with-text h-[46px] w-full md:w-auto md:px-5" target="_blank">
                        <i class="mdi mdi-printer app-button-icon"></i>
                        <span>Cetak Tagihan Semua Siswa</span>
                    </a>
                    <a href="bayar_tagihan.php?id_kelas=<?= $_GET['id_kelas'] ?>" class="app-button app-button-success app-button-with-text h-[46px] w-full md:w-auto md:px-5">
                        <i class="mdi mdi-cash app-button-icon"></i>
                        <span>Bayar Tagihan</span>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php
if (isset($_GET['id_kelas'])) {
    $id_kelas = $_GET['id_kelas'];
    $q_siswa = mysqli_query($koneksi, "SELECT * FROM siswa WHERE id_kelas = '$id_kelas' ORDER BY nama ASC");
    $d_kelas = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT nama_kelas FROM kelas WHERE id_kelas = '$id_kelas'"));
?>

<div class="app-grid">
    <div class="app-col-full app-section-gap app-stretch">
        <div class="app-panel">
            <div class="app-panel-body">
                <h4 class="app-panel-title mb-3">Data Siswa Kelas: <?= $d_kelas['nama_kelas'] ?></h4>
                <div class="app-table-scroll">
                    <table class="app-data-table app-table-bordered app-table-striped">
                        <thead>
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
                            while ($s = mysqli_fetch_assoc($q_siswa)) :
                                // Cek apakah siswa memiliki tagihan tunggakan
                                $tagihan_tunggakan = cek_tagihan_tunggakan($koneksi, $s['nisn']);
                            ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= $s['nisn'] ?></td>
                                    <td><?= $s['nama'] ?></td>
                                    <td class="flex gap-2 items-center">
                                        <a href="tagihan_detail.php?nisn=<?= $s['nisn'] ?>&id_kelas=<?= $id_kelas ?>" class="app-button app-button-info app-button-sm">
                                            <i class="mdi mdi-eye"></i> Lihat Tagihan
                                        </a>
                                        <?php if ($tagihan_tunggakan): ?>
                                            <a href="bayar_tagihan.php?nisn=<?= $s['nisn'] ?>&id_kelas=<?= $id_kelas ?>" class="app-button app-button-success app-button-sm">
                                                <i class="mdi mdi-cash"></i> Bayar
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
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
        $('#id_kelas').select2({
            width: '100%',
            placeholder: '-- Pilih Kelas --',
            allowClear: false
        });

        $('#id_kelas').on('select2:open', function () {
            $('.select2-dropdown').last().addClass('select2-kelas-filter-dropdown');
        });
        $('#id_kelas').on('select2:close', function () {
            $('.select2-dropdown').removeClass('select2-kelas-filter-dropdown');
        });

        // Auto submit form when class is selected
        $('#id_kelas').on('change', function() {
            if ($(this).val()) {
                $(this).closest('form').submit();
            }
        });
    });
</script>
