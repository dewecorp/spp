<?php
/**
 * ETAB Integration Endpoint
 *
 * Endpoint JSON untuk aplikasi ETAB mengambil master jenis bayar,
 * query transaksi pembayaran, dan daftar tagihan siswa untuk proses
 * tarikan/potongan tabungan.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../include/laporan_helper.php';

$valid_api_key = 'SPP_SECRET_KEY_2026';
$hosted_app_url = 'https://sibayar.misultanfattah.sch.id/';

function etab_output($payload, $http_code = 200) {
    http_response_code($http_code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function etab_request_data() {
    $data = $_GET;

    if (!empty($_POST)) {
        $data = array_merge($data, $_POST);
    }

    $raw = file_get_contents('php://input');
    if (is_string($raw) && trim($raw) !== '') {
        $json = json_decode($raw, true);
        if (is_array($json)) {
            $data = array_merge($data, $json);
        }
    }

    return $data;
}

function etab_api_key($data) {
    $api_key = '';

    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        foreach ($headers as $name => $value) {
            if (strtolower($name) === 'x-api-key') {
                $api_key = $value;
                break;
            }
        }
    }

    if ($api_key === '') {
        $api_key = isset($data['api_key']) ? (string) $data['api_key'] : '';
    }

    return $api_key;
}

function etab_bind_params($stmt, $types, $params) {
    if ($types === '') {
        return true;
    }

    $refs = [];
    $refs[] = $types;
    foreach ($params as $key => $value) {
        $refs[] = &$params[$key];
    }

    return call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt], $refs));
}

function etab_valid_date($date) {
    if (!is_string($date) || $date === '') {
        return false;
    }

    $dt = DateTime::createFromFormat('Y-m-d', $date);
    return $dt && $dt->format('Y-m-d') === $date;
}

function etab_positive_int($value, $default, $max = null) {
    $num = (int) $value;
    if ($num <= 0) {
        $num = $default;
    }
    if ($max !== null && $num > $max) {
        $num = $max;
    }
    return $num;
}

function etab_parse_kelas($raw) {
    $raw = trim((string) $raw);
    if ($raw === '') {
        return [];
    }

    $items = array_map('trim', explode(',', $raw));
    return array_values(array_filter($items, static function ($item) {
        return $item !== '';
    }));
}

function etab_exec_select($koneksi, $sql, $types = '', $params = []) {
    $stmt = mysqli_prepare($koneksi, $sql);
    if (!$stmt) {
        etab_output([
            'status' => 'error',
            'message' => 'Gagal menyiapkan query',
            'detail' => mysqli_error($koneksi),
        ], 500);
    }

    if (!etab_bind_params($stmt, $types, $params)) {
        etab_output([
            'status' => 'error',
            'message' => 'Gagal bind parameter query',
            'detail' => mysqli_stmt_error($stmt),
        ], 500);
    }

    if (!mysqli_stmt_execute($stmt)) {
        etab_output([
            'status' => 'error',
            'message' => 'Gagal menjalankan query',
            'detail' => mysqli_stmt_error($stmt),
        ], 500);
    }

    $result = mysqli_stmt_get_result($stmt);
    if (!$result) {
        etab_output([
            'status' => 'error',
            'message' => 'Gagal membaca hasil query',
            'detail' => mysqli_stmt_error($stmt),
        ], 500);
    }

    return $result;
}

function etab_exec_statement($koneksi, $sql, $types = '', $params = []) {
    $stmt = mysqli_prepare($koneksi, $sql);
    if (!$stmt) {
        etab_output([
            'status' => 'error',
            'message' => 'Gagal menyiapkan query',
            'detail' => mysqli_error($koneksi),
        ], 500);
    }

    if (!etab_bind_params($stmt, $types, $params)) {
        etab_output([
            'status' => 'error',
            'message' => 'Gagal bind parameter query',
            'detail' => mysqli_stmt_error($stmt),
        ], 500);
    }

    if (!mysqli_stmt_execute($stmt)) {
        etab_output([
            'status' => 'error',
            'message' => 'Gagal menjalankan query',
            'detail' => mysqli_stmt_error($stmt),
        ], 500);
    }

    return $stmt;
}

function etab_due_month_index() {
    $month_map = [
        'July' => 0,
        'August' => 1,
        'September' => 2,
        'October' => 3,
        'November' => 4,
        'December' => 5,
        'January' => 6,
        'February' => 7,
        'March' => 8,
        'April' => 9,
        'May' => 10,
        'June' => 11,
    ];

    return $month_map[date('F')] ?? 0;
}

function etab_billing_for_student($koneksi, $siswa, $only_unpaid = true) {
    $months = ['Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni'];
    $current_index = etab_due_month_index();
    $items = [];

    $q_jenis = etab_exec_select(
        $koneksi,
        "SELECT * FROM jenis_bayar WHERE status = 'Aktif' ORDER BY nama_pembayaran ASC"
    );

    while ($jb = mysqli_fetch_assoc($q_jenis)) {
        if (!jenis_bayar_berlaku_untuk_kelas($jb['tagihan_kelas'] ?? '', $siswa['id_kelas'], $siswa['nama_kelas'])) {
            continue;
        }

        $id_jb = (int) $jb['id_jenis_bayar'];
        $total_bayar = 0;
        $sisa_tagihan = 0;
        $detail_belum_bayar = [];
        $detail_sudah_bayar = [];

        if ($jb['tipe_bayar'] === 'Bulanan') {
            $paid_months = [];
            $q_bayar = etab_exec_select(
                $koneksi,
                'SELECT bulan_bayar FROM pembayaran WHERE nisn = ? AND id_jenis_bayar = ?',
                'si',
                [$siswa['nisn'], $id_jb]
            );

            while ($row = mysqli_fetch_assoc($q_bayar)) {
                $raw = trim((string) ($row['bulan_bayar'] ?? ''));
                if ($raw === '') {
                    continue;
                }
                $paid_months = array_merge($paid_months, array_map('trim', explode(',', $raw)));
            }

            $paid_months = array_values(array_unique(array_filter($paid_months, static function ($month) {
                return $month !== '';
            })));
            $detail_sudah_bayar = $paid_months;
            $is_extracurricular = stripos($jb['nama_pembayaran'], 'ekstrakurikuler') !== false;

            foreach ($months as $index => $month) {
                if (in_array($month, $paid_months)) {
                    $total_bayar += (int) $jb['nominal'];
                    continue;
                }

                $detail_belum_bayar[] = $month;
                if ($is_extracurricular || $index <= $current_index) {
                    $sisa_tagihan += (int) $jb['nominal'];
                }
            }
        } else {
            $q_total = etab_exec_select(
                $koneksi,
                'SELECT COALESCE(SUM(jumlah_bayar), 0) AS total FROM pembayaran WHERE nisn = ? AND id_jenis_bayar = ?',
                'si',
                [$siswa['nisn'], $id_jb]
            );
            $d_total = mysqli_fetch_assoc($q_total);
            $total_bayar = (int) ($d_total['total'] ?? 0);
            $sisa_tagihan = max(0, (int) $jb['nominal'] - $total_bayar);
            if ($sisa_tagihan > 0) {
                $detail_belum_bayar[] = 'Sisa Tagihan';
            }
        }

        if ($only_unpaid && $sisa_tagihan <= 0) {
            continue;
        }

        $items[] = [
            'id_jenis_bayar' => $id_jb,
            'kode_jenis_bayar' => (string) $id_jb,
            'nama_pembayaran' => $jb['nama_pembayaran'],
            'tipe_bayar' => $jb['tipe_bayar'],
            'nominal' => (int) $jb['nominal'],
            'kali_cicilan' => (int) ($jb['kali_cicilan'] ?? 0),
            'total_bayar' => $total_bayar,
            'sisa_tagihan' => $sisa_tagihan,
            'item_belum_bayar' => $detail_belum_bayar,
            'item_sudah_bayar' => $detail_sudah_bayar,
            'is_lunas' => $sisa_tagihan <= 0,
        ];
    }

    return $items;
}

function etab_array_value($data, $keys, $default = null) {
    foreach ($keys as $key) {
        if (isset($data[$key]) && $data[$key] !== '') {
            return $data[$key];
        }
    }

    return $default;
}

function etab_month_list($raw) {
    if (is_array($raw)) {
        $items = $raw;
    } else {
        $items = explode(',', (string) $raw);
    }

    $items = array_map('trim', $items);
    $items = array_values(array_filter($items, static function ($item) {
        return $item !== '';
    }));

    return array_values(array_unique($items));
}

function etab_next_transaction_number($koneksi, $tgl_bayar, $prefix = 'ETAB') {
    $prefix_trx = $prefix . '-' . date('Ym', strtotime($tgl_bayar)) . '-';
    $prefix_len = strlen($prefix_trx) + 1;
    $safe_prefix = mysqli_real_escape_string($koneksi, $prefix_trx);
    $q_last = mysqli_query(
        $koneksi,
        "SELECT COALESCE(MAX(CAST(SUBSTRING(no_transaksi, $prefix_len) AS UNSIGNED)), 0) AS last_urut
         FROM pembayaran
         WHERE no_transaksi LIKE '$safe_prefix%'"
    );

    if (!$q_last) {
        etab_output([
            'status' => 'error',
            'message' => 'Gagal membuat nomor transaksi ETAB',
            'detail' => mysqli_error($koneksi),
        ], 500);
    }

    $last = mysqli_fetch_assoc($q_last);
    return $prefix_trx . sprintf('%03d', ((int) ($last['last_urut'] ?? 0)) + 1);
}

function etab_default_petugas_id($koneksi, $requested_id = 0) {
    $requested_id = (int) $requested_id;
    if ($requested_id > 0) {
        $q_user = etab_exec_select(
            $koneksi,
            'SELECT id_pengguna FROM pengguna WHERE id_pengguna = ? LIMIT 1',
            'i',
            [$requested_id]
        );
        if (mysqli_fetch_assoc($q_user)) {
            return $requested_id;
        }
    }

    $q_default = etab_exec_select(
        $koneksi,
        "SELECT id_pengguna FROM pengguna ORDER BY FIELD(level, 'admin', 'petugas') DESC, id_pengguna ASC LIMIT 1"
    );
    $default = mysqli_fetch_assoc($q_default);
    if (!$default) {
        etab_output(['status' => 'error', 'message' => 'Data petugas tidak ditemukan'], 500);
    }

    return (int) $default['id_pengguna'];
}

function etab_is_potongan_tabungan($request) {
    $flag = strtolower((string) etab_array_value($request, ['potongan_tabungan', 'is_potongan_tabungan'], ''));
    if (in_array($flag, ['1', 'true', 'ya', 'yes'], true)) {
        return true;
    }

    $sumber = strtolower((string) etab_array_value($request, ['sumber', 'asal', 'metode_bayar', 'metode_pembayaran', 'jenis_transaksi'], ''));
    return strpos($sumber, 'tabungan') !== false || strpos($sumber, 'potongan') !== false;
}

$request = etab_request_data();

if (etab_api_key($request) !== $valid_api_key) {
    etab_output([
        'status' => 'error',
        'message' => 'Unauthorized: Invalid API Key',
    ], 401);
}

$action = isset($request['action']) ? strtolower(trim((string) $request['action'])) : 'check';

if ($action === 'check' || $action === 'health') {
    etab_output([
        'status' => 'success',
        'message' => 'Sibayar ETAB API is active',
        'server_time' => date('Y-m-d H:i:s'),
        'app_url' => $hosted_app_url,
        'api_url' => rtrim($hosted_app_url, '/') . '/api/etab',
        'base_url' => $base_url,
    ]);
}

if ($action === 'jenis_bayar' || $action === 'get_jenis_bayar') {
    $status = isset($request['status']) ? trim((string) $request['status']) : 'Aktif';
    $sql = 'SELECT id_jenis_bayar, nama_pembayaran, nominal, tipe_bayar, kali_cicilan, tagihan_kelas, status FROM jenis_bayar';
    $types = '';
    $params = [];

    if ($status !== '' && strtolower($status) !== 'semua' && strtolower($status) !== 'all') {
        $sql .= ' WHERE status = ?';
        $types .= 's';
        $params[] = $status;
    }

    $sql .= ' ORDER BY status ASC, nama_pembayaran ASC';
    $q = etab_exec_select($koneksi, $sql, $types, $params);

    $data = [];
    while ($row = mysqli_fetch_assoc($q)) {
        $data[] = [
            'id_jenis_bayar' => (int) $row['id_jenis_bayar'],
            'kode_jenis_bayar' => (string) $row['id_jenis_bayar'],
            'nama_pembayaran' => $row['nama_pembayaran'],
            'tipe_bayar' => $row['tipe_bayar'],
            'nominal' => (int) $row['nominal'],
            'kali_cicilan' => (int) ($row['kali_cicilan'] ?? 0),
            'tagihan_kelas' => etab_parse_kelas($row['tagihan_kelas'] ?? ''),
            'status' => $row['status'],
        ];
    }

    etab_output([
        'status' => 'success',
        'app_url' => $hosted_app_url,
        'api_url' => rtrim($hosted_app_url, '/') . '/api/etab',
        'data' => $data,
        'count' => count($data),
    ]);
}

if ($action === 'simpan_pembayaran' || $action === 'bayar' || $action === 'potongan_tabungan') {
    ensure_pembayaran_tahun_ajaran_column($koneksi);
    $nisn = trim((string) etab_array_value($request, ['nisn'], ''));
    $id_jenis_bayar = (int) etab_array_value($request, ['id_jenis_bayar', 'kode_jenis_bayar'], 0);
    $tgl_bayar = trim((string) etab_array_value($request, ['tgl_bayar', 'tanggal_bayar', 'tanggal'], date('Y-m-d')));
    $jumlah_bayar = (int) str_replace('.', '', (string) etab_array_value($request, ['jumlah_bayar', 'nominal', 'amount'], 0));
    $cicilan_ke = (int) etab_array_value($request, ['cicilan_ke'], 0);
    $bulan_bayar = etab_month_list(etab_array_value($request, ['bulan_bayar', 'bulan'], []));
    $no_transaksi_etab = trim((string) etab_array_value($request, ['no_transaksi', 'no_transaksi_etab', 'ref_etab', 'id_transaksi_etab'], ''));
    $tahun_bayar = trim((string) etab_array_value($request, ['tahun_bayar'], date('Y', strtotime($tgl_bayar))));
    $tahun_ajaran = trim((string) etab_array_value($request, ['tahun_ajaran'], get_tahun_ajaran_aktif($koneksi)));

    if ($nisn === '') {
        etab_output(['status' => 'error', 'message' => 'NISN wajib diisi'], 422);
    }
    if ($id_jenis_bayar <= 0) {
        etab_output(['status' => 'error', 'message' => 'id_jenis_bayar wajib diisi'], 422);
    }
    if (!etab_valid_date($tgl_bayar)) {
        etab_output(['status' => 'error', 'message' => 'Format tanggal bayar harus YYYY-MM-DD'], 422);
    }

    $q_siswa = etab_exec_select(
        $koneksi,
        'SELECT s.nisn, s.nama, s.id_kelas, k.nama_kelas FROM siswa s JOIN kelas k ON s.id_kelas = k.id_kelas WHERE s.nisn = ? LIMIT 1',
        's',
        [$nisn]
    );
    $siswa = mysqli_fetch_assoc($q_siswa);
    if (!$siswa) {
        etab_output(['status' => 'error', 'message' => 'Siswa tidak ditemukan'], 404);
    }

    $q_jenis = etab_exec_select(
        $koneksi,
        "SELECT * FROM jenis_bayar WHERE id_jenis_bayar = ? AND status = 'Aktif' LIMIT 1",
        'i',
        [$id_jenis_bayar]
    );
    $jenis_bayar = mysqli_fetch_assoc($q_jenis);
    if (!$jenis_bayar) {
        etab_output(['status' => 'error', 'message' => 'Jenis bayar tidak ditemukan atau tidak aktif'], 404);
    }

    if (!jenis_bayar_berlaku_untuk_kelas($jenis_bayar['tagihan_kelas'] ?? '', $siswa['id_kelas'], $siswa['nama_kelas'])) {
        etab_output(['status' => 'error', 'message' => 'Jenis bayar tidak berlaku untuk kelas siswa ini'], 422);
    }

    $tahun_ajaran_aktif = get_tahun_ajaran_aktif($koneksi);
    if ($tahun_ajaran === $tahun_ajaran_aktif) {
        $tunggakan_lama = cek_tunggakan_tahun_ajaran_lama($koneksi, $nisn, $tahun_ajaran_aktif);
        if ($tunggakan_lama) {
            etab_output([
                'status' => 'error',
                'message' => 'Pembayaran ditolak. Siswa masih memiliki tunggakan tahun ajaran lama.',
                'tahun_ajaran_tunggakan' => array_keys($tunggakan_lama),
            ], 409);
        }
    }

    if ($no_transaksi_etab !== '') {
        $q_dupe = etab_exec_select(
            $koneksi,
            'SELECT id_pembayaran FROM pembayaran WHERE no_transaksi = ? LIMIT 1',
            's',
            [$no_transaksi_etab]
        );
        if (mysqli_fetch_assoc($q_dupe)) {
            etab_output([
                'status' => 'error',
                'message' => 'Nomor transaksi sudah pernah disimpan',
                'no_transaksi' => $no_transaksi_etab,
            ], 409);
        }
    }

    $ket = trim((string) etab_array_value($request, ['ket', 'keterangan'], ''));
    if ($action === 'potongan_tabungan' || etab_is_potongan_tabungan($request)) {
        $ket = 'potongan tabungan';
    }

    $bulan_bayar_str = '';
    if ($jenis_bayar['tipe_bayar'] === 'Bulanan') {
        if (empty($bulan_bayar)) {
            etab_output(['status' => 'error', 'message' => 'bulan_bayar wajib diisi untuk jenis bayar Bulanan'], 422);
        }

        $q_paid = etab_exec_select(
            $koneksi,
            'SELECT bulan_bayar FROM pembayaran WHERE nisn = ? AND id_jenis_bayar = ? AND tahun_ajaran = ?',
            'sis',
            [$nisn, $id_jenis_bayar, $tahun_ajaran]
        );
        $paid_months = [];
        while ($paid = mysqli_fetch_assoc($q_paid)) {
            $paid_months = array_merge($paid_months, etab_month_list($paid['bulan_bayar'] ?? ''));
        }
        $paid_months = array_values(array_unique($paid_months));
        $duplicate_months = array_values(array_intersect($bulan_bayar, $paid_months));
        if (!empty($duplicate_months)) {
            etab_output([
                'status' => 'error',
                'message' => 'Bulan bayar sudah pernah dibayar',
                'bulan_duplikat' => $duplicate_months,
            ], 409);
        }

        $bulan_bayar_str = implode(', ', $bulan_bayar);
        if ($jumlah_bayar <= 0) {
            $jumlah_bayar = ((int) $jenis_bayar['nominal']) * count($bulan_bayar);
        }
        if ($ket === '') {
            $ket = 'Lunas (Bulanan) - ' . $bulan_bayar_str;
        }
        $cicilan_ke = 0;
    } else {
        if ($jumlah_bayar <= 0) {
            etab_output(['status' => 'error', 'message' => 'jumlah_bayar wajib diisi untuk jenis bayar Cicilan'], 422);
        }

        $q_total = etab_exec_select(
            $koneksi,
            'SELECT COALESCE(SUM(jumlah_bayar), 0) AS total_bayar, COALESCE(MAX(cicilan_ke), 0) AS last_cicilan FROM pembayaran WHERE nisn = ? AND id_jenis_bayar = ? AND tahun_ajaran = ?',
            'sis',
            [$nisn, $id_jenis_bayar, $tahun_ajaran]
        );
        $d_total = mysqli_fetch_assoc($q_total);
        $total_sudah_bayar = (int) ($d_total['total_bayar'] ?? 0);
        $sisa_tagihan = max(0, (int) $jenis_bayar['nominal'] - $total_sudah_bayar);

        if ($sisa_tagihan <= 0) {
            etab_output(['status' => 'error', 'message' => 'Tagihan cicilan sudah lunas'], 409);
        }
        if ($jumlah_bayar > $sisa_tagihan) {
            etab_output([
                'status' => 'error',
                'message' => 'Jumlah bayar melebihi sisa tagihan',
                'sisa_tagihan' => $sisa_tagihan,
            ], 422);
        }
        if ($cicilan_ke <= 0) {
            $cicilan_ke = ((int) ($d_total['last_cicilan'] ?? 0)) + 1;
        }
        if ($ket === '') {
            $ket = 'Cicilan ke-' . $cicilan_ke;
        }
    }

    $id_petugas = etab_default_petugas_id($koneksi, etab_array_value($request, ['id_petugas'], 0));
    $no_transaksi = $no_transaksi_etab !== '' ? $no_transaksi_etab : etab_next_transaction_number($koneksi, $tgl_bayar, 'ETAB');

    mysqli_begin_transaction($koneksi);
    try {
        $stmt_insert = mysqli_prepare(
            $koneksi,
            'INSERT INTO pembayaran (no_transaksi, id_petugas, nisn, tgl_bayar, bulan_bayar, tahun_bayar, tahun_ajaran, id_jenis_bayar, jumlah_bayar, cicilan_ke, ket) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        if (!$stmt_insert) {
            throw new Exception('Gagal menyiapkan query simpan pembayaran: ' . mysqli_error($koneksi));
        }

        mysqli_stmt_bind_param(
            $stmt_insert,
            'sisssssiiis',
            $no_transaksi,
            $id_petugas,
            $nisn,
            $tgl_bayar,
            $bulan_bayar_str,
            $tahun_bayar,
            $tahun_ajaran,
            $id_jenis_bayar,
            $jumlah_bayar,
            $cicilan_ke,
            $ket
        );

        if (!mysqli_stmt_execute($stmt_insert)) {
            throw new Exception('Gagal menyimpan pembayaran ETAB: ' . mysqli_stmt_error($stmt_insert));
        }

        $id_pembayaran = mysqli_insert_id($koneksi);
        mysqli_commit($koneksi);
    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        etab_output(['status' => 'error', 'message' => $e->getMessage()], 500);
    }

    if (function_exists('logActivity')) {
        logActivity($koneksi, 'Create', "Menerima pembayaran ETAB No: $no_transaksi NISN: $nisn");
    }

    etab_output([
        'status' => 'success',
        'message' => 'Pembayaran ETAB berhasil disimpan',
        'app_url' => $hosted_app_url,
        'api_url' => rtrim($hosted_app_url, '/') . '/api/etab',
        'data' => [
            'id_pembayaran' => (int) $id_pembayaran,
            'no_transaksi' => $no_transaksi,
            'nisn' => $nisn,
            'nama_siswa' => $siswa['nama'],
            'kelas' => $siswa['nama_kelas'],
            'tgl_bayar' => $tgl_bayar,
            'id_jenis_bayar' => $id_jenis_bayar,
            'nama_pembayaran' => $jenis_bayar['nama_pembayaran'],
            'tipe_bayar' => $jenis_bayar['tipe_bayar'],
            'jumlah_bayar' => $jumlah_bayar,
            'bulan_bayar' => $bulan_bayar,
            'tahun_ajaran' => $tahun_ajaran,
            'cicilan_ke' => $cicilan_ke,
            'ket' => $ket,
        ],
    ], 201);
}

if ($action === 'transaksi' || $action === 'query_transaksi') {
    ensure_pembayaran_tahun_ajaran_column($koneksi);
    $sql = "SELECT p.id_pembayaran, p.no_transaksi, p.id_petugas, p.nisn, s.nama, k.id_kelas, k.nama_kelas,
                   p.tgl_bayar, p.bulan_bayar, p.tahun_bayar, p.tahun_ajaran, p.id_jenis_bayar, jb.nama_pembayaran,
                   jb.tipe_bayar, p.jumlah_bayar, p.cicilan_ke, p.ket, p.created_at,
                   pg.nama_lengkap AS nama_petugas
            FROM pembayaran p
            JOIN siswa s ON p.nisn = s.nisn
            JOIN kelas k ON s.id_kelas = k.id_kelas
            JOIN jenis_bayar jb ON p.id_jenis_bayar = jb.id_jenis_bayar
            LEFT JOIN pengguna pg ON p.id_petugas = pg.id_pengguna
            WHERE 1 = 1";
    $types = '';
    $params = [];

    if (!empty($request['nisn'])) {
        $sql .= ' AND p.nisn = ?';
        $types .= 's';
        $params[] = (string) $request['nisn'];
    }

    if (!empty($request['no_transaksi'])) {
        $sql .= ' AND p.no_transaksi = ?';
        $types .= 's';
        $params[] = (string) $request['no_transaksi'];
    }

    if (!empty($request['id_jenis_bayar'])) {
        $sql .= ' AND p.id_jenis_bayar = ?';
        $types .= 'i';
        $params[] = (int) $request['id_jenis_bayar'];
    }

    if (!empty($request['tahun_ajaran'])) {
        $sql .= ' AND p.tahun_ajaran = ?';
        $types .= 's';
        $params[] = (string) $request['tahun_ajaran'];
    }

    if (!empty($request['tanggal_mulai'])) {
        if (!etab_valid_date((string) $request['tanggal_mulai'])) {
            etab_output(['status' => 'error', 'message' => 'Format tanggal_mulai harus YYYY-MM-DD'], 422);
        }
        $sql .= ' AND p.tgl_bayar >= ?';
        $types .= 's';
        $params[] = (string) $request['tanggal_mulai'];
    }

    if (!empty($request['tanggal_sampai'])) {
        if (!etab_valid_date((string) $request['tanggal_sampai'])) {
            etab_output(['status' => 'error', 'message' => 'Format tanggal_sampai harus YYYY-MM-DD'], 422);
        }
        $sql .= ' AND p.tgl_bayar <= ?';
        $types .= 's';
        $params[] = (string) $request['tanggal_sampai'];
    }

    if (!empty($request['created_mulai'])) {
        $sql .= ' AND p.created_at >= ?';
        $types .= 's';
        $params[] = (string) $request['created_mulai'];
    }

    if (!empty($request['created_sampai'])) {
        $sql .= ' AND p.created_at <= ?';
        $types .= 's';
        $params[] = (string) $request['created_sampai'];
    }

    $limit = etab_positive_int($request['limit'] ?? 100, 100, 500);
    $offset = max(0, (int) ($request['offset'] ?? 0));
    $sql .= ' ORDER BY p.tgl_bayar DESC, p.id_pembayaran DESC LIMIT ? OFFSET ?';
    $types .= 'ii';
    $params[] = $limit;
    $params[] = $offset;

    $q = etab_exec_select($koneksi, $sql, $types, $params);
    $data = [];
    $total_nominal = 0;

    while ($row = mysqli_fetch_assoc($q)) {
        $jumlah_bayar = (int) $row['jumlah_bayar'];
        $total_nominal += $jumlah_bayar;
        $bulan_bayar = etab_parse_kelas($row['bulan_bayar'] ?? '');

        $data[] = [
            'id_pembayaran' => (int) $row['id_pembayaran'],
            'no_transaksi' => $row['no_transaksi'],
            'nisn' => $row['nisn'],
            'nama_siswa' => $row['nama'],
            'id_kelas' => (int) $row['id_kelas'],
            'kelas' => $row['nama_kelas'],
            'tgl_bayar' => $row['tgl_bayar'],
            'id_jenis_bayar' => (int) $row['id_jenis_bayar'],
            'kode_jenis_bayar' => (string) $row['id_jenis_bayar'],
            'nama_pembayaran' => $row['nama_pembayaran'],
            'tipe_bayar' => $row['tipe_bayar'],
            'jumlah_bayar' => $jumlah_bayar,
            'bulan_bayar' => $bulan_bayar,
            'tahun_bayar' => $row['tahun_bayar'],
            'tahun_ajaran' => $row['tahun_ajaran'],
            'cicilan_ke' => (int) ($row['cicilan_ke'] ?? 0),
            'ket' => $row['ket'],
            'id_petugas' => (int) $row['id_petugas'],
            'nama_petugas' => $row['nama_petugas'],
            'created_at' => $row['created_at'],
        ];
    }

    etab_output([
        'status' => 'success',
        'app_url' => $hosted_app_url,
        'api_url' => rtrim($hosted_app_url, '/') . '/api/etab',
        'data' => $data,
        'count' => count($data),
        'limit' => $limit,
        'offset' => $offset,
        'total_nominal' => $total_nominal,
    ]);
}

if ($action === 'tagihan' || $action === 'query_tagihan') {
    $nisn = isset($request['nisn']) ? trim((string) $request['nisn']) : '';
    if ($nisn === '') {
        etab_output(['status' => 'error', 'message' => 'NISN wajib diisi untuk query tagihan'], 422);
    }

    $q_siswa = etab_exec_select(
        $koneksi,
        'SELECT s.*, k.nama_kelas FROM siswa s JOIN kelas k ON s.id_kelas = k.id_kelas WHERE s.nisn = ? LIMIT 1',
        's',
        [$nisn]
    );
    $siswa = mysqli_fetch_assoc($q_siswa);
    if (!$siswa) {
        etab_output(['status' => 'error', 'message' => 'Siswa tidak ditemukan'], 404);
    }

    $only_unpaid = !isset($request['only_unpaid']) || !in_array(strtolower((string) $request['only_unpaid']), ['0', 'false', 'tidak', 'no']);
    $billing = etab_billing_for_student($koneksi, $siswa, $only_unpaid);
    $total_tagihan = 0;
    foreach ($billing as $item) {
        $total_tagihan += (int) $item['sisa_tagihan'];
    }

    etab_output([
        'status' => 'success',
        'app_url' => $hosted_app_url,
        'api_url' => rtrim($hosted_app_url, '/') . '/api/etab',
        'student' => [
            'nisn' => $siswa['nisn'],
            'nama' => $siswa['nama'],
            'id_kelas' => (int) $siswa['id_kelas'],
            'kelas' => $siswa['nama_kelas'],
        ],
        'data' => $billing,
        'count' => count($billing),
        'total_tagihan' => $total_tagihan,
    ]);
}

etab_output([
    'status' => 'error',
    'message' => 'Invalid action',
    'available_actions' => ['check', 'jenis_bayar', 'transaksi', 'tagihan', 'simpan_pembayaran', 'potongan_tabungan'],
], 400);
