<?php
include_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['login']) || !isset($_SESSION['role'])) {
    header('Location: ' . base_url('auth/login.php'));
    exit;
}

$host = isset($_SERVER['HTTP_HOST']) ? (string)$_SERVER['HTTP_HOST'] : '';
$is_local_env = $host === 'localhost' || $host === '127.0.0.1' || preg_match('/(\.test|\.local)(:\d+)?$/i', $host);
$allow_local = isset($_REQUEST['allow_local']) && (string)$_REQUEST['allow_local'] === '1';

$return_to = isset($_REQUEST['return_to']) ? (string)$_REQUEST['return_to'] : '';
$return_to = str_replace(["\r", "\n"], '', $return_to);
$redirectUrl = base_url('index.php');
$baseFull = rtrim(base_url(), '/');
if ($return_to !== '' && strpos($return_to, '://') === false && strpos($return_to, '//') !== 0) {
    $return_to = ltrim($return_to, '/');
    $redirectUrl = $baseFull . '/' . $return_to;
}

$is_ajax = isset($_REQUEST['ajax']) && (string)$_REQUEST['ajax'] === '1';
$respond = static function (string $title, string $text, string $icon, int $statusCode = 200) use ($redirectUrl, $is_ajax): void {
    http_response_code($statusCode);
    if ($is_ajax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['title' => $title, 'text' => $text, 'icon' => $icon], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $_SESSION['flash_swal'] = ['title' => $title, 'text' => $text, 'icon' => $icon];
    header('Location: ' . $redirectUrl);
    exit;
};

if ($_SESSION['role'] !== 'admin') {
    $respond('Gagal', 'Akses ditolak.', 'error', 403);
}

if ($is_local_env && !$allow_local) {
    $respond('Gagal', 'Update Sistem dinonaktifkan di local. Gunakan git/backup.bat untuk sync di local.', 'error', 400);
}

if (!isset($_SESSION['update_token']) || !is_string($_SESSION['update_token']) || $_SESSION['update_token'] === '') {
    $_SESSION['update_token'] = bin2hex(random_bytes(16));
}

$do = isset($_REQUEST['do']) ? (string)$_REQUEST['do'] : '';
$token = isset($_REQUEST['token']) ? (string)$_REQUEST['token'] : '';
$sessionToken = isset($_SESSION['update_token']) ? (string)$_SESSION['update_token'] : '';

if ($do !== '1') {
    $respond('Gagal', 'Aksi tidak valid.', 'error', 400);
}

if ($token === '' || $sessionToken === '' || !hash_equals($sessionToken, $token)) {
    $respond('Gagal', 'Token tidak valid. Silakan buka menu Update Sistem lagi.', 'error', 400);
}

@ini_set('max_execution_time', '0');
@set_time_limit(0);

$repoOwner = 'dewecorp';
$repoName = 'spp';
$defaultBranch = 'main';

$projectRoot = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..');
if ($projectRoot === false) {
    $projectRoot = dirname(__DIR__);
}

$tmpBases = [
    rtrim((string)sys_get_temp_dir(), "\\/"),
    $projectRoot . DIRECTORY_SEPARATOR . 'tmp_update',
];

$tmpBase = '';
foreach ($tmpBases as $candidate) {
    if ($candidate === '') {
        continue;
    }
    if (!is_dir($candidate) && !@mkdir($candidate, 0755, true) && !is_dir($candidate)) {
        continue;
    }
    if (@is_writable($candidate)) {
        $tmpBase = $candidate;
        break;
    }
}

if ($tmpBase === '') {
    $respond('Gagal', 'Folder sementara tidak bisa dibuat. Pastikan permission server.', 'error', 500);
}

$tmpDir = $tmpBase . DIRECTORY_SEPARATOR . 'spp_update_' . bin2hex(random_bytes(6));
$zipPath = $tmpDir . DIRECTORY_SEPARATOR . 'repo.zip';
$extractDir = $tmpDir . DIRECTORY_SEPARATOR . 'extract';

$mk1 = @mkdir($tmpDir, 0755, true);
$mk2 = @mkdir($extractDir, 0755, true);
if (!$mk1 || !$mk2) {
    $respond('Gagal', 'Folder sementara tidak bisa dibuat. Pastikan permission server.', 'error', 500);
}

$cleanup = static function () use ($tmpDir): void {
    $rm = static function (string $path) use (&$rm): void {
        if (!file_exists($path)) {
            return;
        }
        if (is_file($path) || is_link($path)) {
            @unlink($path);
            return;
        }
        $items = @scandir($path);
        if (is_array($items)) {
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }
                $rm($path . DIRECTORY_SEPARATOR . $item);
            }
        }
        @rmdir($path);
    };
    $rm($tmpDir);
};

