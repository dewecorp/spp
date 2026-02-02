<?php
$title = 'Data Siswa';
include '../template/header.php';
include '../template/sidebar.php';

require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// Proses Import
if (isset($_POST['import'])) {
    $file = $_FILES['file_excel']['tmp_name'];
    $ext = pathinfo($_FILES['file_excel']['name'], PATHINFO_EXTENSION);
    
    if (in_array(strtolower($ext), ['xls', 'xlsx'])) {
        try {
            $spreadsheet = IOFactory::load($file);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();
            
            $success_count = 0;
            
            foreach ($rows as $key => $row) {
                // Skip header
                if ($key == 0) continue;
                
                $nisn = trim($row[0] ?? '');
                $nama = trim($row[1] ?? '');
                $nama_kelas = trim($row[2] ?? '');
                $alamat = trim($row[3] ?? '');
                
                // Validate essential data
                if (empty($nisn) || empty($nama)) continue;
                
                // Defaults
                $nis = '-';
                $no_telp = '';
                
                // Cari ID Kelas
                $q_kelas = mysqli_query($koneksi, "SELECT id_kelas FROM kelas WHERE nama_kelas = '$nama_kelas'");
                if (mysqli_num_rows($q_kelas) > 0) {
                    $d_kelas = mysqli_fetch_assoc($q_kelas);
                    $id_kelas = $d_kelas['id_kelas'];
                    
                    // Cek Duplicate NISN
                    $cek = mysqli_query($koneksi, "SELECT nisn FROM siswa WHERE nisn = '$nisn'");
                    if (mysqli_num_rows($cek) == 0) {
                        $insert = mysqli_query($koneksi, "INSERT INTO siswa (nisn, nis, nama, id_kelas, alamat, no_telp) VALUES ('$nisn', '$nis', '$nama', '$id_kelas', '$alamat', '$no_telp')");
                        if ($insert) $success_count++;
                    }
                }
            }
            
            echo "<script>
                Swal.fire({
                    title: 'Selesai',
                    text: 'Berhasil mengimport $success_count data siswa',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location='siswa.php';
                });
            </script>";
            
            logActivity($koneksi, 'Create', "Import $success_count data siswa via Excel");
            
        } catch (Exception $e) {
            echo "<script>Swal.fire('Gagal', 'Terjadi kesalahan saat membaca file: " . $e->getMessage() . "', 'error');</script>";
        }
    } else {
        echo "<script>Swal.fire('Gagal', 'Format file harus Excel (.xls / .xlsx)', 'error');</script>";
    }
}

// Proses Tambah
if (isset($_POST['tambah'])) {
    $nisn = $_POST['nisn'];
    $nis = '-'; // Default
    $nama = $_POST['nama'];
    $id_kelas = $_POST['id_kelas'];
    $alamat = '-'; // Default
    $no_telp = ''; // Default
    
    // Cek duplikasi NISN
    $cek = mysqli_query($koneksi, "SELECT * FROM siswa WHERE nisn='$nisn'");
    if (mysqli_num_rows($cek) > 0) {
         echo "<script>Swal.fire('Gagal', 'NISN sudah ada!', 'error');</script>";
    } else {
        $query = mysqli_query($koneksi, "INSERT INTO siswa VALUES ('$nisn', '$nis', '$nama', '$id_kelas', '$alamat', '$no_telp')");
        if ($query) {
            logActivity($koneksi, 'Create', "Menambah data siswa baru: $nama ($nisn)");
            echo "<script>
                Swal.fire({
                    title: 'Berhasil',
                    text: 'Data berhasil ditambahkan',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    window.location='siswa.php';
                });
            </script>";
        } else {
            echo "<script>Swal.fire('Gagal', 'Data gagal ditambahkan', 'error');</script>";
        }
    }
}

// Proses Edit
if (isset($_POST['edit'])) {
    $nisn_lama = $_POST['nisn_lama'];
    $nisn = $_POST['nisn'];
    $nama = $_POST['nama'];
    $id_kelas = $_POST['id_kelas'];

    $query = mysqli_query($koneksi, "UPDATE siswa SET nisn='$nisn', nama='$nama', id_kelas='$id_kelas' WHERE nisn='$nisn_lama'");
    if ($query) {
        logActivity($koneksi, 'Update', "Mengubah data siswa: $nama ($nisn)");
        echo "<script>
            Swal.fire({
                title: 'Berhasil',
                text: 'Data berhasil diupdate',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                window.location='siswa.php';
            });
        </script>";
    } else {
        echo "<script>Swal.fire('Gagal', 'Data gagal diupdate', 'error');</script>";
    }
}

