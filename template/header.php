<?php
include_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['logo_sekolah_cache'])) {
    $q_setting = mysqli_query($koneksi, "SELECT logo FROM pengaturan WHERE id_pengaturan = 1");
    $d_setting = mysqli_fetch_assoc($q_setting);
    $_SESSION['logo_sekolah_cache'] = $d_setting['logo'] ?? '';
}
$logo_sekolah = (string)$_SESSION['logo_sekolah_cache'];

$style_path = __DIR__ . '/../assets/css/custom.css';
$style_ver = @filemtime($style_path);
if ($style_ver === false) {
    $style_ver = 1;
}

if (!isset($_SESSION['login']) || !isset($_SESSION['nama_lengkap']) || !isset($_SESSION['role'])) {
    session_destroy();
    header("Location: " . base_url('auth/login.php'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?= isset($title) ? $title . ' - ' : '' ?>SiBayar</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:opsz,wght@6..12,400;6..12,500;6..12,600;6..12,700;6..12,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/vendors/mdi/css/materialdesignicons.min.css') ?>">
    <link rel="shortcut icon" href="<?= base_url('assets/images/favicon_pembayaran.svg') ?>" type="image/svg+xml" />
    <!-- Tailwind CSS -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { DEFAULT: '#10b981', dark: '#059669', light: '#34d399', soft: '#d1fae5' },
                    },
                    fontFamily: { sans: ['Nunito Sans', 'ui-sans-serif', 'system-ui', 'sans-serif'] }
                }
            }
        }
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.24/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="<?= base_url('assets/css/custom.css') ?>?v=<?= $style_ver ?>">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
    <script>
        // Konfigurasi Terpusat SweetAlert2
        (function() {
            const OriginalSwal = Swal;
            const GlobalSwal = OriginalSwal.mixin({
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#d33',
                cancelButtonText: 'Batal',
                reverseButtons: true
            });

            // Override global Swal.fire
            window.Swal = {
                ...OriginalSwal,
                fire: function(...args) {
                    if (args.length === 1 && typeof args[0] === 'object') {
                        // Gabungkan dengan default, tapi prioritaskan input user jika ada explicit cancel text
                        const options = {
                            cancelButtonText: 'Batal',
                            ...args[0]
                        };
                        return GlobalSwal.fire(options);
                    }
                    return GlobalSwal.fire(...args);
                }
            };
        })();
    </script>
    <script>
        // Tailwind-only sidebar toggle for mobile.
        document.addEventListener('click', function(event) {
            const offcanvasToggle = event.target.closest('[data-sidebar-toggle]');
            if (!offcanvasToggle) return;
            event.preventDefault();
            const sidebar = document.getElementById('sidebar');
            if (sidebar) sidebar.classList.toggle('active');
        });
    </script>
    <style>
        .swal2-popup {
            font-family: 'Nunito Sans', ui-sans-serif, system-ui, sans-serif !important;
        }
    </style>
