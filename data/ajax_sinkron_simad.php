<?php
ob_start();
include '../config/config.php';
require_once __DIR__ . '/../include/laporan_helper.php';

header('Content-Type: application/json');
ensure_siswa_tanggal_masuk_column($koneksi);

$ep_simad = get_endpoint_masuk($koneksi, 'simad');
if ($ep_simad && !empty($ep_simad['base_url'])) {
    $baseUrl = rtrim($ep_simad['base_url'], '/');
    $apiKey = !empty($ep_simad['api_key']) ? $ep_simad['api_key'] : 'SIS_CENTRAL_HUB_SECRET_2026';
    $apiUrl = "$baseUrl/api/v1/students.php?api_key=$apiKey";
} else {
    $apiKey = 'SIS_CENTRAL_HUB_SECRET_2026';
    $apiUrl = "https://simad.misultanfattah.sch.id/api/v1/students.php?api_key=$apiKey";
}

$respond = function($data) {
    ob_clean();
    echo json_encode($data);
    exit;
};

// --- Coba server-side cURL ---
$cookieJar = sys_get_temp_dir() . '/simad_cookies_' . session_id() . '.txt';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
curl_setopt($ch, CURLOPT_ENCODING, '');
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json, text/plain, */*',
    'Accept-Language: id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7',
    'Referer: https://sibayar.misultanfattah.sch.id/',
]);
$response = curl_exec($ch);
$error_msg = curl_error($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$terkena_imunify = $response !== false && preg_match('/imunify360|bot.protection|automation.should.be.whitelist/i', $response);

if ($response === false && !$terkena_imunify) {
    $respond(['status' => 'error', 'message' => "Gagal koneksi: $error_msg"]);
}

if ($terkena_imunify) {
    $respond([
        'status' => 'client_fetch',
        'url' => $apiUrl,
        'message' => 'Server-side diblokir Imunify360. Browser akan coba fetch langsung.'
    ]);
}

$result = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    $snippet = mb_substr($response, 0, 500);
    $respond([
        'status' => 'error',
        'message' => "Format data tidak valid (Bukan JSON). HTTP Code: $http_code.",
        'debug_raw' => $snippet
    ]);
}

if ($result && isset($result['status']) && $result['status'] === 'success') {
    $dataSiswa = $result['data'];
    $success_count = 0;
    $update_count = 0;
    $error_count = 0;
    $errors = [];
    $total_api = is_array($dataSiswa) ? count($dataSiswa) : 0;

    function normalisasiKelas($nama_kelas) {
        $map = [
            'I'    => '1', 'II'   => '2', 'III'  => '3', 'IV'   => '4', 'V'    => '5', 'VI'   => '6',
            '1'    => '1', '2'    => '2', '3'    => '3', '4'    => '4', '5'    => '5', '6'    => '6'
        ];
        $trimmed = strtoupper(trim($nama_kelas));
        $trimmed = str_replace('KELAS', '', $trimmed);
        $trimmed = trim($trimmed);
        return $map[$trimmed] ?? $trimmed;
    }

    if (is_array($dataSiswa)) {
        foreach ($dataSiswa as $siswa) {
            $nama       = mysqli_real_escape_string($koneksi, $siswa['nama_siswa'] ?? '');
            $nisn       = mysqli_real_escape_string($koneksi, $siswa['nisn'] ?? '');
            $gender     = mysqli_real_escape_string($koneksi, $siswa['jenis_kelamin'] ?? '-');
            $tempat     = mysqli_real_escape_string($koneksi, $siswa['tempat_lahir'] ?? '-');
            $tanggal    = mysqli_real_escape_string($koneksi, $siswa['tanggal_lahir'] ?? '1900-01-01');
            $wali       = mysqli_real_escape_string($koneksi, $siswa['wali'] ?? '-');
            $tgl_masuk_simad = trim($siswa['tanggal_masuk'] ?? '');
            $thn_masuk = '';
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl_masuk_simad)) {
                $thn_masuk = substr($tgl_masuk_simad, 0, 4);
            } elseif (preg_match('/^\d{4}/', $tgl_masuk_simad, $m)) {
                $thn_masuk = $m[0];
            }
            $tgl_masuk_simad = mysqli_real_escape_string($koneksi, $tgl_masuk_simad);
            $thn_masuk = mysqli_real_escape_string($koneksi, $thn_masuk);

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
                    if ($q_existing && mysqli_num_rows($q_existing) > 0) {
                        $d_existing = mysqli_fetch_assoc($q_existing);
                        $id_kelas = $d_existing['id_kelas'];
                    } else {
                        $id_kelas = 1;
                    }
                }
            }

            $cek = mysqli_query($koneksi, "SELECT nisn FROM siswa WHERE nisn = '$nisn'");
            if (mysqli_num_rows($cek) > 0) {
                $set_tgl = $tgl_masuk_simad !== '' ? ", tanggal_masuk = '$tgl_masuk_simad'" : ", tanggal_masuk = NULL";
                $set_thn = $thn_masuk !== '' ? ", tahun_masuk = '$thn_masuk'" : ", tahun_masuk = ''";
                $sql = "UPDATE siswa SET 
                        nama = '$nama', id_kelas = '$id_kelas',
                        jenis_kelamin = '$gender', tempat_lahir = '$tempat',
                        tgl_lahir = '$tanggal', nama_wali = '$wali'$set_tgl$set_thn
                        WHERE nisn = '$nisn'";
                if (mysqli_query($koneksi, $sql)) {
                    $update_count++;
                } else {
                    $error_count++;
                    $errors[] = "Gagal Update NISN $nisn: " . mysqli_error($koneksi);
                }
            } else {
                $tgl_col = $tgl_masuk_simad !== '' ? ", tanggal_masuk" : '';
                $tgl_val = $tgl_masuk_simad !== '' ? ", '$tgl_masuk_simad'" : '';
                $tm_col  = $thn_masuk !== '' ? ", tahun_masuk" : '';
                $tm_val  = $thn_masuk !== '' ? ", '$thn_masuk'" : '';
                $sql = "INSERT INTO siswa (nisn, nama, id_kelas, alamat, no_telp, jenis_kelamin, tempat_lahir, tgl_lahir, nama_wali$tm_col$tgl_col) 
                        VALUES ('$nisn', '$nama', '$id_kelas', '-', '', '$gender', '$tempat', '$tanggal', '$wali'$tm_val$tgl_val)";
                if (mysqli_query($koneksi, $sql)) {
                    $success_count++;
                } else {
                    $error_count++;
                    $errors[] = "Gagal Insert NISN $nisn: " . mysqli_error($koneksi);
                }
            }
        }
    }

    logActivity($koneksi, 'Update', "Sinkronisasi Simad via AJAX: $success_count baru, $update_count update, $error_count error");

    ob_clean();

    mysqli_query($koneksi, "SET FOREIGN_KEY_CHECKS = 0");
    $duplicate_check = mysqli_query($koneksi, "SELECT id_kelas, nama_kelas FROM kelas WHERE nama_kelas IN ('I','II','III','IV','V','VI')");
    while ($dup = mysqli_fetch_assoc($duplicate_check)) {
        $romawi = $dup['nama_kelas'];
        $id_romawi = $dup['id_kelas'];
        $angka = ['I'=>'1','II'=>'2','III'=>'3','IV'=>'4','V'=>'5','VI'=>'6'][$romawi];
        $q_angka = mysqli_query($koneksi, "SELECT id_kelas FROM kelas WHERE nama_kelas = '$angka'");
        if ($target = mysqli_fetch_assoc($q_angka)) {
            $id_angka = $target['id_kelas'];
            mysqli_query($koneksi, "UPDATE siswa SET id_kelas = '$id_angka' WHERE id_kelas = '$id_romawi'");
            mysqli_query($koneksi, "DELETE FROM kelas WHERE id_kelas = '$id_romawi'");
        }
    }
    mysqli_query($koneksi, "SET FOREIGN_KEY_CHECKS = 1");

    echo json_encode([
        'status' => 'success',
        'new' => $success_count,
        'update' => $update_count,
        'failed' => $error_count,
        'total_api' => $total_api,
        'errors' => $errors,
        'message' => 'Sinkronisasi selesai.'
    ]);
} else {
    $msg = isset($result['message']) ? $result['message'] : ($result ? json_encode($result) : 'Respon kosong atau tidak dikenal');
    $respond(['status' => 'error', 'message' => $msg, 'http_code' => $http_code]);
}
?>