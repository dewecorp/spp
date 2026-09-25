<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../include/laporan_helper.php';

ensure_integrasi_tables($koneksi);

// Handle AJAX Test Connection (Harus sebelum include header/sidebar agar output murni JSON)
if (isset($_GET['action']) && $_GET['action'] === 'test_connection') {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    $id = (int)($_GET['id'] ?? 0);
    $q_ep = mysqli_query($koneksi, "SELECT * FROM endpoint_masuk WHERE id = $id LIMIT 1");
    $ep = $q_ep ? mysqli_fetch_assoc($q_ep) : null;

    if (!$ep) {
        echo json_encode(['status' => 'error', 'message' => 'Endpoint tidak ditemukan']);
        exit;
    }

    $baseUrl = trim($ep['base_url']);
    $apiKey  = trim($ep['api_key']);
    $app     = strtolower(trim($ep['aplikasi']));

    if (empty($baseUrl)) {
        $now = date('Y-m-d H:i:s');
        $status_txt = 'GAGAL';
        $detail_txt = "$now\nBase URL kosong.";
        $detail_esc = mysqli_real_escape_string($koneksi, $detail_txt);
        mysqli_query($koneksi, "UPDATE endpoint_masuk SET tes_terakhir_status = '$status_txt', tes_terakhir_detail = '$detail_esc' WHERE id = $id");

        $badge_html = '<div class="text-xs text-center font-medium"><span class="font-bold text-red-600 block">GAGAL</span><span class="text-[11px] text-slate-500 block leading-tight mt-0.5">' . date('Y-m-d H:i:s') . '<br>Base URL kosong.</span></div>';
        echo json_encode([
            'status' => 'error',
            'message' => 'Base URL masih kosong!',
            'badge_html' => $badge_html
        ]);
        exit;
    }

    // Tentukan URL test berdasarkan tipe aplikasi
    if ($app === 'simad') {
        $targetUrl = rtrim($baseUrl, '/') . "/api/v1/students.php?api_key=" . urlencode($apiKey);
    } elseif ($app === 'etab') {
        $targetUrl = rtrim($baseUrl, '/') . "/api/etab.php?action=check&api_key=" . urlencode($apiKey);
    } else {
        $targetUrl = rtrim($baseUrl, '/') . "/api/v1.php?action=health&api_key=" . urlencode($apiKey);
    }

    $startTime = microtime(true);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $targetUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Sibayar-Integration-Tester/1.0');
    
    $response = curl_exec($ch);
    $latency  = round((microtime(true) - $startTime) * 1000);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    // If initial endpoint gave 404/405, attempt fallback root ping
    if ($httpCode === 404 || $httpCode === 405 || ($httpCode === 0 && !empty($curlErr))) {
        $fallbackUrl = rtrim($baseUrl, '/');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $fallbackUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Sibayar-Integration-Tester/1.0');
        $res2 = curl_exec($ch);
        $code2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code2 >= 200 && $code2 < 400) {
            $httpCode = $code2;
            $response = $res2;
        }
    }

    $now = date('Y-m-d H:i:s');
    $isOk = ($httpCode >= 200 && $httpCode < 400 && $response !== false);

    if ($isOk) {
        $status_txt = 'OK';
        $detail_txt = "$now\nHTTP $httpCode OK\n{$latency}ms";
        $detail_esc = mysqli_real_escape_string($koneksi, $detail_txt);
        mysqli_query($koneksi, "UPDATE endpoint_masuk SET tes_terakhir_status = '$status_txt', tes_terakhir_detail = '$detail_esc' WHERE id = $id");

        $badge_html = '<div class="text-xs text-center font-medium"><span class="font-bold text-emerald-600 block">OK</span><span class="text-[11px] text-slate-500 block leading-tight mt-0.5">' . $now . '<br>HTTP ' . $httpCode . ' OK<br>' . $latency . 'ms</span></div>';
        echo json_encode([
            'status' => 'success',
            'message' => "Koneksi Berhasil! HTTP $httpCode OK ({$latency}ms)",
            'badge_html' => $badge_html
        ]);
    } else {
        $status_txt = 'GAGAL';
        $reason = !empty($curlErr) ? "Err: $curlErr" : "HTTP $httpCode";
        $detail_txt = "$now\n$reason";
        $detail_esc = mysqli_real_escape_string($koneksi, $detail_txt);
        mysqli_query($koneksi, "UPDATE endpoint_masuk SET tes_terakhir_status = '$status_txt', tes_terakhir_detail = '$detail_esc' WHERE id = $id");

        $badge_html = '<div class="text-xs text-center font-medium"><span class="font-bold text-red-600 block">GAGAL</span><span class="text-[11px] text-slate-500 block leading-tight mt-0.5">' . $now . '<br>' . htmlspecialchars($reason) . '</span></div>';
        echo json_encode([
            'status' => 'error',
            'message' => "Koneksi Gagal: $reason",
            'badge_html' => $badge_html
        ]);
    }
    exit;
}

