<?php
$title = 'Backup & Restore';
include '../template/header.php';
include '../template/sidebar.php';

$backup_dir = "../backup_db/";

// Handler Hapus
if (isset($_GET['aksi']) && $_GET['aksi'] == 'hapus' && isset($_GET['file'])) {
    $file = $_GET['file'];
    if (file_exists($backup_dir . $file)) {
        unlink($backup_dir . $file);
        logActivity($koneksi, 'Delete', "Menghapus file backup: $file");
        echo "<script>
            Swal.fire({
                title: 'Berhasil!',
                text: 'File backup berhasil dihapus.',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                window.location = 'backup_restore.php?v=1';
            });
        </script>";
    }
}

// Handler Download
if (isset($_GET['aksi']) && $_GET['aksi'] == 'download' && isset($_GET['file'])) {
    $file = $_GET['file'];
    $filepath = $backup_dir . $file;
    if (file_exists($filepath)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($filepath).'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    }
}

// Handler Backup
if (isset($_POST['backup'])) {
    // Generate SQL
    $tables = array();
    $result = mysqli_query($koneksi, "SHOW TABLES");
    while ($row = mysqli_fetch_row($result)) {
        $tables[] = $row[0];
    }
    
    $return = "";
    foreach ($tables as $table) {
        $result = mysqli_query($koneksi, "SELECT * FROM " . $table);
        $num_fields = mysqli_num_fields($result);
        
        $return .= "DROP TABLE IF EXISTS " . $table . ";";
        $row2 = mysqli_fetch_row(mysqli_query($koneksi, "SHOW CREATE TABLE " . $table));
        $return .= "\n\n" . $row2[1] . ";\n\n";
        
        for ($i = 0; $i < $num_fields; $i++) {
            while ($row = mysqli_fetch_row($result)) {
                $return .= "INSERT INTO " . $table . " VALUES(";
                for ($j = 0; $j < $num_fields; $j++) {
                    if (isset($row[$j])) {
                        $row[$j] = addslashes($row[$j]);
                        $row[$j] = preg_replace("/\n/", "\\n", $row[$j]);
                        $return .= '"' . $row[$j] . '"';
                    } else {
                        $return .= '""';
                    }
                    if ($j < ($num_fields - 1)) {
                        $return .= ',';
                    }
                }
                $return .= ");\n";
            }
        }
        $return .= "\n\n\n";
    }
    
    $filename = 'backup_' . date("Y-m-d_H-i-s") . '.sql';
    $handle = fopen($backup_dir . $filename, 'w+');
    fwrite($handle, $return);
    fclose($handle);
    
    logActivity($koneksi, 'Create', "Membuat backup database: $filename");

    echo "<script>
        Swal.fire({
            title: 'Proses Backup...',
            text: 'Sedang membuat backup database',
            icon: 'info',
            timer: 1000,
            showConfirmButton: false,
            willClose: () => {
                Swal.fire({
                    title: 'Berhasil!',
                    text: 'Backup database berhasil dibuat.',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    window.location = 'backup_restore.php';
                });
            }
        });
    </script>";
}

// Handler Restore
if (isset($_POST['restore'])) {
    if ($_FILES['file_sql']['error'] == 0) {
        $filename = $_FILES['file_sql']['tmp_name'];
        $handle = fopen($filename, "r");
        $contents = fread($handle, filesize($filename));
        fclose($handle);
        
        if (mysqli_multi_query($koneksi, $contents)) {
             do {
                if ($result = mysqli_store_result($koneksi)) {
                    mysqli_free_result($result);
                }
            } while (mysqli_more_results($koneksi) && mysqli_next_result($koneksi));
            
            echo "<script>
                Swal.fire({
                    title: 'Berhasil!',
                    text: 'Database berhasil direstore.',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    window.location = 'backup_restore.php';
                });
            </script>";
        } else {
             echo "<script>
                Swal.fire({
                    title: 'Gagal!',
                    text: 'Terjadi kesalahan saat restore: " . mysqli_error($koneksi) . "',
                    icon: 'error'
                });
            </script>";
        }
    }
}
?>

<!-- HTML Content -->
        <div class="page-header">
            <h3 class="page-title"> Backup & Restore Database </h3>
        </div>
        
        <div class="row">
            <!-- Backup Box -->
            <div class="col-md-6 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Backup Database</h4>
                        <p class="card-description">Klik tombol di bawah untuk membackup seluruh database.</p>
                        <form method="post">
                            <button type="submit" name="backup" class="btn btn-primary me-2">
                                <i class="mdi mdi-cloud-download"></i> Proses Backup
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Restore Box -->
            <div class="col-md-6 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Restore Database</h4>
                        <p class="card-description">Upload file .sql untuk merestore database.</p>
                        <form method="post" enctype="multipart/form-data">
                            <div class="form-group">
                                <input type="file" name="file_sql" class="form-control" accept=".sql" required>
                            </div>
                            <button type="submit" name="restore" class="btn btn-danger">
                                <i class="mdi mdi-cloud-upload"></i> Restore Database
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- File List Table -->
        <div class="row">
            <div class="col-lg-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Daftar File Backup</h4>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama File Backup</th>
                                        <th>Ukuran</th>
                                        <th>Tanggal</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if (file_exists($backup_dir)) {
                                        $files = scandir($backup_dir, SCANDIR_SORT_DESCENDING);
                                        $no = 1;
                                        foreach ($files as $file) {
                                            if ($file != '.' && $file != '..' && pathinfo($file, PATHINFO_EXTENSION) == 'sql') {
                                                $filepath = $backup_dir . $file;
                                                $filesize = filesize($filepath);
                                                $size_formatted = number_format($filesize / 1024, 2) . ' KB';
                                                $filetime = date("d-m-Y H:i:s", filemtime($filepath));
                                                ?>
                                                <tr>
                                                    <td><?= $no++ ?></td>
                                                    <td><?= $file ?></td>
                                                    <td><?= $size_formatted ?></td>
                                                    <td><?= $filetime ?></td>
                                                    <td>
                                                        <a href="?aksi=download&file=<?= $file ?>" class="btn btn-success btn-sm">
                                                            <i class="mdi mdi-download"></i> Unduh
                                                        </a>
                                                        <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete('<?= $file ?>')">
                                                            <i class="mdi mdi-delete"></i> Hapus
                                                        </button>
                                                    </td>
                                                </tr>
                                                <?php
                                            }
                                        }
                                    } else {
                                        echo "<tr><td colspan='5' class='text-center'>Folder backup belum ada.</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    
    <script>
    function confirmDelete(file) {
        Swal.fire({
            title: 'Apakah anda yakin?',
            text: "File backup " + file + " akan dihapus permanen!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location = 'backup_restore.php?v=1&aksi=hapus&file=' + file;
            }
        })
    }
    </script>
<?php include '../template/footer.php'; ?>