<?php
include_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['login']) || !isset($_SESSION['role'])) {
    header('Location: ' . base_url('auth/login.php'));
    exit;
}

if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method Not Allowed';
    exit;
}

$token = isset($_POST['token']) ? (string)$_POST['token'] : '';
$sessionToken = isset($_SESSION['update_token']) ? (string)$_SESSION['update_token'] : '';
if ($token === '' || $sessionToken === '' || !hash_equals($sessionToken, $token)) {
    http_response_code(400);
    echo 'Invalid token';
    exit;
}

$render = static function (string $title, string $text, string $icon): void {
    $redirectUrl = base_url('index.php');
    echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>';
    echo '</head><body>';
    echo '<script>Swal.fire({title:' . json_encode($title) . ',text:' . json_encode($text) . ',icon:' . json_encode($icon) . '}).then(()=>{window.location.href=' . json_encode($redirectUrl) . ';});</script>';
    echo '</body></html>';
};

$repoOwner = 'dewecorp';
$repoName = 'spp';

$projectRoot = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..');
if ($projectRoot === false) {
    $projectRoot = dirname(__DIR__);
}

$tmpBase = rtrim((string)sys_get_temp_dir(), "\\/");
$tmpDir = $tmpBase . DIRECTORY_SEPARATOR . 'spp_update_' . bin2hex(random_bytes(6));
$zipPath = $tmpDir . DIRECTORY_SEPARATOR . 'repo.zip';
$extractDir = $tmpDir . DIRECTORY_SEPARATOR . 'extract';

$mk1 = @mkdir($tmpDir, 0755, true);
$mk2 = @mkdir($extractDir, 0755, true);
if (!$mk1 || !$mk2) {
    $render('Gagal', 'Folder sementara tidak bisa dibuat. Pastikan permission server.', 'error');
    exit;
}

$httpGet = static function (string $url): array {
    $headers = [
        'User-Agent: SiBayar-Updater',
        'Accept: application/json',
    ];

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $body = curl_exec($ch);
        $err = curl_error($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        return ['ok' => $body !== false && $code >= 200 && $code < 300, 'code' => $code, 'body' => $body !== false ? $body : '', 'error' => $err];
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => implode("\r\n", $headers) . "\r\n",
            'timeout' => 60,
            'ignore_errors' => true,
        ],
    ]);
    $body = @file_get_contents($url, false, $context);
    $code = 0;
    if (isset($http_response_header) && is_array($http_response_header)) {
        foreach ($http_response_header as $line) {
            if (preg_match('/^HTTP\/\S+\s+(\d+)/', $line, $m)) {
                $code = (int)$m[1];
                break;
            }
        }
    }
    return ['ok' => $body !== false && $code >= 200 && $code < 300, 'code' => $code, 'body' => $body !== false ? $body : '', 'error' => ''];
};

$defaultBranch = '';
$repoInfo = $httpGet("https://api.github.com/repos/$repoOwner/$repoName");
if ($repoInfo['ok']) {
    $json = json_decode($repoInfo['body'], true);
    if (is_array($json) && isset($json['default_branch'])) {
        $defaultBranch = (string)$json['default_branch'];
    }
}
if ($defaultBranch === '') {
    $defaultBranch = 'main';
}

$downloadFile = static function (string $url, string $destPath): bool {
    if (function_exists('curl_init')) {
        $fp = @fopen($destPath, 'wb');
        if ($fp === false) {
            return false;
        }
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['User-Agent: SiBayar-Updater']);
        $ok = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        fclose($fp);
        return $ok !== false && $code >= 200 && $code < 300 && filesize($destPath) > 0;
    }

    $in = @fopen($url, 'rb');
    if ($in === false) {
        return false;
    }
    $out = @fopen($destPath, 'wb');
    if ($out === false) {
        fclose($in);
        return false;
    }
    $copied = @stream_copy_to_stream($in, $out);
    fclose($in);
    fclose($out);
    return is_int($copied) && $copied > 0;
};

$zipUrl = "https://codeload.github.com/$repoOwner/$repoName/zip/refs/heads/" . rawurlencode($defaultBranch);
$downloaded = $downloadFile($zipUrl, $zipPath);
if (!$downloaded && $defaultBranch !== 'master') {
    $defaultBranch = 'master';
    $zipUrl = "https://codeload.github.com/$repoOwner/$repoName/zip/refs/heads/" . rawurlencode($defaultBranch);
    $downloaded = $downloadFile($zipUrl, $zipPath);
}

if (!$downloaded) {
    $render('Gagal', 'Gagal mengunduh ZIP dari GitHub. Cek koneksi server atau repo.', 'error');
    exit;
}

if (!class_exists('ZipArchive')) {
    $render('Gagal', 'Ekstensi ZipArchive tidak tersedia di server.', 'error');
    exit;
}

$zip = new ZipArchive();
if ($zip->open($zipPath) !== true) {
    $render('Gagal', 'ZIP tidak bisa dibuka.', 'error');
    exit;
}
if (!$zip->extractTo($extractDir)) {
    $zip->close();
    $render('Gagal', 'ZIP tidak bisa diekstrak.', 'error');
    exit;
}
$zip->close();

$entries = @scandir($extractDir);
$sourceRoot = '';
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

if ($sourceRoot === '') {
    $render('Gagal', 'Struktur ZIP tidak valid.', 'error');
    exit;
}

$skipPrefixes = [
    'vendor' . DIRECTORY_SEPARATOR,
    'backup_db' . DIRECTORY_SEPARATOR,
];
$skipFiles = [
    'config' . DIRECTORY_SEPARATOR . 'config.php',
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

    $dstDir = dirname($dst);
    if (!is_dir($dstDir) && !@mkdir($dstDir, 0755, true) && !is_dir($dstDir)) {
        $copyErrors[] = $relNorm !== '' ? $relNorm : basename($dst);
        return;
    }

    if (!@copy($src, $dst)) {
        $copyErrors[] = $relNorm !== '' ? $relNorm : basename($dst);
        return;
    }
};

$copyRecursive($sourceRoot, $projectRoot, '');

$rmRecursive = static function (string $path) use (&$rmRecursive) {
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
            $rmRecursive($path . DIRECTORY_SEPARATOR . $item);
        }
    }
    @rmdir($path);
};

$rmRecursive($tmpDir);

if (!empty($copyErrors)) {
    $count = count($copyErrors);
    $msg = "Update selesai, tapi ada $count file/folder yang gagal ditimpa. Cek permission server.";
    $render('Perhatian', $msg, 'warning');
    exit;
}

$render('Berhasil', "Update sistem berhasil dari GitHub ($defaultBranch).", 'success');
