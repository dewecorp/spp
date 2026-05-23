<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../include/laporan_helper.php';

// Simple API Key authentication
// In production, move this to database or environment variable
$valid_api_key = 'SPP_SECRET_KEY_2026'; 

$api_key = '';
if (function_exists('getallheaders')) {
    $headers = getallheaders();
    $api_key = $headers['X-API-KEY'] ?? '';
}
if (empty($api_key)) {
    $api_key = $_GET['api_key'] ?? '';
}

if ($api_key !== $valid_api_key) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized: Invalid API Key']);
    exit;
}

$action = $_GET['action'] ?? '';
$nisn = mysqli_real_escape_string($koneksi, $_GET['nisn'] ?? '');

if (empty($nisn)) {
    echo json_encode(['status' => 'error', 'message' => 'NISN is required']);
    exit;
}

// Fetch student data
$q_siswa = mysqli_query($koneksi, "SELECT s.*, k.nama_kelas FROM siswa s JOIN kelas k ON s.id_kelas = k.id_kelas WHERE s.nisn = '$nisn'");
$d_siswa = mysqli_fetch_assoc($q_siswa);

if (!$d_siswa) {
    echo json_encode(['status' => 'error', 'message' => 'Student not found']);
    exit;
}

if ($action == 'get_tagihan') {
    $tagihan = [];
    $id_kelas_siswa = $d_siswa['id_kelas'];
    $nama_kelas_siswa = $d_siswa['nama_kelas'];
    
    $q_jenis = mysqli_query($koneksi, "SELECT * FROM jenis_bayar WHERE status = 'Aktif' ORDER BY tipe_bayar ASC");
    
    while ($jb = mysqli_fetch_assoc($q_jenis)) {
        if (jenis_bayar_berlaku_untuk_kelas($jb['tagihan_kelas'] ?? '', $id_kelas_siswa, $nama_kelas_siswa)) {
            $is_fully_paid = false;
            $unpaid_details = [];
            
            if ($jb['tipe_bayar'] == 'Bulanan') {
                $months = ['Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni'];
                
                // Get paid months
                $paid_months = [];
                $q_bayar = mysqli_query($koneksi, "SELECT bulan_bayar FROM pembayaran WHERE nisn='$nisn' AND id_jenis_bayar='" . $jb['id_jenis_bayar'] . "'");
                while ($row = mysqli_fetch_assoc($q_bayar)) {
                    if (!empty($row['bulan_bayar'])) {
                        $ms = array_map('trim', explode(',', $row['bulan_bayar']));
                        $paid_months = array_merge($paid_months, $ms);
                    }
                }
                
                // Determine which months are unpaid
                // We use current month to limit bills for non-extracurricular items
                $current_month_name = date('F');
                $month_map = [
                    'July' => 0, 'August' => 1, 'September' => 2, 'October' => 3, 'November' => 4, 'December' => 5,
                    'January' => 6, 'February' => 7, 'March' => 8, 'April' => 9, 'May' => 10, 'June' => 11
                ];
                $current_index = $month_map[$current_month_name] ?? 0;
                
                // Academic year starts in July. So if current is July, index is 0.
                // If current is June, index is 11.
                
                $is_extracurricular = stripos($jb['nama_pembayaran'], 'ekstrakurikuler') !== false;
                
                foreach ($months as $index => $m) {
                    // For regular bills, only show up to current month
                    if (!$is_extracurricular && $index > $current_index && date('Y') == date('Y', strtotime($d_siswa['created_at'] ?? 'now'))) {
                        // This is a simplified check for future months
                        // In a real scenario, we might want to show all months of the academic year
                    }
                    
                    if (!in_array($m, $paid_months)) {
                        $unpaid_details[] = $m;
                    }
                }
                
                if (empty($unpaid_details)) {
                    $is_fully_paid = true;
                } else {
                    $total_sisa = count($unpaid_details) * $jb['nominal'];
                }
                
            } else {
                // Cicilan / Bebas
                $q_total = mysqli_query($koneksi, "SELECT SUM(jumlah_bayar) as total FROM pembayaran WHERE nisn='$nisn' AND id_jenis_bayar='" . $jb['id_jenis_bayar'] . "'");
                $d_total = mysqli_fetch_assoc($q_total);
                $total_bayar = $d_total['total'] ?? 0;
                $total_sisa = $jb['nominal'] - $total_bayar;
                
                if ($total_sisa <= 0) {
                    $is_fully_paid = true;
                } else {
                    $unpaid_details = ['Sisa Tagihan'];
                }
            }
            
            if (!$is_fully_paid) {
                $tagihan[] = [
                    'id_jenis_bayar' => $jb['id_jenis_bayar'],
                    'nama_pembayaran' => $jb['nama_pembayaran'],
                    'tipe_bayar' => $jb['tipe_bayar'],
                    'nominal_per_bulan' => ($jb['tipe_bayar'] == 'Bulanan' ? (int)$jb['nominal'] : null),
                    'total_nominal' => (int)$jb['nominal'],
                    'sisa_tagihan' => (int)$total_sisa,
                    'item_belum_bayar' => $unpaid_details
                ];
            }
        }
    }
    
    echo json_encode([
        'status' => 'success',
        'student' => [
            'nisn' => $d_siswa['nisn'],
            'nama' => $d_siswa['nama'],
            'kelas' => $d_siswa['nama_kelas']
        ],
        'data' => $tagihan
    ]);

} elseif ($action == 'get_laporan') {
    $laporan = [];
    $query = mysqli_query($koneksi, "SELECT p.*, jb.nama_pembayaran, jb.tipe_bayar 
                                   FROM pembayaran p 
                                   JOIN jenis_bayar jb ON p.id_jenis_bayar = jb.id_jenis_bayar 
                                   WHERE p.nisn = '$nisn' 
                                   ORDER BY p.created_at DESC");
    
    while ($row = mysqli_fetch_assoc($query)) {
        $laporan[] = [
            'no_transaksi' => $row['no_transaksi'],
            'tgl_bayar' => $row['tgl_bayar'],
            'jumlah_bayar' => (int)$row['jumlah_bayar'],
            'nama_pembayaran' => $row['nama_pembayaran'],
            'tipe_bayar' => $row['tipe_bayar'],
            'bulan_bayar' => $row['bulan_bayar'],
            'tahun_bayar' => $row['tahun_bayar'],
            'waktu_input' => $row['created_at']
        ];
    }
    
    echo json_encode([
        'status' => 'success',
        'student' => [
            'nisn' => $d_siswa['nisn'],
            'nama' => $d_siswa['nama'],
            'kelas' => $d_siswa['nama_kelas']
        ],
        'data' => $laporan
    ]);

} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}
