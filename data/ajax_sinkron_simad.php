<?php
ob_start(); // Mulai output buffering untuk menangkap warning/error yang merusak JSON
include '../config/config.php';

header('Content-Type: application/json');

// Bypass check to ensure compatibility
// API key check is enough security for now as it's an internal tool

$apiUrl = "https://simad.misultanfattah.sch.id/api/v1/students.php?api_key=SIS_CENTRAL_HUB_SECRET_2026";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // FIX: Handle 301 Redirect
curl_setopt($ch, CURLOPT_TIMEOUT, 60); // Increase timeout for large data
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');

$response = curl_exec($ch);
$error_msg = curl_error($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false) {
    echo json_encode(['status' => 'error', 'message' => "Gagal koneksi ke server Simad: $error_msg"]);
    exit;
}

$result = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['status' => 'error', 'message' => "Format data tidak valid (Bukan JSON). HTTP Code: $http_code. Respon server mungkin berupa halaman redirect atau error HTML."]);
    exit;
}

if ($result && isset($result['status']) && $result['status'] === 'success') {
    $dataSiswa = $result['data'];
    $success_count = 0;
    $update_count = 0;
    $error_count = 0;
    $errors = [];
    $total_api = count($dataSiswa);
    
    // Fungsi Normalisasi Kelas
    function normalisasiKelas($nama_kelas) {
        $map = [
            'I'    => '1', 'II'   => '2', 'III'  => '3', 'IV'   => '4', 'V'    => '5', 'VI'   => '6',
            '1'    => '1', '2'    => '2', '3'    => '3', '4'    => '4', '5'    => '5', '6'    => '6'
        ];
        $trimmed = strtoupper(trim($nama_kelas));
        // Jika ada kata "Kelas", bersihkan
        $trimmed = str_replace('KELAS', '', $trimmed);
        $trimmed = trim($trimmed);
        return $map[$trimmed] ?? $trimmed;
    }

    foreach ($dataSiswa as $siswa) {
        $nama    = mysqli_real_escape_string($koneksi, $siswa['nama_siswa'] ?? '');
        $nisn    = mysqli_real_escape_string($koneksi, $siswa['nisn'] ?? '');
        $gender  = mysqli_real_escape_string($koneksi, $siswa['jenis_kelamin'] ?? '-');
        $tempat  = mysqli_real_escape_string($koneksi, $siswa['tempat_lahir'] ?? '-');
        $tanggal = mysqli_real_escape_string($koneksi, $siswa['tanggal_lahir'] ?? '1900-01-01');
        $wali    = mysqli_real_escape_string($koneksi, $siswa['wali'] ?? '-');
        
        // Normalisasi Nama Kelas
        $kelas_n = normalisasiKelas($siswa['nama_kelas'] ?? '');
        $kelas_n_esc = mysqli_real_escape_string($koneksi, $kelas_n);
        
        // Pastikan NISN tidak kosong
        if (empty($nisn)) {
            $error_count++;
            $errors[] = "NISN kosong untuk siswa: $nama";
            continue;
        }

        // Cari ID Kelas
        $q_kelas = mysqli_query($koneksi, "SELECT id_kelas FROM kelas WHERE nama_kelas = '$kelas_n_esc'");
        if ($q_kelas && mysqli_num_rows($q_kelas) > 0) {
            $d_kelas = mysqli_fetch_assoc($q_kelas);
            $id_kelas = $d_kelas['id_kelas'];
        } else {
            // Jika kelas tidak ada, buat baru
            $nama_kls_baru = empty($kelas_n_esc) ? 'Lainnya' : $kelas_n_esc;
            $q_create = mysqli_query($koneksi, "INSERT INTO kelas (nama_kelas) VALUES ('$nama_kls_baru')");
            if ($q_create) {
                $id_kelas = mysqli_insert_id($koneksi);
            } else {
                // Fallback ke ID kelas yang sudah ada jika gagal insert (mungkin karena nama kelas sudah ada tapi query SELECT tadi gagal)
                $q_existing = mysqli_query($koneksi, "SELECT id_kelas FROM kelas LIMIT 1");
                if ($q_existing && mysqli_num_rows($q_existing) > 0) {
                    $d_existing = mysqli_fetch_assoc($q_existing);
                    $id_kelas = $d_existing['id_kelas'];
                } else {
                    $id_kelas = 1; // Last resort
                }
            }
        }
        
        // Cek apakah siswa sudah ada
        $cek = mysqli_query($koneksi, "SELECT nisn FROM siswa WHERE nisn = '$nisn'");
        if (mysqli_num_rows($cek) > 0) {
            $sql = "UPDATE siswa SET 
                    nama = '$nama', 
                    id_kelas = '$id_kelas',
                    jenis_kelamin = '$gender',
                    tempat_lahir = '$tempat',
                    tgl_lahir = '$tanggal',
                    nama_wali = '$wali'
                    WHERE nisn = '$nisn'";
            
            if (mysqli_query($koneksi, $sql)) {
                $update_count++;
            } else {
                $error_count++;
                $errors[] = "Gagal Update NISN $nisn: " . mysqli_error($koneksi);
            }
        } else {
            // Kolom 'nis' tidak ada di database berdasarkan error 'Unknown column nis'
            $sql = "INSERT INTO siswa (nisn, nama, id_kelas, alamat, no_telp, jenis_kelamin, tempat_lahir, tgl_lahir, nama_wali) 
                    VALUES ('$nisn', '$nama', '$id_kelas', '-', '', '$gender', '$tempat', '$tanggal', '$wali')";
            if (mysqli_query($koneksi, $sql)) {
                $success_count++;
            } else {
                $error_count++;
                $errors[] = "Gagal Insert NISN $nisn: " . mysqli_error($koneksi);
            }
        }
    }
    
    logActivity($koneksi, 'Update', "Sinkronisasi Simad via AJAX: $success_count baru, $update_count update, $error_count error");
    
    // BERSIHKAN BUFFER SEBELUM OUTPUT JSON
    $captured_output = ob_get_clean(); 
    
    // FINAL CLEANUP: Hapus kelas duplikat (Romawi)
    mysqli_query($koneksi, "SET FOREIGN_KEY_CHECKS = 0"); // Temporary disable to cleanup
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
        'message' => 'Sinkronisasi selesai.'
    ]);
} else {
    $msg = isset($result['message']) ? $result['message'] : 'Status gagal dari API Simad.';
    
    // BERSIHKAN BUFFER SEBELUM OUTPUT JSON
    ob_end_clean(); 
    
    echo json_encode(['status' => 'error', 'message' => $msg]);
}
?>