<?php
$title = 'Data Siswa';
include '../template/header.php';
include '../template/sidebar.php';

// Autoload for PHP 7.4 compatibility
if (file_exists(__DIR__ . '/../vendor/autoload_simple.php')) {
    require __DIR__ . '/../vendor/autoload_simple.php';
} elseif (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
}

use PhpOffice\PhpSpreadsheet\IOFactory;

// --- SINKRONISASI OTOMATIS KOLOM DATABASE ---
$columns_to_add = [
    'jenis_kelamin' => "VARCHAR(20) DEFAULT '-'",
    'tempat_lahir'  => "VARCHAR(100) DEFAULT '-'",
    'tgl_lahir'     => "DATE DEFAULT '1900-01-01'",
    'nama_wali'     => "VARCHAR(100) DEFAULT '-'"
];

foreach ($columns_to_add as $col => $type) {
    $check_col = mysqli_query($koneksi, "SHOW COLUMNS FROM siswa LIKE '$col'");
    if (mysqli_num_rows($check_col) == 0) {
        mysqli_query($koneksi, "ALTER TABLE siswa ADD $col $type");
    }
}

function pastikan_kelas_alumni($koneksi) {
    $q_alumni = mysqli_query($koneksi, "SELECT id_kelas FROM kelas WHERE LOWER(nama_kelas) = 'alumni' LIMIT 1");
    if ($q_alumni && mysqli_num_rows($q_alumni) > 0) {
        $d_alumni = mysqli_fetch_assoc($q_alumni);
        return (int) $d_alumni['id_kelas'];
    }

    mysqli_query($koneksi, "INSERT INTO kelas (nama_kelas) VALUES ('Alumni')");
    return (int) mysqli_insert_id($koneksi);
}

function kelas_id_valid($koneksi, $id_kelas) {
    $id_kelas_raw = trim((string) $id_kelas);
    if ($id_kelas_raw === '' || !ctype_digit($id_kelas_raw)) {
        return false;
    }

    $id_kelas = (int) $id_kelas_raw;
    $q_kelas = mysqli_query($koneksi, "SELECT id_kelas FROM kelas WHERE id_kelas = '$id_kelas' LIMIT 1");
    return $q_kelas && mysqli_num_rows($q_kelas) > 0;
}

pastikan_kelas_alumni($koneksi);

