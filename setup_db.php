<?php
include 'config/config.php';

$query = "CREATE TABLE IF NOT EXISTS log_aktivitas (
    id_log INT PRIMARY KEY AUTO_INCREMENT,
    id_pengguna INT,
    jenis_aktivitas VARCHAR(50),
    deskripsi TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($koneksi, $query)) {
    echo "Tabel log_aktivitas berhasil dibuat/sudah ada.\n";
} else {
    echo "Gagal membuat tabel: " . mysqli_error($koneksi) . "\n";
}
?>