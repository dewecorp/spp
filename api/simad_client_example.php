<?php
/**
 * SIBAYAR SPP - INTEGRATION CLIENT FOR SIMAD
 * Versi: 1.0.0
 * 
 * Script ini dirancang untuk digunakan oleh pengembang SIMAD agar dapat 
 * terhubung dengan API Sibayar (SPP) dengan mudah.
 */

class SibayarClient {
    private $apiUrl;
    private $apiKey;
    private $isProduction;

    /**
     * Konfigurasi Client
     * 
     * @param string $apiKey API Key yang disepakati
     * @param bool $isProduction Set true untuk ke server web, false untuk localhost
     */
    public function __construct($apiKey = 'SPP_SECRET_KEY_2026') {
        $this->apiKey = $apiKey;
        // Selalu arahkan ke server web produksi
        $this->apiUrl = "https://sibayar.misultanfattah.sch.id/api/simad.php";
    }

    /**
     * Kirim Request ke API
     */
    private function request($action, $params = []) {
        $queryParams = array_merge([
            'api_key' => $this->apiKey,
            'action' => $action
        ], $params);

        $url = $this->apiUrl . '?' . http_build_query($queryParams);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        // Menggunakan Header X-API-KEY sebagai standar keamanan tambahan
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'X-API-KEY: ' . $this->apiKey,
            'Accept: application/json'
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            return [
                'status' => 'error',
                'message' => 'Connection failed: ' . $error
            ];
        }

        $result = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'status' => 'error',
                'message' => 'Invalid JSON response',
                'raw_response' => $response
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
     * 2. Ambil Ringkasan Data Seluruh Siswa (Bulk Sync)
     * Cocok digunakan untuk sinkronisasi massal ke database SIMAD
     */
    public function getAllStudentSummary() {
        return $this->request('get_all_summary');
    }

    /**
     * 3. Ambil Detail Tagihan & Riwayat Pembayaran Siswa per NISN
     */
    public function getStudentDetail($nisn) {
        return $this->request('get_student_data', ['nisn' => $nisn]);
    }
}

// ==========================================
// CONTOH PENGGUNAAN (EXAMPLE USAGE)
// ==========================================

/*
// 1. Inisialisasi
$sibayar = new SibayarClient('SPP_SECRET_KEY_2026');

// 2. Test Koneksi
$health = $sibayar->checkConnection();
echo "Status API: " . $health['status'] . " | Env: " . $health['env'] . "\n";

// 3. Ambil Data Semua Siswa untuk Sync Dashboard
$allData = $sibayar->getAllStudentSummary();
if ($allData['status'] === 'success') {
    foreach ($allData['data'] as $siswa) {
        echo "Nama: {$siswa['nama']} | Tunggakan: Rp" . number_format($siswa['total_tunggakan']) . "\n";
    }
}

// 4. Ambil Detail per Siswa
$detail = $sibayar->getStudentDetail('3137563185'); // Contoh NISN
if ($detail['status'] === 'success') {
    // Tampilkan Billing
    print_r($detail['billing']);
    // Tampilkan Riwayat Bayar
    print_r($detail['payments']);
}
*/
