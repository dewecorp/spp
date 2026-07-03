<?php
$title = 'Detail Tagihan';
include '../template/header.php';
include '../template/sidebar.php';

if (!isset($_GET['nisn']) || !isset($_GET['id_kelas'])) {
    echo "<script>alert('Parameter tidak valid!'); window.location='tagihan.php';</script>";
    exit;
}

$nisn = $_GET['nisn'];
$id_kelas = $_GET['id_kelas'];
ensure_pembayaran_tahun_ajaran_column($koneksi);
$tahun_ajaran = isset($_GET['tahun_ajaran']) && trim((string)$_GET['tahun_ajaran']) !== ''
    ? trim((string)$_GET['tahun_ajaran'])
    : get_tahun_ajaran_aktif($koneksi);
$tahun_ajaran_aktif = get_tahun_ajaran_aktif($koneksi);
$is_tahun_ajaran_aktif = $tahun_ajaran === $tahun_ajaran_aktif;
$tahun_ajaran_esc = mysqli_real_escape_string($koneksi, $tahun_ajaran);

// Get Data Siswa & Kelas
$q_siswa_detail = mysqli_query($koneksi, "SELECT siswa.*, kelas.nama_kelas FROM siswa JOIN kelas ON siswa.id_kelas = kelas.id_kelas WHERE siswa.nisn = '$nisn'");
$d_siswa = mysqli_fetch_assoc($q_siswa_detail);
$nama_kelas = $d_siswa['nama_kelas'];

// Get Jenis Bayar
$q_jb = mysqli_query($koneksi, "SELECT * FROM jenis_bayar WHERE status = 'Aktif' ORDER BY tipe_bayar ASC, nama_pembayaran ASC");
?>

