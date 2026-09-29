<?php
$title = 'Naik Kelas';
include '../template/header.php';
include '../template/sidebar.php';

function nk_is_alumni($nama) {
    return stripos(trim((string)$nama), 'alumni') !== false;
}

function nk_parse_level($nama) {
    $nama = trim((string)$nama);
    if ($nama === '' || nk_is_alumni($nama)) return null;
    if (preg_match('/(\d+)/', $nama, $m)) return (int)$m[1];
    $upper = strtoupper($nama);
    $tokens = preg_split('/[^A-Z]+/', $upper, -1, PREG_SPLIT_NO_EMPTY);
    $map = ['XII'=>12,'XI'=>11,'X'=>10,'IX'=>9,'VIII'=>8,'VII'=>7,'VI'=>6,'V'=>5,'IV'=>4,'III'=>3,'II'=>2,'I'=>1];
    foreach ($tokens as $t) {
        if (isset($map[$t])) return $map[$t];
    }
    return null;
}

function nk_suffix($nama) {
    $nama = trim((string)$nama);
    if (preg_match('/\d+\s*(.*)$/', $nama, $m)) return strtoupper(trim($m[1]));
    if (preg_match('/\b(XII|XI|X|IX|VIII|VII|VI|V|IV|III|II|I)\b\s*(.*)$/i', $nama, $m)) return strtoupper(trim($m[2]));
    return '';
}

function nk_ensure_alumni($koneksi) {
    $q = mysqli_query($koneksi, "SELECT id_kelas FROM kelas WHERE LOWER(nama_kelas)='alumni' LIMIT 1");
    if ($q && mysqli_num_rows($q) > 0) {
        $d = mysqli_fetch_assoc($q);
        return (int)$d['id_kelas'];
    }
    mysqli_query($koneksi, "INSERT INTO kelas (nama_kelas) VALUES ('Alumni')");
    return (int)mysqli_insert_id($koneksi);
}

function nk_kelas_ada($koneksi, $id) {
    $id = (int)$id;
    if ($id <= 0) return false;
    $q = mysqli_query($koneksi, "SELECT id_kelas FROM kelas WHERE id_kelas='$id' LIMIT 1");
    return $q && mysqli_num_rows($q) > 0;
}

function nk_tahun_berikutnya($ta) {
    if (preg_match('/^(\d{4})\s*\/\s*(\d{4})$/', trim((string)$ta), $m)) {
        return ((int)$m[1]+1) . '/' . ((int)$m[2]+1);
    }
    return '';
}

function nk_cari_tujuan($koneksi, $asal_id, $asal_nama) {
    $id_alumni = nk_ensure_alumni($koneksi);
    $q = mysqli_query($koneksi, "SELECT id_kelas, nama_kelas FROM kelas");
    $semua = [];
    while ($r = mysqli_fetch_assoc($q)) $semua[] = $r;
    $level_asal = nk_parse_level($asal_nama);
    $non_alumni = array_values(array_filter($semua, function($k){ return !nk_is_alumni($k['nama_kelas']); }));
    if ($level_asal !== null) {
        $kandidat = [];
        foreach ($non_alumni as $k) {
            $lv = nk_parse_level($k['nama_kelas']);
            if ($lv !== null && $lv > $level_asal) $kandidat[] = ['row'=>$k,'lv'=>$lv];
        }
        if (empty($kandidat)) {
            $qa = mysqli_query($koneksi, "SELECT id_kelas, nama_kelas FROM kelas WHERE id_kelas='$id_alumni' LIMIT 1");
            $ra = mysqli_fetch_assoc($qa);
            return $ra ? $ra : ['id_kelas'=>$id_alumni,'nama_kelas'=>'Alumni'];
        }
        usort($kandidat, function($a,$b){
            if ($a['lv']==$b['lv']) return strcmp($a['row']['nama_kelas'],$b['row']['nama_kelas']);
            return $a['lv'] <=> $b['lv'];
        });
        $lv_target = $kandidat[0]['lv'];
        $selevel = array_values(array_filter($kandidat, function($x) use ($lv_target){ return $x['lv']==$lv_target; }));
        $sfx = nk_suffix($asal_nama);
        if ($sfx !== '') {
            foreach ($selevel as $s) {
                if (nk_suffix($s['row']['nama_kelas']) === $sfx) return $s['row'];
            }
        }
        return $selevel[0]['row'];
    }
    usort($non_alumni, function($a,$b){ return strcmp($a['nama_kelas'],$b['nama_kelas']); });
    $idx = -1;
    foreach ($non_alumni as $i=>$k) { if ((int)$k['id_kelas']===(int)$asal_id) { $idx=$i; break; } }
    if ($idx >= 0 && isset($non_alumni[$idx+1])) return $non_alumni[$idx+1];
    $qa = mysqli_query($koneksi, "SELECT id_kelas, nama_kelas FROM kelas WHERE id_kelas='$id_alumni' LIMIT 1");
    $ra = $qa ? mysqli_fetch_assoc($qa) : null;
    return $ra ? $ra : ['id_kelas'=>$id_alumni,'nama_kelas'=>'Alumni'];
}

