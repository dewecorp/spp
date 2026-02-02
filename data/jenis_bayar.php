<?php
$title = 'Jenis Pembayaran';
include '../template/header.php';
include '../template/sidebar.php';

// Include Select2 CSS
?>
<link rel="stylesheet" href="<?= base_url('assets/vendors/select2/select2.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/vendors/select2-bootstrap-theme/select2-bootstrap.min.css') ?>">

<?php
// Fetch Kelas Data
$q_kelas = mysqli_query($koneksi, "SELECT * FROM kelas ORDER BY nama_kelas ASC");
$kelas_list = [];
while ($k = mysqli_fetch_assoc($q_kelas)) {
    $kelas_list[] = $k;
}

// Proses Tambah
if (isset($_POST['tambah'])) {
    $nama_pembayaran = $_POST['nama_pembayaran'];
    $nominal = $_POST['nominal'];
    $tipe_bayar = $_POST['tipe_bayar'];
    $kali_cicilan = ($tipe_bayar == 'Cicilan') ? $_POST['kali_cicilan'] : 0;
    
    // Handle Tagihan Kepada (Array to String)
    $tagihan_kelas = isset($_POST['tagihan_kelas']) ? implode(',', $_POST['tagihan_kelas']) : '';
    
    $query = mysqli_query($koneksi, "INSERT INTO jenis_bayar (nama_pembayaran, nominal, tipe_bayar, kali_cicilan, tagihan_kelas) VALUES ('$nama_pembayaran', '$nominal', '$tipe_bayar', '$kali_cicilan', '$tagihan_kelas')");
    if ($query) {
        logActivity($koneksi, 'Create', "Menambah jenis bayar: $nama_pembayaran ($tipe_bayar)");
        echo "<script>
            Swal.fire({
                title: 'Berhasil',
                text: 'Data berhasil ditambahkan',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                window.location='jenis_bayar.php';
            });
        </script>";
    } else {
        echo "<script>Swal.fire('Gagal', 'Data gagal ditambahkan', 'error');</script>";
    }
}

// Proses Edit
if (isset($_POST['edit'])) {
    $id_jenis_bayar = $_POST['id_jenis_bayar'];
    $nama_pembayaran = $_POST['nama_pembayaran'];
    $nominal = $_POST['nominal'];
    $tipe_bayar = $_POST['tipe_bayar'];
    $kali_cicilan = ($tipe_bayar == 'Cicilan') ? $_POST['kali_cicilan'] : 0;

    // Handle Tagihan Kepada (Array to String)
    $tagihan_kelas = isset($_POST['tagihan_kelas']) ? implode(',', $_POST['tagihan_kelas']) : '';

    $query = mysqli_query($koneksi, "UPDATE jenis_bayar SET nama_pembayaran='$nama_pembayaran', nominal='$nominal', tipe_bayar='$tipe_bayar', kali_cicilan='$kali_cicilan', tagihan_kelas='$tagihan_kelas' WHERE id_jenis_bayar='$id_jenis_bayar'");
    if ($query) {
        logActivity($koneksi, 'Update', "Mengedit jenis bayar: $nama_pembayaran ($tipe_bayar)");
        echo "<script>
            Swal.fire({
                title: 'Berhasil',
                text: 'Data berhasil diupdate',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                window.location='jenis_bayar.php';
            });
        </script>";
    } else {
        echo "<script>Swal.fire('Gagal', 'Data gagal diupdate', 'error');</script>";
    }
}

// Proses Hapus
if (isset($_GET['hapus'])) {
    $id_jenis_bayar = $_GET['hapus'];
    $query = mysqli_query($koneksi, "DELETE FROM jenis_bayar WHERE id_jenis_bayar='$id_jenis_bayar'");
    if ($query) {
        logActivity($koneksi, 'Delete', "Menghapus jenis bayar ID: $id_jenis_bayar");
        echo "<script>
            Swal.fire({
                title: 'Berhasil',
                text: 'Data berhasil dihapus',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                window.location='jenis_bayar.php';
            });
        </script>";
    } else {
        echo "<script>Swal.fire('Gagal', 'Data gagal dihapus', 'error');</script>";
    }
}
?>