// Proses Sinkronisasi Simad
// Logic moved to ajax_sinkron_simad.php

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
            $duplicate_count = 0;
            $missing_class_count = 0;
            $invalid_count = 0;
            $failed_insert_count = 0;

            foreach ($rows as $key => $row) {
                // Skip header
                if ($key == 0) continue;

                $nisn = mysqli_real_escape_string($koneksi, trim($row[0] ?? ''));
                $nama = mysqli_real_escape_string($koneksi, trim($row[1] ?? ''));
                $nama_kelas = mysqli_real_escape_string($koneksi, trim($row[2] ?? ''));
                $alamat = mysqli_real_escape_string($koneksi, trim($row[3] ?? ''));

                // Validate essential data
                if (empty($nisn) || empty($nama)) {
                    $invalid_count++;
                    continue;
                }

                // Defaults
                $no_telp = '';

                // Cari ID Kelas
                $q_kelas = mysqli_query($koneksi, "SELECT id_kelas FROM kelas WHERE nama_kelas = '$nama_kelas'");
                if (mysqli_num_rows($q_kelas) > 0) {
                    $d_kelas = mysqli_fetch_assoc($q_kelas);
                    $id_kelas = $d_kelas['id_kelas'];

                    // Cek Duplicate NISN
                    $cek = mysqli_query($koneksi, "SELECT nisn FROM siswa WHERE nisn = '$nisn'");
                    if (mysqli_num_rows($cek) == 0) {
                        $insert = mysqli_query($koneksi, "INSERT INTO siswa (nisn, nis, nama, id_kelas, alamat, no_telp, jenis_kelamin, tempat_lahir, tgl_lahir, nama_wali)
                            VALUES ('$nisn', '-', '$nama', '$id_kelas', '$alamat', '$no_telp', '-', '-', '1900-01-01', '-')");
                        if ($insert) {
                            $success_count++;
                        } else {
                            $failed_insert_count++;
                        }
                    } else {
                        $duplicate_count++;
                    }
                } else {
                    $missing_class_count++;
                }
            }

            $failed_count = $failed_insert_count;

            echo "<script>
                Swal.fire({
                    title: 'Selesai',
                    html: 'Import selesai.<br>' +
                          'Berhasil: <b>$success_count</b><br>' +
                          'Gagal: <b>$failed_count</b>',
                    icon: 'success',
                    timer: 4000,
                    showConfirmButton: false
                }).then(() => {
                    window.location='siswa.php';
                });
            </script>";

            logActivity($koneksi, 'Create', "Import $success_count siswa (gagal insert: $failed_count) via Excel");

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
    $nama = $_POST['nama'];
    $id_kelas = $_POST['id_kelas'];
    $gender = $_POST['jenis_kelamin'];
    $tempat = $_POST['tempat_lahir'];
    $tgl = $_POST['tgl_lahir'];
    $wali = $_POST['nama_wali'];
    $alamat = '-'; // Default
    $no_telp = ''; // Default

    // Cek duplikasi NISN
    $cek = mysqli_query($koneksi, "SELECT * FROM siswa WHERE nisn='$nisn'");
    if (mysqli_num_rows($cek) > 0) {
         echo "<script>Swal.fire('Gagal', 'NISN sudah ada!', 'error');</script>";
    } else {
        $query = mysqli_query($koneksi, "INSERT INTO siswa (nisn, nis, nama, id_kelas, alamat, no_telp, jenis_kelamin, tempat_lahir, tgl_lahir, nama_wali)
            VALUES ('$nisn', '-', '$nama', '$id_kelas', '$alamat', '$no_telp', '$gender', '$tempat', '$tgl', '$wali')");
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
    $nisn_lama = trim((string)$_POST['nisn_lama']);
    $nisn = trim((string)$_POST['nisn']);
    $nama = trim((string)$_POST['nama']);
    $id_kelas = (int)$_POST['id_kelas'];
    $gender = trim((string)$_POST['jenis_kelamin']);
    $tempat = trim((string)$_POST['tempat_lahir']);
    $tgl = trim((string)$_POST['tgl_lahir']);
    $wali = trim((string)$_POST['nama_wali']);

    $stmt = mysqli_prepare($koneksi, "UPDATE siswa SET
        nisn = ?,
        nama = ?,
        id_kelas = ?,
        jenis_kelamin = ?,
        tempat_lahir = ?,
        tgl_lahir = ?,
        nama_wali = ?
        WHERE nisn = ?");

    $query = false;
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'ssisssss', $nisn, $nama, $id_kelas, $gender, $tempat, $tgl, $wali, $nisn_lama);
        $query = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

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
        $error_update = htmlspecialchars(mysqli_error($koneksi) ?: 'Data gagal diupdate', ENT_QUOTES);
        echo "<script>Swal.fire('Gagal', '$error_update', 'error');</script>";
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
    $error_messages = [];

    $stmt_multi_edit = mysqli_prepare($koneksi, "UPDATE siswa SET nama = ?, id_kelas = ? WHERE nisn = ?");
    $stmt_multi_edit_nisn = mysqli_prepare($koneksi, "UPDATE siswa SET nisn = ?, nama = ?, id_kelas = ? WHERE nisn = ?");

    foreach ($nisn_lama as $key => $old_nisn) {
        $old_nisn = trim((string)$old_nisn);
        $new_nisn = trim((string)($nisn[$key] ?? ''));
        $new_nama = trim((string)($nama[$key] ?? ''));
        $new_kelas_raw = trim((string)($id_kelas[$key] ?? ''));
        $new_kelas = $new_kelas_raw !== '' && ctype_digit($new_kelas_raw) ? (int)$new_kelas_raw : -1;

        if ($old_nisn === '' || $new_nisn === '' || $new_nama === '' || !kelas_id_valid($koneksi, $new_kelas)) {
            $error_count++;
            if (count($error_messages) < 3) {
                $error_messages[] = "Data tidak valid: $old_nisn";
            }
            continue;
        }

        if ($new_nisn === $old_nisn) {
            if (!$stmt_multi_edit) {
                $error_count++;
                if (count($error_messages) < 3) {
                    $error_messages[] = mysqli_error($koneksi) ?: 'Query update kelas gagal disiapkan';
                }
                continue;
            }

            mysqli_stmt_bind_param($stmt_multi_edit, 'sis', $new_nama, $new_kelas, $old_nisn);
            $execute_ok = mysqli_stmt_execute($stmt_multi_edit);
            $execute_error = mysqli_stmt_error($stmt_multi_edit);
        } else {
            $new_nisn_esc = mysqli_real_escape_string($koneksi, $new_nisn);
            $old_nisn_esc = mysqli_real_escape_string($koneksi, $old_nisn);
            $q_duplikat = mysqli_query($koneksi, "SELECT nisn FROM siswa WHERE nisn = '$new_nisn_esc' AND nisn <> '$old_nisn_esc' LIMIT 1");
            if ($q_duplikat && mysqli_num_rows($q_duplikat) > 0) {
                $error_count++;
                if (count($error_messages) < 3) {
                    $error_messages[] = "NISN $new_nisn sudah digunakan";
                }
                continue;
            }

            if (!$stmt_multi_edit_nisn) {
                $error_count++;
                if (count($error_messages) < 3) {
                    $error_messages[] = mysqli_error($koneksi) ?: 'Query update NISN gagal disiapkan';
                }
                continue;
            }

            mysqli_stmt_bind_param($stmt_multi_edit_nisn, 'ssis', $new_nisn, $new_nama, $new_kelas, $old_nisn);
            $execute_ok = mysqli_stmt_execute($stmt_multi_edit_nisn);
            $execute_error = mysqli_stmt_error($stmt_multi_edit_nisn);
        }

        if ($execute_ok) {
            $success_count++;
        } else {
            $error_count++;
            if (count($error_messages) < 3) {
                $error_messages[] = $execute_error ?: mysqli_error($koneksi) ?: "Gagal update NISN $old_nisn";
            }
        }
    }

    if ($stmt_multi_edit) {
        mysqli_stmt_close($stmt_multi_edit);
    }
    if ($stmt_multi_edit_nisn) {
        mysqli_stmt_close($stmt_multi_edit_nisn);
    }

    if ($success_count > 0) {
        logActivity($koneksi, 'Update', "Multi update $success_count data siswa");
        $message_multi_edit = $error_count > 0 ? "$success_count data berhasil diupdate, $error_count data gagal." : "$success_count Data berhasil diupdate";
        echo "<script>
            Swal.fire({
                title: 'Berhasil',
                text: '$message_multi_edit',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                window.location='siswa.php';
            });
        </script>";
    } else {
        $error_detail = !empty($error_messages) ? htmlspecialchars(implode(' | ', $error_messages), ENT_QUOTES) : 'Data gagal diupdate';
        echo "<script>Swal.fire('Gagal', '$error_detail', 'error');</script>";
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
$filter_kelas = isset($_GET['kelas']) ? trim((string)$_GET['kelas']) : '';
$where_sql = "";
if ($filter_kelas !== '') {
    $filter_kelas_esc = mysqli_real_escape_string($koneksi, $filter_kelas);
    $where_sql = " WHERE siswa.id_kelas = '$filter_kelas_esc' ";
}

$query_siswa = mysqli_query($koneksi, "SELECT siswa.*, kelas.nama_kelas FROM siswa JOIN kelas ON siswa.id_kelas = kelas.id_kelas $where_sql ORDER BY kelas.nama_kelas ASC, siswa.nama ASC");
$jumlah_siswa = mysqli_num_rows($query_siswa);
?>

<div class="app-grid">
    <div class="app-col-full app-section-gap app-stretch">
        <div class="app-panel">
            <div class="app-panel-body">
                <h4 class="app-panel-title">Data Siswa</h4>

                <div class="toolbar flex justify-between mb-3 items-center">
                    <div>
                        <button type="button" class="app-button app-button-primary" data-tailwind-modal-target="#modalTambah">
                            <i class="mdi mdi-plus"></i> Tambah Siswa
                        </button>
                        <button type="button" class="app-button app-button-success" data-tailwind-modal-target="#modalImport">
                            <i class="mdi mdi-file-excel"></i> Import Data
                        </button>
                        <form action="" method="post" style="display: inline;" id="formSinkronSimad">
                            <button type="submit" name="sinkron_simad" class="app-button app-button-info" id="btnSinkronSimad">
                                <i class="mdi mdi-sync"></i> Sinkron Simad
                            </button>
                        </form>
                    </div>

                    <form action="" method="get" class="flex items-center">
                        <select name="kelas" class="app-control filter-kelas" onchange="this.form.submit()" style="width: 250px;">
                            <option value="">-- Semua Kelas --</option>
                            <?php foreach($data_kelas as $kls) : ?>
                                <option value="<?= $kls['id_kelas'] ?>" <?= ($filter_kelas !== '' && $filter_kelas == $kls['id_kelas']) ? 'selected' : '' ?>>
                                    <?= $kls['nama_kelas'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>

                <form action="" method="post" id="formMultiHapus">
                    <div class="toolbar-secondary mb-2 flex justify-between items-center">
                        <div>
                            <button type="button" id="btnMultiHapus" class="app-button app-button-danger app-button-sm">
                                <i class="mdi mdi-delete"></i> Hapus Terpilih
                            </button>
                            <button type="button" id="btnMultiEdit" class="app-button app-button-warning app-button-sm">
                                <i class="mdi mdi-pencil"></i> Edit Terpilih
                            </button>
                        </div>
                        <span class="app-badge app-badge-info" style="font-size: 14px;">Total Siswa: <b><?= number_format($jumlah_siswa) ?></b></span>
                    </div>

                    <div class="app-table-scroll dt-wrap-siswa">
                        <table class="app-data-table app-table-striped w-full" id="table-siswa" data-dt-scroll-x="1">
                            <thead>
                                <tr>
                                    <th width="5%">
                                        <div class="form-check m-0">
                                            <label class="form-check-label">
                                                <input type="checkbox" class="app-checkbox" id="checkAll">
                                                <i class="input-helper"></i>
                                            </label>
                                        </div>
                                    </th>
                                    <th>No</th>
                                <th>NISN</th>
                                <th>Nama</th>
                                <th>L/P</th>
                                <th>Kelas</th>
                                <th>Wali</th>
                                <th class="aksi-col dt-nowrap text-right" style="min-width:110px;">Aksi</th>
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
                                                <input type="checkbox" class="app-checkbox check-item" name="cek_nisn[]" value="<?= $row['nisn'] ?>" data-nama="<?= htmlspecialchars($row['nama']) ?>" data-kelas="<?= $row['id_kelas'] ?>">
                                                <i class="input-helper"></i>
                                            </label>
                                        </div>
                                    </td>
                                    <td><?= $no++ ?></td>
                                    <td><?= $row['nisn'] ?></td>
                                    <td><?= $row['nama'] ?></td>
                                    <td><?= $row['jenis_kelamin'] ?></td>
                                    <td><?= $row['nama_kelas'] ?></td>
                                    <td><?= $row['nama_wali'] ?></td>
                                    <td class="aksi-col dt-nowrap text-right">
                                        <button type="button" class="app-button app-button-warning app-button-sm" data-tailwind-modal-target="#modalEdit<?= $row['nisn'] ?>">
                                            <i class="mdi mdi-pencil"></i>
                                        </button>
                                        <a href="siswa.php?hapus=<?= $row['nisn'] ?>" class="app-button app-button-danger app-button-sm btn-hapus">
                                            <i class="mdi mdi-delete"></i>
                                        </a>
                                    </td>
                                </tr>

                                <!-- Modal Edit -->
                                <div class="fixed inset-0 z-[1055] hidden overflow-y-auto bg-slate-950/60 px-4 py-6 backdrop-blur-sm" id="modalEdit<?= $row['nisn'] ?>" data-tailwind-modal tabindex="-1" role="dialog" aria-hidden="true">
                                    <div class="mx-auto flex min-h-full w-full max-w-2xl items-start">
                                        <div class="flex max-h-[calc(100vh-3rem)] w-full flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl">
                                            <div class="flex shrink-0 items-center justify-between border-b border-slate-200 px-6 py-4">
                                                <h5 class="text-base font-extrabold text-slate-900">Edit Siswa</h5>
                                                <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-100 hover:text-slate-900" data-tailwind-modal-close aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <form action="" method="post">
                                                <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
                                                    <input type="hidden" name="nisn_lama" value="<?= $row['nisn'] ?>">
                                                    <div class="app-field">
                                                        <label>NISN</label>
                                                        <input type="text" name="nisn" class="app-control" value="<?= $row['nisn'] ?>" required>
                                                    </div>
                                                    <div class="app-field">
                                                        <label>Nama Siswa</label>
                                                        <input type="text" name="nama" class="app-control" value="<?= $row['nama'] ?>" required>
                                                    </div>
                                                    <div class="app-grid">
                                                        <div class="app-col-half">
                                                            <div class="app-field">
                                                                <label>L/P</label>
                                                                <select name="jenis_kelamin" class="app-control">
                                                                    <option value="L" <?= ($row['jenis_kelamin'] == 'L') ? 'selected' : '' ?>>Laki-laki</option>
                                                                    <option value="P" <?= ($row['jenis_kelamin'] == 'P') ? 'selected' : '' ?>>Perempuan</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="app-col-half">
                                                            <div class="app-field">
                                                                <label>Kelas</label>
                                                                <select name="id_kelas" class="app-control" required>
                                                                    <?php foreach($data_kelas as $kls) : ?>
                                                                        <option value="<?= $kls['id_kelas'] ?>" <?= ($kls['id_kelas'] == $row['id_kelas']) ? 'selected' : '' ?>>
                                                                            <?= $kls['nama_kelas'] ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="app-grid">
                                                        <div class="app-col-half">
                                                            <div class="app-field">
                                                                <label>Tempat Lahir</label>
                                                                <input type="text" name="tempat_lahir" class="app-control" value="<?= $row['tempat_lahir'] ?>">
                                                            </div>
                                                        </div>
                                                        <div class="app-col-half">
                                                            <div class="app-field">
                                                                <label>Tgl Lahir</label>
                                                                <input type="date" name="tgl_lahir" class="app-control" value="<?= $row['tgl_lahir'] ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="app-field">
                                                        <label>Nama Wali</label>
                                                        <input type="text" name="nama_wali" class="app-control" value="<?= $row['nama_wali'] ?>">
                                                    </div>
                                                </div>
                                                <div class="flex shrink-0 items-center justify-end gap-3 border-t border-slate-200 px-6 py-4">
                                                    <button type="button" class="app-button app-button-secondary" data-tailwind-modal-close>Batal</button>
                                                    <button type="submit" name="edit" class="app-button app-button-primary">Simpan</button>
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
<div class="fixed inset-0 z-[1055] hidden overflow-y-auto bg-slate-950/60 px-4 py-6 backdrop-blur-sm" id="modalMultiEdit" tabindex="-1" role="dialog" aria-hidden="true" data-tailwind-modal>
    <div class="mx-auto flex min-h-full w-full max-w-4xl items-start">
        <div class="flex max-h-[calc(100vh-3rem)] w-full flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl">
            <div class="flex shrink-0 items-center justify-between border-b border-slate-200 px-6 py-4">
                <h5 class="text-base font-extrabold text-slate-900">Edit Data Siswa Terpilih</h5>
                <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-100 hover:text-slate-900" data-tailwind-modal-close aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="" method="post" class="flex min-h-0 flex-1 flex-col">
                <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
                    <div class="app-table-scroll">
                        <table class="app-data-table app-table-bordered">
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
                <div class="flex shrink-0 items-center justify-end gap-3 border-t border-slate-200 bg-white px-6 py-4">
                    <button type="button" class="app-button app-button-secondary" data-tailwind-modal-close>Batal</button>
                    <button type="submit" name="multi_edit_save" class="app-button app-button-primary">Simpan Perubahan</button>
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
    const optionsKelas = `<?php foreach($data_kelas as $kls) { echo '<option value="'.htmlspecialchars($kls['id_kelas'], ENT_QUOTES).'">'.htmlspecialchars($kls['nama_kelas'], ENT_QUOTES).'</option>'; } ?>`;

    // Handle Multi Edit Button
    const btnMultiEdit = document.getElementById('btnMultiEdit');
    const multiEditTableBody = document.getElementById('multiEditTableBody');
    const escapeAttribute = function(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    };

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
                const safeNisn = escapeAttribute(nisn);
                const safeNama = escapeAttribute(nama);

                const row = `
                    <tr>
                        <td>${no++}</td>
                        <td>
                            <input type="hidden" name="nisn_lama[]" value="${safeNisn}">
                            <input type="text" name="nisn[]" class="app-control" value="${safeNisn}" required>
                        </td>
                        <td>
                            <input type="text" name="nama[]" class="app-control" value="${safeNama}" required>
                        </td>
                        <td>
                            <select name="id_kelas[]" class="app-control" required>
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
            AppModal.open('#modalMultiEdit');
        });
    }

    // SweetAlert untuk Sinkron Simad
    const btnSinkronSimad = document.getElementById('btnSinkronSimad');
    if (btnSinkronSimad) {
        btnSinkronSimad.addEventListener('click', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Konfirmasi Sinkronisasi',
                text: 'Apakah Anda yakin ingin menyinkronkan data dengan Simad? Data lokal akan diperbarui.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Sinkronkan!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading
                    Swal.fire({
                        title: 'Sedang Sinkronisasi...',
                        html: 'Mohon tunggu sebentar, sedang mengambil data dari Simad.<br>Proses ini mungkin memakan waktu beberapa menit jika data banyak.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading()
                        }
                    });

                    // AJAX Request
                    const formData = new FormData();
                    formData.append('sinkron_simad', '1');

                    fetch('ajax_sinkron_simad.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                             throw new Error('HTTP error! status: ' + response.status);
                        }
                        return response.text(); // Ambil sebagai teks dulu untuk cek format
                    })
                    .then(text => {
                        try {
                            const data = JSON.parse(text);
                            if (data.status === 'success') {
                                Swal.fire({
                                    title: 'Sinkronisasi Selesai',
                                    html: `<div class="text-center"><i class="mdi mdi-check-circle-outline text-emerald-600" style="font-size: 50px;"></i><br>
                                          Proses sinkronisasi telah selesai.<br>
                                          <span class="app-badge app-badge-success">Total Data Simad: ${data.total_api}</span><br>
                                           <span class="app-badge app-badge-primary">Berhasil (Baru/Update): ${data.new + data.update}</span>
                                           <span class="app-badge app-badge-danger">Gagal: ${data.failed}</span></div>`,
                                    icon: 'success',
                                    confirmButtonText: 'Mantap!',
                                    confirmButtonColor: '#3085d6'
                                }).then(() => {
                                    window.location.reload();
                                });
                            } else {
                                Swal.fire('Gagal Sinkronisasi', data.message, 'error');
                            }
                        } catch (e) {
                            console.error('Format JSON tidak valid:', text);
                            Swal.fire('Gagal Format Data', 'Respon server bukan format JSON yang valid. Silakan hubungi pengembang atau cek log server.', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire('Error Sistem', 'Terjadi kesalahan saat memproses sinkronisasi. Silakan coba lagi.', 'error');
                    });
                }
            })
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

    // SweetAlert untuk Hapus satu siswa
    const singleDeleteButtons = document.querySelectorAll('.btn-hapus');
    if (singleDeleteButtons.length > 0) {
        singleDeleteButtons.forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const href = this.getAttribute('href');
                Swal.fire({
                    title: 'Konfirmasi',
                    text: 'Yakin ingin menghapus data siswa ini?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Hapus!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = href;
                    }
                });
            });
        });
    }
});
</script>

<!-- Modal Tambah -->
<div class="fixed inset-0 z-[1055] hidden overflow-y-auto bg-slate-950/60 px-4 py-6 backdrop-blur-sm" id="modalTambah" tabindex="-1" role="dialog" aria-hidden="true" data-tailwind-modal>
    <div class="mx-auto flex min-h-full w-full max-w-2xl items-start">
        <div class="flex max-h-[calc(100vh-3rem)] w-full flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl">
            <div class="flex shrink-0 items-center justify-between border-b border-slate-200 px-6 py-4">
                <h5 class="text-base font-extrabold text-slate-900">Tambah Siswa</h5>
                <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-100 hover:text-slate-900" data-tailwind-modal-close aria-label="Close" style="background: transparent; border: none;">
                    <i class="mdi mdi-close"></i>
                </button>
            </div>
            <form action="" method="post">
                <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
                    <div class="app-field">
                        <label>NISN</label>
                        <input type="text" name="nisn" class="app-control" required>
                    </div>
                    <div class="app-field">
                        <label>Nama Siswa</label>
                        <input type="text" name="nama" class="app-control" required>
                    </div>
                    <div class="app-grid">
                        <div class="app-col-half">
                            <div class="app-field">
                                <label>L/P</label>
                                <select name="jenis_kelamin" class="app-control">
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                            </div>
                        </div>
                        <div class="app-col-half">
                            <div class="app-field">
                                <label>Kelas</label>
                                <select name="id_kelas" class="app-control" required>
                                    <option value="">-- Pilih Kelas --</option>
                                    <?php foreach($data_kelas as $kls) : ?>
                                        <option value="<?= $kls['id_kelas'] ?>"><?= $kls['nama_kelas'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="app-grid">
                        <div class="app-col-half">
                            <div class="app-field">
                                <label>Tempat Lahir</label>
                                <input type="text" name="tempat_lahir" class="app-control">
                            </div>
                        </div>
                        <div class="app-col-half">
                            <div class="app-field">
                                <label>Tgl Lahir</label>
                                <input type="date" name="tgl_lahir" class="app-control">
                            </div>
                        </div>
                    </div>
                    <div class="app-field">
                        <label>Nama Wali</label>
                        <input type="text" name="nama_wali" class="app-control">
                    </div>
                </div>
                <div class="flex shrink-0 items-center justify-end gap-3 border-t border-slate-200 px-6 py-4">
                    <button type="button" class="app-button app-button-secondary" data-tailwind-modal-close>Batal</button>
                    <button type="submit" name="tambah" class="app-button app-button-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Import -->
<div class="fixed inset-0 z-[1055] hidden overflow-y-auto bg-slate-950/60 px-4 py-6 backdrop-blur-sm" id="modalImport" tabindex="-1" role="dialog" aria-hidden="true" data-tailwind-modal>
    <div class="mx-auto flex min-h-full w-full max-w-2xl items-start">
        <div class="flex max-h-[calc(100vh-3rem)] w-full flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl">
            <div class="flex shrink-0 items-center justify-between border-b border-slate-200 px-6 py-4">
                <h5 class="text-base font-extrabold text-slate-900">Import Data Siswa</h5>
                <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-100 hover:text-slate-900" data-tailwind-modal-close aria-label="Close" style="background: transparent; border: none;">
                    <i class="mdi mdi-close"></i>
                </button>
            </div>
            <form id="formImport" action="" method="post" enctype="multipart/form-data" onsubmit="startProgress()">
                <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
                    <div class="app-alert app-alert-info">
                        Gunakan file template Excel berikut untuk mengimport data: <br>
                        <a href="template_siswa.xlsx" class="app-button app-button-sm app-button-success mt-2">
                            <i class="mdi mdi-download"></i> Download Template
                        </a>
                    </div>
                    <div class="app-field">
                        <label>File Excel</label>
                        <input type="file" name="file_excel" id="file_excel" class="app-control" accept=".xls, .xlsx" required>
                    </div>
                    <!-- Progress Bar -->
                    <div class="app-progress hidden" id="progressContainer" style="height: 20px;">
                        <div class="app-progress-bar app-progress-striped app-progress-animated" role="progressbar"
                             style="width: 0%;" id="progressBar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                    </div>
                </div>
                <div class="flex shrink-0 items-center justify-end gap-3 border-t border-slate-200 px-6 py-4">
                    <button type="button" class="app-button app-button-secondary" data-tailwind-modal-close>Batal</button>
                    <button type="submit" name="import" class="app-button app-button-primary">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function startProgress(event) {
    if (event) {
        event.preventDefault();
    }
    var fileInput = document.getElementById('file_excel');
    if (fileInput && fileInput.files.length > 0) {
        var container = document.getElementById('progressContainer');
        container.classList.remove('hidden');
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
        }, 50);
        setTimeout(function() {
            document.getElementById('formImport').submit();
        }, 100);
    }
}
</script>

<?php include '../template/footer.php'; ?>

