<?php
$title = 'Dashboard';
include 'template/header.php';
include 'template/sidebar.php';

// Auto-delete aktivitas lebih dari 24 jam
mysqli_query($koneksi, "DELETE FROM log_aktivitas WHERE created_at < NOW() - INTERVAL 24 HOUR");

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

// Data Aktivitas Pengguna (24 Jam Terakhir)
$q_aktivitas = mysqli_query($koneksi, "
    SELECT l.*, p.nama_lengkap 
    FROM log_aktivitas l
    LEFT JOIN pengguna p ON l.id_pengguna = p.id_pengguna
    ORDER BY l.created_at DESC
");
$jml_aktivitas = mysqli_num_rows($q_aktivitas);
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
    <!-- Timeline Aktivitas -->
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">
                    Aktivitas Pengguna (24 Jam Terakhir)
                    <span class="badge badge-primary ml-2"><?= $jml_aktivitas ?></span>
                </h4>
                <p class="card-description">Memantau aktivitas login, logout, dan manajemen data.</p>
                
                <div style="height: 400px; overflow-y: auto; overflow-x: hidden;">
                    <ul class="activity-timeline">
                        <?php if ($jml_aktivitas > 0) : ?>
                            <?php while ($row = mysqli_fetch_assoc($q_aktivitas)) : 
                                $jenis = $row['jenis_aktivitas'];
                                $icon = 'mdi-information';
                                $bg_color = 'bg-info';
    
                                if ($jenis == 'Login') {
                                    $icon = 'mdi-login';
                                    $bg_color = 'bg-success';
                                } elseif ($jenis == 'Logout') {
                                    $icon = 'mdi-logout';
                                    $bg_color = 'bg-secondary';
                                } elseif ($jenis == 'Create') {
                                    $icon = 'mdi-plus-circle';
                                    $bg_color = 'bg-primary';
                                } elseif ($jenis == 'Update') {
                                    $icon = 'mdi-pencil';
                                    $bg_color = 'bg-warning';
                                } elseif ($jenis == 'Delete') {
                                    $icon = 'mdi-delete';
                                    $bg_color = 'bg-danger';
                                }
                            ?>
                            <li>
                                <div class="activity-timeline-item">
                                    <div class="activity-icon <?= $bg_color ?>">
                                        <i class="mdi <?= $icon ?>"></i>
                                    </div>
                                    <div class="activity-content">
                                        <h5 class="font-weight-bold mb-1"><?= $row['nama_lengkap'] ?> <span class="text-muted small">- <?= $jenis ?></span></h5>
                                        <p class="mb-1 text-dark"><?= $row['deskripsi'] ?></p>
                                        <small class="text-muted">
                                            <i class="mdi mdi-clock"></i> <?= date('d/m/Y H:i', strtotime($row['created_at'])) ?> 
                                            &bull; <?= time_ago($row['created_at']) ?>
                                        </small>
                                    </div>
                                </div>
                            </li>
                            <?php endwhile; ?>
                        <?php else : ?>
                            <li>
                                <p class="text-center text-muted">Belum ada aktivitas dalam 24 jam terakhir.</p>
                            </li>
                        <?php endif; ?>
                    </ul>
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