<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Data Jenis Bayar</h4>
                <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#modalTambahJenis">
                    <i class="mdi mdi-plus"></i> Tambah Jenis Bayar
                </button>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Pembayaran</th>
                                <th>Nominal</th>
                                <th>Waktu Bayar</th>
                                <th>Tagihan Kepada</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            $query = mysqli_query($koneksi, "SELECT * FROM jenis_bayar ORDER BY id_jenis_bayar DESC");
                            while ($row = mysqli_fetch_assoc($query)) :
                                // Explode tagihan_kelas for check
                                $selected_kelas = explode(',', $row['tagihan_kelas'] ?? '');
                            ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= $row['nama_pembayaran'] ?></td>
                                    <td>Rp <?= number_format($row['nominal'], 0, ',', '.') ?></td>
                                    <td>
                                        <?= $row['tipe_bayar'] ?>
                                        <?php if ($row['tipe_bayar'] == 'Cicilan') : ?>
                                            (<?= $row['kali_cicilan'] ?>x)
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['tagihan_kelas'])) : ?>
                                            <span class="badge badge-info" title="<?= $row['tagihan_kelas'] ?>">
                                                <?= count($selected_kelas) ?> Kelas
                                            </span>
                                        <?php else : ?>
                                            <span class="badge badge-secondary">Semua / Kosong</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $row['id_jenis_bayar'] ?>">
                                            <i class="mdi mdi-pencil"></i>
                                        </button>
                                        <a href="jenis_bayar.php?hapus=<?= $row['id_jenis_bayar'] ?>" class="btn btn-danger btn-sm btn-hapus">
                                            <i class="mdi mdi-delete"></i>
                                        </a>
                                    </td>
                                </tr>

                                <!-- Modal Edit -->
                                <div class="modal fade" id="modalEdit<?= $row['id_jenis_bayar'] ?>" tabindex="-1" role="dialog" aria-labelledby="labelEdit<?= $row['id_jenis_bayar'] ?>" aria-hidden="true">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="labelEdit<?= $row['id_jenis_bayar'] ?>">Edit Jenis Bayar</h5>
                                                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close" style="background: transparent; border: none;">
                                                    <i class="mdi mdi-close"></i>
                                                </button>
                                            </div>
                                            <form action="" method="post">
                                                <div class="modal-body">
                                                    <input type="hidden" name="id_jenis_bayar" value="<?= $row['id_jenis_bayar'] ?>">
                                                    <div class="form-group">
                                                        <label>Nama Pembayaran</label>
                                                        <input type="text" name="nama_pembayaran" class="form-control" value="<?= $row['nama_pembayaran'] ?>" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Nominal</label>
                                                        <input type="number" name="nominal" class="form-control" value="<?= $row['nominal'] ?>" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Waktu Bayar</label>
                                                        <select name="tipe_bayar" class="form-control tipe-bayar" data-target="#cicilanEdit<?= $row['id_jenis_bayar'] ?>" required>
                                                            <option value="Bulanan" <?= ($row['tipe_bayar'] == 'Bulanan') ? 'selected' : '' ?>>Bulanan</option>
                                                            <option value="Cicilan" <?= ($row['tipe_bayar'] == 'Cicilan') ? 'selected' : '' ?>>Cicilan</option>
                                                        </select>
                                                    </div>
                                                    <div class="form-group cicilan-group" id="cicilanEdit<?= $row['id_jenis_bayar'] ?>" style="<?= ($row['tipe_bayar'] == 'Cicilan') ? '' : 'display:none;' ?>">
                                                        <label>Kali Cicilan</label>
                                                        <input type="number" name="kali_cicilan" class="form-control" value="<?= $row['kali_cicilan'] ?>" placeholder="Contoh: 3">
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Tagihan Kepada (Kelas)</label>
                                                        <div>
                                                            <button type="button" class="btn btn-xs btn-info mb-2 btn-pilih-semua" data-target="#selectKelasEdit<?= $row['id_jenis_bayar'] ?>">Pilih Semua</button>
                                                            <button type="button" class="btn btn-xs btn-danger mb-2 btn-batal-semua" data-target="#selectKelasEdit<?= $row['id_jenis_bayar'] ?>">Batal Semua</button>
                                                        </div>
                                                        <select class="form-control select2-multiple" id="selectKelasEdit<?= $row['id_jenis_bayar'] ?>" name="tagihan_kelas[]" multiple="multiple" style="width: 100%;" required>
                                                            <?php foreach ($kelas_list as $kelas) : ?>
                                                                <option value="<?= $kelas['nama_kelas'] ?>" <?= in_array($kelas['nama_kelas'], $selected_kelas) ? 'selected' : '' ?>>
                                                                    <?= $kelas['nama_kelas'] ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" name="edit" class="btn btn-primary">Simpan</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="modalTambahJenis" tabindex="-1" role="dialog" aria-labelledby="labelTambahJenis" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="labelTambahJenis">Tambah Jenis Bayar</h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close" style="background: transparent; border: none;">
                    <i class="mdi mdi-close"></i>
                </button>
            </div>
            <form action="" method="post">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nama Pembayaran</label>
                        <input type="text" name="nama_pembayaran" class="form-control" placeholder="Contoh: SPP Juli 2024" required>
                    </div>
                    <div class="form-group">
                        <label>Nominal</label>
                        <input type="number" name="nominal" class="form-control" placeholder="Contoh: 50000" required>
                    </div>
                    <div class="form-group">
                        <label>Waktu Bayar</label>
                        <select name="tipe_bayar" class="form-control tipe-bayar" data-target="#cicilanTambah" required>
                            <option value="Bulanan">Bulanan</option>
                            <option value="Cicilan">Cicilan</option>
                        </select>
                    </div>
                    <div class="form-group cicilan-group" id="cicilanTambah" style="display:none;">
                        <label>Kali Cicilan</label>
                        <input type="number" name="kali_cicilan" class="form-control" placeholder="Contoh: 3">
                    </div>
                    <div class="form-group">
                        <label>Tagihan Kepada (Kelas)</label>
                        <div>
                            <button type="button" class="btn btn-xs btn-info mb-2 btn-pilih-semua" data-target="#selectKelasTambah">Pilih Semua</button>
                            <button type="button" class="btn btn-xs btn-danger mb-2 btn-batal-semua" data-target="#selectKelasTambah">Batal Semua</button>
                        </div>
                        <select class="form-control select2-multiple" id="selectKelasTambah" name="tagihan_kelas[]" multiple="multiple" style="width: 100%;" required>
                            <?php foreach ($kelas_list as $kelas) : ?>
                                <option value="<?= $kelas['nama_kelas'] ?>"><?= $kelas['nama_kelas'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../template/footer.php'; ?>

<!-- Select2 JS -->
<script src="<?= base_url('assets/vendors/select2/select2.min.js') ?>"></script>

<script>
    $(document).ready(function() {
        // Initialize Select2 with dropdownParent fix for Modals
        $('.select2-multiple').each(function() {
            $(this).select2({
                placeholder: "Pilih Kelas",
                allowClear: true,
                theme: "bootstrap",
                dropdownParent: $(this).closest('.modal')
            });
        });

        $('.btn-hapus').on('click', function(e) {
            e.preventDefault();
            const href = $(this).attr('href');
            Swal.fire({
                title: 'Apakah anda yakin?',
                text: "Data yang dihapus tidak dapat dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, hapus!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = href;
                }
            })
        });

        // Toggle Input Cicilan
        $('.tipe-bayar').on('change', function() {
            const target = $(this).data('target');
            if ($(this).val() === 'Cicilan') {
                $(target).show();
                $(target).find('input').prop('required', true);
            } else {
                $(target).hide();
                $(target).find('input').prop('required', false);
            }
        });

        // Pilih Semua Kelas
        $('.btn-pilih-semua').click(function() {
            var target = $(this).data('target');
            $(target + ' > option').prop("selected", true);
            $(target).trigger("change");
        });

        // Batal Semua Kelas
        $('.btn-batal-semua').click(function() {
            var target = $(this).data('target');
            $(target + ' > option').prop("selected", false);
            $(target).trigger("change");
        });
    });
</script>