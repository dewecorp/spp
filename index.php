<?php
include 'template/header.php';
include 'template/sidebar.php';

// 1. Hitung Jumlah Siswa
$q_siswa = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM siswa");
$d_siswa = mysqli_fetch_assoc($q_siswa);
$jml_siswa = $d_siswa['total'];

// 2. Hitung Jumlah Jenis Bayar
$q_jenis = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM jenis_bayar");
$d_jenis = mysqli_fetch_assoc($q_jenis);
$jml_jenis = $d_jenis['total'];

// 3. Hitung Total Pembayaran (Semua Waktu)
$q_bayar = mysqli_query($koneksi, "SELECT SUM(jumlah_bayar) as total FROM pembayaran");
$d_bayar = mysqli_fetch_assoc($q_bayar);
$total_bayar = $d_bayar['total'] ?? 0;

// 4. Hitung Siswa Belum Bayar (Bulan Ini)
// Asumsi: Siswa yang belum melakukan transaksi apapun di bulan ini
$bulan_ini = date('m');
$tahun_ini = date('Y');
$q_sudah_bayar = mysqli_query($koneksi, "SELECT COUNT(DISTINCT nisn) as total FROM pembayaran WHERE MONTH(tgl_bayar) = '$bulan_ini' AND YEAR(tgl_bayar) = '$tahun_ini'");
$d_sudah_bayar = mysqli_fetch_assoc($q_sudah_bayar);
$jml_sudah_bayar = $d_sudah_bayar['total'];
$jml_belum_bayar = $jml_siswa - $jml_sudah_bayar;
if ($jml_belum_bayar < 0) $jml_belum_bayar = 0; // Prevent negative if data inconsistency

// Data Grafik Pembayaran per Bulan (Tahun Ini)
$chart_labels = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"];
$chart_data = [];
for ($i = 1; $i <= 12; $i++) {
    $q_chart = mysqli_query($koneksi, "SELECT SUM(jumlah_bayar) as total FROM pembayaran WHERE MONTH(tgl_bayar) = '$i' AND YEAR(tgl_bayar) = '$tahun_ini'");
    $d_chart = mysqli_fetch_assoc($q_chart);
    $chart_data[] = $d_chart['total'] ?? 0;
}

// Data Aktivitas Terakhir (5 Transaksi Terakhir)
$q_aktivitas = mysqli_query($koneksi, "
    SELECT p.*, s.nama, j.nama_pembayaran, u.nama_lengkap as nama_petugas
    FROM pembayaran p
    JOIN siswa s ON p.nisn = s.nisn
    JOIN jenis_bayar j ON p.id_jenis_bayar = j.id_jenis_bayar
    JOIN pengguna u ON p.id_petugas = u.id_pengguna
    ORDER BY p.created_at DESC
    LIMIT 5
");
?>

<div class="row">
    <!-- Card Jumlah Siswa -->
    <div class="col-xl-3 col-lg-3 col-md-3 col-sm-6 grid-margin stretch-card">
        <div class="card card-statistics">
            <div class="card-body">
                <div class="clearfix">
                    <div class="float-left">
                        <i class="mdi mdi-account-multiple text-danger icon-lg"></i>
                    </div>
                    <div class="float-right">
                        <p class="mb-0 text-right">Jumlah Siswa</p>
                        <div class="fluid-container">
                            <h3 class="font-weight-medium text-right mb-0"><?= number_format($jml_siswa) ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Card Jumlah Jenis Bayar -->
    <div class="col-xl-3 col-lg-3 col-md-3 col-sm-6 grid-margin stretch-card">
        <div class="card card-statistics">
            <div class="card-body">
                <div class="clearfix">
                    <div class="float-left">
                        <i class="mdi mdi-receipt text-warning icon-lg"></i>
                    </div>
                    <div class="float-right">
                        <p class="mb-0 text-right">Jenis Bayar</p>
                        <div class="fluid-container">
                            <h3 class="font-weight-medium text-right mb-0"><?= number_format($jml_jenis) ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Card Total Pembayaran -->
    <div class="col-xl-3 col-lg-3 col-md-3 col-sm-6 grid-margin stretch-card">
        <div class="card card-statistics">
            <div class="card-body">
                <div class="clearfix">
                    <div class="float-left">
                        <i class="mdi mdi-cash-multiple text-success icon-lg"></i>
                    </div>
                    <div class="float-right">
                        <p class="mb-0 text-right">Total Pembayaran</p>
                        <div class="fluid-container">
                            <h3 class="font-weight-medium text-right mb-0">Rp <?= number_format($total_bayar, 0, ',', '.') ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Card Siswa Belum Bayar -->
    <div class="col-xl-3 col-lg-3 col-md-3 col-sm-6 grid-margin stretch-card">
        <div class="card card-statistics">
            <div class="card-body">
                <div class="clearfix">
                    <div class="float-left">
                        <i class="mdi mdi-account-off text-info icon-lg"></i>
                    </div>
                    <div class="float-right">
                        <p class="mb-0 text-right">Belum Bayar (Bln Ini)</p>
                        <div class="fluid-container">
                            <h3 class="font-weight-medium text-right mb-0"><?= number_format($jml_belum_bayar) ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Grafik Pembayaran -->
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Grafik Pembayaran Tahun <?= $tahun_ini ?></h4>
                <div style="position: relative; height: 300px; width: 100%;">
                    <canvas id="pembayaranChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Box Data Aktivitas -->
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Aktivitas Pembayaran Terakhir</h4>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Siswa</th>
                                <th>Pembayaran</th>
                                <th>Jumlah</th>
                                <th>Petugas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($q_aktivitas) > 0) : ?>
                                <?php while ($row = mysqli_fetch_assoc($q_aktivitas)) : ?>
                                    <tr>
                                        <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                                        <td><?= $row['nama'] ?></td>
                                        <td><?= $row['nama_pembayaran'] ?></td>
                                        <td>Rp <?= number_format($row['jumlah_bayar'], 0, ',', '.') ?></td>
                                        <td><?= $row['nama_petugas'] ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="5" class="text-center">Belum ada aktivitas pembayaran.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'template/footer.php'; ?>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        var ctx = document.getElementById('pembayaranChart').getContext('2d');
        var myChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($chart_labels) ?>,
                datasets: [{
                    label: 'Total Pembayaran (Rp)',
                    data: <?= json_encode($chart_data) ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                animation: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value, index, values) {
                                return 'Rp ' + value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                            }
                        }
                    }
                },
                responsive: true,
                maintainAspectRatio: false
            }
        });
    });
</script>