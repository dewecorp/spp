<?php
/**
 * SIBAYAR SPP - INTEGRATION CLIENT FOR SIMAD
 * Versi: 1.1.0
 *
 * Script ini dirancang untuk digunakan oleh pengembang SIMAD agar dapat
 * terhubung dengan API Sibayar (SPP) dengan mudah.
 */

class SibayarClient {
    private $apiUrl;
    private $apiKey;

    /**
     * Konfigurasi Client
     *
     * @param string $apiKey API Key yang disepakati
     */
    public function __construct($apiKey = 'SPP_SECRET_KEY_2026') {
        $this->apiKey = $apiKey;
        $this->apiUrl = 'https://sibayar.misultanfattah.sch.id/api/simad.php';
    }

    /**
     * Kirim Request ke API
     */
    private function request($action, $params = []) {
        $queryParams = array_merge([
            'api_key' => $this->apiKey,
            'action' => $action,
        ], $params);

        $url = $this->apiUrl . '?' . http_build_query($queryParams);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'X-API-KEY: ' . $this->apiKey,
            'Accept: application/json',
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return [
                'status' => 'error',
                'message' => 'Connection failed: ' . $error,
            ];
        }

        $result = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'status' => 'error',
                'message' => 'Invalid JSON response',
                'raw_response' => $response,
            ];
        }

        return $result;
    }

    /**
     * 1. Cek Koneksi & Info Environment
     */
    public function checkConnection() {
        return $this->request('check');
    }

    /**
     * 2. Ambil daftar tahun ajaran
     */
    public function getTahunAjaran() {
        return $this->request('get_tahun_ajaran');
    }

    /**
     * 3. Ambil Ringkasan Data Seluruh Siswa (Bulk Sync)
     * Termasuk tunggakan tahun ajaran aktif dan tahun ajaran lama.
     *
     * @param string|null $tahunAjaran Kosongkan untuk memakai tahun ajaran aktif.
     */
    public function getAllStudentSummary($tahunAjaran = null) {
        $params = [];
        if ($tahunAjaran !== null && $tahunAjaran !== '') {
            $params['tahun_ajaran'] = $tahunAjaran;
        }

        return $this->request('get_all_summary', $params);
    }

    /**
     * 4. Ambil Detail Tagihan, Tunggakan Lama, dan Riwayat Pembayaran Siswa per NISN
     *
     * @param string $nisn
     * @param string|null $tahunAjaran Kosongkan untuk memakai tahun ajaran aktif.
     */
    public function getStudentDetail($nisn, $tahunAjaran = null) {
        $params = ['nisn' => $nisn];
        if ($tahunAjaran !== null && $tahunAjaran !== '') {
            $params['tahun_ajaran'] = $tahunAjaran;
        }

        return $this->request('get_student_data', $params);
    }
}

// ==========================================
// CONTOH PENGGUNAAN (EXAMPLE USAGE)
// ==========================================

/*
$sibayar = new SibayarClient('SPP_SECRET_KEY_2026');

$health = $sibayar->checkConnection();
echo "Status API: " . $health['status'] . " | Tahun Aktif: " . ($health['tahun_ajaran_aktif'] ?? '-') . "\n";

$allData = $sibayar->getAllStudentSummary();
if ($allData['status'] === 'success') {
    foreach ($allData['data'] as $siswa) {
        echo $siswa['nama']
            . " | Tunggakan Aktif: Rp" . number_format($siswa['total_tunggakan_aktif'])
            . " | Tunggakan Lama: Rp" . number_format($siswa['total_tunggakan_tahun_lama'])
            . " | Total: Rp" . number_format($siswa['total_tunggakan']) . "\n";
    }
}

$detail = $sibayar->getStudentDetail('3137563185');
if ($detail['status'] === 'success') {
    print_r($detail['billing']);
    print_r($detail['tunggakan_tahun_ajaran_lama']);
    print_r($detail['summary']);
}
*/