// Proses Multi Edit Save
if (isset($_POST['multi_edit_save'])) {
    $nisn_lama = $_POST['nisn_lama'];
    $nisn = $_POST['nisn'];
    $nama = $_POST['nama'];
    $id_kelas = $_POST['id_kelas'];
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($nisn_lama as $key => $old_nisn) {
        $new_nisn = $nisn[$key];
        $new_nama = $nama[$key];
        $new_kelas = $id_kelas[$key];
        
        $query = mysqli_query($koneksi, "UPDATE siswa SET nisn='$new_nisn', nama='$new_nama', id_kelas='$new_kelas' WHERE nisn='$old_nisn'");
        if ($query) {
            $success_count++;
        } else {
            $error_count++;
        }
    }
    
    if ($success_count > 0) {
        logActivity($koneksi, 'Update', "Multi update $success_count data siswa");
        echo "<script>
            Swal.fire({
                title: 'Berhasil',
                text: '$success_count Data berhasil diupdate',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                window.location='siswa.php';
            });
        </script>";
    } else {
        echo "<script>Swal.fire('Gagal', 'Data gagal diupdate', 'error');</script>";
    }
}

// Proses Multi Hapus
if (isset($_POST['multi_hapus'])) {
    if (!empty($_POST['cek_nisn'])) {
        $ids = $_POST['cek_nisn'];
        $jumlah = count($ids);
        $ids_string = implode("','", $ids);
        
        $query = mysqli_query($koneksi, "DELETE FROM siswa WHERE nisn IN ('$ids_string')");
        if ($query) {
            logActivity($koneksi, 'Delete', "Menghapus $jumlah data siswa");
            echo "<script>
                Swal.fire({
                    title: 'Berhasil',
                    text: '$jumlah Data berhasil dihapus',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    window.location='siswa.php';
                });
            </script>";
        } else {
            echo "<script>Swal.fire('Gagal', 'Data gagal dihapus', 'error');</script>";
        }
    } else {
        echo "<script>Swal.fire('Peringatan', 'Tidak ada data yang dipilih', 'warning');</script>";
    }
}

// Proses Hapus
if (isset($_GET['hapus'])) {
    $nisn = $_GET['hapus'];
    $query = mysqli_query($koneksi, "DELETE FROM siswa WHERE nisn='$nisn'");
    if ($query) {
        logActivity($koneksi, 'Delete', "Menghapus data siswa dengan NISN: $nisn");
        echo "<script>
            Swal.fire({
                title: 'Berhasil',
                text: 'Data berhasil dihapus',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                window.location='siswa.php';
            });
        </script>";
    } else {
        echo "<script>Swal.fire('Gagal', 'Data gagal dihapus', 'error');</script>";
    }
}

// Ambil data kelas untuk dropdown
$kelas = mysqli_query($koneksi, "SELECT * FROM kelas ORDER BY nama_kelas ASC");
$data_kelas = [];
while($k = mysqli_fetch_assoc($kelas)) {
    $data_kelas[] = $k;
}

// Logic Filter & Query Data Siswa
$filter_kelas = isset($_GET['kelas']) ? $_GET['kelas'] : '';
$where_sql = "";
if (!empty($filter_kelas)) {
    $where_sql = " WHERE siswa.id_kelas = '$filter_kelas' ";
}

$query_siswa = mysqli_query($koneksi, "SELECT siswa.*, kelas.nama_kelas FROM siswa JOIN kelas ON siswa.id_kelas = kelas.id_kelas $where_sql ORDER BY kelas.nama_kelas ASC, siswa.nama ASC");
$jumlah_siswa = mysqli_num_rows($query_siswa);
?>