$title = 'Pengaturan Endpoint Integrasi';
include '../template/header.php';
include '../template/sidebar.php';

// POST Processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Tambah Endpoint Keluar
    if (isset($_POST['tambah_keluar'])) {
        $nama      = mysqli_real_escape_string($koneksi, trim($_POST['nama'] ?? ''));
        $deskripsi = mysqli_real_escape_string($koneksi, trim($_POST['deskripsi'] ?? ''));
        $metode    = mysqli_real_escape_string($koneksi, trim($_POST['metode'] ?? 'GET'));
        $path      = mysqli_real_escape_string($koneksi, trim($_POST['path_endpoint'] ?? ''));

        if (!empty($nama) && !empty($path)) {
            mysqli_query($koneksi, "INSERT INTO endpoint_keluar (nama, deskripsi, metode, path_endpoint, status) VALUES ('$nama', '$deskripsi', '$metode', '$path', 'Aktif')");
            logActivity($koneksi, 'Create', "Menambah endpoint keluar: $nama");
            echo "<script>Swal.fire({icon:'success', title:'Berhasil', text:'Endpoint Keluar berhasil ditambahkan!', timer:1500, showConfirmButton:false}).then(()=>{ window.location.href='endpoint.php'; });</script>";
        }
    }

    // 2. Toggle Status Endpoint Keluar
    if (isset($_POST['toggle_keluar'])) {
        $id = (int)$_POST['id_keluar'];
        $q = mysqli_query($koneksi, "SELECT status FROM endpoint_keluar WHERE id = $id");
        if ($d = mysqli_fetch_assoc($q)) {
            $new_status = ($d['status'] === 'Aktif') ? 'Non-aktif' : 'Aktif';
            mysqli_query($koneksi, "UPDATE endpoint_keluar SET status = '$new_status' WHERE id = $id");
            logActivity($koneksi, 'Update', "Mengubah status endpoint keluar ID $id ke $new_status");
            echo "<script>window.location.href='endpoint.php';</script>";
            exit;
        }
    }

    // 3. Hapus Endpoint Keluar
    if (isset($_POST['hapus_keluar'])) {
        $id = (int)$_POST['id_keluar'];
        mysqli_query($koneksi, "DELETE FROM endpoint_keluar WHERE id = $id");
        logActivity($koneksi, 'Delete', "Menghapus endpoint keluar ID $id");
        echo "<script>Swal.fire({icon:'success', title:'Berhasil', text:'Endpoint Keluar telah dihapus!', timer:1500, showConfirmButton:false}).then(()=>{ window.location.href='endpoint.php'; });</script>";
    }

    // 4. Tambah Endpoint Masuk (Aplikasi Baru)
    if (isset($_POST['tambah_masuk'])) {
        $aplikasi  = mysqli_real_escape_string($koneksi, strtolower(trim($_POST['aplikasi'] ?? '')));
        $nama_app  = mysqli_real_escape_string($koneksi, trim($_POST['nama_aplikasi'] ?? $aplikasi));
        $deskripsi = mysqli_real_escape_string($koneksi, trim($_POST['deskripsi'] ?? ''));
        $base_url_in  = mysqli_real_escape_string($koneksi, trim($_POST['base_url'] ?? ''));
        $api_key_in   = mysqli_real_escape_string($koneksi, trim($_POST['api_key'] ?? ''));

        if (!empty($aplikasi)) {
            $sql = "INSERT INTO endpoint_masuk (aplikasi, nama_aplikasi, deskripsi, base_url, api_key, status, tes_terakhir_status) 
                    VALUES ('$aplikasi', '$nama_app', '$deskripsi', '$base_url_in', '$api_key_in', 1, 'Belum dites')
                    ON DUPLICATE KEY UPDATE base_url = VALUES(base_url), api_key = VALUES(api_key), deskripsi = VALUES(deskripsi)";
            mysqli_query($koneksi, $sql);
            logActivity($koneksi, 'Create', "Menambah endpoint masuk aplikasi: $aplikasi");
            echo "<script>Swal.fire({icon:'success', title:'Berhasil', text:'Aplikasi baru berhasil ditambahkan!', timer:1500, showConfirmButton:false}).then(()=>{ window.location.href='endpoint.php'; });</script>";
        }
    }

    // 5. Simpan Semua Endpoint Masuk
    if (isset($_POST['simpan_semua_masuk'])) {
        if (!empty($_POST['base_url']) && is_array($_POST['base_url'])) {
            foreach ($_POST['base_url'] as $id_app => $val_url) {
                $id_app  = (int)$id_app;
                $url_esc = mysqli_real_escape_string($koneksi, trim($val_url));
                $key_esc = mysqli_real_escape_string($koneksi, trim($_POST['api_key'][$id_app] ?? ''));
                $st_val  = isset($_POST['status_masuk'][$id_app]) ? 1 : 0;

                mysqli_query($koneksi, "UPDATE endpoint_masuk SET base_url = '$url_esc', api_key = '$key_esc', status = $st_val WHERE id = $id_app");
            }
            logActivity($koneksi, 'Update', "Memperbarui konfigurasi endpoint masuk");
            echo "<script>Swal.fire({icon:'success', title:'Tersimpan', text:'Semua endpoint masuk berhasil diperbarui!', timer:1500, showConfirmButton:false}).then(()=>{ window.location.href='endpoint.php'; });</script>";
        }
    }

    // 6. Hapus Endpoint Masuk
    if (isset($_POST['hapus_masuk'])) {
        $id = (int)$_POST['id_masuk'];
        mysqli_query($koneksi, "DELETE FROM endpoint_masuk WHERE id = $id");
        logActivity($koneksi, 'Delete', "Menghapus endpoint masuk ID $id");
        echo "<script>Swal.fire({icon:'success', title:'Berhasil', text:'Aplikasi berhasil dihapus!', timer:1500, showConfirmButton:false}).then(()=>{ window.location.href='endpoint.php'; });</script>";
    }
}

