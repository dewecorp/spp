<?php
$title = 'Detail Laporan Pembayaran';
include '../template/header.php';
include '../template/sidebar.php';

$nisn = $_GET['nisn'];
$q_siswa = mysqli_query($koneksi, "SELECT * FROM siswa JOIN kelas ON siswa.id_kelas = kelas.id_kelas WHERE nisn = '$nisn'");
$d_siswa = mysqli_fetch_assoc($q_siswa);

if (!$d_siswa) {
    echo "<script>alert('Data siswa tidak ditemukan!'); window.location='laporan.php';</script>";
    exit;
}
?>

<div class="row">
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title mb-0">Laporan Pembayaran Siswa</h4>
                    <div>
                        <a href="cetak_laporan.php?nisn=<?= $nisn ?>" target="_blank" class="btn btn-warning btn-icon-text">
                            <i class="mdi mdi-printer btn-icon-prepend"></i> Cetak Laporan
                        </a>
                        <a href="laporan.php?v=1&id_kelas=<?= $d_siswa['id_kelas'] ?>" class="btn btn-light">Kembali</a>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <th width="150">NISN</th>
                                <td>: <?= $d_siswa['nisn'] ?></td>
                            </tr>
                            <tr>
                                <th>Nama Siswa</th>
                                <td>: <?= $d_siswa['nama'] ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <th width="150">Kelas</th>
                                <td>: <?= $d_siswa['nama_kelas'] ?></td>
                            </tr>
                            <tr>
                                <th>Tahun Ajaran</th>
                                <td>: <?= date('Y') ?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <?php
                // Ambil semua jenis pembayaran yang berlaku untuk kelas siswa ini
                // Logic: Check tagihan_kelas contains id_kelas OR tagihan_kelas is NULL/Empty (Assuming applies to all if logic dictates, but for now stick to explicit list if available)
                // Note: Based on previous check, some types have tagihan_kelas, some don't.
                // Assuming if tagihan_kelas is empty/null, it might apply to all? Or none?
                // Let's assume if column exists and has values, we use FIND_IN_SET.
                // If id_jenis_bayar 1 has no tagihan_kelas printed, let's assume it applies to all for now or check logic later.
                
                $id_kelas_siswa = $d_siswa['id_kelas'];
                $q_jenis = mysqli_query($koneksi, "SELECT * FROM jenis_bayar ORDER BY tipe_bayar ASC");
                
                while ($d_jenis = mysqli_fetch_assoc($q_jenis)) {
                    // Filter tagihan kelas
                    // Jika tagihan_kelas tidak kosong, cek apakah kelas siswa ada di dalamnya
                    // Jika kosong, anggap berlaku untuk semua (atau sesuaikan logic jika user minta lain)
                    $applies = true;
                    if (!empty($d_jenis['tagihan_kelas'])) {
                        $kelas_ids = explode(',', $d_jenis['tagihan_kelas']);
                        if (!in_array($id_kelas_siswa, $kelas_ids)) {
                            $applies = false;
                        }
                    }

                    if ($applies) {
                ?>
                        <div class="card mb-4 border border-secondary">
                            <div class="card-header bg-secondary text-white">
                                <h6 class="mb-0 text-white"><?= $d_jenis['nama_pembayaran'] ?> (<?= $d_jenis['tipe_bayar'] ?>) - Rp. <?= number_format($d_jenis['nominal'], 0, ',', '.') ?></h6>
                            </div>
                            <div class="card-body p-0">
                                <table class="table table-bordered">
                                    <?php if ($d_jenis['tipe_bayar'] == 'Bulanan') { ?>
                                        <thead class="bg-light">
                                            <tr>
                                                <th>Bulan</th>
                                                <th>Status</th>
                                                <th>Tanggal Bayar</th>
                                                <th>Jumlah</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $bulan = ['Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni'];
                                            
                                            // Calculate current month index (relative to school year starting July)
                                            $current_month_num = date('n'); // 1-12
                                            $limit_index = ($current_month_num >= 7) ? $current_month_num - 7 : $current_month_num + 5;
                                            
                                            foreach ($bulan as $index => $bln) {
                                                if ($index > $limit_index) continue; // Skip future months
                                                
                                                $q_bayar = mysqli_query($koneksi, "SELECT * FROM pembayaran WHERE nisn = '$nisn' AND id_jenis_bayar = '" . $d_jenis['id_jenis_bayar'] . "' AND bulan_bayar = '$bln'");
                                                $d_bayar = mysqli_fetch_assoc($q_bayar);
                                                
                                                $status = $d_bayar ? '<span class="badge badge-success">Lunas</span>' : '<span class="badge badge-danger">Belum Bayar</span>';
                                                $tgl = $d_bayar ? date('d/m/Y', strtotime($d_bayar['tgl_bayar'])) : '-';
                                                $jml = $d_bayar ? 'Rp. ' . number_format($d_bayar['jumlah_bayar'], 0, ',', '.') : '-';
                                            ?>
                                                <tr>
                                                    <td><?= $bln ?></td>
                                                    <td><?= $status ?></td>
                                                    <td><?= $tgl ?></td>
                                                    <td><?= $jml ?></td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    <?php } else { 
                                        // Tipe Cicilan / Bebas
                                        $q_total_bayar = mysqli_query($koneksi, "SELECT SUM(jumlah_bayar) as total FROM pembayaran WHERE nisn = '$nisn' AND id_jenis_bayar = '" . $d_jenis['id_jenis_bayar'] . "'");
                                        $d_total = mysqli_fetch_assoc($q_total_bayar);
                                        $total_bayar = $d_total['total'] ?? 0;
                                        $sisa = $d_jenis['nominal'] - $total_bayar;
                                        $status_lunas = ($sisa <= 0) ? '<span class="badge badge-success">Lunas</span>' : '<span class="badge badge-warning">Belum Lunas</span>';
                                    ?>
                                        <tbody>
                                            <tr>
                                                <td width="200">Total Tagihan</td>
                                                <td>Rp. <?= number_format($d_jenis['nominal'], 0, ',', '.') ?></td>
                                            </tr>
                                            <tr>
                                                <td>Total Dibayar</td>
                                                <td>Rp. <?= number_format($total_bayar, 0, ',', '.') ?></td>
                                            </tr>
                                            <tr>
                                                <td>Sisa Tagihan</td>
                                                <td class="text-danger font-weight-bold">Rp. <?= number_format($sisa > 0 ? $sisa : 0, 0, ',', '.') ?></td>
                                            </tr>
                                            <tr>
                                                <td>Status</td>
                                                <td><?= $status_lunas ?></td>
                                            </tr>
                                            <tr>
                                                <td colspan="2">
                                                    <strong>Riwayat Pembayaran:</strong>
                                                    <ul class="mt-2">
                                                        <?php
                                                        $q_riwayat = mysqli_query($koneksi, "SELECT * FROM pembayaran WHERE nisn = '$nisn' AND id_jenis_bayar = '" . $d_jenis['id_jenis_bayar'] . "' ORDER BY tgl_bayar ASC");
                                                        if (mysqli_num_rows($q_riwayat) > 0) {
                                                            while ($r = mysqli_fetch_assoc($q_riwayat)) {
                                                                echo "<li>" . date('d/m/Y', strtotime($r['tgl_bayar'])) . " - Rp. " . number_format($r['jumlah_bayar'], 0, ',', '.') . "</li>";
                                                            }
                                                        } else {
                                                            echo "<li>Belum ada pembayaran</li>";
                                                        }
                                                        ?>
                                                    </ul>
                                                </td>
                                            </tr>
                                        </tbody>
                                    <?php } ?>
                                </table>
                            </div>
                        </div>
                <?php
                    } // end if applies
                } // end while jenis bayar
                ?>

            </div>
        </div>
    </div>
</div>

<?php include '../template/footer.php'; ?>