<div class="app-page">
    <div class="app-surface">
            <div class="app-titlebar">
                <div class="flex min-w-0 flex-wrap items-center gap-3">
                    <a href="tagihan.php?id_kelas=<?= $id_kelas ?>" class="inline-flex items-center gap-2 rounded-lg border border-primary px-4 py-2 text-sm font-bold text-primary shadow-sm transition hover:bg-primary hover:text-white">
                        <i class="mdi mdi-arrow-left"></i> Kembali
                    </a>
                    <h4 class="truncate text-xl font-extrabold tracking-normal text-slate-950">Detail Tagihan: <?= $d_siswa['nama'] ?> (<?= $nama_kelas ?>)</h4>
                    <span class="app-badge app-badge-info">Tahun Ajaran <?= htmlspecialchars($tahun_ajaran, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="flex items-center gap-2">
                    <?php if (!$is_tahun_ajaran_aktif): ?>
                        <a href="bayar_tagihan.php?nisn=<?= $nisn ?>&id_kelas=<?= $id_kelas ?>&tahun_ajaran=<?= urlencode($tahun_ajaran) ?>" class="inline-flex h-10 w-auto px-4 items-center justify-center rounded-lg bg-success text-white shadow-sm transition hover:bg-success-600">
                            <i class="mdi mdi-cash mr-2"></i> Bayar Tagihan
                        </a>
                    <?php else: ?>
                        <span class="app-badge app-badge-warning">Pembayaran tahun berjalan lewat menu Transaksi</span>
                    <?php endif; ?>
                    <a href="export_excel.php?nisn=<?= $nisn ?>&id_kelas=<?= $id_kelas ?>" 
                       class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-500 text-white shadow-sm transition hover:bg-emerald-600" target="_blank" title="Export Excel">
                        <i class="mdi mdi-file-excel"></i>
                    </a>
                    <a href="export_pdf.php?nisn=<?= $nisn ?>&id_kelas=<?= $id_kelas ?>" 
                       class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-red-500 text-white shadow-sm transition hover:bg-red-600" target="_blank" title="Cetak Tagihan">
                        <i class="mdi mdi-printer"></i>
                    </a>
                </div>
            </div>

            <div class="app-table-scroll">
                <table class="app-table min-w-full">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th width="20%">Jenis Pembayaran</th>
                            <th width="15%">Tipe</th>
                            <th width="15%">Nominal / Tagihan</th>
                            <th>Status Pembayaran</th>
                        </tr>
                    </thead>
                    <tbody>
                            <?php
                            $no = 1;
                            $months = ['Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni'];
                            
                            // Calculate current month index (relative to school year starting July)
                            $limit_index = limit_index_bulan_tahun_ajaran($koneksi, $tahun_ajaran);

                            $displayed_bills = 0;

                            while ($jb = mysqli_fetch_assoc($q_jb)) {
                                // Filter by Class
                                if (!empty($jb['tagihan_kelas'])) {
                                    $allowed_kelas = array_map('trim', explode(',', $jb['tagihan_kelas']));
                                    if (!in_array($nama_kelas, $allowed_kelas)) {
                                        continue;
                                    }
                                }

                                // Check status before displaying
                                $is_fully_paid = false;
                                $paid_months = []; // For Bulanan
                                $total_bayar = 0; // For Cicilan/Bebas
                                $sisa = 0; // For Cicilan/Bebas

                                if ($jb['tipe_bayar'] == 'Bulanan') {
                                    // Get payments for this student and this payment type
                                    $q_bayar = mysqli_query($koneksi, "SELECT bulan_bayar FROM pembayaran WHERE nisn='$nisn' AND id_jenis_bayar='" . $jb['id_jenis_bayar'] . "' AND tahun_ajaran='$tahun_ajaran_esc'");
                                    while ($row = mysqli_fetch_assoc($q_bayar)) {
                                        if (!empty($row['bulan_bayar'])) {
                                            $ms = array_map('trim', explode(',', $row['bulan_bayar']));
                                            $paid_months = array_merge($paid_months, $ms);
                                        }
                                    }

                                    // Check if there are any unpaid months to display
                                    $is_extracurricular = stripos($jb['nama_pembayaran'], 'ekstrakurikuler') !== false;
                                    $has_unpaid = false;
                                    foreach ($months as $index => $m) {
                                        if ($limit_index < 0) continue;
                                        if (!$is_extracurricular && $index > $limit_index) continue;
                                        if (!in_array($m, $paid_months)) {
                                            $has_unpaid = true;
                                            break;
                                        }
                                    }

                                    if (!$has_unpaid) {
                                        $is_fully_paid = true;
                                    }
                                } else {
                                    // Cicilan / Bebas
                                    $q_total = mysqli_query($koneksi, "SELECT SUM(jumlah_bayar) as total FROM pembayaran WHERE nisn='$nisn' AND id_jenis_bayar='" . $jb['id_jenis_bayar'] . "' AND tahun_ajaran='$tahun_ajaran_esc'");
                                    $d_total = mysqli_fetch_assoc($q_total);
                                    $total_bayar = $d_total['total'] ?? 0;
                                    $sisa = $jb['nominal'] - $total_bayar;

                                    if ($sisa <= 0) {
                                        $is_fully_paid = true;
                                    }
                                }

                                // Skip if fully paid
                                if ($is_fully_paid) {
                                    continue;
                                }

                                $displayed_bills++;

                                echo "<tr>";
                                echo "<td>" . $no++ . "</td>";
                                echo "<td>" . $jb['nama_pembayaran'] . "</td>";
                                echo "<td>" . $jb['tipe_bayar'] . "</td>";
                                echo "<td>Rp " . number_format($jb['nominal'], 0, ',', '.') . "</td>";
                                echo "<td>";

                                if ($jb['tipe_bayar'] == 'Bulanan') {
                                    $is_extracurricular = stripos($jb['nama_pembayaran'], 'ekstrakurikuler') !== false;
                                    echo '<div class="flex flex-wrap gap-3">';
                                    foreach ($months as $index => $m) {
                                        if ($limit_index < 0) continue;
                                        if (!$is_extracurricular && $index > $limit_index) continue; // Skip future months (non-ekskul)
                                        if (in_array($m, $paid_months)) continue; // Skip paid months

                                        $icon = '<i class="mdi mdi-close-circle text-red-500" style="font-size: 1.2em;"></i>';
                                        
                                        echo '<div class="inline-flex items-center gap-2">';
                                        echo '<span>' . $icon . '</span>';
                                        echo '<span>' . $m . '</span>';
                                        echo '</div>';
                                    }
                                    echo '</div>';
                                } else {
                                    echo '<div class="flex flex-col gap-1">';
                                    echo '<span>Sudah Bayar: Rp ' . number_format($total_bayar, 0, ',', '.') . '</span>';
                                    echo '<span class="text-red-500 font-bold"><i class="mdi mdi-close-circle"></i> Kurang: Rp ' . number_format($sisa, 0, ',', '.') . '</span>';
                                    echo '</div>';
                                }

                                echo "</td>";
                                echo "</tr>";
                            }

                            if ($displayed_bills == 0) {
                                echo '<tr><td colspan="5" class="text-center text-red-500 font-bold py-4">Tidak ada tagihan (Lunas Semua)</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
    </div>
</div>

<?php include '../template/footer.php'; ?>
