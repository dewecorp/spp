<?php
$title = 'Data Pengguna';
include '../template/header.php';
include '../template/sidebar.php';

// Proses Tambah/Edit/Hapus
if (isset($_POST['tambah'])) {
    $nama = $_POST['nama_lengkap'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $foto = '';

    // Upload Foto
    if (!empty($_FILES['foto']['name'])) {
        $file_name = $_FILES['foto']['name'];
        $file_tmp = $_FILES['foto']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png'];

        if (in_array($file_ext, $allowed_ext)) {
            $new_name = uniqid() . '.' . $file_ext;
            $upload_path = "../assets/images/faces/" . $new_name;

            if (move_uploaded_file($file_tmp, $upload_path)) {
                $foto = $new_name;
            }
        }
    }

    $query = "INSERT INTO pengguna (nama_lengkap, username, password, role, foto) VALUES ('$nama', '$username', '$password', '$role', '$foto')";
    if (mysqli_query($koneksi, $query)) {
        logActivity($koneksi, 'Create', "Menambah pengguna baru: $username ($role)");
        echo "<script>
            Swal.fire({
                title: 'Berhasil',
                text: 'Data pengguna berhasil ditambahkan',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                window.location.href = 'pengguna.php';
            });
        </script>";
    } else {
        echo "<script>Swal.fire('Gagal', 'Data gagal ditambahkan: " . mysqli_error($koneksi) . "', 'error');</script>";
    }
}

if (isset($_POST['edit'])) {
    $id = $_POST['id_pengguna'];
    $nama = $_POST['nama_lengkap'];
    $username = $_POST['username'];
    $role = $_POST['role'];
    
    $query = "UPDATE pengguna SET nama_lengkap='$nama', username='$username', role='$role'";

    // Upload Foto
    if (!empty($_FILES['foto']['name'])) {
        $file_name = $_FILES['foto']['name'];
        $file_tmp = $_FILES['foto']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png'];

        if (in_array($file_ext, $allowed_ext)) {
            $new_name = uniqid() . '.' . $file_ext;
            $upload_path = "../assets/images/faces/" . $new_name;

            if (move_uploaded_file($file_tmp, $upload_path)) {
                $query .= ", foto='$new_name'";
            }
        }
    }
    
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $query .= ", password='$password'";
    }

    $query .= " WHERE id_pengguna='$id'";

    if (mysqli_query($koneksi, $query)) {
        logActivity($koneksi, 'Update', "Mengubah data pengguna: $username");
        echo "<script>
            Swal.fire({
                title: 'Berhasil',
                text: 'Data pengguna berhasil diubah',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                window.location.href = 'pengguna.php';
            });
        </script>";
    } else {
        echo "<script>Swal.fire('Gagal', 'Data gagal diubah: " . mysqli_error($koneksi) . "', 'error');</script>";
    }
}