function nk_pindah($koneksi, $dari, $ke, $list) {
    $list = array_values(array_unique(array_filter(array_map(function($v){ return trim((string)$v); }, (array)$list))));
    if (empty($list)) return ['ok'=>0,'err'=>''];
    $stmt = mysqli_prepare($koneksi, "UPDATE siswa SET id_kelas=? WHERE nisn=? AND id_kelas=?");
    if (!$stmt) return ['ok'=>0,'err'=>mysqli_error($koneksi)];
    $ok = 0;
    foreach ($list as $nisn) {
        mysqli_stmt_bind_param($stmt, 'isi', $ke, $nisn, $dari);
        if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) $ok++;
    }
    mysqli_stmt_close($stmt);
    return ['ok'=>$ok,'err'=>''];
}

$is_post = (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST');
$aksi = trim((string)($_POST['nk_aksi'] ?? ''));
if ($is_post && ($aksi === 'naik' || isset($_POST['proses_naik']))) {
    $asal = (int)($_POST['kelas_asal'] ?? 0);
    $tujuan = (int)($_POST['kelas_tujuan'] ?? 0);
    $list = $_POST['nisn'] ?? [];
    if ($asal > 0 && $tujuan > 0 && $asal !== $tujuan && nk_kelas_ada($koneksi, $asal) && nk_kelas_ada($koneksi, $tujuan) && !empty($list)) {
        $hasil = nk_pindah($koneksi, $asal, $tujuan, $list);
        if ($hasil['ok'] > 0) {
            logActivity($koneksi, 'Update', "Naik kelas {$hasil['ok']} siswa dari kelas ID $asal ke $tujuan");
            $ok = (int)$hasil['ok'];
            echo "<script>Swal.fire('Berhasil','$ok siswa berhasil naik kelas','success').then(()=>{window.location='naik_kelas.php?kelas_asal=$asal';});</script>";
        } else {
            $err = $hasil['err'] !== '' ? htmlspecialchars($hasil['err'], ENT_QUOTES) : 'Tidak ada data berubah. Siswa mungkin sudah pindah.';
            echo "<script>Swal.fire('Gagal','$err','error');</script>";
        }
    } else {
        echo "<script>Swal.fire('Gagal','Tidak ada siswa yang dipilih','error');</script>";
    }
}

if ($is_post && ($aksi === 'batal' || isset($_POST['batal_naik']))) {
    $asal = (int)($_POST['kelas_asal'] ?? 0);
    $tujuan = (int)($_POST['kelas_tujuan'] ?? 0);
    $list = $_POST['nisn_batal'] ?? [];
    if ($asal > 0 && $tujuan > 0 && $asal !== $tujuan && nk_kelas_ada($koneksi, $asal) && nk_kelas_ada($koneksi, $tujuan) && !empty($list)) {
        $hasil = nk_pindah($koneksi, $tujuan, $asal, $list);
        if ($hasil['ok'] > 0) {
            logActivity($koneksi, 'Update', "Batal naik kelas {$hasil['ok']} siswa dari kelas ID $tujuan ke $asal");
            $ok = (int)$hasil['ok'];
            echo "<script>Swal.fire('Berhasil','$ok siswa dikembalikan ke kelas asal','success').then(()=>{window.location='naik_kelas.php?kelas_asal=$asal';});</script>";
        } else {
            $err = $hasil['err'] !== '' ? htmlspecialchars($hasil['err'], ENT_QUOTES) : 'Tidak ada data berubah. Siswa mungkin sudah dikembalikan.';
            echo "<script>Swal.fire('Gagal','$err','error');</script>";
        }
    } else {
        echo "<script>Swal.fire('Gagal','Tidak ada siswa yang dipilih','error');</script>";
    }
}

$tahun_asal = function_exists('get_tahun_ajaran_aktif') ? get_tahun_ajaran_aktif($koneksi) : '';
$tahun_tujuan = nk_tahun_berikutnya($tahun_asal);

$qk = mysqli_query($koneksi, "SELECT id_kelas, nama_kelas FROM kelas WHERE LOWER(nama_kelas) NOT LIKE '%alumni%' ORDER BY nama_kelas ASC");
$data_kelas = [];
while ($r = mysqli_fetch_assoc($qk)) $data_kelas[] = $r;

$kelas_asal_id = isset($_GET['kelas_asal']) ? (int)$_GET['kelas_asal'] : 0;
if ($is_post && $kelas_asal_id <= 0) $kelas_asal_id = (int)($_POST['kelas_asal'] ?? 0);
$kelas_asal_nama = '';
if ($kelas_asal_id > 0) {
    $qa = mysqli_query($koneksi, "SELECT nama_kelas FROM kelas WHERE id_kelas='$kelas_asal_id' LIMIT 1");
    if ($qa && $ra = mysqli_fetch_assoc($qa)) $kelas_asal_nama = $ra['nama_kelas'];
    else $kelas_asal_id = 0;
}

$tujuan_row = null;
if ($kelas_asal_id > 0) $tujuan_row = nk_cari_tujuan($koneksi, $kelas_asal_id, $kelas_asal_nama);
$tujuan_id = $tujuan_row ? (int)$tujuan_row['id_kelas'] : 0;
$tujuan_nama = $tujuan_row ? $tujuan_row['nama_kelas'] : '';

$siswa_asal = [];
if ($kelas_asal_id > 0) {
    $qs = mysqli_query($koneksi, "SELECT nisn, nama, jenis_kelamin FROM siswa WHERE id_kelas='$kelas_asal_id' ORDER BY nama ASC");
    while ($r = mysqli_fetch_assoc($qs)) $siswa_asal[] = $r;
}
$siswa_tujuan = [];
if ($tujuan_id > 0) {
    $qt = mysqli_query($koneksi, "SELECT nisn, nama, jenis_kelamin FROM siswa WHERE id_kelas='$tujuan_id' ORDER BY nama ASC");
    while ($r = mysqli_fetch_assoc($qt)) $siswa_tujuan[] = $r;
}
?>

<div class="app-grid">
    <div class="app-col-half app-section-gap app-stretch">
        <div class="app-panel">
            <div class="app-panel-body">
                <h4 class="app-panel-title"><?= htmlspecialchars($tahun_asal) ?> Tahun Ajaran Asal</h4>
                <form method="get" action="">
                    <div class="app-field">
                        <label>Kelas</label>
                        <select name="kelas_asal" class="app-control" onchange="this.form.submit()">
                            <option value="">Pilih Kelas</option>
                            <?php foreach($data_kelas as $kls): ?>
                                <option value="<?= $kls['id_kelas'] ?>" <?= $kelas_asal_id==(int)$kls['id_kelas']?'selected':'' ?>><?= htmlspecialchars($kls['nama_kelas']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
                <form method="post" action="naik_kelas.php?kelas_asal=<?= $kelas_asal_id ?>" id="formNaik">
                    <input type="hidden" name="kelas_asal" value="<?= $kelas_asal_id ?>">
                    <input type="hidden" name="kelas_tujuan" value="<?= $tujuan_id ?>">
                    <div class="app-table-scroll">
                        <table class="app-data-table app-table-striped w-full" id="table-asal">
                            <thead>
                                <tr>
                                    <th width="5%"><input type="checkbox" class="app-checkbox" id="checkAllAsal"></th>
                                    <th>No</th>
                                    <th>NISN</th>
                                    <th>Nama</th>
                                    <th>L/P</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no=1; foreach($siswa_asal as $s): ?>
                                <tr>
                                    <td><input type="checkbox" class="app-checkbox check-asal" name="nisn[]" value="<?= htmlspecialchars($s['nisn']) ?>"></td>
                                    <td><?= $no++ ?></td>
                                    <td><?= htmlspecialchars($s['nisn']) ?></td>
                                    <td style="color:#2563eb;font-weight:700;"><?= htmlspecialchars($s['nama']) ?></td>
                                    <td><?= htmlspecialchars($s['jenis_kelamin']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <button type="submit" name="proses_naik" id="btnNaik" class="app-button app-button-success w-full mt-3" style="display:none;width:100%;">
                        <i class="mdi mdi-arrow-up"></i> Proses Naik Kelas
                    </button>
                </form>
            </div>
        </div>
    </div>
    <div class="app-col-half app-section-gap app-stretch">
        <div class="app-panel">
            <div class="app-panel-body">
                <h4 class="app-panel-title"><?= htmlspecialchars($tahun_tujuan) ?> Tahun Ajaran Tujuan</h4>
                <div class="app-field">
                    <label>Kelas</label>
                    <input type="text" class="app-control" value="<?= htmlspecialchars($tujuan_nama) ?>" disabled placeholder="Pilih kelas asal dulu">
                    <?php if ($kelas_asal_id > 0): ?>
                    <small class="text-slate-500">Kelas tujuan otomatis berdasarkan kelas asal yang dipilih</small>
                    <?php endif; ?>
                </div>
                <?php if ($kelas_asal_id > 0): ?>
                <div class="app-alert app-alert-info mb-3">
                    <i class="mdi mdi-information"></i> <b>Info:</b> Kelas tujuan memiliki <?= count($siswa_tujuan) ?> siswa. Siswa yang akan naik akan ditambahkan ke kelas ini.
                </div>
                <?php endif; ?>
                <form method="post" action="naik_kelas.php?kelas_asal=<?= $kelas_asal_id ?>" id="formBatal">
                    <input type="hidden" name="kelas_asal" value="<?= $kelas_asal_id ?>">
                    <input type="hidden" name="kelas_tujuan" value="<?= $tujuan_id ?>">
                    <div class="app-table-scroll">
                        <table class="app-data-table app-table-striped w-full" id="table-tujuan">
                            <thead>
                                <tr>
                                    <th width="5%"><input type="checkbox" class="app-checkbox" id="checkAllTujuan"></th>
                                    <th>No</th>
                                    <th>NISN</th>
                                    <th>Nama</th>
                                    <th>L/P</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no=1; foreach($siswa_tujuan as $s): ?>
                                <tr>
                                    <td><input type="checkbox" class="app-checkbox check-tujuan" name="nisn_batal[]" value="<?= htmlspecialchars($s['nisn']) ?>"></td>
                                    <td><?= $no++ ?></td>
                                    <td><?= htmlspecialchars($s['nisn']) ?></td>
                                    <td style="color:#2563eb;font-weight:700;"><?= htmlspecialchars($s['nama']) ?></td>
                                    <td><?= htmlspecialchars($s['jenis_kelamin']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <button type="submit" name="batal_naik" id="btnBatal" class="app-button mt-3" style="display:none;width:100%;background:#fff !important;color:#e11d48 !important;border:1px solid #e11d48 !important;">
                        <i class="mdi mdi-restore"></i> Batal Naik (Kembalikan ke Kelas Asal)
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
(function(){
    var selAsal = {};
    var selTujuan = {};
    function countSel(o){ return Object.keys(o).length; }
    function toArray(nl){ return Array.prototype.slice.call(nl); }

    var btnNaik = document.getElementById('btnNaik');
    var btnBatal = document.getElementById('btnBatal');
    var checkAllAsal = document.getElementById('checkAllAsal');
    var checkAllTujuan = document.getElementById('checkAllTujuan');

    function dtApi(id){
        try {
            if (window.jQuery && jQuery.fn.DataTable && jQuery.fn.DataTable.isDataTable('#' + id)) {
                return jQuery('#' + id).DataTable();
            }
        } catch (e) {}
        return null;
    }

    function pageBoxes(tableId, cls){
        var api = dtApi(tableId);
        if (api) {
            var out = [];
            toArray(api.rows({page: 'current'}).nodes()).forEach(function(tr){
                if (tr.querySelector) {
                    var b = tr.querySelector('input.' + cls);
                    if (b) out.push(b);
                }
            });
            return out;
        }
        return toArray(document.querySelectorAll('#' + tableId + ' input.' + cls));
    }

    function refresh(){
        toArray(document.querySelectorAll('.check-asal')).forEach(function(c){
            c.checked = Object.prototype.hasOwnProperty.call(selAsal, c.value);
        });
        toArray(document.querySelectorAll('.check-tujuan')).forEach(function(c){
            c.checked = Object.prototype.hasOwnProperty.call(selTujuan, c.value);
        });
        if (btnNaik) btnNaik.style.display = countSel(selAsal) > 0 ? '' : 'none';
        if (btnBatal) btnBatal.style.display = countSel(selTujuan) > 0 ? '' : 'none';
        if (checkAllAsal) {
            var pa = pageBoxes('table-asal', 'check-asal');
            checkAllAsal.checked = pa.length > 0 && pa.every(function(b){ return b.checked; });
        }
        if (checkAllTujuan) {
            var pt = pageBoxes('table-tujuan', 'check-tujuan');
            checkAllTujuan.checked = pt.length > 0 && pt.every(function(b){ return b.checked; });
        }
    }

    document.addEventListener('change', function(e){
        var t = e.target;
        if (!t || !t.classList) return;
        if (t.classList.contains('check-asal')) {
            if (t.checked) selAsal[t.value] = 1; else delete selAsal[t.value];
            refresh();
        } else if (t.classList.contains('check-tujuan')) {
            if (t.checked) selTujuan[t.value] = 1; else delete selTujuan[t.value];
            refresh();
        } else if (t.id === 'checkAllAsal') {
            pageBoxes('table-asal', 'check-asal').forEach(function(b){
                b.checked = checkAllAsal.checked;
                if (checkAllAsal.checked) selAsal[b.value] = 1; else delete selAsal[b.value];
            });
            refresh();
        } else if (t.id === 'checkAllTujuan') {
            pageBoxes('table-tujuan', 'check-tujuan').forEach(function(b){
                b.checked = checkAllTujuan.checked;
                if (checkAllTujuan.checked) selTujuan[b.value] = 1; else delete selTujuan[b.value];
            });
            refresh();
        }
    });

    if (window.jQuery) {
        jQuery(document).on('draw.dt', '#table-asal, #table-tujuan', refresh);
    }

    function injectAndSubmit(form, fieldName, sel, aksi){
        toArray(form.querySelectorAll('input[data-nk="1"]')).forEach(function(n){ n.remove(); });
        Object.keys(sel).forEach(function(v){
            var h = document.createElement('input');
            h.type = 'hidden'; h.name = fieldName; h.value = v;
            h.setAttribute('data-nk', '1');
            form.appendChild(h);
        });
        var a = document.createElement('input');
        a.type = 'hidden'; a.name = 'nk_aksi'; a.value = aksi;
        a.setAttribute('data-nk', '1');
        form.appendChild(a);
        toArray(form.querySelectorAll('input.check-asal, input.check-tujuan')).forEach(function(b){ b.checked = false; });
        form.submit();
    }

    function ready(fn){
        if (document.readyState !== 'loading') fn();
        else document.addEventListener('DOMContentLoaded', fn);
    }

    ready(function(){
        refresh();
        var formNaik = document.getElementById('formNaik');
        if (formNaik) formNaik.addEventListener('submit', function(e){
            e.preventDefault();
            if (countSel(selAsal) === 0) { Swal.fire('Peringatan','Pilih dulu siswa di kelas asal','warning'); return; }
            var n = countSel(selAsal);
            Swal.fire({title:'Proses naik kelas?', text:n+' siswa akan dipindah ke kelas tujuan.', icon:'question', showCancelButton:true, confirmButtonText:'Ya, proses!'}).then(function(r){
                if (r.isConfirmed) injectAndSubmit(formNaik, 'nisn[]', selAsal, 'naik');
            });
        });
        var formBatal = document.getElementById('formBatal');
        if (formBatal) formBatal.addEventListener('submit', function(e){
            e.preventDefault();
            if (countSel(selTujuan) === 0) { Swal.fire('Peringatan','Pilih dulu siswa di kelas tujuan','warning'); return; }
            var n = countSel(selTujuan);
            Swal.fire({title:'Batalkan kenaikan?', text:n+' siswa akan dikembalikan ke kelas asal.', icon:'warning', showCancelButton:true, confirmButtonText:'Ya, kembalikan!'}).then(function(r){
                if (r.isConfirmed) injectAndSubmit(formBatal, 'nisn_batal[]', selTujuan, 'batal');
            });
        });
    });
})();
</script>

<?php include '../template/footer.php'; ?>
