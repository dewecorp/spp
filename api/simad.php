<?php
/**
 * SIMAD Integration Endpoint
 * Menyediakan data tagihan, tunggakan, dan riwayat pembayaran untuk sinkronisasi Web SIMAD.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../include/laporan_helper.php';

ensure_pembayaran_tahun_ajaran_column($koneksi);

function simad_resolve_tahun_ajaran($koneksi, $tahun_ajaran_param) {
    $tahun_ajaran = trim((string) $tahun_ajaran_param);
    if ($tahun_ajaran === '') {
        return get_tahun_ajaran_aktif($koneksi);
    }

    return $tahun_ajaran;
}

function simad_format_tunggakan_lama($tunggakan_lama) {
    if (!$tunggakan_lama || !is_array($tunggakan_lama)) {
        return [];
    }

    $result = [];
    foreach ($tunggakan_lama as $tahun => $items) {
        $formatted_items = [];
        $total = 0;

        foreach ($items as $item) {
            $sisa = (int) ($item['sisa'] ?? 0);
            $formatted_items[] = [
                'id_jenis_bayar' => (int) ($item['id_jenis_bayar'] ?? 0),
                'nama_pembayaran' => $item['nama_pembayaran'] ?? '',
                'tipe_bayar' => $item['tipe_bayar'] ?? '',
                'tahun_ajaran' => $tahun,
                'total_nominal' => (int) ($item['nominal'] ?? 0),
                'sisa_tagihan' => $sisa,
                'item_belum_bayar' => $item['unpaid_details'] ?? [],
                'is_lunas' => false,
            ];
            $total += $sisa;
        }

        $result[$tahun] = [
            'tahun_ajaran' => $tahun,
            'total_tunggakan' => $total,
            'items' => $formatted_items,
        ];
    }

    return $result;
}

function simad_total_sudah_bayar($koneksi, $nisn, $tahun_ajaran) {
    ensure_pembayaran_arsip_table($koneksi);

    $nisn_esc = mysqli_real_escape_string($koneksi, (string) $nisn);
    $tahun_esc = mysqli_real_escape_string($koneksi, (string) $tahun_ajaran);
    $total = 0;
    $queries = [
        "SELECT COALESCE(SUM(jumlah_bayar), 0) AS total FROM pembayaran WHERE nisn = '$nisn_esc' AND tahun_ajaran = '$tahun_esc'",
        "SELECT COALESCE(SUM(jumlah_bayar), 0) AS total FROM pembayaran_arsip WHERE nisn = '$nisn_esc' AND tahun_ajaran = '$tahun_esc'",
    ];

    foreach ($queries as $sql) {
        $q = mysqli_query($koneksi, $sql);
        if ($q && $row = mysqli_fetch_assoc($q)) {
            $total += (int) ($row['total'] ?? 0);
        }
    }

    return $total;
}

function simad_billing_for_student($koneksi, $siswa, $tahun_ajaran) {
    if (!tahun_ajaran_boleh_ditagihkan($koneksi, $tahun_ajaran)) {
        return [];
    }

    $months = bulan_akademik_list();
    $limit_index = limit_index_bulan_tahun_ajaran($koneksi, $tahun_ajaran);
    $billing = [];

    $q_jenis = mysqli_query($koneksi, "SELECT * FROM jenis_bayar WHERE status = 'Aktif' ORDER BY tipe_bayar ASC, nama_pembayaran ASC");
    if (!$q_jenis) {
        return $billing;
    }

    while ($jb = mysqli_fetch_assoc($q_jenis)) {
        if (!jenis_bayar_berlaku_untuk_kelas($jb['tagihan_kelas'] ?? '', $siswa['id_kelas'], $siswa['nama_kelas'])) {
            continue;
        }

        $unpaid_details = [];
        $total_sisa = 0;
        $total_bayar = 0;

        if ($jb['tipe_bayar'] === 'Bulanan') {
            $paid_months = ambil_bulan_bayar_tersimpan($koneksi, $siswa['nisn'], $jb['id_jenis_bayar'], $tahun_ajaran);

            foreach ($months as $index => $month) {
                if ($limit_index < 0 || $index > $limit_index) {
                    continue;
                }

                if (in_array($month, $paid_months, true)) {
                    $total_bayar += (int) $jb['nominal'];
                    continue;
                }

                $unpaid_details[] = $month;
                $total_sisa += (int) $jb['nominal'];
            }
        } else {
            $total_bayar = ambil_total_bayar_tersimpan($koneksi, $siswa['nisn'], $jb['id_jenis_bayar'], $tahun_ajaran);
            $total_sisa = max(0, (int) $jb['nominal'] - $total_bayar);
            if ($total_sisa > 0) {
                $unpaid_details[] = 'Belum Lunas';
            }
        }

        $billing[] = [
            'id_jenis_bayar' => (int) $jb['id_jenis_bayar'],
            'nama_pembayaran' => $jb['nama_pembayaran'],
            'tipe_bayar' => $jb['tipe_bayar'],
            'tahun_ajaran' => $tahun_ajaran,
            'total_nominal' => (int) $jb['nominal'],
            'total_bayar' => $total_bayar,
            'sisa_tagihan' => $total_sisa,
            'item_belum_bayar' => $unpaid_details,
            'is_lunas' => ($total_sisa <= 0),
        ];
    }

    return $billing;
}

function simad_sum_billing_tunggakan($billing) {
    $total = 0;
    foreach ($billing as $item) {
        $total += (int) ($item['sisa_tagihan'] ?? 0);
    }

    return $total;
}

function simad_sum_tunggakan_lama($tunggakan_lama) {
    $total = 0;
    foreach ($tunggakan_lama as $group) {
        $total += (int) ($group['total_tunggakan'] ?? 0);
    }

    return $total;
}

function simad_student_financial_summary($koneksi, $siswa, $tahun_ajaran) {
    $billing = simad_billing_for_student($koneksi, $siswa, $tahun_ajaran);
    $tunggakan_aktif = simad_sum_billing_tunggakan($billing);
    $tunggakan_lama_raw = cek_tunggakan_tahun_ajaran_lama($koneksi, $siswa['nisn'], $tahun_ajaran);
    $tunggakan_lama = simad_format_tunggakan_lama($tunggakan_lama_raw);
    $total_tunggakan_lama = simad_sum_tunggakan_lama($tunggakan_lama);
    $total_tunggakan = $tunggakan_aktif + $total_tunggakan_lama;

    return [
        'billing' => $billing,
        'tunggakan_tahun_ajaran_lama' => $tunggakan_lama,
        'total_tunggakan_aktif' => $tunggakan_aktif,
        'total_tunggakan_tahun_lama' => $total_tunggakan_lama,
        'total_tunggakan' => $total_tunggakan,
        'total_sudah_bayar' => simad_total_sudah_bayar($koneksi, $siswa['nisn'], $tahun_ajaran),
        'tahun_ajaran_tunggakan' => array_keys($tunggakan_lama),
        'is_lunas' => ($total_tunggakan <= 0),
    ];
}

function simad_fetch_payments($koneksi, $nisn, $tahun_ajaran_filter = '') {
    ensure_pembayaran_arsip_table($koneksi);

    $nisn_esc = mysqli_real_escape_string($koneksi, (string) $nisn);
    $payments = [];

    $pembayaran_query = "SELECT p.*, jb.nama_pembayaran
                         FROM pembayaran p
                         JOIN jenis_bayar jb ON p.id_jenis_bayar = jb.id_jenis_bayar
                         WHERE p.nisn = '$nisn_esc'";

    if ($tahun_ajaran_filter !== '') {
        $tahun_esc = mysqli_real_escape_string($koneksi, $tahun_ajaran_filter);
        $pembayaran_query .= " AND p.tahun_ajaran = '$tahun_esc'";
    }

    $pembayaran_query .= " ORDER BY p.tgl_bayar DESC, p.id_pembayaran DESC";
    $q_history = mysqli_query($koneksi, $pembayaran_query);

    while ($q_history && ($h = mysqli_fetch_assoc($q_history))) {
        $payments[] = [
            'no_transaksi' => $h['no_transaksi'] ?? '',
            'tgl_bayar' => $h['tgl_bayar'] ?? '',
            'jumlah_bayar' => (int) ($h['jumlah_bayar'] ?? 0),
            'nama_pembayaran' => $h['nama_pembayaran'] ?? '',
            'ket' => $h['ket'] ?? '',
            'waktu_input' => $h['created_at'] ?? '',
            'tahun_bayar' => $h['tahun_bayar'] ?? '',
            'tahun_ajaran' => $h['tahun_ajaran'] ?? '',
        ];
    }

    return $payments;
}

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
        'env' => 'production',
        'base_url' => 'https://sibayar.misultanfattah.sch.id',
    ]);
    exit;
}

$action = $_GET['action'] ?? 'get_student_data';
$tahun_ajaran_param = $_GET['tahun_ajaran'] ?? '';
$tahun_ajaran = simad_resolve_tahun_ajaran($koneksi, $tahun_ajaran_param);
$tahun_ajaran_aktif = get_tahun_ajaran_aktif($koneksi);
$filter_tahun_ajaran = trim((string) $tahun_ajaran_param);

// 1. Health Check & Environment Info
if ($action == 'check') {
    echo json_encode([
        'status' => 'success',
        'message' => 'Sibayar SPP API is active',
        'env' => 'production',
        'base_url' => 'https://sibayar.misultanfattah.sch.id',
        'tahun_ajaran_aktif' => $tahun_ajaran_aktif,
        'server_time' => date('Y-m-d H:i:s'),
        'php_version' => PHP_VERSION,
        'available_actions' => ['check', 'get_tahun_ajaran', 'get_all_summary', 'get_student_data'],
    ]);
    exit;
}

// 2. Get All Students Summary (Bulk Sync)
if ($action == 'get_all_summary') {
    $data = [];
    $query = mysqli_query(
        $koneksi,
        "SELECT s.nisn, s.nama, k.nama_kelas, k.id_kelas
         FROM siswa s
         JOIN kelas k ON s.id_kelas = k.id_kelas
         ORDER BY k.nama_kelas ASC, s.nama ASC"
    );

    while ($query && ($siswa = mysqli_fetch_assoc($query))) {
        $summary = simad_student_financial_summary($koneksi, $siswa, $tahun_ajaran);

        $data[] = [
            'nisn' => $siswa['nisn'],
            'nama' => $siswa['nama'],
            'kelas' => $siswa['nama_kelas'],
            'tahun_ajaran' => $tahun_ajaran,
            'total_tunggakan_aktif' => $summary['total_tunggakan_aktif'],
            'total_tunggakan_tahun_lama' => $summary['total_tunggakan_tahun_lama'],
            'total_tunggakan' => $summary['total_tunggakan'],
            'total_sudah_bayar' => $summary['total_sudah_bayar'],
            'tahun_ajaran_tunggakan' => $summary['tahun_ajaran_tunggakan'],
            'is_lunas' => $summary['is_lunas'],
        ];
    }

    echo json_encode([
        'status' => 'success',
        'data' => $data,
        'count' => count($data),
        'tahun_ajaran' => $tahun_ajaran,
        'tahun_ajaran_aktif' => $tahun_ajaran_aktif,
        'env' => 'production',
    ]);
    exit;
}

// 3. Get available tahun ajaran list
if ($action == 'get_tahun_ajaran') {
    $tahun_ajaran_list = [];

    $q_ta = mysqli_query($koneksi, "SELECT DISTINCT tahun_ajaran FROM jenis_bayar WHERE tahun_ajaran IS NOT NULL AND tahun_ajaran <> '' ORDER BY tahun_ajaran DESC");
    while ($q_ta && ($ta = mysqli_fetch_assoc($q_ta))) {
        $tahun_ajaran_list[] = $ta['tahun_ajaran'];
    }

    $q_pay = mysqli_query($koneksi, "SELECT DISTINCT tahun_ajaran FROM pembayaran WHERE tahun_ajaran IS NOT NULL AND tahun_ajaran <> '' ORDER BY tahun_ajaran DESC");
    while ($q_pay && ($ta = mysqli_fetch_assoc($q_pay))) {
        $tahun = trim((string) ($ta['tahun_ajaran'] ?? ''));
        if ($tahun !== '' && !in_array($tahun, $tahun_ajaran_list, true)) {
            $tahun_ajaran_list[] = $tahun;
        }
    }

    rsort($tahun_ajaran_list);

    echo json_encode([
        'status' => 'success',
        'data' => $tahun_ajaran_list,
        'tahun_ajaran_aktif' => $tahun_ajaran_aktif,
        'env' => 'production',
    ]);
    exit;
}

// 4. Single Student Actions
$nisn = mysqli_real_escape_string($koneksi, $_GET['nisn'] ?? '');

if (empty($nisn)) {
    echo json_encode(['status' => 'error', 'message' => 'NISN is required for this action']);
    exit;
}

$q_siswa = mysqli_query(
    $koneksi,
    "SELECT s.*, k.nama_kelas, k.id_kelas
     FROM siswa s
     JOIN kelas k ON s.id_kelas = k.id_kelas
     WHERE s.nisn = '$nisn'"
);
$d_siswa = mysqli_fetch_assoc($q_siswa);

if (!$d_siswa) {
    echo json_encode(['status' => 'error', 'message' => 'Student not found']);
    exit;
}

if ($action == 'get_student_data') {
    $summary = simad_student_financial_summary($koneksi, $d_siswa, $tahun_ajaran);

    echo json_encode([
        'status' => 'success',
        'student' => [
            'nisn' => $d_siswa['nisn'],
            'nama' => $d_siswa['nama'],
            'kelas' => $d_siswa['nama_kelas'],
        ],
        'tahun_ajaran' => $tahun_ajaran,
        'tahun_ajaran_aktif' => $tahun_ajaran_aktif,
        'billing' => $summary['billing'],
        'tunggakan_tahun_ajaran_lama' => $summary['tunggakan_tahun_ajaran_lama'],
        'payments' => simad_fetch_payments($koneksi, $nisn, $filter_tahun_ajaran),
        'summary' => [
            'total_tunggakan_aktif' => $summary['total_tunggakan_aktif'],
            'total_tunggakan_tahun_lama' => $summary['total_tunggakan_tahun_lama'],
            'total_tunggakan' => $summary['total_tunggakan'],
            'total_sudah_bayar' => $summary['total_sudah_bayar'],
            'tahun_ajaran_tunggakan' => $summary['tahun_ajaran_tunggakan'],
            'is_lunas' => $summary['is_lunas'],
        ],
        'env' => 'production',
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}
