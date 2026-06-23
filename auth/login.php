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
    <link rel="shortcut icon" href="<?= base_url('assets/images/favicon_pembayaran.svg') ?>" type="image/svg+xml" />
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { DEFAULT: '#059669', dark: '#047857', light: '#10b981' },
                    },
                    fontFamily: { sans: ['Poppins', 'system-ui', 'sans-serif'] }
                }
            }
        }
    </script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
    <script>
        const OriginalSwal = Swal;
        const GlobalSwal = OriginalSwal.mixin({
            confirmButtonColor: '#059669',
            cancelButtonColor: '#d33',
            cancelButtonText: 'Batal',
            reverseButtons: true
        });
        window.Swal = {
            ...OriginalSwal,
            fire: function(...args) {
                if (args.length === 1 && typeof args[0] === 'object') {
                    const options = { cancelButtonText: 'Batal', ...args[0] };
                    return GlobalSwal.fire(options);
                }
                return GlobalSwal.fire(...args);
            }
        };
    </script>
</head>
<body class="font-sans bg-gray-100">
    <div class="min-h-screen flex items-center justify-center p-4" style="<?= !empty($bg_login) ? "background-image: url('" . base_url('assets/images/' . $bg_login) . "'); background-size: cover; background-position: center;" : "background-color: #f3f4f6;" ?>">
        <div class="w-full max-w-md">
            <div class="bg-white bg-opacity-90 p-8 rounded-2xl shadow-2xl">
                <div class="text-center mb-6">
                    <?php if(!empty($logo_sekolah)): ?>
                        <img src="<?= base_url('assets/images/'.$logo_sekolah) ?>" alt="logo" class="w-20 mx-auto mb-3">
                    <?php endif; ?>
                    <h4 class="font-bold text-black text-xl uppercase"><?= $nama_sekolah ?></h4>
                    <h6 class="font-medium text-gray-700 mt-1">Sistem Pembayaran Siswa</h6>
                </div>
                <form action="" method="post">
                    <div class="mb-4">
                        <label class="label block text-black font-semibold text-sm mb-2" for="login-username">Username</label>
                        <div class="relative">
                            <i class="mdi mdi-account-outline absolute left-4 top-1/2 -translate-y-1/2 text-xl text-primary pointer-events-none z-10" aria-hidden="true"></i>
                            <input id="login-username" type="text" name="username" class="w-full min-h-[3rem] py-3 pl-12 pr-4 border border-gray-200 rounded-xl bg-gray-50 hover:border-gray-300 focus:outline-none focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary focus:ring-opacity-20 transition text-gray-900 font-medium placeholder:text-black placeholder:font-normal" placeholder="Masukkan username" required autocomplete="username">
                        </div>
                    </div>
                    <div class="mb-6">
                        <label class="label block text-black font-semibold text-sm mb-2" for="login-password">Password</label>
                        <div class="relative">
                            <i class="mdi mdi-lock-outline absolute left-4 top-1/2 -translate-y-1/2 text-xl text-primary pointer-events-none z-10" aria-hidden="true"></i>
                            <input id="login-password" type="password" name="password" class="w-full min-h-[3rem] py-3 pl-12 pr-4 border border-gray-200 rounded-xl bg-gray-50 hover:border-gray-300 focus:outline-none focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary focus:ring-opacity-20 transition text-gray-900 font-medium placeholder:text-black placeholder:font-normal" placeholder="Masukkan password" required autocomplete="current-password">
                        </div>
                    </div>
                    <div class="text-center">
                        <button type="submit" name="login" class="px-8 py-3 bg-primary text-white font-semibold rounded-xl hover:bg-primary-dark transition shadow-sm tracking-wide">MASUK APLIKASI</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <?= $script ?>
</body>
</html>