<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Data Siswa</h4>
                
                <div class="d-flex justify-content-between mb-3 align-items-center">
                    <div>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
                            <i class="mdi mdi-plus"></i> Tambah Siswa
                        </button>
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalImport">
                            <i class="mdi mdi-file-excel"></i> Import Data
                        </button>
                    </div>
                    
                    <form action="" method="get" class="d-flex align-items-center">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-primary text-white">Filter Kelas</span>
                            </div>
                            <select name="kelas" class="form-control" onchange="this.form.submit()" style="width: 200px;">
                                <option value="">-- Semua Kelas --</option>
                                <?php foreach($data_kelas as $kls) : ?>
                                    <option value="<?= $kls['id_kelas'] ?>" <?= (isset($_GET['kelas']) && $_GET['kelas'] == $kls['id_kelas']) ? 'selected' : '' ?>>
                                        <?= $kls['nama_kelas'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>
                
                <form action="" method="post" id="formMultiHapus">
                    <div class="mb-2 d-flex justify-content-between align-items-center">
                        <div>
                            <button type="button" id="btnMultiHapus" class="btn btn-danger btn-sm">
                                <i class="mdi mdi-delete"></i> Hapus Terpilih
                            </button>
                            <button type="button" id="btnMultiEdit" class="btn btn-warning btn-sm">
                                <i class="mdi mdi-pencil"></i> Edit Terpilih
                            </button>
                        </div>
                        <span class="badge badge-info" style="font-size: 14px;">Total Siswa: <b><?= number_format($jumlah_siswa) ?></b></span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th width="5%">
                                        <div class="form-check m-0">
                                            <label class="form-check-label">
                                                <input type="checkbox" class="form-check-input" id="checkAll">
                                                <i class="input-helper"></i>
                                            </label>
                                        </div>
                                    </th>
                                    <th>No</th>
                                <th>NISN</th>
                                <th>Nama</th>
                                <th>Kelas</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            while ($row = mysqli_fetch_assoc($query_siswa)) :
                            ?>
                                <tr>
                                    <td>
                                        <div class="form-check m-0">
                                            <label class="form-check-label">
                                                <input type="checkbox" class="form-check-input check-item" name="cek_nisn[]" value="<?= $row['nisn'] ?>" data-nama="<?= htmlspecialchars($row['nama']) ?>" data-kelas="<?= $row['id_kelas'] ?>">
                                                <i class="input-helper"></i>
                                            </label>
                                        </div>
                                    </td>
                                    <td><?= $no++ ?></td>
                                    <td><?= $row['nisn'] ?></td>
                                    <td><?= $row['nama'] ?></td>
                                    <td><?= $row['nama_kelas'] ?></td>
                                    <td>
                                        <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $row['nisn'] ?>">
                                            <i class="mdi mdi-pencil"></i>
                                        </button>
                                        <a href="siswa.php?hapus=<?= $row['nisn'] ?>" class="btn btn-danger btn-sm btn-hapus">
                                            <i class="mdi mdi-delete"></i>
                                        </a>
                                    </td>
                                </tr>

                                <!-- Modal Edit -->
                                <div class="modal fade" id="modalEdit<?= $row['nisn'] ?>" tabindex="-1" role="dialog" aria-hidden="true">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Siswa</h5>
                                                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <form action="" method="post">
                                                <div class="modal-body">
                                                    <input type="hidden" name="nisn_lama" value="<?= $row['nisn'] ?>">
                                                    <div class="form-group">
                                                        <label>NISN</label>
                                                        <input type="text" name="nisn" class="form-control" value="<?= $row['nisn'] ?>" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Nama Siswa</label>
                                                        <input type="text" name="nama" class="form-control" value="<?= $row['nama'] ?>" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Kelas</label>
                                                        <select name="id_kelas" class="form-control" required>
                                                            <?php foreach($data_kelas as $kls) : ?>
                                                                <option value="<?= $kls['id_kelas'] ?>" <?= ($kls['id_kelas'] == $row['id_kelas']) ? 'selected' : '' ?>>
                                                                    <?= $kls['nama_kelas'] ?>
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
                </form> <!-- End Form Multi Hapus -->
            </div>
        </div>
    </div>
</div>

<!-- Modal Multi Edit -->
<div class="modal fade" id="modalMultiEdit" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Data Siswa Terpilih</h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="" method="post">
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>NISN</th>
                                    <th>Nama Siswa</th>
                                    <th>Kelas</th>
                                </tr>
                            </thead>
                            <tbody id="multiEditTableBody">
                                <!-- Data will be injected here via JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="multi_edit_save" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Check All functionality
    const checkAll = document.getElementById('checkAll');
    const checkItems = document.querySelectorAll('.check-item');

    if (checkAll) {
        checkAll.addEventListener('change', function() {
            checkItems.forEach(item => {
                item.checked = this.checked;
            });
        });
    }

    // Data Kelas Options untuk JS
    const optionsKelas = `<?php foreach($data_kelas as $kls) { echo '<option value="'.$kls['id_kelas'].'">'.$kls['nama_kelas'].'</option>'; } ?>`;

    // Handle Multi Edit Button
    const btnMultiEdit = document.getElementById('btnMultiEdit');
    const modalMultiEdit = new bootstrap.Modal(document.getElementById('modalMultiEdit'));
    const multiEditTableBody = document.getElementById('multiEditTableBody');

    if (btnMultiEdit) {
        btnMultiEdit.addEventListener('click', function() {
            const checkedItems = document.querySelectorAll('.check-item:checked');
            if (checkedItems.length === 0) {
                Swal.fire('Peringatan', 'Tidak ada data yang dipilih', 'warning');
                return;
            }

            // Clear existing rows
            multiEditTableBody.innerHTML = '';

            let no = 1;
            checkedItems.forEach(item => {
                const nisn = item.value;
                const nama = item.getAttribute('data-nama');
                const idKelas = item.getAttribute('data-kelas');

                const row = `
                    <tr>
                        <td>${no++}</td>
                        <td>
                            <input type="hidden" name="nisn_lama[]" value="${nisn}">
                            <input type="text" name="nisn[]" class="form-control" value="${nisn}" required>
                        </td>
                        <td>
                            <input type="text" name="nama[]" class="form-control" value="${nama}" required>
                        </td>
                        <td>
                            <select name="id_kelas[]" class="form-control" required>
                                ${optionsKelas}
                            </select>
                        </td>
                    </tr>
                `;
                
                // Insert row
                multiEditTableBody.insertAdjacentHTML('beforeend', row);
                
                // Set selected value for dropdown
                const lastRow = multiEditTableBody.lastElementChild;
                const select = lastRow.querySelector('select');
                select.value = idKelas;
            });

            // Show Modal
            modalMultiEdit.show();
        });
    }

    // SweetAlert untuk Multi Hapus
    const btnMultiHapus = document.getElementById('btnMultiHapus');
    if (btnMultiHapus) {
        btnMultiHapus.addEventListener('click', function(e) {
            e.preventDefault();
            const checkedItems = document.querySelectorAll('.check-item:checked');
            if (checkedItems.length === 0) {
                Swal.fire('Peringatan', 'Tidak ada data yang dipilih', 'warning');
                return;
            }

            Swal.fire({
                title: 'Konfirmasi',
                text: "Yakin ingin menghapus " + checkedItems.length + " data terpilih?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.getElementById('formMultiHapus');
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'multi_hapus';
                    input.value = '1';
                    form.appendChild(input);
                    form.submit();
                }
            })
        });
    }
});
</script>

