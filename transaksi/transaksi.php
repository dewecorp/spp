<?php
include '../template/header.php';
include '../template/sidebar.php';

// Include Select2 CSS
?>
<link rel="stylesheet" href="<?= base_url('assets/vendors/select2/select2.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/vendors/select2-bootstrap-theme/select2-bootstrap.min.css') ?>">

<?php
// Fetch Data Siswa & Jenis Bayar for Modal
$q_siswa = mysqli_query($koneksi, "SELECT siswa.*, kelas.nama_kelas FROM siswa JOIN kelas ON siswa.id_kelas = kelas.id_kelas ORDER BY siswa.nama ASC");
$siswa_list = [];
while ($s = mysqli_fetch_assoc($q_siswa)) {
    $siswa_list[] = $s;
}

$q_jb = mysqli_query($koneksi, "SELECT * FROM jenis_bayar ORDER BY nama_pembayaran ASC");
$jb_list = [];
while ($jb = mysqli_fetch_assoc($q_jb)) {
    $jb_list[] = $jb;
}

// Proses Tambah
if (isset($_POST['tambah'])) {
    $id_petugas = $_SESSION['id_pengguna'];
    $nisn = $_POST['nisn'];
    $tgl_bayar = $_POST['tgl_bayar'];
    $tahun_bayar = date('Y', strtotime($tgl_bayar));
    
    // Support multiselect types
    $id_jenis_bayar_input = $_POST['id_jenis_bayar']; 
    
    if (!is_array($id_jenis_bayar_input)) {
        $id_jenis_bayar_input = [$id_jenis_bayar_input];
    }
    
    // Generate No Transaksi
    $no_transaksi = 'TRX-' . date('YmdHis') . '-' . rand(100, 999);
    
    $success_count = 0;
    
    foreach ($id_jenis_bayar_input as $id_jb) {
        // Retrieve details from payment array or fallbacks (if any)
        $detail = isset($_POST['payment'][$id_jb]) ? $_POST['payment'][$id_jb] : [];
        
        // Get info jenis bayar
        $q_cek_jb = mysqli_query($koneksi, "SELECT * FROM jenis_bayar WHERE id_jenis_bayar='$id_jb'");
        $d_jb = mysqli_fetch_assoc($q_cek_jb);
        
        $cicilan_ke = 0;
        $jumlah_bayar = 0;
        $ket = '';
        $bulan_bayar_str = '';
    
        if ($d_jb['tipe_bayar'] == 'Cicilan') {
            $cicilan_ke = isset($detail['cicilan_ke']) ? $detail['cicilan_ke'] : 0;
            $nominal_val = isset($detail['nominal']) ? str_replace('.', '', $detail['nominal']) : 0;
            $jumlah_bayar = $nominal_val;
            $ket = "Cicilan ke-$cicilan_ke";
        } else {
            // Bulanan
            $bulan_bayar_arr = isset($detail['bulan_bayar']) ? $detail['bulan_bayar'] : [];
            $bulan_bayar_str = implode(', ', $bulan_bayar_arr);
            $jumlah_bayar = $d_jb['nominal'] * count($bulan_bayar_arr);
            $ket = "Lunas (Bulanan) - " . $bulan_bayar_str;
        }
    
        $query = mysqli_query($koneksi, "INSERT INTO pembayaran (id_petugas, nisn, tgl_bayar, id_jenis_bayar, jumlah_bayar, cicilan_ke, ket, bulan_bayar, tahun_bayar, no_transaksi) VALUES ('$id_petugas', '$nisn', '$tgl_bayar', '$id_jb', '$jumlah_bayar', '$cicilan_ke', '$ket', '$bulan_bayar_str', '$tahun_bayar', '$no_transaksi')");
        
        if ($query) $success_count++;
    }

    if ($success_count > 0) {
        logActivity($koneksi, 'Create', "Menambah $success_count transaksi pembayaran NISN: $nisn");
        echo "<script>
            Swal.fire({
                title: 'Berhasil',
                text: 'Data berhasil ditambahkan',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                window.location='transaksi.php';
            });
        </script>";
    } else {
        echo "<script>Swal.fire('Gagal', 'Data gagal ditambahkan', 'error');</script>";
    }
}