// Fetch Data
$q_keluar = mysqli_query($koneksi, "SELECT * FROM endpoint_keluar ORDER BY id ASC");
$q_masuk  = mysqli_query($koneksi, "SELECT * FROM endpoint_masuk ORDER BY id ASC");
?>

<div class="app-grid space-y-6">
    <!-- SECTION 1: ENDPOINT KELUAR -->
    <div class="app-col-full app-stretch">
        <div class="app-panel bg-white shadow-sm rounded-xl border border-slate-200/80 overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h4 class="text-lg font-bold text-slate-800">Endpoint Keluar — <span class="text-slate-500 font-normal">dari Sibayar ke web lain</span></h4>
                    <p class="text-sm text-slate-500 mt-1">
                        Salin URL endpoint tagihan Sibayar lalu tempel di web lain (SIMAD / ETAB). Base URL terdeteksi otomatis: 
                        <code class="text-rose-600 font-semibold bg-rose-50 px-1.5 py-0.5 rounded border border-rose-100"><?= htmlspecialchars($base_url) ?></code>
                    </p>
                </div>
                <button type="button" class="app-button app-button-primary flex items-center gap-1.5 text-sm px-4 py-2" onclick="openModalKeluar()">
                    <i class="mdi mdi-plus text-base"></i> Tambah
                </button>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-700">
                    <thead class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-200/70 uppercase text-xs">
                        <tr>
                            <th class="py-3.5 px-4 w-12 text-center">No</th>
                            <th class="py-3.5 px-4">Nama</th>
                            <th class="py-3.5 px-4 text-center">Metode</th>
                            <th class="py-3.5 px-4">URL Siap Salin</th>
                            <th class="py-3.5 px-4 text-center">Status</th>
                            <th class="py-3.5 px-4 text-center w-28">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php 
                        $no = 1;
                        if (mysqli_num_rows($q_keluar) > 0) :
                            while ($r_k = mysqli_fetch_assoc($q_keluar)) :
                                $full_url = rtrim($base_url, '/') . '/' . ltrim($r_k['path_endpoint'], '/');
                                $is_aktif = ($r_k['status'] === 'Aktif');
                        ?>
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="py-3.5 px-4 text-center font-medium text-slate-500"><?= $no++ ?></td>
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-800"><?= htmlspecialchars($r_k['nama']) ?></div>
                                <?php if (!empty($r_k['deskripsi'])) : ?>
                                    <div class="text-xs text-slate-400 mt-0.5"><?= htmlspecialchars($r_k['deskripsi']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-block px-2.5 py-1 text-xs font-semibold rounded-full bg-cyan-500 text-white shadow-xs">
                                    <?= htmlspecialchars($r_k['metode']) ?>
                                </span>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-2 max-w-xl">
                                    <input type="text" class="w-full bg-slate-100 text-slate-600 text-xs px-3 py-2 rounded-lg border border-slate-200/80 font-mono select-all focus:outline-none" value="<?= htmlspecialchars($full_url) ?>" readonly id="url-k-<?= $r_k['id'] ?>">
                                    <button type="button" class="p-2 text-slate-500 hover:text-cyan-600 hover:bg-cyan-50 rounded-lg border border-slate-200/80 transition flex-shrink-0" onclick="copyToClipboard('<?= addslashes($full_url) ?>')" title="Salin URL">
                                        <i class="mdi mdi-content-copy text-base"></i>
                                    </button>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <?php if ($is_aktif) : ?>
                                    <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full bg-emerald-500 text-white shadow-xs">Aktif</span>
                                <?php else : ?>
                                    <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full bg-slate-400 text-white shadow-xs">Non-aktif</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="flex items-center justify-center gap-1.5">
                                    <form method="post" class="inline">
                                        <input type="hidden" name="id_keluar" value="<?= $r_k['id'] ?>">
                                        <button type="submit" name="toggle_keluar" class="p-1.5 text-white <?= $is_aktif ? 'bg-amber-500 hover:bg-amber-600' : 'bg-emerald-500 hover:bg-emerald-600' ?> rounded-lg transition" title="<?= $is_aktif ? 'Matikan' : 'Aktifkan' ?>">
                                            <i class="mdi mdi-power text-sm"></i>
                                        </button>
                                    </form>
                                    <form method="post" class="inline" onsubmit="return confirm('Yakin hapus endpoint ini?')">
                                        <input type="hidden" name="id_keluar" value="<?= $r_k['id'] ?>">
                                        <button type="submit" name="hapus_keluar" class="p-1.5 text-white bg-rose-500 hover:bg-rose-600 rounded-lg transition" title="Hapus">
                                            <i class="mdi mdi-delete text-sm"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php 
                            endwhile;
                        else :
                        ?>
                        <tr>
                            <td colspan="6" class="py-6 text-center text-slate-400">Belum ada endpoint keluar ditambahkan.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- SECTION 2: ENDPOINT MASUK -->
    <div class="app-col-full app-stretch">
        <div class="app-panel bg-white shadow-sm rounded-xl border border-slate-200/80 overflow-hidden">
            <form method="post">
                <div class="p-5 border-b border-slate-100 flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h4 class="text-lg font-bold text-slate-800">Endpoint Masuk — <span class="text-slate-500 font-normal">dari web lain ke Sibayar</span></h4>
                        <p class="text-sm text-slate-500 mt-1">
                            Isi base URL + API key aplikasi eksternal (SIMAD untuk tarik data siswa, ETAB untuk tabungan). Sibayar memakai ini saat menarik data — ganti domain cukup edit di sini.
                        </p>
                    </div>
                    <button type="button" class="app-button app-button-primary flex items-center gap-1.5 text-sm px-4 py-2" onclick="openModalMasuk()">
                        <i class="mdi mdi-plus text-base"></i> Tambah Aplikasi
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-700">
                        <thead class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-200/70 uppercase text-xs">
                            <tr>
                                <th class="py-3.5 px-4 w-48">Aplikasi</th>
                                <th class="py-3.5 px-4">Base URL</th>
                                <th class="py-3.5 px-4 w-52">API Key</th>
                                <th class="py-3.5 px-4 text-center w-16">Aktif</th>
                                <th class="py-3.5 px-4 text-center w-48">Tes Terakhir</th>
                                <th class="py-3.5 px-4 text-center w-36">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php 
                            if (mysqli_num_rows($q_masuk) > 0) :
                                while ($r_m = mysqli_fetch_assoc($q_masuk)) :
                                    $id_m = $r_m['id'];
                                    $st_tes = $r_m['tes_terakhir_status'] ?? 'Belum dites';
                                    $dt_tes = $r_m['tes_terakhir_detail'] ?? '';
                            ?>
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-800 text-base"><?= htmlspecialchars($r_m['aplikasi']) ?></div>
                                    <div class="text-xs text-slate-400 mt-0.5"><?= htmlspecialchars($r_m['deskripsi']) ?></div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <input type="text" name="base_url[<?= $id_m ?>]" class="w-full bg-white text-slate-700 text-sm px-3 py-2 rounded-lg border border-slate-300 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 font-mono" value="<?= htmlspecialchars($r_m['base_url']) ?>" placeholder="https://aplikasi.example.com">
                                </td>
                                <td class="py-3.5 px-4">
                                    <input type="text" name="api_key[<?= $id_m ?>]" class="w-full bg-white text-slate-700 text-sm px-3 py-2 rounded-lg border border-slate-300 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 font-mono" value="<?= htmlspecialchars($r_m['api_key']) ?>" placeholder="API key">
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <input type="checkbox" name="status_masuk[<?= $id_m ?>]" value="1" <?= $r_m['status'] == 1 ? 'checked' : '' ?> class="w-4 h-4 text-cyan-600 rounded border-slate-300 focus:ring-cyan-500">
                                </td>
                                <td class="py-3.5 px-4 text-center" id="badge-tes-<?= $id_m ?>">
                                    <?php if ($st_tes === 'OK') : ?>
                                        <div class="text-xs text-center font-medium">
                                            <span class="font-bold text-emerald-600 block">OK</span>
                                            <span class="text-[11px] text-slate-500 block leading-tight mt-0.5"><?= nl2br(htmlspecialchars($dt_tes)) ?></span>
                                        </div>
                                    <?php elseif ($st_tes === 'GAGAL') : ?>
                                        <div class="text-xs text-center font-medium">
                                            <span class="font-bold text-red-600 block">GAGAL</span>
                                            <span class="text-[11px] text-slate-500 block leading-tight mt-0.5"><?= nl2br(htmlspecialchars($dt_tes)) ?></span>
                                        </div>
                                    <?php else : ?>
                                        <span class="text-xs text-slate-400 font-medium">Belum dites</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button type="button" class="px-3 py-1.5 text-xs font-semibold text-white bg-cyan-500 hover:bg-cyan-600 rounded-lg transition flex items-center gap-1 shadow-xs" onclick="tesKoneksi(<?= $id_m ?>, this)">
                                            <i class="mdi mdi-power-plug text-sm"></i> Tes
                                        </button>
                                        <button type="button" class="p-1.5 text-white bg-rose-500 hover:bg-rose-600 rounded-lg transition" title="Hapus" onclick="hapusMasuk(<?= $id_m ?>)">
                                            <i class="mdi mdi-delete text-sm"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php 
                                endwhile;
                            else :
                            ?>
                            <tr>
                                <td colspan="6" class="py-6 text-center text-slate-400">Belum ada endpoint masuk ditambahkan.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="p-5 border-t border-slate-100 bg-slate-50/50">
                    <button type="submit" name="simpan_semua_masuk" class="app-button app-button-primary px-5 py-2.5 flex items-center gap-2">
                        <i class="mdi mdi-content-save text-base"></i> Simpan Semua
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- FORM TERSEMBUNYI UNTUK HAPUS MASUK -->
<form id="form-hapus-masuk" method="post" style="display:none;">
    <input type="hidden" name="id_masuk" id="hapus-id-masuk">
    <input type="hidden" name="hapus_masuk" value="1">
</form>

<!-- MODAL TAMBAH ENDPOINT KELUAR -->
<div id="modalKeluar" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-xs hidden">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-6 relative">
        <h3 class="text-lg font-bold text-slate-800 mb-4">Tambah Endpoint Keluar</h3>
        <form method="post" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Nama Endpoint</label>
                <input type="text" name="nama" class="app-control" placeholder="Contoh: Data Siswa (SIMAD)" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Deskripsi</label>
                <input type="text" name="deskripsi" class="app-control" placeholder="Deskripsi singkat fungsi endpoint">
            </div>
            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Metode</label>
                    <select name="metode" class="app-control">
                        <option value="GET">GET</option>
                        <option value="POST">POST</option>
                        <option value="GET/POST">GET/POST</option>
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Path Endpoint (relatif ke base_url)</label>
                    <input type="text" name="path_endpoint" class="app-control font-mono text-xs" placeholder="api/v1.php?action=health&api_key=..." required>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-2">
                <button type="button" class="app-button app-button-light" onclick="closeModalKeluar()">Batal</button>
                <button type="submit" name="tambah_keluar" class="app-button app-button-primary">Simpan Endpoint</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL TAMBAH ENDPOINT MASUK -->
<div id="modalMasuk" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-xs hidden">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-6 relative">
        <h3 class="text-lg font-bold text-slate-800 mb-4">Tambah Aplikasi Integration (Endpoint Masuk)</h3>
        <form method="post" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Kode Aplikasi (unik, misal: etab, simad, sigaji)</label>
                <input type="text" name="aplikasi" class="app-control" placeholder="etab" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Nama Aplikasi</label>
                <input type="text" name="nama_aplikasi" class="app-control" placeholder="Etab">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Deskripsi</label>
                <input type="text" name="deskripsi" class="app-control" placeholder="Ambil endpoint/data dari aplikasi Etab.">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Base URL Aplikasi</label>
                <input type="text" name="base_url" class="app-control font-mono text-xs" placeholder="https://etab.example.com">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">API Key</label>
                <input type="text" name="api_key" class="app-control font-mono text-xs" placeholder="API key">
            </div>
            <div class="mt-6 flex justify-end gap-2">
                <button type="button" class="app-button app-button-light" onclick="closeModalMasuk()">Batal</button>
                <button type="submit" name="tambah_masuk" class="app-button app-button-primary">Simpan Aplikasi</button>
            </div>
        </form>
    </div>
</div>

<script>
function copyToClipboard(text) {
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(function() {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: 'URL berhasil disalin ke clipboard!',
                timer: 1500,
                showConfirmButton: false
            });
        }).catch(function(err) {
            fallbackCopy(text);
        });
    } else {
        fallbackCopy(text);
    }
}

