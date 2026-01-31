<?php
include 'config/config.php';

// Buat Database jika belum ada
$query_db = "CREATE DATABASE IF NOT EXISTS spp_sekolah";
if (mysqli_query($koneksi, $query_db)) {
    echo "Database berhasil dibuat atau sudah ada.<br>";
    mysqli_select_db($koneksi, "spp_sekolah");
} else {
    die("Gagal membuat database: " . mysqli_error($koneksi));
}

// Tabel Pengguna
$sql_pengguna = "CREATE TABLE IF NOT EXISTS pengguna (
    id_pengguna INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    role ENUM('admin', 'petugas') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if (mysqli_query($koneksi, $sql_pengguna)) echo "Tabel pengguna OK.<br>";

// Insert Admin Default
$pass_admin = password_hash('admin123', PASSWORD_DEFAULT);
$sql_insert_admin = "INSERT INTO pengguna (username, password, nama_lengkap, role) 
                     SELECT 'admin', '$pass_admin', 'Administrator', 'admin' 
                     WHERE NOT EXISTS (SELECT username FROM pengguna WHERE username = 'admin')";
mysqli_query($koneksi, $sql_insert_admin);

// Tabel Kelas
$sql_kelas = "CREATE TABLE IF NOT EXISTS kelas (
    id_kelas INT AUTO_INCREMENT PRIMARY KEY,
    nama_kelas VARCHAR(20) NOT NULL
)";
if (mysqli_query($koneksi, $sql_kelas)) echo "Tabel kelas OK.<br>";

// Tabel Siswa
$sql_siswa = "CREATE TABLE IF NOT EXISTS siswa (
    nisn VARCHAR(20) PRIMARY KEY,
    nis VARCHAR(20) NOT NULL,
    nama VARCHAR(100) NOT NULL,
    id_kelas INT NOT NULL,
    alamat TEXT,
    no_telp VARCHAR(20),
    FOREIGN KEY (id_kelas) REFERENCES kelas(id_kelas) ON DELETE CASCADE
)";
if (mysqli_query($koneksi, $sql_siswa)) echo "Tabel siswa OK.<br>";

// Tabel Jenis Bayar
$sql_jenis = "CREATE TABLE IF NOT EXISTS jenis_bayar (
    id_jenis_bayar INT AUTO_INCREMENT PRIMARY KEY,
    nama_pembayaran VARCHAR(50) NOT NULL,
    nominal INT NOT NULL,
    tahun_ajaran VARCHAR(20) NOT NULL
)";
if (mysqli_query($koneksi, $sql_jenis)) echo "Tabel jenis_bayar OK.<br>";

// Tabel Pembayaran
$sql_bayar = "CREATE TABLE IF NOT EXISTS pembayaran (
    id_pembayaran INT AUTO_INCREMENT PRIMARY KEY,
    id_petugas INT NOT NULL,
    nisn VARCHAR(20) NOT NULL,
    tgl_bayar DATE NOT NULL,
    bulan_bayar VARCHAR(20),
    tahun_bayar VARCHAR(20),
    id_jenis_bayar INT NOT NULL,
    jumlah_bayar INT NOT NULL,
    ket TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_petugas) REFERENCES pengguna(id_pengguna),
    FOREIGN KEY (nisn) REFERENCES siswa(nisn) ON DELETE CASCADE,
    FOREIGN KEY (id_jenis_bayar) REFERENCES jenis_bayar(id_jenis_bayar)
)";
if (mysqli_query($koneksi, $sql_bayar)) echo "Tabel pembayaran OK.<br>";

// Tabel Pengaturan
$sql_pengaturan = "CREATE TABLE IF NOT EXISTS pengaturan (
    id_pengaturan INT PRIMARY KEY,
    nama_sekolah VARCHAR(100),
    alamat_sekolah TEXT,
    logo VARCHAR(255)
)";
if (mysqli_query($koneksi, $sql_pengaturan)) {
    echo "Tabel pengaturan OK.<br>";
    // Insert default pengaturan
    $sql_insert_set = "INSERT INTO pengaturan (id_pengaturan, nama_sekolah, alamat_sekolah) 
                       SELECT 1, 'MI Sultan Fattah Sukosono', 'Sukosono, Jepara'
                       WHERE NOT EXISTS (SELECT id_pengaturan FROM pengaturan WHERE id_pengaturan = 1)";
    mysqli_query($koneksi, $sql_insert_set);
}

echo "Selesai instalasi database. Silakan hapus file ini jika sudah live.";
?>
