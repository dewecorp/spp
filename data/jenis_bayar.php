<?php
include '../template/header.php';
include '../template/sidebar.php';

// Proses Tambah
if (isset($_POST['tambah'])) {
    $nama_pembayaran = $_POST['nama_pembayaran'];
    $nominal = $_POST['nominal'];
    $tahun_ajaran = $_POST['tahun_ajaran'];
    
    $query = mysqli_query($koneksi, "INSERT INTO jenis_bayar (nama_pembayaran, nominal, tahun_ajaran) VALUES ('$nama_pembayaran', '$nominal', '$tahun_ajaran')");
    if ($query) {
        logActivity($koneksi, 'Create', "Menambah jenis bayar: $nama_pembayaran ($tahun_ajaran)");
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
    $tahun_ajaran = $_POST['tahun_ajaran'];

    $query = mysqli_query($koneksi, "UPDATE jenis_bayar SET nama_pembayaran='$nama_pembayaran', nominal='$nominal', tahun_ajaran='$tahun_ajaran' WHERE id_jenis_bayar='$id_jenis_bayar'");
    if ($query) {
        logActivity($koneksi, 'Update', "Mengedit jenis bayar: $nama_pembayaran ($tahun_ajaran)");
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
                                <th>Tahun Ajaran</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            $query = mysqli_query($koneksi, "SELECT * FROM jenis_bayar ORDER BY tahun_ajaran DESC");
                            while ($row = mysqli_fetch_assoc($query)) :
                            ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= $row['nama_pembayaran'] ?></td>
                                    <td>Rp <?= number_format($row['nominal'], 0, ',', '.') ?></td>
                                    <td><?= $row['tahun_ajaran'] ?></td>
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
                                                        <label>Tahun Ajaran</label>
                                                        <input type="text" name="tahun_ajaran" class="form-control" value="<?= $row['tahun_ajaran'] ?>" required>
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
                        <label>Tahun Ajaran</label>
                        <input type="text" name="tahun_ajaran" class="form-control" placeholder="Contoh: 2024/2025" required>
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

<script>
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
</script>
