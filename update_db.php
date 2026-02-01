<?php
include 'config/config.php';
$query = "ALTER TABLE pembayaran ADD COLUMN no_transaksi VARCHAR(50) AFTER id_pembayaran";
if (mysqli_query($koneksi, $query)) {
    echo "Column no_transaksi added successfully";
} else {
    echo "Error adding column: " . mysqli_error($koneksi);
}
?>