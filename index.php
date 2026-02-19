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
                            <h3 class="font-weight-medium text-right mb-0" style="white-space: nowrap;">Rp <?= number_format($total_bayar, 0, ',', '.') ?></h3>
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
                <style>
                    .activity-timeline { list-style: none; margin: 0; padding: 0 0 0 56px; position: relative; }
                    .activity-timeline:before { content: ""; position: absolute; left: 28px; top: 0; bottom: 0; width: 2px; background: #f0f0f0; }
                    .activity-timeline li { position: relative; margin-bottom: 16px; }
                    .activity-item-card { background: #ffffff; border: 1px solid #eee; border-radius: 12px; padding: 12px 16px; padding-left: 56px; box-shadow: 0 4px 12px rgba(0,0,0,0.04); display: flex; gap: 12px; align-items: flex-start; position: relative; }
                    .activity-icon { position: absolute; left: 16px; top: 16px; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; }
                    .activity-content { flex: 1; }
                    .activity-header { display: flex; align-items: center; gap: 8px; margin-bottom: 6px; }
                    .badge-type { display: inline-block; padding: 4px 8px; border-radius: 999px; font-size: 12px; line-height: 1; }
                    .type-Login .badge-type { background: #eaf7f0; color: #2ecc71; }
                    .type-Logout .badge-type { background: #eef0f2; color: #6c757d; }
                    .type-Create .badge-type { background: #e9f0ff; color: #4e73df; }
                    .type-Update .badge-type { background: #fff7e6; color: #f6c23e; }
                    .type-Delete .badge-type { background: #ffecec; color: #e74a3b; }
                    .type-Login .activity-item-card { border-left: 4px solid #2ecc71; }
                    .type-Logout .activity-item-card { border-left: 4px solid #6c757d; }
                    .type-Create .activity-item-card { border-left: 4px solid #4e73df; }
                    .type-Update .activity-item-card { border-left: 4px solid #f6c23e; }
                    .type-Delete .activity-item-card { border-left: 4px solid #e74a3b; }
                    .activity-title { font-weight: 600; font-size: 14px; }
                    .activity-desc { margin: 4px 0 6px; color: #2c2e33; }
                    .activity-meta { font-size: 12px; color: #6c757d; display: flex; align-items: center; gap: 6px; }
                </style>
                
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
                            <li class="type-<?= $jenis ?>">
                                <div class="activity-item-card">
                                    <div class="activity-icon <?= $bg_color ?>">
                                        <i class="mdi <?= $icon ?>"></i>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-header">
                                            <span class="badge-type"><?= strtoupper($jenis) ?></span>
                                            <span class="activity-title"><?= $row['nama_lengkap'] ?></span>
                                        </div>
                                        <div class="activity-desc"><?= $row['deskripsi'] ?></div>
                                        <div class="activity-meta">
                                            <i class="mdi mdi-clock"></i>
                                            <span><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></span>
                                            <span>&bull;</span>
                                            <span><?= time_ago($row['created_at']) ?></span>
                                        </div>
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
