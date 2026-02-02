<?php
include 'config/config.php';
$result = mysqli_query($koneksi, "SHOW COLUMNS FROM pembayaran");
if (!$result) {
    echo "Query failed: " . mysqli_error($koneksi);
} else {
    while ($row = mysqli_fetch_assoc($result)) {
        echo $row['Field'] . " " . $row['Type'] . " Null: " . $row['Null'] . "\n";
    }
}