function fallbackCopy(text) {
    var tempInput = document.createElement("input");
    tempInput.value = text;
    document.body.appendChild(tempInput);
    tempInput.select();
    document.execCommand("copy");
    document.body.removeChild(tempInput);
    Swal.fire({
        icon: 'success',
        title: 'Berhasil',
        text: 'URL berhasil disalin ke clipboard!',
        timer: 1500,
        showConfirmButton: false
    });
}

function tesKoneksi(id, btnEl) {
    var origHtml = btnEl.innerHTML;
    btnEl.disabled = true;
    btnEl.innerHTML = '<i class="mdi mdi-spin mdi-loading"></i> Tes...';

    fetch('endpoint.php?action=test_connection&id=' + id)
        .then(res => res.json())
        .then(data => {
            btnEl.disabled = false;
            btnEl.innerHTML = origHtml;
            var badgeEl = document.getElementById('badge-tes-' + id);
            if (badgeEl && data.badge_html) {
                badgeEl.innerHTML = data.badge_html;
            }
            if (data.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Koneksi Sukses!',
                    text: data.message,
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Koneksi Gagal!',
                    text: data.message
                });
            }
        })
        .catch(err => {
            btnEl.disabled = false;
            btnEl.innerHTML = origHtml;
            Swal.fire({ icon: 'error', title: 'Error', text: err.message });
        });
}

function hapusMasuk(id) {
    Swal.fire({
        title: 'Apakah anda yakin?',
        text: "Aplikasi endpoint masuk ini akan dihapus.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus!'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('hapus-id-masuk').value = id;
            document.getElementById('form-hapus-masuk').submit();
        }
    });
}

function openModalKeluar() { document.getElementById('modalKeluar').classList.remove('hidden'); }
function closeModalKeluar() { document.getElementById('modalKeluar').classList.add('hidden'); }
function openModalMasuk() { document.getElementById('modalMasuk').classList.remove('hidden'); }
function closeModalMasuk() { document.getElementById('modalMasuk').classList.add('hidden'); }
</script>

<?php include '../template/footer.php'; ?>
