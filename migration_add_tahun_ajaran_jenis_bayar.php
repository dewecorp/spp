<?php
include 'config/config.php';

// Check if tahun_ajaran column exists in jenis_bayar
$check = mysqli_query($koneksi, "SHOW COLUMNS FROM jenis_bayar LIKE 'tahun_ajaran'");
if (mysqli_num_rows($check) == 0) {
    // Add the column
    $alter = mysqli_query($koneksi, "ALTER TABLE jenis_bayar ADD COLUMN tahun_ajaran VARCHAR(20) NULL DEFAULT NULL AFTER status");
    if ($alter) {
        echo "✅ Column tahun_ajaran added to jenis_bayar successfully\n";
    } else {
        echo "❌ Error adding column: " . mysqli_error($koneksi) . "\n";
    }
} else {
    echo "✅ Column tahun_ajaran already exists in jenis_bayar\n";
}

// Also make sure pembayaran has tahun_ajaran
$check2 = mysqli_query($koneksi, "SHOW COLUMNS FROM pembayaran LIKE 'tahun_ajaran'");
if (mysqli_num_rows($check2) == 0) {
    $alter2 = mysqli_query($koneksi, "ALTER TABLE pembayaran ADD COLUMN tahun_ajaran VARCHAR(20) NULL DEFAULT NULL AFTER tahun_bayar");
    if ($alter2) {
        echo "✅ Column tahun_ajaran added to pembayaran successfully\n";
    } else {
        echo "❌ Error adding column to pembayaran: " . mysqli_error($koneksi) . "\n";
    }
} else {
    echo "✅ Column tahun_ajaran already exists in pembayaran\n";
}

echo "\nMigration complete!";
?>
