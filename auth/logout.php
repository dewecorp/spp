<?php
session_start();
include '../config/config.php';

// Log Aktivitas
if (isset($_SESSION['id_pengguna'])) {
    logActivity($koneksi, 'Logout', 'Logout berhasil');
}

session_destroy();
header("Location: login.php");
?>
