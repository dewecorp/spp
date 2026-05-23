<?php
/**
 * SIMAD Integration Endpoint
 * This script provides data for SIMAD web application to sync billing and payment reports.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../include/laporan_helper.php';

// Simple API Key authentication
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
    echo json_encode([
        'status' => 'error', 
        'message' => 'Unauthorized: Invalid API Key',
        'env' => 'production', // Selalu laporkan sebagai produksi untuk integrasi SIMAD
        'base_url' => 'https://sibayar.misultanfattah.sch.id'
    ]);
    exit;
}

$action = $_GET['action'] ?? 'get_student_data';

// 1. Health Check & Environment Info
if ($action == 'check') {
    echo json_encode([
        'status' => 'success',
        'message' => 'Sibayar SPP API is active',
        'env' => 'production',
        'base_url' => 'https://sibayar.misultanfattah.sch.id',
        'server_time' => date('Y-m-d H:i:s'),
        'php_version' => PHP_VERSION
    ]);
    exit;
}

// 2. Get All Students Summary (Bulk Sync)
if ($action == 'get_all_summary') {
    $data = [];
    $query = mysqli_query($koneksi, "SELECT s.nisn, s.nama, k.nama_kelas, k.id_kelas 
                                   FROM siswa s 
                                   JOIN kelas k ON s.id_kelas = k.id_kelas 
                                   ORDER BY k.nama_kelas ASC, s.nama ASC");
    
    // Cache jenis_bayar to avoid multiple queries
    $q_jenis = mysqli_query($koneksi, "SELECT * FROM jenis_bayar WHERE status = 'Aktif'");
    $jenis_bayar_all = [];
    while($jb = mysqli_fetch_assoc($q_jenis)) {
        $jenis_bayar_all[] = $jb;
    }

    $current_month_name = date('F');
    $month_map = [
        'July' => 0, 'August' => 1, 'September' => 2, 'October' => 3, 'November' => 4, 'December' => 5,
        'January' => 6, 'February' => 7, 'March' => 8, 'April' => 9, 'May' => 10, 'June' => 11
    ];
    $current_index = $month_map[$current_month_name] ?? 0;
    $months = ['Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni'];

    while ($siswa = mysqli_fetch_assoc($query)) {
        $total_tunggakan = 0;
        $total_sudah_bayar = 0;
        $nisn = $siswa['nisn'];
        
        // Get all payments for this student at once to optimize
        $paid_data = [];
        $q_p = mysqli_query($koneksi, "SELECT id_jenis_bayar, jumlah_bayar, bulan_bayar FROM pembayaran WHERE nisn='$nisn'");
        while($p = mysqli_fetch_assoc($q_p)) {
            $jb_id = $p['id_jenis_bayar'];
            if(!isset($paid_data[$jb_id])) {
                $paid_data[$jb_id] = ['total' => 0, 'months' => []];
            }
            $paid_data[$jb_id]['total'] += (int)$p['jumlah_bayar'];
            if (!empty($p['bulan_bayar'])) {
                $ms = array_map('trim', explode(',', $p['bulan_bayar']));
                $paid_data[$jb_id]['months'] = array_merge($paid_data[$jb_id]['months'], $ms);
            }
        }

        foreach ($jenis_bayar_all as $jb) {
            if (jenis_bayar_berlaku_untuk_kelas($jb['tagihan_kelas'] ?? '', $siswa['id_kelas'], $siswa['nama_kelas'])) {
                $jb_id = $jb['id_jenis_bayar'];
                $sudah_bayar_jb = $paid_data[$jb_id]['total'] ?? 0;
                $total_sudah_bayar += $sudah_bayar_jb;

                if ($jb['tipe_bayar'] == 'Bulanan') {
                    $paid_months = $paid_data[$jb_id]['months'] ?? [];
                    $is_extracurricular = stripos($jb['nama_pembayaran'], 'ekstrakurikuler') !== false;
                    
                    foreach ($months as $index => $m) {
                        if (!in_array($m, $paid_months)) {
                            // Only count as tunggakan if it's already due
                            if ($is_extracurricular || $index <= $current_index) {
                                $total_tunggakan += (int)$jb['nominal'];
                            }
                        }
                    }
                } else {
                    // Cicilan
                    $sisa = (int)$jb['nominal'] - $sudah_bayar_jb;
                    if ($sisa > 0) {
                        $total_tunggakan += $sisa;
                    }
                }
            }
        }

        $data[] = [
            'nisn' => $siswa['nisn'],
            'nama' => $siswa['nama'],
            'kelas' => $siswa['nama_kelas'],
            'total_tunggakan' => $total_tunggakan,
            'total_sudah_bayar' => $total_sudah_bayar,
            'is_lunas' => ($total_tunggakan <= 0)
        ];
    }

    echo json_encode([
        'status' => 'success',
        'data' => $data,
        'count' => count($data),
        'env' => 'production'
    ]);
    exit;
}

// 3. Single Student Actions
$nisn = mysqli_real_escape_string($koneksi, $_GET['nisn'] ?? '');

if (empty($nisn)) {
    echo json_encode(['status' => 'error', 'message' => 'NISN is required for this action']);
    exit;
}

// Fetch student data
$q_siswa = mysqli_query($koneksi, "SELECT s.*, k.nama_kelas FROM siswa s JOIN kelas k ON s.id_kelas = k.id_kelas WHERE s.nisn = '$nisn'");
$d_siswa = mysqli_fetch_assoc($q_siswa);

if (!$d_siswa) {
    echo json_encode(['status' => 'error', 'message' => 'Student not found']);
    exit;
}

if ($action == 'get_student_data') {
    // Get Billing & Payments for specific student
    $tagihan = [];
    $pembayaran = [];
    
    // Get Tagihan
    $id_kelas_siswa = $d_siswa['id_kelas'];
    $nama_kelas_siswa = $d_siswa['nama_kelas'];
    $q_jenis = mysqli_query($koneksi, "SELECT * FROM jenis_bayar WHERE status = 'Aktif' ORDER BY tipe_bayar ASC");
    
    $current_month_name = date('F');
    $month_map = [
        'July' => 0, 'August' => 1, 'September' => 2, 'October' => 3, 'November' => 4, 'December' => 5,
        'January' => 6, 'February' => 7, 'March' => 8, 'April' => 9, 'May' => 10, 'June' => 11
    ];
    $current_index = $month_map[$current_month_name] ?? 0;
    $months = ['Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni'];

    while ($jb = mysqli_fetch_assoc($q_jenis)) {
        if (jenis_bayar_berlaku_untuk_kelas($jb['tagihan_kelas'] ?? '', $id_kelas_siswa, $nama_kelas_siswa)) {
            $unpaid_details = [];
            $total_sisa = 0;
            
            if ($jb['tipe_bayar'] == 'Bulanan') {
                $paid_months = [];
                $q_bayar = mysqli_query($koneksi, "SELECT bulan_bayar FROM pembayaran WHERE nisn='$nisn' AND id_jenis_bayar='" . $jb['id_jenis_bayar'] . "'");
                while ($row = mysqli_fetch_assoc($q_bayar)) {
                    if (!empty($row['bulan_bayar'])) {
                        $ms = array_map('trim', explode(',', $row['bulan_bayar']));
                        $paid_months = array_merge($paid_months, $ms);
                    }
                }
                
                $is_extracurricular = stripos($jb['nama_pembayaran'], 'ekstrakurikuler') !== false;
                foreach ($months as $index => $m) {
                    if (!in_array($m, $paid_months)) {
                        $unpaid_details[] = $m;
                        if ($is_extracurricular || $index <= $current_index) {
                            $total_sisa += (int)$jb['nominal'];
                        }
                    }
                }
            } else {
                $q_total = mysqli_query($koneksi, "SELECT SUM(jumlah_bayar) as total FROM pembayaran WHERE nisn='$nisn' AND id_jenis_bayar='" . $jb['id_jenis_bayar'] . "'");
                $d_total = mysqli_fetch_assoc($q_total);
                $total_bayar = $d_total['total'] ?? 0;
                $total_sisa = max(0, (int)$jb['nominal'] - (int)$total_bayar);
                if ($total_sisa > 0) $unpaid_details = ['Belum Lunas'];
            }
            
            $tagihan[] = [
                'id_jenis_bayar' => $jb['id_jenis_bayar'],
                'nama_pembayaran' => $jb['nama_pembayaran'],
                'tipe_bayar' => $jb['tipe_bayar'],
                'total_nominal' => (int)$jb['nominal'],
                'sisa_tagihan' => $total_sisa,
                'item_belum_bayar' => $unpaid_details,
                'is_lunas' => ($total_sisa <= 0)
            ];
        }
    }

    // Get Payment History
    $q_history = mysqli_query($koneksi, "SELECT p.*, jb.nama_pembayaran 
                                       FROM pembayaran p 
                                       JOIN jenis_bayar jb ON p.id_jenis_bayar = jb.id_jenis_bayar 
                                       WHERE p.nisn = '$nisn' 
                                       ORDER BY p.tgl_bayar DESC, p.id_pembayaran DESC");
    while($h = mysqli_fetch_assoc($q_history)) {
        $pembayaran[] = [
            'no_transaksi' => $h['no_transaksi'],
            'tgl_bayar' => $h['tgl_bayar'],
            'jumlah_bayar' => (int)$h['jumlah_bayar'],
            'nama_pembayaran' => $h['nama_pembayaran'],
            'ket' => $h['ket'],
            'waktu_input' => $h['created_at']
        ];
    }

    echo json_encode([
        'status' => 'success',
        'student' => [
            'nisn' => $d_siswa['nisn'],
            'nama' => $d_siswa['nama'],
            'kelas' => $d_siswa['nama_kelas']
        ],
        'billing' => $tagihan,
        'payments' => $pembayaran,
        'env' => 'production'
    ]);

} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}
