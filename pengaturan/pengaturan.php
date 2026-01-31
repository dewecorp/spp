<?php
include '../template/header.php';
include '../template/sidebar.php';

// Ambil data pengaturan
$query = mysqli_query($koneksi, "SELECT * FROM pengaturan WHERE id_pengaturan = 1");
$data = mysqli_fetch_assoc($query);

if (isset($_POST['simpan'])) {
    $nama_sekolah = $_POST['nama_sekolah'];
    $alamat_sekolah = $_POST['alamat_sekolah'];
    
    // Upload Logo (Optional implementation)
    // Jika ada file logo yang diupload...
    
    $query_update = "UPDATE pengaturan SET nama_sekolah='$nama_sekolah', alamat_sekolah='$alamat_sekolah' WHERE id_pengaturan = 1";
    
    if (mysqli_query($koneksi, $query_update)) {
        echo "<script>
            Swal.fire({
                title: 'Berhasil',
                text: 'Pengaturan sekolah berhasil diperbarui',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                window.location.href = 'pengaturan.php';
            });
        </script>";
    } else {
        echo "<script>Swal.fire('Gagal', 'Gagal memperbarui pengaturan', 'error');</script>";
    }
}
?>

<div class="row">
    <div class="col-md-6 grid-margin stretch-card offset-md-3">
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
                    <!-- 
                    <div class="form-group">
                        <label>Logo Sekolah</label>
                        <input type="file" name="img[]" class="file-upload-default">
                        <div class="input-group col-xs-12">
                            <input type="text" class="form-control file-upload-info" disabled placeholder="Upload Image">
                            <span class="input-group-append">
                                <button class="file-upload-browse btn btn-primary" type="button">Upload</button>
                            </span>
                        </div>
                    </div>
                    -->
                    <button type="submit" name="simpan" class="btn btn-primary mr-2">Simpan Perubahan</button>
                    <button type="button" class="btn btn-light" onclick="window.history.back()">Batal</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../template/footer.php'; ?>