if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $query = "DELETE FROM pengguna WHERE id_pengguna='$id'";
    if (mysqli_query($koneksi, $query)) {
        logActivity($koneksi, 'Delete', "Menghapus pengguna ID: $id");
        echo "<script>
            Swal.fire({
                title: 'Berhasil',
                text: 'Data pengguna berhasil dihapus',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                window.location.href = 'pengguna.php';
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
                <h4 class="card-title">Data Pengguna</h4>
                <button type="button" class="btn btn-primary mb-3" data-tailwind-modal-target="#modalTambah">
                    <i class="mdi mdi-plus"></i> Tambah Pengguna
                </button>
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="table-pengguna">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Foto</th>
                                <th>Nama Lengkap</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            $query = mysqli_query($koneksi, "SELECT * FROM pengguna ORDER BY id_pengguna DESC");
                            while ($row = mysqli_fetch_assoc($query)) :
                                $foto = $row['foto'] ?? '';
                                $foto_disk = __DIR__ . '/../assets/images/faces/' . $foto;
                                if ($foto !== '' && is_file($foto_disk)) {
                                    $foto_url = base_url('assets/images/faces/' . rawurlencode($foto));
                                } else {
                                    $foto_url = 'https://ui-avatars.com/api/?name=' . urlencode($row['nama_lengkap']) . '&background=random&color=fff';
                                }
                            ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td>
                                        <img src="<?= $foto_url ?>" alt="Foto Profil" class="img-lg rounded-circle" style="width: 50px; height: 50px; object-fit: cover;">
                                    </td>
                                    <td><?= $row['nama_lengkap'] ?></td>
                                    <td><?= $row['username'] ?></td>
                                    <td>
                                        <?php if($row['role'] == 'admin'): ?>
                                            <span class="badge badge-success">Admin</span>
                                        <?php else: ?>
                                            <span class="badge badge-info">Petugas</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-warning btn-sm btn-edit" 
                                            data-id="<?= $row['id_pengguna'] ?>"
                                            data-nama="<?= $row['nama_lengkap'] ?>"
                                            data-username="<?= $row['username'] ?>"
                                            data-role="<?= $row['role'] ?>">
                                            <i class="mdi mdi-pencil"></i>
                                        </button>
                                        <a href="pengguna.php?hapus=<?= $row['id_pengguna'] ?>" class="btn btn-danger btn-sm btn-hapus">
                                            <i class="mdi mdi-delete"></i>
                                        </a>
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

<!-- Modal Tambah -->
<div class="fixed inset-0 z-[1055] hidden overflow-y-auto bg-slate-950/60 px-4 py-6 backdrop-blur-sm" id="modalTambah" tabindex="-1" role="dialog" aria-labelledby="modalTambahLabel" aria-hidden="true" data-tailwind-modal>
    <div class="mx-auto flex min-h-full w-full max-w-2xl items-start">
        <div class="flex max-h-[calc(100vh-3rem)] w-full flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl">
            <div class="flex shrink-0 items-center justify-between border-b border-slate-200 px-6 py-4">
                <h5 class="text-base font-extrabold text-slate-900" id="modalTambahLabel">Tambah Pengguna</h5>
                <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-100 hover:text-slate-900" data-tailwind-modal-close aria-label="Close" style="background: transparent; border: none;">
                    <i class="mdi mdi-close"></i>
                </button>
            </div>
            <form action="" method="post" enctype="multipart/form-data">
                <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
                    <div class="form-group">
                        <label>Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <select name="role" class="form-control" required>
                            <option value="admin">Admin</option>
                            <option value="petugas">Petugas</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Foto Profil (Opsional)</label>
                        <input type="file" name="foto" class="form-control" accept=".jpg, .jpeg, .png">
                        <small class="text-muted">Format: JPG, JPEG, PNG.</small>
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

<!-- Modal Edit -->
<div class="fixed inset-0 z-[1055] hidden overflow-y-auto bg-slate-950/60 px-4 py-6 backdrop-blur-sm" id="modalEdit" tabindex="-1" role="dialog" aria-labelledby="modalEditLabel" aria-hidden="true" data-tailwind-modal>
    <div class="mx-auto flex min-h-full w-full max-w-2xl items-start">
        <div class="flex max-h-[calc(100vh-3rem)] w-full flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl">
            <div class="flex shrink-0 items-center justify-between border-b border-slate-200 px-6 py-4">
                <h5 class="text-base font-extrabold text-slate-900" id="modalEditLabel">Edit Pengguna</h5>
                <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-100 hover:text-slate-900" data-tailwind-modal-close aria-label="Close" style="background: transparent; border: none;">
                    <i class="mdi mdi-close"></i>
                </button>
            </div>
            <form action="" method="post" enctype="multipart/form-data">
                <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
                    <input type="hidden" name="id_pengguna" id="edit_id">
                    <div class="form-group">
                        <label>Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" id="edit_nama" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" id="edit_username" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Password (Kosongkan jika tidak diubah)</label>
                        <input type="password" name="password" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <select name="role" id="edit_role" class="form-control" required>
                            <option value="admin">Admin</option>
                            <option value="petugas">Petugas</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Ganti Foto (Opsional)</label>
                        <input type="file" name="foto" class="form-control" accept=".jpg, .jpeg, .png">
                        <small class="text-muted">Format: JPG, JPEG, PNG. Biarkan kosong jika tidak ingin mengganti foto.</small>
                    </div>
                </div>
                <div class="flex shrink-0 items-center justify-end gap-3 border-t border-slate-200 px-6 py-4">
                    <button type="button" class="btn btn-secondary" data-tailwind-modal-close>Batal</button>
                    <button type="submit" name="edit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../template/footer.php'; ?>

<script>
    $(document).ready(function() {
        $('#table-pengguna').DataTable();

        $('.btn-edit').on('click', function() {
            var id = $(this).data('id');
            var nama = $(this).data('nama');
            var username = $(this).data('username');
            var role = $(this).data('role');

            $('#edit_id').val(id);
            $('#edit_nama').val(nama);
            $('#edit_username').val(username);
            $('#edit_role').val(role);

            $('#modalEdit').modal('show');
        });

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
    });
</script>

