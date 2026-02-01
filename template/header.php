<?php
session_start();
include_once __DIR__ . '/../config/config.php';

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
    <title>Aplikasi SPP - MI Sultan Fattah Sukosono</title>
    <link rel="stylesheet" href="<?= base_url('assets/vendors/mdi/css/materialdesignicons.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/vendors/css/vendor.bundle.base.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
    <link rel="shortcut icon" href="<?= base_url('assets/images/favicon.png') ?>" />
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap4.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
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
        }
        
        /* Force Left Alignment */
        .sidebar .nav .nav-item .nav-link {
            display: flex !important;
            align-items: center !important;
            justify-content: flex-start !important;
            text-align: left !important;
            width: 100% !important;
            padding-left: 20px !important;
            padding-right: 20px !important;
        }
        
        .sidebar .nav .nav-item .nav-link .menu-title {
            margin-left: 10px !important;
            margin-right: auto !important;
            display: inline-block !important;
            vertical-align: middle !important;
        }

        .sidebar .nav .nav-item .nav-link i.menu-icon {
            font-size: 1.2rem;
            line-height: 1;
            margin-right: 0 !important;
            margin-left: 0 !important;
            width: 30px; /* Fixed width for icon alignment */
            text-align: center;
            color: #a7a7a7;
        }
        /* Fix dropdown arrow position */
        .sidebar .nav .nav-item .nav-link .menu-arrow {
            margin-left: auto !important;
            margin-right: 0 !important;
        }

        /* Custom Scrollbar for Sidebar */
        .sidebar::-webkit-scrollbar {
            width: 5px;
        }
        .sidebar::-webkit-scrollbar-track {
            background: #f1f1f1; 
        }
        .sidebar::-webkit-scrollbar-thumb {
            background: #888; 
            border-radius: 5px;
        }
        .sidebar::-webkit-scrollbar-thumb:hover {
            background: #555; 
        }

        /* Purple Gradient Navbar */
        .navbar.default-layout {
            background: linear-gradient(120deg, #da8cff, #9a55ff) !important;
        }
        .navbar.default-layout .navbar-brand-wrapper {
            background: transparent !important;
        }
        .navbar.default-layout .navbar-menu-wrapper {
            background: transparent !important;
        }
        .navbar.default-layout .navbar-brand-wrapper h3 {
            color: #ffffff !important;
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
    </style>
</head>
<body>
    <div class="container-scroller">
        <nav class="navbar default-layout col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
            <div class="text-center navbar-brand-wrapper d-flex align-items-top justify-content-center">
                <a class="navbar-brand brand-logo" href="<?= base_url() ?>">
                    <h3 class="mt-2">APP SPP</h3>
                </a>
                <a class="navbar-brand brand-logo-mini" href="<?= base_url() ?>">
                    <img src="<?= base_url('assets/images/logo-mini.svg') ?>" alt="logo" />
                </a>
            </div>
            <div class="navbar-menu-wrapper d-flex align-items-center">
                <div class="d-none d-lg-block me-auto">
                    <span class="text-white fw-bold" id="current-datetime" style="font-size: 0.9rem;"></span>
                </div>
                <ul class="navbar-nav navbar-nav-right">
                    <li class="nav-item dropdown d-none d-xl-inline-block">
                        <a class="nav-link dropdown-toggle" id="UserDropdown" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="d-none d-md-block me-3 text-end">
                                <p class="mb-0 font-weight-bold text-dark"><?= $_SESSION['nama_lengkap'] ?></p>
                                <p class="mb-0 text-muted small"><?= $_SESSION['role'] ?></p>
                            </div>
                            <?php
                            $foto = $_SESSION['foto'] ?? '';
                            // Cek fisik file (karena include, __DIR__ adalah folder template)
                            $path_fisik = __DIR__ . '/../assets/images/faces/' . $foto;
                            
                            if (!empty($foto) && file_exists($path_fisik)) {
                                $foto_url = base_url('assets/images/faces/' . $foto);
                            } else {
                                $foto_url = "https://ui-avatars.com/api/?name=" . urlencode($_SESSION['nama_lengkap']) . "&background=random&color=fff";
                            }
                            ?>
                            <img class="img-xs rounded-circle" src="<?= $foto_url ?>" alt="Profile image" style="object-fit: cover;">
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
