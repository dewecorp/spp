<?php
$is_local = ($_SERVER['HTTP_HOST'] == 'localhost' || $_SERVER['HTTP_HOST'] == '127.0.0.1' || strpos($_SERVER['HTTP_HOST'], '.test') !== false || strpos($_SERVER['HTTP_HOST'], '.local') !== false);

if ($is_local) {
    $host = "127.0.0.1";
    $user = "dev";
    $pass = "devpass";
    $db   = "spp"; 
} else {
    $host = "localhost";
    $user = "kvzveyrg_sibayar";
    $pass = "sultanfattah26";
    $db   = "kvzveyrg_sibayar";
    
    // Set Base URL manual untuk produksi agar aset terbaca dengan benar
    $base_url = "https://sibayar.misultanfattah.sch.id";
}

// Matikan report mode exception agar tidak fatal error saat DB belum ada
mysqli_report(MYSQLI_REPORT_OFF);

// Koneksi ke database server
$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    // Jika koneksi gagal, coba tampilkan detail error
    $error_msg = mysqli_connect_error();
    die("Koneksi server gagal (" . $_SERVER['HTTP_HOST'] . "): " . $error_msg);
}

// Cek apakah database ada (Sudah dicek di mysqli_connect, tapi ini untuk backward compatibility)
$db_selected = true; 
if (!$koneksi) {
    $db_selected = false;
}

// Base URL (Deteksi Root Project)
if (!isset($base_url)) {
    // 1. Tentukan Protokol
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
    
    // 2. Tentukan Host
    $host = $_SERVER['HTTP_HOST'];
    
    // 3. Deteksi Path Project dari Lokasi File config.php
    // File ini ada di [root]/config/config.php
    // Jadi kita ambil dirname(__DIR__) untuk dapat [root]
    $project_root = dirname(__DIR__); // D:\laragon\www\spp
    
    // Normalisasi slash dan lowercase untuk perbandingan case-insensitive (Windows)
    $project_root = strtolower(str_replace('\\', '/', $project_root));
    $doc_root = strtolower(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']));
    
    // Hapus bagian document root dari project root untuk dapat path relatif URL
    if (strpos($project_root, $doc_root) === 0) {
        $path = substr($project_root, strlen($doc_root));
    } else {
        // Fallback jika project_root tidak diawali doc_root (misal VirtualHost)
        $path = '';
    }
    
    $path = trim($path, '/');
    
    if (empty($path)) {
        $base_url = $protocol . "://" . $host;
    } else {
        $base_url = $protocol . "://" . $host . "/" . $path;
    }
}

// Override Manual jika di Hosting (Hapus comment jika perlu)
// $base_url = "https://sibayar.misultanfattah.sch.id";

function base_url($path = "") {
    global $base_url;
    // Hapus double slash jika ada, tapi biarkan protokol (http://)
    $url = $base_url . "/" . ltrim($path, "/");
    return $url;
}

 $vendorAutoload = __DIR__ . '/../vendor/autoload.php';
 if (file_exists($vendorAutoload)) {
     require_once $vendorAutoload;
 }
 include_once __DIR__ . '/../include/activity_helper.php';
 include_once __DIR__ . '/../include/qr_helper.php';
?>
