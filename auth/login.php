<?php
include '../config/config.php';

if (isset($_SESSION['login'])) {
    header("Location: " . base_url('index.php'));
    exit;
}

$script = "";

// Ambil pengaturan
$q_setting = mysqli_query($koneksi, "SELECT * FROM pengaturan WHERE id_pengaturan = 1");
$d_setting = mysqli_fetch_assoc($q_setting);
$bg_login = $d_setting['bg_login'] ?? '';
$nama_sekolah = $d_setting['nama_sekolah'] ?? 'Sekolah';
$logo_sekolah = $d_setting['logo'] ?? '';

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $query = mysqli_query($koneksi, "SELECT * FROM pengguna WHERE username='$username'");
    
    if (mysqli_num_rows($query) > 0) {
        $data = mysqli_fetch_assoc($query);
        if (password_verify($password, $data['password'])) {
            $_SESSION['login'] = true;
            $_SESSION['id_pengguna'] = $data['id_pengguna'];
            $_SESSION['username'] = $username;
            $_SESSION['nama_lengkap'] = $data['nama_lengkap'];
            $_SESSION['role'] = $data['role'];
            $_SESSION['foto'] = $data['foto'];

            // Log Aktivitas
            logActivity($koneksi, 'Login', 'Login berhasil');

            $script = "<script>
                Swal.fire({
                    title: 'Login Berhasil!',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    window.location='" . base_url('index.php?v=' . time()) . "';
                });
            </script>";
        } else {
            $script = "<script>Swal.fire('Gagal', 'Password Salah!', 'error');</script>";
        }
    } else {
        $script = "<script>Swal.fire('Gagal', 'Username tidak ditemukan!', 'error');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Login SPP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/vendors/mdi/css/materialdesignicons.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/vendors/css/vendor.bundle.base.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
    <link rel="shortcut icon" href="<?= base_url('assets/images/favicon_pembayaran.svg') ?>" type="image/svg+xml" />
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
    <style>
        /* Tipografi login: Poppins + hitam tegas (halaman ini tidak memakai template/header.php) */
        body.auth-login-page {
            font-family: "Poppins", system-ui, sans-serif !important;
        }
        .auth-bg-1 .auto-form-wrapper h4,
        .auth-bg-1 .auto-form-wrapper h6,
        .auth-bg-1 .auto-form-wrapper label,
        .auth-bg-1 .auto-form-wrapper .form-control,
        .auth-bg-1 .auto-form-wrapper .submit-btn {
            font-family: "Poppins", system-ui, sans-serif !important;
        }
        /* Ikon MDI tidak memakai Poppins */
        .auth-bg-1 .mdi[class*="mdi-"] {
            font-family: "Material Design Icons" !important;
        }
        .auth-bg-1 .auto-form-wrapper h4.font-weight-bold.text-dark {
            color: #000000 !important;
            font-weight: 700 !important;
            letter-spacing: 0.02em;
        }
        .auth-bg-1 .auto-form-wrapper h6.font-weight-light.text-dark {
            color: #1a1a1a !important;
            font-weight: 500 !important;
        }
        .auth-bg-1 .auto-form-wrapper label.label.text-dark {
            color: #000000 !important;
            font-weight: 600 !important;
        }
        .auth-bg-1 .auto-form-wrapper .form-control {
            color: #111827 !important;
            font-weight: 500 !important;
        }
        .auth-bg-1 .auto-form-wrapper .form-control::placeholder {
            color: #000000 !important;
            font-weight: 400 !important;
        }
        .auth-bg-1 .auto-form-wrapper .btn-primary.submit-btn {
            font-weight: 600 !important;
            letter-spacing: 0.04em;
            border-radius: 12px !important;
        }

        /* Field login: satu blok rapi, ikon di dalam input (tanpa kotak tambahan) */
        .auth-bg-1 .login-field {
            position: relative;
            margin-bottom: 0;
        }
        .auth-bg-1 .login-field .login-field-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.15rem;
            color: #006b3f;
            pointer-events: none;
            z-index: 2;
            line-height: 1;
        }
        .auth-bg-1 .login-field .login-field-input {
            width: 100%;
            min-height: 3rem;
            padding: 0.65rem 1rem 0.65rem 2.85rem !important;
            border: 1px solid #e5e7eb !important;
            border-radius: 12px !important;
            background-color: #f9fafb !important;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.04);
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
        }
        .auth-bg-1 .login-field .login-field-input:hover {
            border-color: #d1d5db !important;
        }
        .auth-bg-1 .login-field .login-field-input:focus {
            outline: none !important;
            background-color: #ffffff !important;
            border-color: #006b3f !important;
            box-shadow: 0 0 0 3px rgba(0, 107, 63, 0.12) !important;
        }
        .auth-bg-1 .auto-form-wrapper .form-group label.label {
            display: block;
            margin-bottom: 0.4rem;
            font-size: 0.875rem;
        }
    </style>
</head>
<body class="auth-login-page">
    <div class="container-scroller">
        <div class="container-fluid page-body-wrapper full-page-wrapper">
            <div class="content-wrapper d-flex align-items-center auth auth-bg-1 theme-one" style="<?= !empty($bg_login) ? "background-image: url('" . base_url('assets/images/' . $bg_login) . "'); background-size: cover; background-position: center;" : "" ?>">
                <div class="row w-100">
                    <div class="col-lg-4 mx-auto">
                        <div class="auto-form-wrapper" style="background-color: rgba(255, 255, 255, 0.9); padding: 30px; border-radius: 10px; box-shadow: 0px 0px 20px rgba(0,0,0,0.3);">
                            <div class="text-center mb-4">
                                <?php if(!empty($logo_sekolah)): ?>
                                    <img src="<?= base_url('assets/images/'.$logo_sekolah) ?>" alt="logo" style="width: 80px; margin-bottom: 10px;">
                                <?php endif; ?>
                                <h4 class="font-weight-bold text-dark text-uppercase"><?= $nama_sekolah ?></h4>
                                <h6 class="font-weight-light text-dark">Sistem Pembayaran Siswa</h6>
                            </div>
                            <form action="" method="post">
                                <div class="form-group">
                                    <label class="label text-dark font-weight-bold" for="login-username">Username</label>
                                    <div class="login-field">
                                        <i class="mdi mdi-account-outline login-field-icon" aria-hidden="true"></i>
                                        <input id="login-username" type="text" name="username" class="form-control login-field-input" placeholder="Masukkan username" required autocomplete="username">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="label text-dark font-weight-bold" for="login-password">Password</label>
                                    <div class="login-field">
                                        <i class="mdi mdi-lock-outline login-field-icon" aria-hidden="true"></i>
                                        <input id="login-password" type="password" name="password" class="form-control login-field-input" placeholder="Masukkan password" required autocomplete="current-password">
                                    </div>
                                </div>
                                <div class="form-group text-center">
                                    <button type="submit" name="login" class="btn btn-primary submit-btn shadow-sm px-5">MASUK APLIKASI</button>
                                </div>
                            </form>
                        </div>
                        <!-- Info default user removed -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="<?= base_url('assets/vendors/js/vendor.bundle.base.js') ?>"></script>
    <script src="<?= base_url('assets/js/misc.js') ?>"></script>
    <?= $script ?>
</body>
</html>