</head>
<body>
    <div class="app-shell">
        <nav class="app-navbar app-navbar-theme w-full p-0 fixed top-0 z-50 flex flex-row">
            <div class="app-brand-area flex items-center justify-start">
                <a class="app-brand app-brand-full flex items-center justify-start" href="<?= base_url() ?>" style="padding-left: 15px;">
                    <?php if (!empty($logo_sekolah)) : ?>
                        <img src="<?= base_url('assets/images/' . $logo_sekolah) ?>" alt="logo" style="width: 40px; height: 40px; margin: 0 10px 0 0 !important; filter: drop-shadow(0px 0px 5px white);" />
                    <?php endif; ?>
                    <h3 class="mb-0 font-bold text-white">SIBAYAR</h3>
                </a>
                <a class="app-brand app-brand-mini" href="<?= base_url() ?>">
                    <?php if (!empty($logo_sekolah)) : ?>
                        <img src="<?= base_url('assets/images/' . $logo_sekolah) ?>" alt="logo" style="width: 40px; height: 40px; margin: 0 !important; filter: drop-shadow(0px 0px 5px white);" />
                    <?php else: ?>
                        <img src="<?= base_url('assets/images/logo-mini.svg') ?>" alt="logo" />
                    <?php endif; ?>
                </a>
            </div>
            <div class="app-navbar-menu flex items-center">
                <div class="hidden lg:block mr-auto">
                    <span class="text-white font-bold" id="current-datetime" style="font-size: 0.9rem;"></span>
                </div>
                <ul class="app-nav-actions app-nav-actions-right">
                    <li class="app-nav-item app-dropdown hidden xl:inline-block" x-data="{ dropdownOpen: false }">
                        <a class="app-nav-link app-dropdown-toggle" id="UserDropdown" href="#" @click.prevent="dropdownOpen = !dropdownOpen">
                            <?php
                            // Ambil nama, role, foto terbaru dari DB agar sesuai setelah edit di Data Pengguna
                            $nav_nama = $_SESSION['nama_lengkap'] ?? '';
                            $nav_role = $_SESSION['role'] ?? '';
                            $nav_foto = $_SESSION['foto'] ?? '';
                            if (!empty($_SESSION['id_pengguna']) && !empty($koneksi)) {
                                $nav_id = (int)$_SESSION['id_pengguna'];
                                if ($nav_id > 0) {
                                    $nav_q = mysqli_query($koneksi, "SELECT nama_lengkap, role, foto FROM pengguna WHERE id_pengguna = " . $nav_id . " LIMIT 1");
                                    if ($nav_q && $nav_row = mysqli_fetch_assoc($nav_q)) {
                                        $nav_nama = (string)($nav_row['nama_lengkap'] ?? $nav_nama);
                                        $nav_role = (string)($nav_row['role'] ?? $nav_role);
                                        $nav_foto = (string)($nav_row['foto'] ?? '');
                                        $_SESSION['nama_lengkap'] = $nav_nama;
                                        $_SESSION['role'] = $nav_role;
                                        $_SESSION['foto'] = $nav_foto;
                                    }
                                }
                            }
                            $path_fisik = __DIR__ . '/../assets/images/faces/' . $nav_foto;
                            if ($nav_foto !== '' && is_file($path_fisik)) {
                                $foto_url = base_url('assets/images/faces/' . rawurlencode($nav_foto));
                            } else {
                                $foto_url = 'https://ui-avatars.com/api/?name=' . urlencode($nav_nama) . '&background=random&color=fff';
                            }
                            ?>
                            <div class="hidden md:block mr-3 text-right">
                                <p class="mb-0 font-bold text-white"><?= htmlspecialchars($nav_nama, ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="mb-0 text-white text-sm"><?= htmlspecialchars($nav_role, ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                            <img class="w-8 h-8 rounded-full app-profile-image" src="<?= htmlspecialchars($foto_url, ENT_QUOTES, 'UTF-8') ?>" alt="Profile image" style="object-fit: cover;">
                        </a>
                        <div x-show="dropdownOpen" @click.away="dropdownOpen = false" x-cloak class="app-dropdown-menu app-dropdown-menu-right app-user-menu absolute right-0 mt-2 bg-white rounded-lg shadow-lg border border-gray-200 py-2" style="min-width: 180px;">
                            <?php if (($nav_role ?? '') === 'admin') : ?>
                                <?php
                                if (!isset($_SESSION['update_token']) || !is_string($_SESSION['update_token']) || $_SESSION['update_token'] === '') {
                                    $_SESSION['update_token'] = bin2hex(random_bytes(16));
                                }
                                $update_token = $_SESSION['update_token'];
                                ?>
                                <a class="app-dropdown-item flex items-center gap-3 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100" href="#" data-update-system-trigger>
                                    <i class="mdi mdi-update text-lg text-emerald-600"></i>
                                    <span>Update Sistem</span>
                                </a>
                            <?php endif; ?>
                            <a class="app-dropdown-item mt-1 flex items-center gap-3 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100" href="<?= base_url('auth/logout.php') ?>" onclick="confirmLogout(event)">
                                <i class="mdi mdi-logout text-lg text-red-500"></i>
                                <span>Sign Out</span>
                            </a>
                        </div>
                    </li>
                </ul>
                <button class="app-nav-toggle app-nav-toggle-right lg:hidden self-center" type="button" data-sidebar-toggle>
                    <span class="mdi mdi-menu"></span>
                </button>
            </div>
        </nav>
        <div class="app-container app-body">