// Proses Update Transaksi
if (isset($_POST['update_transaksi'])) {
    $no_transaksi = $_POST['no_transaksi'];
    $tgl_bayar = $_POST['tgl_bayar'];
    $nisn = $_POST['nisn']; 
    $payments_input = isset($_POST['payment']) ? $_POST['payment'] : [];

    // 1. Get existing IDs for this transaction to track what to keep/delete
    $q_exist = mysqli_query($koneksi, "SELECT id_pembayaran FROM pembayaran WHERE no_transaksi='$no_transaksi'");
    $existing_ids = [];
    while($row = mysqli_fetch_assoc($q_exist)) {
        $existing_ids[] = $row['id_pembayaran'];
    }

    $submitted_ids = [];
    $success_count = 0;

    foreach ($payments_input as $id_jb => $data) {
        $id_pembayaran = isset($data['id_pembayaran']) ? $data['id_pembayaran'] : null;
        
        // Get Info Jenis Bayar
        $q_jb = mysqli_query($koneksi, "SELECT * FROM jenis_bayar WHERE id_jenis_bayar='$id_jb'");
        $d_jb = mysqli_fetch_assoc($q_jb);

        // Prepare common vars
        $cicilan_ke = 0;
        $jumlah_bayar = 0;
        $ket = '';
        $bulan_bayar_str = '';

        if ($d_jb['tipe_bayar'] == 'Cicilan') {
            $cicilan_ke = isset($data['cicilan_ke']) ? $data['cicilan_ke'] : 0;
            $nominal = isset($data['nominal']) ? str_replace('.', '', $data['nominal']) : 0;
            $jumlah_bayar = $nominal;
            $ket = "Cicilan ke-$cicilan_ke";
        } else {
            // Bulanan
            $bulan_bayar_arr = isset($data['bulan_bayar']) ? $data['bulan_bayar'] : [];
            $bulan_bayar_str = implode(', ', $bulan_bayar_arr);
            $jumlah_bayar = $d_jb['nominal'] * count($bulan_bayar_arr);
            $ket = "Lunas (Bulanan) - " . $bulan_bayar_str;
        }

        if ($id_pembayaran && in_array($id_pembayaran, $existing_ids)) {
            // UPDATE EXISTING ITEM
            $query = mysqli_query($koneksi, "UPDATE pembayaran SET 
                tgl_bayar='$tgl_bayar', 
                jumlah_bayar='$jumlah_bayar', 
                cicilan_ke='$cicilan_ke', 
                ket='$ket', 
                bulan_bayar='$bulan_bayar_str' 
                WHERE id_pembayaran='$id_pembayaran'");
            
            if ($query) {
                $submitted_ids[] = $id_pembayaran;
                $success_count++;
            }
        } else {
            // INSERT NEW ITEM (User added a new payment type in edit modal)
            $id_petugas = $_SESSION['id_pengguna'];
            $tahun_bayar = date('Y', strtotime($tgl_bayar));
            
            $query = mysqli_query($koneksi, "INSERT INTO pembayaran 
                (id_petugas, nisn, tgl_bayar, id_jenis_bayar, jumlah_bayar, cicilan_ke, ket, bulan_bayar, tahun_bayar, no_transaksi) 
                VALUES 
                ('$id_petugas', '$nisn', '$tgl_bayar', '$id_jb', '$jumlah_bayar', '$cicilan_ke', '$ket', '$bulan_bayar_str', '$tahun_bayar', '$no_transaksi')");
            
            if ($query) $success_count++;
        }
    }

    // DELETE REMOVED ITEMS (User deselected a payment type)
    $ids_to_delete = array_diff($existing_ids, $submitted_ids);
    if (!empty($ids_to_delete)) {
        $ids_str = implode(',', $ids_to_delete);
        mysqli_query($koneksi, "DELETE FROM pembayaran WHERE id_pembayaran IN ($ids_str)");
    }

    if ($success_count > 0 || !empty($ids_to_delete)) {
        logActivity($koneksi, 'Update', "Mengedit transaksi No: $no_transaksi");
        echo "<script>
            Swal.fire({
                title: 'Berhasil',
                text: 'Data transaksi berhasil diperbarui',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                window.location='transaksi.php';
            });
        </script>";
    } else {
         echo "<script>Swal.fire('Gagal', 'Tidak ada perubahan data', 'info');</script>";
    }
}

// Proses Hapus
if (isset($_GET['hapus_transaksi'])) {
    $no_transaksi = $_GET['hapus_transaksi'];
    $query = mysqli_query($koneksi, "DELETE FROM pembayaran WHERE no_transaksi='$no_transaksi'");
    if ($query) {
        logActivity($koneksi, 'Delete', "Menghapus transaksi No: $no_transaksi");
        echo "<script>
            Swal.fire({
                title: 'Berhasil',
                text: 'Data berhasil dihapus',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                window.location='transaksi.php';
            });
        </script>";
    } else {
        echo "<script>Swal.fire('Gagal', 'Data gagal dihapus', 'error');</script>";
    }
}
?>

<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Data Transaksi Pembayaran</h4>
                <div class="d-flex justify-content-between mb-3">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
                        <i class="mdi mdi-plus"></i> Tambah Transaksi
                    </button>
                    <div>
                        <a href="export_excel.php" class="btn btn-success" target="_blank">
                            <i class="mdi mdi-file-excel"></i> Export Excel
                        </a>
                        <a href="export_pdf.php" class="btn btn-danger" target="_blank">
                            <i class="mdi mdi-file-pdf"></i> Export PDF
                        </a>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped" id="table-transaksi">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Siswa</th>
                                <th>Kelas</th>
                                <th>Jenis Bayar</th>
                                <th>Bulan Bayar</th>
                                <th>Cicilan Ke</th>
                                <th>Nominal</th>
                                <th>Tanggal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            $query = mysqli_query($koneksi, "SELECT 
                                                                p.no_transaksi,
                                                                MAX(p.id_pembayaran) as id_pembayaran,
                                                                MAX(p.tgl_bayar) as tgl_bayar,
                                                                MAX(s.nama) as nama_siswa,
                                                                MAX(k.nama_kelas) as nama_kelas,
                                                                GROUP_CONCAT(DISTINCT jb.nama_pembayaran SEPARATOR '<br>') as nama_pembayaran,
                                                                GROUP_CONCAT(DISTINCT p.bulan_bayar SEPARATOR '<br>') as bulan_bayar,
                                                                GROUP_CONCAT(DISTINCT p.cicilan_ke SEPARATOR ', ') as cicilan_ke,
                                                                SUM(p.jumlah_bayar) as jumlah_bayar
                                                             FROM pembayaran p
                                                             JOIN siswa s ON p.nisn = s.nisn 
                                                             JOIN kelas k ON s.id_kelas = k.id_kelas 
                                                             JOIN jenis_bayar jb ON p.id_jenis_bayar = jb.id_jenis_bayar 
                                                             GROUP BY p.no_transaksi
                                                             ORDER BY tgl_bayar DESC, id_pembayaran DESC");
                            while ($row = mysqli_fetch_assoc($query)) :
                            ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= $row['nama_siswa'] ?></td>
                                    <td><?= $row['nama_kelas'] ?></td>
                                    <td><?= $row['nama_pembayaran'] ?></td>
                                    <td><?= $row['bulan_bayar'] ?></td>
                                    <td><?= $row['cicilan_ke'] == '0' ? '-' : $row['cicilan_ke'] ?></td>
                                    <td>Rp <?= number_format($row['jumlah_bayar'], 0, ',', '.') ?></td>
                                    <td><?= date('d/m/Y', strtotime($row['tgl_bayar'])) ?></td>
                                    <td>
                                        <a href="cetak_transaksi.php?no_transaksi=<?= $row['no_transaksi'] ?>" class="btn btn-info btn-sm" target="_blank">
                                            <i class="mdi mdi-printer"></i>
                                        </a>
                                        <button type="button" class="btn btn-warning btn-sm btn-edit-transaksi" data-id="<?= $row['no_transaksi'] ?>" data-bs-toggle="modal" data-bs-target="#modalEdit">
                                            <i class="mdi mdi-pencil"></i>
                                        </button>
                                        <a href="transaksi.php?hapus_transaksi=<?= $row['no_transaksi'] ?>" class="btn btn-danger btn-sm btn-hapus">
                                            <i class="mdi mdi-delete"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="modalTambah" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Transaksi</h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close" style="background: transparent; border: none;">
                    <i class="mdi mdi-close"></i>
                </button>
            </div>
            <form action="" method="post">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Tanggal Bayar</label>
                        <input type="date" name="tgl_bayar" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Nama Siswa</label>
                        <select name="nisn" id="nisnTambah" class="form-control select2-modal" style="width: 100%;" required>
                            <option value="">-- Pilih Siswa --</option>
                            <?php foreach ($siswa_list as $s) : ?>
                                <option value="<?= $s['nisn'] ?>" data-kelas="<?= $s['nama_kelas'] ?>">
                                    <?= $s['nama'] ?> - <?= $s['nama_kelas'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Jenis Bayar</label>
                        <select name="id_jenis_bayar[]" class="form-control select2-multiple" id="jbTambah" multiple="multiple" style="width: 100%;" required>
                            <?php foreach ($jb_list as $jb) : ?>
                                <option value="<?= $jb['id_jenis_bayar'] ?>" 
                                    data-tipe="<?= $jb['tipe_bayar'] ?>" 
                                    data-nominal="<?= $jb['nominal'] ?>"
                                    data-tagihan="<?= $jb['tagihan_kelas'] ?>"
                                    data-nama="<?= $jb['nama_pembayaran'] ?>">
                                    <?= $jb['nama_pembayaran'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div id="extraTambah">
                        <!-- Dynamic Content -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit -->
<div class="modal fade" id="modalEdit" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Transaksi</h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close" style="background: transparent; border: none;">
                    <i class="mdi mdi-close"></i>
                </button>
            </div>
            <form action="" method="post">
                <div class="modal-body" id="modalEditBody">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="update_transaksi" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../template/footer.php'; ?>
<!-- Select2 JS -->
<script src="<?= base_url('assets/vendors/select2/select2.min.js') ?>"></script>

<script>
    $(document).ready(function() {
        // Initialize Select2 in Modals
        $('.select2-modal').each(function() {
            $(this).select2({
                theme: "bootstrap",
                dropdownParent: $(this).closest('.modal')
            });
        });

        // Trigger validation/update when Student changes
        $('#nisnTambah').on('change', function() {
            // Trigger change on Jenis Bayar to re-validate against the new student's class
            $('#jbTambah').trigger('change');
        });

        // Handler for Edit Button Click
        $('.btn-edit-transaksi').on('click', function() {
            var no_transaksi = $(this).data('id');
            var modalBody = $('#modalEditBody');
            
            // Show loading
            modalBody.html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div></div>');
            
            // Fetch Data
            $.ajax({
                url: 'get_transaksi_detail.php',
                type: 'POST',
                data: { no_transaksi: no_transaksi },
                success: function(response) {
                    modalBody.html(response);
                },
                error: function() {
                    modalBody.html('<div class="text-center text-danger">Gagal memuat data.</div>');
                }
            });
        });

        // Initialize Select2 Multiple for Bulan
        $('.select2-multiple').each(function() {
            $(this).select2({
                placeholder: "Pilih Bulan",
                allowClear: true,
                theme: "bootstrap",
                dropdownParent: $(this).closest('.modal')
            });
        });

        // Handler for Tambah Transaksi (Multiselect Jenis Bayar)
        $('#jbTambah').on('change', function() {
            var selectedOptions = $(this).find('option:selected');
            var container = $('#extraTambah');
            container.empty();

            var siswaSelect = $('#nisnTambah');
            var selectedSiswa = siswaSelect.find('option:selected');
            var kelasSiswa = selectedSiswa.attr('data-kelas');
            
            selectedOptions.each(function() {
                var opt = $(this);
                var id = opt.val();
                var nama = opt.data('nama');
                var tipe = opt.data('tipe');
                var nominal = opt.data('nominal');
                var tagihan = opt.attr('data-tagihan');

                // Validation
                if (tagihan && tagihan !== '' && kelasSiswa) {
                    var allowedKelas = tagihan.split(',').map(s => s.trim());
                    if (!allowedKelas.includes(kelasSiswa)) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Jenis pembayaran ' + nama + ' tidak tersedia untuk Kelas ' + kelasSiswa
                        });
                        // We continue rendering but user knows it's invalid.
                        // Ideally we should unselect it.
                    }
                }

                // Build HTML
                var html = '<div class="card mb-2 border"><div class="card-body p-2" style="background: #f8f9fa;">';
                html += '<h6 class="mb-2 text-primary">' + nama + ' (' + tipe + ')</h6>';
                
                // Add hidden input for ID Type
                // We use array structure: payment[id_jenis_bayar]
                html += '<input type="hidden" name="payment[' + id + '][id_jenis_bayar]" value="' + id + '">';

                if (tipe === 'Bulanan') {
                    html += '<div class="form-group">';
                    html += '<label>Bayar Bulan</label>';
                    html += '<select name="payment[' + id + '][bulan_bayar][]" class="form-control select2-dynamic-bulan" multiple="multiple" style="width: 100%;" required>';
                    var months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                    var currentMonthIndex = new Date().getMonth();
                    months.forEach(function(m, i) {
                         var selected = (i === currentMonthIndex) ? 'selected' : '';
                         html += '<option value="' + m + '" ' + selected + '>' + m + '</option>';
                    });
                    html += '</select></div>';
                } else {
                     html += '<div class="form-group"><label>Cicilan Ke</label>';
                     html += '<input type="number" name="payment[' + id + '][cicilan_ke]" class="form-control" value="1" required></div>';
                     html += '<div class="form-group"><label>Nominal</label>';
                     html += '<input type="number" name="payment[' + id + '][nominal]" class="form-control" placeholder="Nominal" required></div>';
                }
                html += '</div></div>';
                container.append(html);
            });

            // Re-init Select2 for new elements
            $('.select2-dynamic-bulan').select2({
                placeholder: "Pilih Bulan",
                allowClear: true,
                theme: "bootstrap",
                dropdownParent: $('#modalTambah')
            });
        });

        // Logic for Jenis Bayar Change (Edit Modal only)
        $(document).on('change', '.select-jenis-bayar:not(#jbTambah)', function() {
            var selectedOption = $(this).find('option:selected');
            var tipe = selectedOption.data('tipe');
            var nominal = selectedOption.data('nominal');
            var tagihan = selectedOption.attr('data-tagihan');
            var targetId = $(this).data('target');
            
            // Validation Logic
            var modalId = $(this).closest('.modal').attr('id');
            var siswaSelect = $(this).closest('.modal-body').find('select[name="nisn"]');
            var selectedSiswa = siswaSelect.find('option:selected');
            var kelasSiswa = selectedSiswa.attr('data-kelas');

            if (tagihan && tagihan !== '' && kelasSiswa) {
                var allowedKelas = tagihan.split(',').map(s => s.trim());
                if (!allowedKelas.includes(kelasSiswa)) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Jenis pembayaran ini tidak tersedia untuk Kelas ' + kelasSiswa
                    });
                    $(this).val('').trigger('change'); // Reset selection
                    return;
                }
            }

            // Show/Hide Fields (Edit Modal Logic)
            if (tipe === 'Cicilan') {
                $(targetId).find('.cicilan-group').show();
                $(targetId).find('.nominal-group').show();
                $(targetId).find('.bulan-group').hide();
                $(targetId).find('input[name="cicilan_ke"]').prop('required', true);
                $(targetId).find('input[name="nominal"]').prop('required', true);
                $(targetId).find('select[name="bulan_bayar[]"]').prop('required', false);
            } else if (tipe === 'Bulanan') {
                $(targetId).find('.cicilan-group').hide();
                $(targetId).find('.nominal-group').hide();
                $(targetId).find('.bulan-group').show();
                $(targetId).find('input[name="cicilan_ke"]').prop('required', false);
                $(targetId).find('input[name="nominal"]').prop('required', false);
                $(targetId).find('select[name="bulan_bayar[]"]').prop('required', true);
            } else {
                $(targetId).find('.cicilan-group').hide();
                $(targetId).find('.nominal-group').hide();
                $(targetId).find('.bulan-group').hide();
            }
        });
        
        // Validate when Siswa changes in Tambah
        $('#nisnTambah').on('change', function() {
            var jbSelect = $('#jbTambah');
            if (jbSelect.val() && jbSelect.val().length > 0) {
                jbSelect.trigger('change'); // Re-trigger validation and re-render
            }
        });

        // Delete Confirmation
        $('.btn-hapus').on('click', function(e) {
            e.preventDefault();
            const href = $(this).attr('href');
            Swal.fire({
                title: 'Apakah anda yakin?',
                text: "Data yang dihapus tidak dapat dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, hapus!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = href;
                }
            })
        });
    });
</script>