<?php
// Deteksi Environment
$is_local = ($_SERVER['HTTP_HOST'] == 'localhost' || $_SERVER['HTTP_HOST'] == '127.0.0.1' || strpos($_SERVER['HTTP_HOST'], '.test') !== false || strpos($_SERVER['HTTP_HOST'], '.local') !== false);

// KONFIGURASI SESI (24 JAM)
// Harus diset sebelum session_start()
ini_set('session.gc_maxlifetime', 86400);
session_set_cookie_params(86400);

if (session_status() == PHP_SESSION_NONE) {
    session_name("SPP_SESSION_NEW"); 
    session_start();
}

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
}

mysqli_report(MYSQLI_REPORT_OFF);
$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    // Fallback: Coba koneksi default root jika user dev gagal
    $koneksi = mysqli_connect("localhost", "root", "", "spp");
}

if (!$koneksi) {
    die("Koneksi server gagal: " . mysqli_connect_error());
}

// === LOGIKA BASE URL (SIMPLIFIED & SAFE) ===
if (!isset($base_url)) {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
    $server_host = $_SERVER['HTTP_HOST'];
    
    // 1. SKENARIO VIRTUAL HOST (.test / .local)
    if (strpos($server_host, '.test') !== false || strpos($server_host, '.local') !== false) {
        $base_url = $protocol . "://" . $server_host;
    } 
    // 2. SKENARIO LOCALHOST BIASA
    else {
        // Deteksi path project relatif terhadap document root
        $project_path = str_replace('\\', '/', __DIR__); // d:/laragon/www/spp/config
        $project_path = dirname($project_path); // d:/laragon/www/spp
        $doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']); // d:/laragon/www
        
        $path = str_replace($doc_root, '', $project_path); // /spp
        $path = trim($path, '/');
        
        if (empty($path)) {
            $base_url = $protocol . "://" . $server_host;
        } else {
            $base_url = $protocol . "://" . $server_host . "/" . $path;
        }
    }
}

// Override Manual jika di Hosting
if (!$is_local) {
    $base_url = "https://sibayar.misultanfattah.sch.id";
}

// Ensure base_url never contains local file paths
if (strpos($base_url, 'D:/') !== false || strpos($base_url, 'C:/') !== false || strpos($base_url, 'laragon') !== false) {
    // Emergency Reset
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
    $base_url = $protocol . "://" . $_SERVER['HTTP_HOST'];
}

function base_url($path = "") {
    global $base_url;
    // FINAL SAFETY NET: Jika URL rusak, paksa ke root domain
    if (strpos($base_url, 'D:/') !== false || strpos($base_url, 'C:/') !== false) {
         $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
         $base_url = $protocol . "://" . $_SERVER['HTTP_HOST'];
    }
    
    return $base_url . "/" . ltrim($path, "/");
}

$vendorAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($vendorAutoload)) {
    require_once $vendorAutoload;
}
include_once __DIR__ . '/../include/activity_helper.php';
include_once __DIR__ . '/../include/qr_helper.php';
?>
