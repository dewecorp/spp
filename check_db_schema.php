<?php
include 'config/config.php';

echo "<h2>Table: jenis_bayar</h2>";
$q = mysqli_query($koneksi, "DESCRIBE jenis_bayar");
echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
while ($row = mysqli_fetch_assoc($q)) {
    echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td><td>{$row['Default']}</td><td>{$row['Extra']}</td></tr>";
}
echo "</table>";

echo "<h2>Sample jenis_bayar Data</h2>";
$q2 = mysqli_query($koneksi, "SELECT * FROM jenis_bayar LIMIT 5");
echo "<table border='1'>";
$first = true;
while ($row = mysqli_fetch_assoc($q2)) {
    if ($first) {
        echo "<tr><th>" . implode("</th><th>", array_keys($row)) . "</th></tr>";
        $first = false;
    }
    echo "<tr><td>" . implode("</td><td>", array_values($row)) . "</td></tr>";
}
echo "</table>";

echo "<h2>Table: pembayaran</h2>";
$q3 = mysqli_query($koneksi, "DESCRIBE pembayaran");
echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
while ($row = mysqli_fetch_assoc($q3)) {
    echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td><td>{$row['Default']}</td><td>{$row['Extra']}</td></tr>";
}
echo "</table>";

echo "<h2>Sample pembayaran Data</h2>";
$q4 = mysqli_query($koneksi, "SELECT * FROM pembayaran LIMIT 5");
echo "<table border='1'>";
$first = true;
while ($row = mysqli_fetch_assoc($q4)) {
    if ($first) {
        echo "<tr><th>" . implode("</th><th>", array_keys($row)) . "</th></tr>";
        $first = false;
    }
    echo "<tr><td>" . implode("</td><td>", array_values($row)) . "</td></tr>";
}
echo "</table>";

echo "<h2>Table: pembayaran_arsip</h2>";
$q5 = mysqli_query($koneksi, "DESCRIBE pembayaran_arsip");
if ($q5) {
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = mysqli_fetch_assoc($q5)) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td><td>{$row['Default']}</td><td>{$row['Extra']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "Table pembayaran_arsip not exists";
}
?>
