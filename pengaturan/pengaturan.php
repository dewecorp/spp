<?php
$title = 'Pengaturan Sekolah';
include '../template/header.php';
include '../template/sidebar.php';

// Ambil data pengaturan
$query = mysqli_query($koneksi, "SELECT * FROM pengaturan WHERE id_pengaturan = 1");
$data = mysqli_fetch_assoc($query);

if (isset($_POST['simpan'])) {
    $nama_sekolah = $_POST['nama_sekolah'];
    $alamat_sekolah = $_POST['alamat_sekolah'];
    $nama_bendahara = $_POST['nama_bendahara'];
    $tahun_ajaran = $_POST['tahun_ajaran'];
    
    // Default Query
    $query_update = "UPDATE pengaturan SET nama_sekolah='$nama_sekolah', alamat_sekolah='$alamat_sekolah', nama_bendahara='$nama_bendahara', tahun_ajaran='$tahun_ajaran'";

    // Upload Logo
    if (!empty($_FILES['logo']['name'])) {
        $file_name = $_FILES['logo']['name'];
        $file_tmp = $_FILES['logo']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png'];

        if (in_array($file_ext, $allowed_ext)) {
            $new_name = "logo." . $file_ext;
            $upload_path = "../assets/images/" . $new_name;

            if (move_uploaded_file($file_tmp, $upload_path)) {
                $query_update .= ", logo='$new_name'";
            } else {
                echo "<script>Swal.fire('Gagal', 'Gagal mengupload logo', 'error');</script>";
            }
        } else {
            echo "<script>Swal.fire('Gagal', 'Format logo harus JPG/JPEG/PNG', 'error');</script>";
        }
    }
    
    // Upload Background Login
    if (!empty($_FILES['bg_login']['name'])) {
        $bg_name = $_FILES['bg_login']['name'];
        $bg_tmp = $_FILES['bg_login']['tmp_name'];
        $bg_ext = strtolower(pathinfo($bg_name, PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png'];

        if (in_array($bg_ext, $allowed_ext)) {
            $new_bg_name = "bg_login." . $bg_ext;
            $bg_upload_path = "../assets/images/" . $new_bg_name;

            if (move_uploaded_file($bg_tmp, $bg_upload_path)) {
                $query_update .= ", bg_login='$new_bg_name'";
            } else {
                echo "<script>Swal.fire('Gagal', 'Gagal mengupload background login', 'error');</script>";
            }
        } else {
            echo "<script>Swal.fire('Gagal', 'Format background harus JPG/JPEG/PNG', 'error');</script>";
        }
    }
    
    $query_update .= " WHERE id_pengaturan = 1";
    
    if (mysqli_query($koneksi, $query_update)) {
        logActivity($koneksi, 'Update', "Memperbarui pengaturan sekolah");
        echo "<script>
            Swal.fire({
                title: 'Berhasil',
                text: 'Pengaturan sekolah berhasil diperbarui',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                window.location.href = 'pengaturan.php?v=1';
            });
        </script>";
    } else {
        echo "<script>Swal.fire('Gagal', 'Gagal memperbarui pengaturan: " . mysqli_error($koneksi) . "', 'error');</script>";
    }
}

// Logic Reset Data Transaksi
if (isset($_POST['reset_data'])) {
    if ($_SESSION['role'] == 'admin') {
        // Disable foreign key checks to allow truncate/delete
        mysqli_query($koneksi, "SET FOREIGN_KEY_CHECKS = 0");
        $q_reset = mysqli_query($koneksi, "TRUNCATE TABLE pembayaran");
        mysqli_query($koneksi, "SET FOREIGN_KEY_CHECKS = 1");
        
        if ($q_reset) {
            logActivity($koneksi, 'Reset', "Mereset (menghapus) semua data transaksi pembayaran untuk tahun ajaran baru");
             echo "<script>
                Swal.fire({
                    title: 'Berhasil',
                    text: 'Semua data transaksi pembayaran berhasil dihapus!',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = 'pengaturan.php?v=1';
                });
            </script>";
        } else {
             echo "<script>Swal.fire('Gagal', 'Gagal mereset data: " . mysqli_error($koneksi) . "', 'error');</script>";
        }
    } else {
         echo "<script>Swal.fire('Akses Ditolak', 'Hanya Admin yang dapat melakukan reset data!', 'warning');</script>";
    }
}
?>

<div class="row">
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Pengaturan Sekolah</h4>
                <p class="card-description">
                    Ubah informasi sekolah
                </p>
                <form class="forms-sample" method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="nama_sekolah">Nama Sekolah</label>
                        <input type="text" class="form-control" id="nama_sekolah" name="nama_sekolah" value="<?= $data['nama_sekolah'] ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="alamat_sekolah">Alamat Sekolah</label>
                        <textarea class="form-control" id="alamat_sekolah" name="alamat_sekolah" rows="4" required><?= $data['alamat_sekolah'] ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="nama_bendahara">Nama Bendahara</label>
                        <input type="text" class="form-control" id="nama_bendahara" name="nama_bendahara" value="<?= $data['nama_bendahara'] ?? '' ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="tahun_ajaran">Tahun Ajaran Aktif</label>
                        <input type="text" class="form-control" id="tahun_ajaran" name="tahun_ajaran" value="<?= $data['tahun_ajaran'] ?? '' ?>" placeholder="Contoh: 2024/2025" required>
                    </div>
                    <div class="form-group">
                        <label>Logo Sekolah</label>
                        <?php if (!empty($data['logo'])) : ?>
                            <div class="mb-2">
                                <img src="../assets/images/<?= $data['logo'] ?>" alt="Logo Sekolah" style="max-width: 150px; border: 1px solid #eee; padding: 5px;">
                            </div>
                        <?php endif; ?>
                        <input type="file" name="logo" class="file-upload-default" id="logoInput" accept=".jpg, .jpeg, .png" style="display:none">
                        <div class="input-group col-xs-12">
                            <input type="text" class="form-control file-upload-info" disabled placeholder="Upload Logo">
                            <span class="input-group-append">
                                <button class="file-upload-browse btn btn-primary" type="button" onclick="document.getElementById('logoInput').click()">Upload</button>
                            </span>
                        </div>
                        <small class="text-muted">Format: JPG, JPEG, PNG.</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Background Login</label>
                        <?php if (!empty($data['bg_login'])) : ?>
                            <div class="mb-2">
                                <img src="../assets/images/<?= $data['bg_login'] ?>" alt="Background Login" style="max-width: 150px; border: 1px solid #eee; padding: 5px;">
                            </div>
                        <?php endif; ?>
                        <input type="file" name="bg_login" class="file-upload-default" id="bgInput" accept=".jpg, .jpeg, .png" style="display:none">
                        <div class="input-group col-xs-12">
                            <input type="text" class="form-control file-upload-info-bg" disabled placeholder="Upload Background Login">
                            <span class="input-group-append">
                                <button class="file-upload-browse btn btn-primary" type="button" onclick="document.getElementById('bgInput').click()">Upload</button>
                            </span>
                        </div>
                        <small class="text-muted">Format: JPG, JPEG, PNG.</small>
                    </div>

                    <script>
                        document.getElementById('logoInput').addEventListener('change', function() {
                            var fileName = this.files[0].name;
                            document.querySelector('.file-upload-info').value = fileName;
                        });
                        document.getElementById('bgInput').addEventListener('change', function() {
                            var fileName = this.files[0].name;
                            document.querySelector('.file-upload-info-bg').value = fileName;
                        });
                    </script>
                    <button type="submit" name="simpan" class="btn btn-primary mr-2">Simpan Perubahan</button>
                    <button type="button" class="btn btn-light" onclick="window.history.back()">Batal</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card card-danger">
            <div class="card-body">
                <h4 class="card-title text-danger">Reset Data Transaksi</h4>
                <p class="card-description">
                    Hapus semua data transaksi pembayaran. Fitur ini digunakan saat pergantian tahun ajaran baru.
                    <br><strong class="text-danger">PERINGATAN: Data yang dihapus tidak dapat dikembalikan!</strong>
                </p>
                <form method="post" id="form-reset">
                    <button type="button" class="btn btn-danger mr-2" onclick="confirmReset()">Reset Data Transaksi</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmReset() {
    Swal.fire({
        title: 'Apakah Anda Yakin?',
        text: "Semua data transaksi pembayaran akan dihapus permanen! Pastikan Anda sudah melakukan backup/export data terlebih dahulu.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Hapus Semuanya!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            // Create a hidden input to simulate the button click
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'reset_data';
            input.value = 'true';
            document.getElementById('form-reset').appendChild(input);
            document.getElementById('form-reset').submit();
        }
    })
}
</script>

<?php include '../template/footer.php'; ?>