<?php
$c = @mysqli_connect('127.0.0.1', 'dev', 'devpass', 'spp');
if (!$c) {
    $c = @mysqli_connect('localhost', 'root', '', 'spp');
}
if (!$c) {
    fwrite(STDERR, mysqli_connect_error());
    exit(1);
}
$q = mysqli_query($c, "SELECT id_jenis_bayar, nama_pembayaran, tipe_bayar, nominal, IFNULL(tagihan_kelas,'') AS tagihan_kelas, status FROM jenis_bayar ORDER BY id_jenis_bayar");
if (!$q) {
    fwrite(STDERR, mysqli_error($c));
    exit(1);
}
while ($r = mysqli_fetch_assoc($q)) {
    echo json_encode($r, JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
?>
