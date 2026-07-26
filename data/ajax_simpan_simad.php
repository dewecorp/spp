<?php
include '../config/config.php';
require_once __DIR__ . '/../include/laporan_helper.php';
header('Content-Type: application/json');
ensure_siswa_tanggal_masuk_column($koneksi);

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['data']) || !is_array($input['data'])) {
    echo json_encode(['status' => 'error', 'message' => 'Data tidak valid']);
    exit;
}

$dataSiswa = $input['data'];
$success_count = 0;
$update_count = 0;
$error_count = 0;
$errors = [];
$total_api = count($dataSiswa);

function normalisasiKelas($nama_kelas) {
    $map = [
        'I' => '1', 'II' => '2', 'III' => '3', 'IV' => '4', 'V' => '5', 'VI' => '6',
        '1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6'
    ];
    $trimmed = str_replace('KELAS', '', strtoupper(trim($nama_kelas)));
    return $map[$trimmed] ?? trim($trimmed);
}

foreach ($dataSiswa as $siswa) {
    $nama    = mysqli_real_escape_string($koneksi, $siswa['nama_siswa'] ?? '');
    $nisn    = mysqli_real_escape_string($koneksi, $siswa['nisn'] ?? '');
    $gender  = mysqli_real_escape_string($koneksi, $siswa['jenis_kelamin'] ?? '-');
    $tempat  = mysqli_real_escape_string($koneksi, $siswa['tempat_lahir'] ?? '-');
    $tanggal = mysqli_real_escape_string($koneksi, $siswa['tanggal_lahir'] ?? '1900-01-01');
    $wali    = mysqli_real_escape_string($koneksi, $siswa['wali'] ?? '-');

    $kelas_n = normalisasiKelas($siswa['nama_kelas'] ?? '');
    $kelas_n_esc = mysqli_real_escape_string($koneksi, $kelas_n);

    if (empty($nisn)) {
        $error_count++;
        $errors[] = "NISN kosong untuk siswa: $nama";
        continue;
    }

    $q_kelas = mysqli_query($koneksi, "SELECT id_kelas FROM kelas WHERE nama_kelas = '$kelas_n_esc'");
    if ($q_kelas && mysqli_num_rows($q_kelas) > 0) {
        $d_kelas = mysqli_fetch_assoc($q_kelas);
        $id_kelas = $d_kelas['id_kelas'];
    } else {
        $nama_kls_baru = empty($kelas_n_esc) ? 'Lainnya' : $kelas_n_esc;
        $q_create = mysqli_query($koneksi, "INSERT INTO kelas (nama_kelas) VALUES ('$nama_kls_baru')");
        if ($q_create) {
            $id_kelas = mysqli_insert_id($koneksi);
        } else {
            $q_existing = mysqli_query($koneksi, "SELECT id_kelas FROM kelas LIMIT 1");
            $id_kelas = $q_existing && mysqli_num_rows($q_existing) > 0 ? mysqli_fetch_assoc($q_existing)['id_kelas'] : 1;
        }
    }

    $cek = mysqli_query($koneksi, "SELECT nisn FROM siswa WHERE nisn = '$nisn'");
    if (mysqli_num_rows($cek) > 0) {
        $sql = "UPDATE siswa SET nama = '$nama', id_kelas = '$id_kelas', jenis_kelamin = '$gender', tempat_lahir = '$tempat', tgl_lahir = '$tanggal', nama_wali = '$wali' WHERE nisn = '$nisn'";
        if (mysqli_query($koneksi, $sql)) {
            $update_count++;
        } else {
            $error_count++;
            $errors[] = "Gagal Update NISN $nisn: " . mysqli_error($koneksi);
        }
    } else {
        $sql = "INSERT INTO siswa (nisn, nama, id_kelas, alamat, no_telp, jenis_kelamin, tempat_lahir, tgl_lahir, nama_wali, tanggal_masuk) VALUES ('$nisn', '$nama', '$id_kelas', '-', '', '$gender', '$tempat', '$tanggal', '$wali', CURDATE())";
        if (mysqli_query($koneksi, $sql)) {
            $success_count++;
        } else {
            $error_count++;
            $errors[] = "Gagal Insert NISN $nisn: " . mysqli_error($koneksi);
        }
    }
}

logActivity($koneksi, 'Update', "Sinkronisasi Simad via Browser: $success_count baru, $update_count update, $error_count error");

echo json_encode([
    'status' => 'success',
    'new' => $success_count,
    'update' => $update_count,
    'failed' => $error_count,
    'total_api' => $total_api,
    'errors' => $errors,
    'message' => 'Sinkronisasi selesai.'
]);