$zipUrl = "https://github.com/$repoOwner/$repoName/archive/refs/heads/$defaultBranch.zip";
$downloaded = false;

if (function_exists('curl_init')) {
    $fp = @fopen($zipPath, 'wb');
    if ($fp !== false) {
        $ch = curl_init($zipUrl);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['User-Agent: SiBayar-Updater']);
        $ok = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        fclose($fp);
        $downloaded = $ok !== false && $code >= 200 && $code < 300 && file_exists($zipPath) && filesize($zipPath) > 0;
    }
} else {
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "User-Agent: SiBayar-Updater\r\n",
            'timeout' => 120,
            'ignore_errors' => true,
        ],
    ]);
    $data = @file_get_contents($zipUrl, false, $context);
    if ($data !== false && $data !== '') {
        $downloaded = @file_put_contents($zipPath, $data) !== false;
    }
}

if (!$downloaded) {
    $cleanup();
    $respond('Gagal', 'Gagal mengunduh ZIP dari GitHub. Cek koneksi server atau repo.', 'error', 500);
}

if (!class_exists('ZipArchive')) {
    $cleanup();
    $respond('Gagal', 'Ekstensi ZipArchive tidak tersedia di server.', 'error', 500);
}

$zip = new ZipArchive();
if ($zip->open($zipPath) !== true) {
    $cleanup();
    $respond('Gagal', 'ZIP tidak bisa dibuka.', 'error', 500);
}
if (!$zip->extractTo($extractDir)) {
    $zip->close();
    $cleanup();
    $respond('Gagal', 'ZIP tidak bisa diekstrak.', 'error', 500);
}
$zip->close();

$sourceRoot = $extractDir . DIRECTORY_SEPARATOR . $repoName . '-' . $defaultBranch;
if (!is_dir($sourceRoot)) {
    $sourceRoot = '';
    $entries = @scandir($extractDir);
    if (is_array($entries)) {
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $full = $extractDir . DIRECTORY_SEPARATOR . $entry;
            if (is_dir($full)) {
                $sourceRoot = $full;
                break;
            }
        }
    }
}

if ($sourceRoot === '' || !is_dir($sourceRoot)) {
    $cleanup();
    $respond('Gagal', 'Struktur ZIP tidak valid.', 'error', 500);
}

$skipPrefixes = [
    '.git' . DIRECTORY_SEPARATOR,
    'vendor' . DIRECTORY_SEPARATOR,
    'backup_db' . DIRECTORY_SEPARATOR,
    'tmp_update' . DIRECTORY_SEPARATOR,
];
$skipFiles = [
    'config' . DIRECTORY_SEPARATOR . 'config.php',
    'pengaturan' . DIRECTORY_SEPARATOR . 'update_sistem.php',
];

$copyErrors = [];
$copyRecursive = static function (string $src, string $dst, string $rel = '') use (&$copyRecursive, &$copyErrors, $skipPrefixes, $skipFiles) {
    $relNorm = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($rel, "\\/"));
    foreach ($skipFiles as $skipFile) {
        if ($relNorm === $skipFile) {
            return;
        }
    }
    foreach ($skipPrefixes as $skipPrefix) {
        if ($relNorm !== '' && strpos($relNorm . DIRECTORY_SEPARATOR, $skipPrefix) === 0) {
            return;
        }
    }

    if (is_dir($src)) {
        if (!is_dir($dst) && !@mkdir($dst, 0755, true) && !is_dir($dst)) {
            $copyErrors[] = $relNorm !== '' ? $relNorm : '(root)';
            return;
        }
        $items = @scandir($src);
        if (!is_array($items)) {
            $copyErrors[] = $relNorm !== '' ? $relNorm : '(root)';
            return;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $srcPath = $src . DIRECTORY_SEPARATOR . $item;
            $dstPath = $dst . DIRECTORY_SEPARATOR . $item;
            $nextRel = $relNorm === '' ? $item : ($relNorm . DIRECTORY_SEPARATOR . $item);
            $copyRecursive($srcPath, $dstPath, $nextRel);
        }
        return;
    }

    if (!is_file($src)) {
        return;
    }

    $dstDir = dirname($dst);
    if (!is_dir($dstDir) && !@mkdir($dstDir, 0755, true) && !is_dir($dstDir)) {
        $copyErrors[] = $relNorm;
        return;
    }

    if (!@copy($src, $dst)) {
        $copyErrors[] = $relNorm;
        return;
    }
};

$copyRecursive($sourceRoot, $projectRoot, '');
$cleanup();

if (!empty($copyErrors)) {
    $count = count($copyErrors);
    $msg = "Update selesai, tapi ada $count file/folder yang gagal ditimpa. Cek permission server.";
    $respond('Perhatian', $msg, 'warning', 200);
}

$respond('Berhasil', "Update sistem berhasil dari GitHub ($defaultBranch).", 'success', 200);
