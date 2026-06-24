<?php
$title = 'Data Kelas';
include '../template/header.php';
include '../template/sidebar.php';

// Proses Tambah
if (isset($_POST['tambah'])) {
    $nama_kelas = $_POST['nama_kelas'];
    $query = mysqli_query($koneksi, "INSERT INTO kelas (nama_kelas) VALUES ('$nama_kelas')");
    if ($query) {
        logActivity($koneksi, 'Create', "Menambah kelas: $nama_kelas");
        echo "<script>
            Swal.fire({
                title: 'Berhasil',
                text: 'Data berhasil ditambahkan',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                window.location='kelas.php';
            });
        </script>";
    } else {
        echo "<script>Swal.fire('Gagal', 'Data gagal ditambahkan', 'error');</script>";
    }
}

// Proses Edit
if (isset($_POST['edit'])) {
    $id_kelas = $_POST['id_kelas'];
    $nama_kelas = $_POST['nama_kelas'];
    $query = mysqli_query($koneksi, "UPDATE kelas SET nama_kelas='$nama_kelas' WHERE id_kelas='$id_kelas'");
    if ($query) {
        logActivity($koneksi, 'Update', "Mengedit kelas: $nama_kelas");
        echo "<script>
            Swal.fire({
                title: 'Berhasil',
                text: 'Data berhasil diupdate',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                window.location='kelas.php';
            });
        </script>";
    } else {
        echo "<script>Swal.fire('Gagal', 'Data gagal diupdate', 'error');</script>";
    }
}

// Proses Hapus
if (isset($_GET['hapus'])) {
    $id_kelas = $_GET['hapus'];
    $query = mysqli_query($koneksi, "DELETE FROM kelas WHERE id_kelas='$id_kelas'");
    if ($query) {
        logActivity($koneksi, 'Delete', "Menghapus kelas ID: $id_kelas");
        echo "<script>
            Swal.fire({
                title: 'Berhasil',
                text: 'Data berhasil dihapus',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                window.location='kelas.php';
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
                <h4 class="card-title">Data Kelas</h4>
                <button type="button" class="btn btn-primary mb-3" data-tailwind-modal-target="#modalTambah">
                    <i class="mdi mdi-plus"></i> Tambah Kelas
                </button>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Kelas</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            $query = mysqli_query($koneksi, "SELECT * FROM kelas ORDER BY nama_kelas ASC");
                            while ($row = mysqli_fetch_assoc($query)) :
                            ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= $row['nama_kelas'] ?></td>
                                    <td>
                                        <button type="button" class="btn btn-warning btn-sm" data-tailwind-modal-target="#modalEdit<?= $row['id_kelas'] ?>">
                                            <i class="mdi mdi-pencil"></i>
                                        </button>
                                        <a href="kelas.php?hapus=<?= $row['id_kelas'] ?>" class="btn btn-danger btn-sm btn-hapus">
                                            <i class="mdi mdi-delete"></i>
                                        </a>
                                    </td>
                                </tr>

                                <!-- Modal Edit -->
                                <div class="fixed inset-0 z-[1055] hidden overflow-y-auto bg-slate-950/60 px-4 py-6 backdrop-blur-sm" id="modalEdit<?= $row['id_kelas'] ? data-tailwind-modal>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                    <div class="mx-auto flex min-h-full w-full max-w-2xl items-start">
                                        <div class="flex max-h-[calc(100vh-3rem)] w-full flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl">
                                            <div class="flex shrink-0 items-center justify-between border-b border-slate-200 px-6 py-4">
                                                <h5 class="text-base font-extrabold text-slate-900" id="exampleModalLabel">Edit Kelas</h5>
                                                <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-100 hover:text-slate-900" data-tailwind-modal-close aria-label="Close" style="background: transparent; border: none;">
                                                    <i class="mdi mdi-close"></i>
                                                </button>
                                            </div>
                                            <form action="" method="post">
                                                <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
                                                    <input type="hidden" name="id_kelas" value="<?= $row['id_kelas'] ?>">
                                                    <div class="form-group">
                                                        <label>Nama Kelas</label>
                                                        <input type="text" name="nama_kelas" class="form-control" value="<?= $row['nama_kelas'] ?>" required>
                                                    </div>
                                                </div>
                                                <div class="flex shrink-0 items-center justify-end gap-3 border-t border-slate-200 px-6 py-4">
                                                    <button type="button" class="btn btn-secondary" data-tailwind-modal-close>Batal</button>
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
<div class="fixed inset-0 z-[1055] hidden overflow-y-auto bg-slate-950/60 px-4 py-6 backdrop-blur-sm" id="modalTambah" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" data-tailwind-modal>
    <div class="mx-auto flex min-h-full w-full max-w-2xl items-start">
        <div class="flex max-h-[calc(100vh-3rem)] w-full flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl">
            <div class="flex shrink-0 items-center justify-between border-b border-slate-200 px-6 py-4">
                <h5 class="text-base font-extrabold text-slate-900" id="exampleModalLabel">Tambah Kelas</h5>
                <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-100 hover:text-slate-900" data-tailwind-modal-close aria-label="Close" style="background: transparent; border: none;">
                    <i class="mdi mdi-close"></i>
                </button>
            </div>
            <form action="" method="post">
                <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
                    <div class="form-group">
                        <label>Nama Kelas</label>
                        <input type="text" name="nama_kelas" class="form-control" placeholder="Contoh: 1 A" required>
                    </div>
                </div>
                <div class="flex shrink-0 items-center justify-end gap-3 border-t border-slate-200 px-6 py-4">
                    <button type="button" class="btn btn-secondary" data-tailwind-modal-close>Batal</button>
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
            confirmButtonText: 'Ya, hapus!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = href;
            }
        })
    });
</script>

