<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "spp_sekolah";

// Matikan report mode exception agar tidak fatal error saat DB belum ada
mysqli_report(MYSQLI_REPORT_OFF);

// Koneksi ke database server
$koneksi = mysqli_connect($host, $user, $pass);

if (!$koneksi) {
    die("Koneksi server gagal: " . mysqli_connect_error());
}

// Cek apakah database ada
$db_selected = mysqli_select_db($koneksi, $db);

if (!$db_selected) {
    // Database belum ada, mungkin perlu diinstall
    // Jangan die() disini agar script install bisa jalan
}

// Base URL (Sesuaikan dengan folder project)
$base_url = "http://localhost/spp/";

function base_url($path = "") {
    global $base_url;
    return $base_url . $path;
}
?>
