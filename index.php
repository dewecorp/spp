<?php
$title = 'Dashboard';
include 'template/header.php';
include 'template/sidebar.php';

if (!isset($_SESSION['last_log_cleanup']) || (time() - (int)$_SESSION['last_log_cleanup']) > 3600) {
    mysqli_query($koneksi, "DELETE FROM log_aktivitas WHERE created_at < NOW() - INTERVAL 24 HOUR");
    $_SESSION['last_log_cleanup'] = time();
}

function dashboard_kelas_sort_key($nama_kelas) {
    $nama = strtoupper(trim((string) $nama_kelas));
    if (strpos($nama, 'ALUMNI') !== false) {
        return 99;
    }

    $roman_map = [
        'I' => 1,
        'II' => 2,
        'III' => 3,
        'IV' => 4,
        'V' => 5,
        'VI' => 6,
    ];

    if (isset($roman_map[$nama])) {
        return $roman_map[$nama];
    }

    if (preg_match('/\b([1-6])\b/', $nama, $m) || preg_match('/([1-6])/', $nama, $m)) {
        return (int) $m[1];
    }

    return 50;
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
$tahun_ajaran_sebelumnya_dashboard = tahun_ajaran_sebelumnya($tahun_ajaran_aktif_dashboard);
$total_tagihan = 0;
$kelas_tunggakan = [];
$q_kelas_dashboard = mysqli_query($koneksi, "SELECT id_kelas, nama_kelas FROM kelas ORDER BY nama_kelas ASC");
while ($kelas_dashboard = mysqli_fetch_assoc($q_kelas_dashboard)) {
    $id_kelas_dashboard = (int) $kelas_dashboard['id_kelas'];
    $kelas_tunggakan[$id_kelas_dashboard] = [
        'id_kelas' => $id_kelas_dashboard,
        'nama_kelas' => $kelas_dashboard['nama_kelas'],
        'total_siswa' => 0,
        'siswa_tunggakan' => 0,
        'total_tagihan' => 0,
        'siswa' => [],
    ];
}

$q_siswa_tagihan = mysqli_query($koneksi, "
    SELECT s.nisn, s.nama, k.id_kelas, k.nama_kelas
    FROM siswa s
    JOIN kelas k ON s.id_kelas = k.id_kelas
    ORDER BY k.nama_kelas ASC, s.nama ASC
");
while ($siswa_tagihan = mysqli_fetch_assoc($q_siswa_tagihan)) {
    $id_kelas_dashboard = (int) $siswa_tagihan['id_kelas'];
    if (!isset($kelas_tunggakan[$id_kelas_dashboard])) {
        $kelas_tunggakan[$id_kelas_dashboard] = [
            'id_kelas' => $id_kelas_dashboard,
            'nama_kelas' => $siswa_tagihan['nama_kelas'],
            'total_siswa' => 0,
            'siswa_tunggakan' => 0,
            'total_tagihan' => 0,
            'siswa' => [],
        ];
    }

    $kelas_tunggakan[$id_kelas_dashboard]['total_siswa']++;
    $tagihan_siswa_per_tahun = cek_tunggakan_tahun_ajaran_lama($koneksi, $siswa_tagihan['nisn'], $tahun_ajaran_aktif_dashboard);
    if (!$tagihan_siswa_per_tahun) {
        continue;
    }

    $total_tagihan_siswa = 0;
    $tahun_tunggakan_siswa = [];
    foreach ($tagihan_siswa_per_tahun as $tahun_tunggakan => $tagihan_siswa) {
        $tahun_tunggakan_siswa[] = $tahun_tunggakan;
        foreach ($tagihan_siswa as $item_tagihan) {
            $total_tagihan_siswa += (int) ($item_tagihan['sisa'] ?? 0);
        }
    }

    if ($total_tagihan_siswa <= 0) {
        continue;
    }

    $total_tagihan += $total_tagihan_siswa;
    $kelas_tunggakan[$id_kelas_dashboard]['siswa_tunggakan']++;
    $kelas_tunggakan[$id_kelas_dashboard]['total_tagihan'] += $total_tagihan_siswa;
    $kelas_tunggakan[$id_kelas_dashboard]['siswa'][] = [
        'nisn' => $siswa_tagihan['nisn'],
        'nama' => $siswa_tagihan['nama'],
        'total_tagihan' => $total_tagihan_siswa,
        'tahun_ajaran' => implode(', ', array_unique($tahun_tunggakan_siswa)),
    ];
}

$kelas_tunggakan = array_values(array_filter($kelas_tunggakan, static function ($kelas) {
    return (int) $kelas['siswa_tunggakan'] > 0;
}));
usort($kelas_tunggakan, static function ($a, $b) {
    $sort_a = dashboard_kelas_sort_key($a['nama_kelas']);
    $sort_b = dashboard_kelas_sort_key($b['nama_kelas']);
    if ($sort_a === $sort_b) {
        return strnatcasecmp((string) $a['nama_kelas'], (string) $b['nama_kelas']);
    }
    return $sort_a <=> $sort_b;
});

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

<div class="mb-6">
    <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-bold text-emerald-700">Tunggakan per kelas</p>
            <h2 class="mt-1 text-xl font-extrabold tracking-normal text-slate-950">Siswa Menunggak Tahun Ajaran <?= htmlspecialchars($tahun_ajaran_sebelumnya_dashboard !== '' ? $tahun_ajaran_sebelumnya_dashboard : 'Sebelumnya') ?></h2>
        </div>
        <div class="inline-flex w-fit items-center gap-2 rounded-full border border-amber-200 bg-amber-50 px-3 py-2 text-sm font-bold text-amber-700">
            <i class="mdi mdi-file-document-alert"></i>
            Tunggakan: Rp <?= number_format($total_tagihan, 0, ',', '.') ?>
        </div>
    </div>

    <?php if (!empty($kelas_tunggakan)) : ?>
        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2 xl:grid-cols-3">
            <?php foreach ($kelas_tunggakan as $kelas_item) :
                $total_siswa_kelas = max(1, (int) $kelas_item['total_siswa']);
                $jumlah_tunggakan_kelas = (int) $kelas_item['siswa_tunggakan'];
                $persen_siswa_tunggakan = min(100, round(($jumlah_tunggakan_kelas / $total_siswa_kelas) * 100));
            ?>
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-slate-500">Kelas</p>
                            <h3 class="mt-1 truncate text-2xl font-extrabold tracking-normal text-slate-950" title="<?= htmlspecialchars($kelas_item['nama_kelas']) ?>">
                                <?= htmlspecialchars($kelas_item['nama_kelas']) ?>
                            </h3>
                        </div>
                        <a href="<?= base_url('tagihan/tagihan.php?id_kelas=' . (int) $kelas_item['id_kelas']) ?>" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100 transition hover:bg-emerald-100" title="Buka tagihan kelas <?= htmlspecialchars($kelas_item['nama_kelas']) ?>">
                            <i class="mdi mdi-arrow-right text-xl"></i>
                        </a>
                    </div>

                    <div class="mt-5 grid grid-cols-2 gap-4 border-y border-slate-100 py-4">
                        <div>
                            <p class="text-xs font-bold uppercase text-slate-400">Total Tagihan</p>
                            <p class="mt-1 break-words text-lg font-extrabold text-slate-950">Rp <?= number_format($kelas_item['total_tagihan'], 0, ',', '.') ?></p>
                        </div>
                        <div class="border-l border-slate-100 pl-4">
                            <p class="text-xs font-bold uppercase text-amber-600">Siswa</p>
                            <p class="mt-1 text-lg font-extrabold text-slate-950"><?= number_format($jumlah_tunggakan_kelas) ?> / <?= number_format($kelas_item['total_siswa']) ?></p>
                        </div>
                    </div>

                    <div class="mt-5">
                        <div class="mb-2 flex items-center justify-between text-xs font-bold text-slate-500">
                            <span>Persentase siswa menunggak</span>
                            <span><?= $persen_siswa_tunggakan ?>%</span>
                        </div>
                        <div class="h-2.5 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-amber-500" style="width: <?= $persen_siswa_tunggakan ?>%"></div>
                        </div>
                        <div class="mt-2 flex items-center justify-between text-xs font-semibold text-slate-400">
                            <span><?= $persen_siswa_tunggakan ?>% siswa kelas ini menunggak</span>
                        </div>
                    </div>

                    <details class="group mt-5 rounded-lg border border-slate-200 bg-slate-50/70">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 text-sm font-extrabold text-slate-700 transition hover:bg-slate-100">
                            <span class="inline-flex min-w-0 items-center gap-2">
                                <i class="mdi mdi-account-alert text-amber-500"></i>
                                <span class="truncate">Daftar siswa menunggak</span>
                            </span>
                            <span class="inline-flex shrink-0 items-center gap-2 text-xs font-bold text-slate-500">
                                <?= number_format($jumlah_tunggakan_kelas) ?> siswa
                                <i class="mdi mdi-chevron-down text-lg transition group-open:rotate-180"></i>
                            </span>
                        </summary>
                        <div class="max-h-72 overflow-y-auto border-t border-slate-200 bg-white px-3 py-2">
                            <?php foreach ($kelas_item['siswa'] as $index_siswa => $siswa_preview) : ?>
                                <div class="flex items-start justify-between gap-3 <?= $index_siswa > 0 ? 'border-t border-slate-100' : '' ?> py-2.5">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-extrabold text-slate-700" title="<?= htmlspecialchars($siswa_preview['nama']) ?>">
                                            <?= htmlspecialchars($siswa_preview['nama']) ?>
                                        </p>
                                        <p class="mt-0.5 text-xs font-semibold text-slate-400">NISN <?= htmlspecialchars($siswa_preview['nisn']) ?><?= $siswa_preview['tahun_ajaran'] !== '' ? ' - ' . htmlspecialchars($siswa_preview['tahun_ajaran']) : '' ?></p>
                                    </div>
                                    <span class="shrink-0 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-extrabold text-amber-700 ring-1 ring-amber-100">
                                        Rp <?= number_format($siswa_preview['total_tagihan'], 0, ',', '.') ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </details>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else : ?>
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-5 text-sm font-bold text-emerald-700">
            <i class="mdi mdi-check-circle mr-1"></i> Belum ada tunggakan tahun ajaran lama.
        </div>
    <?php endif; ?>
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
