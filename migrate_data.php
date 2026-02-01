<?php
include 'config/config.php';
// Select rows with empty no_transaksi
$q = mysqli_query($koneksi, "SELECT id_pembayaran FROM pembayaran WHERE no_transaksi IS NULL OR no_transaksi = ''");
$count = 0;
while($row = mysqli_fetch_assoc($q)) {
    $id = $row['id_pembayaran'];
    // Generate distinct transaction ID for each old record
    $trx = 'TRX-OLD-' . $id . '-' . rand(1000,9999);
    mysqli_query($koneksi, "UPDATE pembayaran SET no_transaksi='$trx' WHERE id_pembayaran='$id'");
    $count++;
}
echo "Updated $count old records.";
?>