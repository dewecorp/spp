<?php
include 'config/config.php';

echo "Structure of table 'transaksi':\n";
$result = mysqli_query($koneksi, "DESCRIBE transaksi");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
} else {
    echo "Table 'transaksi' does not exist (checking 'pembayaran' instead...)\n";
    $result = mysqli_query($koneksi, "DESCRIBE pembayaran");
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            echo $row['Field'] . " - " . $row['Type'] . "\n";
        }
    } else {
        echo "Table 'pembayaran' also does not exist: " . mysqli_error($koneksi) . "\n";
    }
}

echo "\nStructure of table 'siswa':\n";
$result = mysqli_query($koneksi, "DESCRIBE siswa");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
} else {
    echo "Error: " . mysqli_error($koneksi) . "\n";
}
?>