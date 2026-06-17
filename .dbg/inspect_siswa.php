<?php
$c = @mysqli_connect('127.0.0.1', 'dev', 'devpass', 'spp');
if (!$c) {
    $c = @mysqli_connect('localhost', 'root', '', 'spp');
}
if (!$c) {
    fwrite(STDERR, mysqli_connect_error());
    exit(1);
}
$q = mysqli_query($c, "SELECT s.nisn, s.nama, k.nama_kelas FROM siswa s JOIN kelas k ON s.id_kelas = k.id_kelas WHERE k.nama_kelas IN ('1','2','3','4','5','6') ORDER BY k.nama_kelas, s.nama LIMIT 12");
if (!$q) {
    fwrite(STDERR, mysqli_error($c));
    exit(1);
}
while ($r = mysqli_fetch_assoc($q)) {
    echo json_encode($r, JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
?>