<!-- Modal Tambah -->
<div class="modal fade" id="modalTambah" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Siswa</h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close" style="background: transparent; border: none;">
                    <i class="mdi mdi-close"></i>
                </button>
            </div>
            <form action="" method="post">
                <div class="modal-body">
                    <div class="form-group">
                        <label>NISN</label>
                        <input type="text" name="nisn" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Nama Siswa</label>
                        <input type="text" name="nama" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Kelas</label>
                        <select name="id_kelas" class="form-control" required>
                            <option value="">-- Pilih Kelas --</option>
                            <?php foreach($data_kelas as $kls) : ?>
                                <option value="<?= $kls['id_kelas'] ?>"><?= $kls['nama_kelas'] ?></option>
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

<!-- Modal Import -->
<div class="modal fade" id="modalImport" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import Data Siswa</h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close" style="background: transparent; border: none;">
                    <i class="mdi mdi-close"></i>
                </button>
            </div>
            <form id="formImport" action="" method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="alert alert-info">
                        Gunakan file template Excel berikut untuk mengimport data: <br>
                        <a href="template_siswa.xlsx" class="btn btn-sm btn-success mt-2">
                            <i class="mdi mdi-download"></i> Download Template
                        </a>
                    </div>
                    <div class="form-group">
                        <label>File Excel</label>
                        <input type="file" name="file_excel" id="file_excel" class="form-control" accept=".xls, .xlsx" required>
                    </div>
                    <!-- Progress Bar -->
                    <div class="progress d-none" id="progressContainer" style="height: 20px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" 
                             style="width: 0%;" id="progressBar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="import" class="btn btn-primary" onclick="startProgress()">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function startProgress() {
    var fileInput = document.getElementById('file_excel');
    if(fileInput.files.length > 0) {
        document.getElementById('progressContainer').classList.remove('d-none');
        var bar = document.getElementById('progressBar');
        var width = 0;
        var interval = setInterval(function() {
            if (width >= 90) {
                clearInterval(interval);
            } else {
                width++;
                bar.style.width = width + '%';
                bar.innerText = width + '%';
            }
        }, 50); // Simulate progress
    }
}
</script>

<?php include '../template/footer.php'; ?>
