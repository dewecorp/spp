<?php
session_start();
include '../config/config.php';

if (isset($_SESSION['login'])) {
    header("Location: ../index.php");
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
    <link rel="stylesheet" href="<?= base_url('assets/vendors/mdi/css/materialdesignicons.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/vendors/css/vendor.bundle.base.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
    <link rel="shortcut icon" href="<?= base_url('assets/images/favicon.png') ?>" />
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
</head>
<body>
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
                                <h6 class="font-weight-light text-dark">Sistem Informasi Pembayaran</h6>
                            </div>
                            <form action="" method="post">
                                <div class="form-group">
                                    <label class="label text-dark font-weight-bold">Username</label>
                                    <div class="input-group">
                                        <input type="text" name="username" class="form-control" placeholder="Username" required style="border-color: #ccc;">
                                        <div class="input-group-append">
                                            <span class="input-group-text" style="border-color: #ccc;">
                                                <i class="mdi mdi-check-circle-outline text-primary"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="label text-dark font-weight-bold">Password</label>
                                    <div class="input-group">
                                        <input type="password" name="password" class="form-control" placeholder="********" required style="border-color: #ccc;">
                                        <div class="input-group-append">
                                            <span class="input-group-text" style="border-color: #ccc;">
                                                <i class="mdi mdi-check-circle-outline text-primary"></i>
                                            </span>
                                        </div>
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
