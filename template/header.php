<?php
include_once __DIR__ . '/../config/config.php';

// Ambil data pengaturan untuk logo
$q_setting = mysqli_query($koneksi, "SELECT logo FROM pengaturan WHERE id_pengaturan = 1");
$d_setting = mysqli_fetch_assoc($q_setting);
$logo_sekolah = $d_setting['logo'] ?? '';

if (!isset($_SESSION['login']) || !isset($_SESSION['nama_lengkap']) || !isset($_SESSION['role'])) {
    session_destroy();
    header("Location: " . base_url('auth/login.php'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?= isset($title) ? $title . ' - ' : '' ?>SiBayar</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/vendors/mdi/css/materialdesignicons.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/vendors/css/vendor.bundle.base.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>?v=<?= time() ?>">
    <link rel="shortcut icon" href="<?= base_url('assets/images/favicon_pembayaran.svg') ?>" type="image/svg+xml" />
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap4.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
    <script>
        // Konfigurasi Terpusat SweetAlert2
        (function() {
            const OriginalSwal = Swal;
            const GlobalSwal = OriginalSwal.mixin({
                confirmButtonColor: '#006b3f',
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
    <style>
        /* Fix Sticky Sidebar Context */
        .container-scroller {
            overflow: visible !important;
        }

        /* Fixed Sidebar for Desktop */
        @media (min-width: 992px) {
            .sidebar {
                position: fixed !important;
                top: 70px !important;
                bottom: 0 !important;
                left: 0 !important;
                height: calc(100vh - 70px) !important;
                overflow-y: auto !important;
                z-index: 999;
                width: 260px !important;
            }
            
            .main-panel {
                margin-left: 260px !important;
                width: calc(100% - 260px) !important;
            }
        }
        
        /* Mobile Sidebar adjustments */
        @media (max-width: 991px) {
            .sidebar {
                top: 70px !important;
                height: calc(100vh - 70px) !important;
            }
            
            /* Show App Name on Mobile */
            .navbar .navbar-brand-wrapper {
                width: auto !important;
                padding-left: 15px;
            }
            .navbar .navbar-brand-wrapper .navbar-brand.brand-logo {
                display: flex !important;
                align-items: center !important;
            }
            .navbar .navbar-brand-wrapper .navbar-brand.brand-logo-mini {
                display: none !important;
            }
            .navbar .navbar-brand-wrapper .navbar-brand.brand-logo img {
                width: 36px !important;
                height: 36px !important;
                margin-right: 8px !important;
            }
            .navbar .navbar-brand-wrapper h3 {
                font-size: 1.25rem !important;
                line-height: 1.2 !important;
                letter-spacing: .5px !important;
                margin: 0 !important;
                white-space: nowrap !important;
            }
            .navbar .navbar-menu-wrapper {
                padding-right: 10px !important;
            }
            .navbar .navbar-menu-wrapper .navbar-toggler {
                margin-left: auto !important;
            }
        }
        
        /*
         Rapatkan sidebar tanpa narik teks saja:
         ruang besar berasal dari `.nav-item { padding: 0 2.25rem }` di tema.
         Ikon tema pakai `margin-left: auto` — harus dibatalkan supaya blok [ikon + judul]
         bergeser serempak (glyph MDI butuh ruang lebar, jangan dibatasi 14px atau tabrakan).
        */
        .sidebar .nav {
            padding-left: 0 !important;
            margin-left: 0 !important;
        }
        .sidebar .nav .nav-item {
            margin-left: 0 !important;
            padding-left: 0.9rem !important;
            padding-right: 1rem !important;
        }
        .sidebar .nav .nav-item .nav-link {
            display: flex !important;
            align-items: center !important;
            justify-content: flex-start !important;
            text-align: left !important;
            width: 100% !important;
            gap: 0 !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
            color: #ffffff !important;
        }

        .sidebar .nav .nav-item .nav-link i.menu-icon {
            font-size: 1.2rem !important;
            line-height: 1 !important;
            flex-shrink: 0 !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
            width: auto !important;
            min-width: 1.5rem !important;
            text-align: center;
            color: #ffffff !important;
        }

        .sidebar .nav .nav-item .nav-link .menu-title {
            margin-left: 0.5rem !important;
            margin-right: auto !important;
            flex: 1 1 auto !important;
            min-width: 0 !important;
            display: inline-block !important;
            vertical-align: middle !important;
            color: #ffffff !important;
            font-weight: 500 !important;
        }

        .sidebar .nav .nav-item .nav-link .menu-arrow {
            margin-left: auto !important;
            margin-right: 0 !important;
            flex-shrink: 0 !important;
            color: rgba(255, 255, 255, 0.85) !important;
        }

        /* Sub-menu: satu blok, tanpa margin negatif */
        .sidebar .nav.sub-menu {
            padding-left: 0.5rem !important;
            margin-left: 0 !important;
        }
        .sidebar .nav.sub-menu .nav-item {
            margin-left: 0 !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
        }
        .sidebar .nav.sub-menu .nav-item .nav-link {
            padding-left: 1.1rem !important;
            padding-right: 0.5rem !important;
            color: rgba(255, 255, 255, 0.95) !important;
            font-weight: 400 !important;
        }
        .sidebar .nav.sub-menu .nav-item .nav-link:before {
            left: 0.25rem !important;
        }

        /* Custom Scrollbar for Sidebar */
        .sidebar::-webkit-scrollbar {
            width: 5px;
        }
        .sidebar::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.15);
        }
        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.35);
            border-radius: 5px;
        }
        .sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }

        /* Kemenag Green Gradient Navbar */
        .navbar.default-layout {
            background: linear-gradient(120deg, #006b3f, #1b8f5a) !important;
        }
        .navbar.default-layout .navbar-brand-wrapper {
            background: transparent !important;
        }
        .navbar.default-layout .navbar-menu-wrapper {
            background: transparent !important;
        }
        .navbar.default-layout .navbar-brand-wrapper h3,
        .navbar.default-layout .navbar-brand.brand-logo h3 {
            color: #ffffff !important;
            font-family: "Poppins", system-ui, -apple-system, "Segoe UI", sans-serif !important;
            font-weight: 700 !important;
            letter-spacing: 0.02em;
        }
        .navbar.default-layout .navbar-menu-wrapper .nav-link {
            color: #ffffff !important;
        }
        .navbar.default-layout .navbar-menu-wrapper .nav-link .profile-text {
            color: #ffffff !important;
        }
        .navbar.default-layout .navbar-menu-wrapper .mdi {
            color: #ffffff !important;
        }
        #UserDropdown.dropdown-toggle::after {
            display: none !important;
        }

        /* Halo putih di foto userinfo (navbar) */
        .navbar.default-layout .navbar-profile-img {
            box-shadow:
                0 0 0 2px rgba(255, 255, 255, 0.95),
                0 0 14px 2px rgba(255, 255, 255, 0.55),
                0 2px 8px rgba(0, 0, 0, 0.12);
        }

        /* Responsive toolbars and buttons on mobile */
        @media (max-width: 768px) {
            .sidebar .nav .nav-item {
                padding-left: 0.75rem !important;
                padding-right: 0.75rem !important;
            }
            .sidebar .nav .nav-item .nav-link .menu-title {
                margin-left: 0.45rem !important;
            }
            .sidebar .nav.sub-menu {
                padding-left: 0.25rem !important;
            }
            .sidebar .nav.sub-menu .nav-item .nav-link {
                padding-left: 1rem !important;
            }
            .sidebar .nav.sub-menu .nav-item .nav-link:before {
                left: 0.15rem !important;
            }
            .toolbar,
            .toolbar-secondary {
                flex-direction: column !important;
                align-items: stretch !important;
                gap: .5rem !important;
            }
            .toolbar .btn,
            .toolbar-secondary .btn {
                width: 100% !important;
            }
            .toolbar .input-group {
                width: 100% !important;
            }
            .toolbar select {
                width: 100% !important;
            }
        }
    </style>
</head>
<body>
    <div class="container-scroller">
        <nav class="navbar default-layout col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
            <div class="navbar-brand-wrapper d-flex align-items-center justify-content-start">
                <a class="navbar-brand brand-logo d-flex align-items-center justify-content-start" href="<?= base_url() ?>" style="padding-left: 15px;">
                    <?php if (!empty($logo_sekolah)) : ?>
                        <img src="<?= base_url('assets/images/' . $logo_sekolah) ?>" alt="logo" style="width: 40px; height: 40px; margin: 0 10px 0 0 !important; filter: drop-shadow(0px 0px 5px white);" />
                    <?php endif; ?>
                    <h3 class="mb-0 font-weight-bold text-white">SIBAYAR</h3>
                </a>
                <a class="navbar-brand brand-logo-mini" href="<?= base_url() ?>">
                    <?php if (!empty($logo_sekolah)) : ?>
                        <img src="<?= base_url('assets/images/' . $logo_sekolah) ?>" alt="logo" style="width: 40px; height: 40px; margin: 0 !important; filter: drop-shadow(0px 0px 5px white);" />
                    <?php else: ?>
                        <img src="<?= base_url('assets/images/logo-mini.svg') ?>" alt="logo" />
                    <?php endif; ?>
                </a>
            </div>
            <div class="navbar-menu-wrapper d-flex align-items-center">
                <div class="d-none d-lg-block me-auto">
                    <span class="text-white fw-bold" id="current-datetime" style="font-size: 0.9rem;"></span>
                </div>
                <ul class="navbar-nav navbar-nav-right">
                    <li class="nav-item dropdown d-none d-xl-inline-block">
                        <a class="nav-link dropdown-toggle" id="UserDropdown" href="#" data-bs-toggle="dropdown" aria-expanded="false">
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
                            <div class="d-none d-md-block me-3 text-end">
                                <p class="mb-0 font-weight-bold text-white"><?= htmlspecialchars($nav_nama, ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="mb-0 text-white small"><?= htmlspecialchars($nav_role, ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                            <img class="img-xs rounded-circle navbar-profile-img" src="<?= htmlspecialchars($foto_url, ENT_QUOTES, 'UTF-8') ?>" alt="Profile image" style="object-fit: cover;">
                        </a>
                        <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="UserDropdown">
                            <a class="dropdown-item mt-2" href="<?= base_url('auth/logout.php') ?>" onclick="confirmLogout(event)">
                                Sign Out
                            </a>
                        </div>
                    </li>
                </ul>
                <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-toggle="offcanvas">
                    <span class="mdi mdi-menu"></span>
                </button>
            </div>
        </nav>
        <div class="container-fluid page-body-wrapper">
