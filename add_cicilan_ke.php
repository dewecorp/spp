<?php
include 'config/config.php';

$query = "ALTER TABLE pembayaran ADD COLUMN cicilan_ke INT DEFAULT 0 AFTER jumlah_bayar";
if (mysqli_query($koneksi, $query)) {
    echo "Column 'cicilan_ke' added successfully to 'pembayaran' table.";
} else {
    echo "Error adding column: " . mysqli_error($koneksi);
}
?>