<?php
$title = 'Dashboard';
include 'template/header.php';
include 'template/sidebar.php';

if (!isset($_SESSION['last_log_cleanup']) || (time() - (int)$_SESSION['last_log_cleanup']) > 3600) {
    mysqli_query($koneksi, "DELETE FROM log_aktivitas WHERE created_at < NOW() - INTERVAL 24 HOUR");
    $_SESSION['last_log_cleanup'] = time();
}

// 1. Hitung Jumlah Siswa
$q_siswa = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM siswa");
$d_siswa = mysqli_fetch_assoc($q_siswa);
$jml_siswa = $d_siswa['total'];

// 2. Hitung Jumlah Jenis Bayar (Aktif)
$q_jenis = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM jenis_bayar WHERE status = 'Aktif'");
$d_jenis = mysqli_fetch_assoc($q_jenis);
$jml_jenis = $d_jenis['total'];

// 3. Hitung Total Pembayaran (Semua Waktu)
$q_bayar = mysqli_query($koneksi, "SELECT SUM(jumlah_bayar) as total FROM pembayaran");
$d_bayar = mysqli_fetch_assoc($q_bayar);
$total_bayar = $d_bayar['total'] ?? 0;

// 3b. Total bayar per jenis (dinamis, mengikuti jenis bayar aktif)
$jenis_totals = [];
$q_jenis_total = mysqli_query($koneksi, "
    SELECT
        jb.id_jenis_bayar,
        jb.nama_pembayaran,
        COALESCE(SUM(p.jumlah_bayar), 0) AS total_bayar_jenis
    FROM jenis_bayar jb
    LEFT JOIN pembayaran p ON p.id_jenis_bayar = jb.id_jenis_bayar
    WHERE jb.status = 'Aktif'
    GROUP BY jb.id_jenis_bayar, jb.nama_pembayaran
    ORDER BY
        CASE
            WHEN LOWER(jb.nama_pembayaran) LIKE '%ekstrakurikuler%' THEN 1
            WHEN LOWER(jb.nama_pembayaran) LIKE '%lks%' THEN 2
            WHEN LOWER(jb.nama_pembayaran) LIKE '%ujian%' THEN 3
            WHEN LOWER(jb.nama_pembayaran) LIKE '%rekreasi%' THEN 4
            ELSE 5
        END,
        jb.nama_pembayaran ASC
");
while ($row = mysqli_fetch_assoc($q_jenis_total)) {
    $jenis_totals[] = $row;
}

$tahun_ini = date('Y');
$tahun_ajaran_aktif_dashboard = get_tahun_ajaran_aktif($koneksi);
$total_tagihan = 0;
$q_siswa_tagihan = mysqli_query($koneksi, "SELECT nisn FROM siswa");
while ($siswa_tagihan = mysqli_fetch_assoc($q_siswa_tagihan)) {
    $tagihan_siswa = cek_tagihan_tunggakan($koneksi, $siswa_tagihan['nisn'], $tahun_ajaran_aktif_dashboard);
    if (!$tagihan_siswa) {
        continue;
    }

    foreach ($tagihan_siswa as $item_tagihan) {
        $total_tagihan += (int) ($item_tagihan['sisa'] ?? 0);
    }
}

// Data Grafik Pembayaran per Bulan (Tahun Ini)
$chart_labels = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"];
$chart_data = array_fill(0, 12, 0);
$start_year = $tahun_ini . '-01-01';
$next_year = ((int)$tahun_ini + 1) . '-01-01';
$q_chart = mysqli_query($koneksi, "SELECT MONTH(tgl_bayar) AS bulan, COALESCE(SUM(jumlah_bayar), 0) AS total FROM pembayaran WHERE tgl_bayar >= '$start_year' AND tgl_bayar < '$next_year' GROUP BY MONTH(tgl_bayar)");
while ($r = mysqli_fetch_assoc($q_chart)) {
    $idx = ((int)($r['bulan'] ?? 0)) - 1;
    if ($idx >= 0 && $idx < 12) {
        $chart_data[$idx] = $r['total'] ?? 0;
    }
}

// Data Aktivitas Pengguna (24 Jam Terakhir)
$q_aktivitas_count = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM log_aktivitas WHERE created_at >= NOW() - INTERVAL 24 HOUR");
$d_aktivitas_count = mysqli_fetch_assoc($q_aktivitas_count);
$jml_aktivitas = $d_aktivitas_count['total'] ?? 0;
$q_aktivitas = mysqli_query($koneksi, "
    SELECT l.*, p.nama_lengkap 
    FROM log_aktivitas l
    LEFT JOIN pengguna p ON l.id_pengguna = p.id_pengguna
    WHERE l.created_at >= NOW() - INTERVAL 24 HOUR
    ORDER BY l.created_at DESC
    LIMIT 200
");
?>

<div class="mb-7 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <p class="text-sm font-semibold text-emerald-700">Ringkasan pembayaran</p>
        <h1 class="mt-1 text-2xl font-extrabold tracking-normal text-slate-950">Dashboard SiBayar</h1>
    </div>
    <div class="inline-flex w-fit items-center gap-2 rounded-full border border-emerald-200 bg-white px-3 py-2 text-sm font-semibold text-slate-600 shadow-sm">
        <i class="mdi mdi-calendar-clock text-emerald-600"></i>
        Tahun <?= $tahun_ini ?>
    </div>
</div>

<div class="mb-6 grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
    <?php
    $summary_cards = [
        ['label' => 'Jumlah Siswa', 'value' => number_format($jml_siswa), 'icon' => 'mdi-account-multiple', 'tone' => 'emerald'],
        ['label' => 'Jenis Bayar Aktif', 'value' => number_format($jml_jenis), 'icon' => 'mdi-receipt-text', 'tone' => 'sky'],
        ['label' => 'Total Pembayaran', 'value' => 'Rp ' . number_format($total_bayar, 0, ',', '.'), 'icon' => 'mdi-cash-multiple', 'tone' => 'violet'],
        ['label' => 'Total Tagihan', 'value' => 'Rp ' . number_format($total_tagihan, 0, ',', '.'), 'icon' => 'mdi-file-document-alert', 'tone' => 'amber'],
    ];
    foreach ($summary_cards as $card) :
        $tone_class = [
            'emerald' => 'bg-emerald-500 shadow-emerald-500/20',
            'sky' => 'bg-sky-500 shadow-sky-500/20',
            'violet' => 'bg-sky-500 shadow-sky-500/20',
            'amber' => 'bg-amber-500 shadow-amber-500/20',
        ][$card['tone']];
    ?>
    <div class="rounded-lg border border-slate-200 bg-white px-5 py-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
        <div class="flex items-center gap-4">
            <div class="grid h-12 w-12 shrink-0 place-items-center rounded-lg text-white shadow-lg <?= $tone_class ?>">
                <i class="mdi <?= $card['icon'] ?> text-2xl"></i>
            </div>
            <div class="min-w-0">
                <p class="text-sm font-semibold leading-5 text-slate-500"><?= $card['label'] ?></p>
                <h3 class="mt-1 break-words text-xl font-extrabold leading-7 tracking-normal text-slate-950"><?= $card['value'] ?></h3>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="mb-6 grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
    <?php
    $icon_by_keyword = [
        'ekstrakurikuler' => 'mdi-soccer',
        'lks' => 'mdi-book-open-page-variant',
        'ujian' => 'mdi-file-check',
        'rekreasi' => 'mdi-bus',
    ];
    foreach ($jenis_totals as $jenis_item) :
        $nama_jenis = $jenis_item['nama_pembayaran'];
        $nama_jenis_tampil = preg_replace('/ekstrakurikuler/i', 'Ekskul', $nama_jenis);
        $nama_lower = strtolower($nama_jenis);
        $icon = 'mdi-cash';
        foreach ($icon_by_keyword as $keyword => $mapped_icon) {
            if (strpos($nama_lower, $keyword) !== false) {
                $icon = $mapped_icon;
                break;
            }
        }
    ?>
    <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <p class="truncate text-sm font-semibold text-slate-500" title="Total Bayar <?= htmlspecialchars($nama_jenis_tampil) ?>">
                    Total Bayar <?= htmlspecialchars($nama_jenis_tampil) ?>
                </p>
                <h3 class="mt-2 break-words text-xl font-extrabold tracking-normal text-slate-950">
                    Rp <?= number_format($jenis_item['total_bayar_jenis'], 0, ',', '.') ?>
                </h3>
            </div>
            <div class="grid h-11 w-11 shrink-0 place-items-center rounded-lg bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100">
                <i class="mdi <?= $icon ?> text-2xl"></i>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="mb-6 grid grid-cols-1 gap-5">
    <!-- Grafik Pembayaran -->
    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h4 class="mb-4 text-lg font-extrabold tracking-normal text-slate-950">Grafik Pembayaran Tahun <?= $tahun_ini ?></h4>
            <div style="position: relative; height: 300px; width: 100%;">
                <canvas id="pembayaranChart"></canvas>
            </div>
    </div>
</div>

<div class="grid grid-cols-1 gap-5">
    <!-- Timeline Aktivitas -->
    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h4 class="mb-2 text-lg font-extrabold tracking-normal text-slate-950">
                Aktivitas Pengguna (24 Jam Terakhir)
                <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full bg-primary text-white ml-2"><?= $jml_aktivitas ?></span>
            </h4>
            <p class="text-gray-500 text-sm mb-4">Memantau aktivitas login, logout, dan manajemen data.</p>
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
            
            <div class="h-[400px] overflow-y-auto overflow-x-hidden">
                    <ul class="activity-timeline">
                        <?php if ($jml_aktivitas > 0) : ?>
                            <?php while ($row = mysqli_fetch_assoc($q_aktivitas)) : 
                                $jenis = $row['jenis_aktivitas'];
                                $icon = 'mdi-information';
                                $bg_color = 'bg-sky-500';
    
                                if ($jenis == 'Login') {
                                    $icon = 'mdi-login';
                                    $bg_color = 'bg-emerald-500';
                                } elseif ($jenis == 'Logout') {
                                    $icon = 'mdi-logout';
                                    $bg_color = 'bg-gray-500';
                                } elseif ($jenis == 'Create') {
                                    $icon = 'mdi-plus-circle';
                                    $bg_color = 'bg-primary';
                                } elseif ($jenis == 'Update') {
                                    $icon = 'mdi-pencil';
                                    $bg_color = 'bg-amber-500';
                                } elseif ($jenis == 'Delete') {
                                    $icon = 'mdi-delete';
                                    $bg_color = 'bg-red-500';
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
                                <p class="text-center text-gray-500">Belum ada aktivitas dalam 24 jam terakhir.</p>
                            </li>
                        <?php endif; ?>
                    </ul>
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
                    backgroundColor: 'rgba(5, 150, 105, 0.18)',
                    borderColor: '#10b981',
                    borderRadius: 8,
                    borderSkipped: false,
                    borderWidth: 1,
                    maxBarThickness: 42
                }]
            },
            options: {
                animation: false,
                plugins: {
                    legend: {
                        labels: {
                            color: '#475569',
                            font: { family: 'Nunito Sans', weight: '600' }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#e2e8f0' },
                        ticks: {
                            color: '#64748b',
                            callback: function(value, index, values) {
                                return 'Rp ' + value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                            }
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#64748b' }
                    }
                },
                responsive: true,
                maintainAspectRatio: false
            }
        });
    });
</script>
