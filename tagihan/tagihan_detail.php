<?php
$title = 'Detail Tagihan';
include '../template/header.php';
include '../template/sidebar.php';

if (!isset($_GET['nisn']) || !isset($_GET['id_kelas'])) {
    echo "<script>alert('Parameter tidak valid!'); window.location='tagihan.php?v=1';</script>";
    exit;
}

$nisn = $_GET['nisn'];
$id_kelas = $_GET['id_kelas'];

// Get Data Siswa & Kelas
$q_siswa_detail = mysqli_query($koneksi, "SELECT siswa.*, kelas.nama_kelas FROM siswa JOIN kelas ON siswa.id_kelas = kelas.id_kelas WHERE siswa.nisn = '$nisn'");
$d_siswa = mysqli_fetch_assoc($q_siswa_detail);
$nama_kelas = $d_siswa['nama_kelas'];

// Get Jenis Bayar
$q_jb = mysqli_query($koneksi, "SELECT * FROM jenis_bayar ORDER BY tipe_bayar ASC, nama_pembayaran ASC");
?>

<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex align-items-center">
                        <a href="tagihan.php?v=1&id_kelas=<?= $id_kelas ?>" class="btn btn-secondary btn-sm me-3" style="margin-right: 10px;"><i class="mdi mdi-arrow-left"></i> Kembali</a>
                        <h4 class="card-title mb-0">Detail Tagihan: <?= $d_siswa['nama'] ?> (<?= $nama_kelas ?>)</h4>
                    </div>
                    <div>
                        <a href="export_excel.php?nisn=<?= $nisn ?>&id_kelas=<?= $id_kelas ?>" class="btn btn-success" target="_blank">
                            <i class="mdi mdi-file-excel"></i> Export Excel
                        </a>
                        <a href="export_pdf.php?nisn=<?= $nisn ?>&id_kelas=<?= $id_kelas ?>" class="btn btn-danger" target="_blank">
                            <i class="mdi mdi-printer"></i> Cetak Tagihan
                        </a>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="bg-primary text-white">
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
                            $current_month_num = date('n'); // 1-12
                            $limit_index = ($current_month_num >= 7) ? $current_month_num - 7 : $current_month_num + 5;

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
                                    $q_bayar = mysqli_query($koneksi, "SELECT bulan_bayar FROM pembayaran WHERE nisn='$nisn' AND id_jenis_bayar='" . $jb['id_jenis_bayar'] . "'");
                                    while ($row = mysqli_fetch_assoc($q_bayar)) {
                                        if (!empty($row['bulan_bayar'])) {
                                            $ms = array_map('trim', explode(',', $row['bulan_bayar']));
                                            $paid_months = array_merge($paid_months, $ms);
                                        }
                                    }

                                    // Check if there are any unpaid months to display
                                    $has_unpaid = false;
                                    foreach ($months as $index => $m) {
                                        if ($index > $limit_index) continue;
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
                                    $q_total = mysqli_query($koneksi, "SELECT SUM(jumlah_bayar) as total FROM pembayaran WHERE nisn='$nisn' AND id_jenis_bayar='" . $jb['id_jenis_bayar'] . "'");
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
                                    echo '<div class="d-flex flex-wrap">';
                                    foreach ($months as $index => $m) {
                                        if ($index > $limit_index) continue; // Skip future months
                                        if (in_array($m, $paid_months)) continue; // Skip paid months

                                        $icon = '<i class="mdi mdi-close-circle text-danger" style="font-size: 1.2em;"></i>';
                                        
                                        echo '<div class="me-3 mb-2 d-inline-block">';
                                        echo '<div class="d-flex align-items-center">';
                                        echo '<span class="me-2" style="margin-right: 5px;">' . $icon . '</span>';
                                        echo '<span>' . $m . '</span>';
                                        echo '</div>';
                                        echo '</div>';
                                    }
                                    echo '</div>';
                                } else {
                                    echo '<div class="d-flex flex-column">';
                                    echo '<span>Sudah Bayar: Rp ' . number_format($total_bayar, 0, ',', '.') . '</span>';
                                    echo '<span class="text-danger font-weight-bold"><i class="mdi mdi-close-circle"></i> Kurang: Rp ' . number_format($sisa, 0, ',', '.') . '</span>';
                                    echo '</div>';
                                }

                                echo "</td>";
                                echo "</tr>";
                            }

                            if ($displayed_bills == 0) {
                                echo '<tr><td colspan="5" class="text-center font-weight-bold text-danger">Tidak ada tagihan (Lunas Semua)</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../template/footer.php'; ?>