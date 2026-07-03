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
$bg_login_url = !empty($bg_login) ? base_url('assets/images/' . rawurlencode($bg_login)) : '';
$logo_sekolah_url = !empty($logo_sekolah) ? base_url('assets/images/' . rawurlencode($logo_sekolah)) : '';

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $query = mysqli_query($koneksi, "SELECT * FROM pengguna WHERE username='$username'");
    
    if (mysqli_num_rows($query) > 0) {
        $data = mysqli_fetch_assoc($query);
        if (password_verify($password, $data['password'])) {
            session_regenerate_id(true);
            $_SESSION['login'] = true;
            $_SESSION['id_pengguna'] = $data['id_pengguna'];
            $_SESSION['username'] = $username;
            $_SESSION['nama_lengkap'] = $data['nama_lengkap'];
            $_SESSION['role'] = $data['role'];
            $_SESSION['foto'] = $data['foto'];
            $_SESSION['last_activity'] = time();

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
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Login - SiBayar</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:opsz,wght@6..12,400;6..12,500;6..12,600;6..12,700;6..12,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/vendors/mdi/css/materialdesignicons.min.css') ?>">
    <link rel="shortcut icon" href="<?= base_url('assets/images/favicon_pembayaran.svg') ?>" type="image/svg+xml" />
    <script>
        window.tailwind = window.tailwind || {};
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { DEFAULT: '#10b981', dark: '#059669', light: '#34d399', soft: '#d1fae5' },
                    },
                    fontFamily: { sans: ['Nunito Sans', 'ui-sans-serif', 'system-ui', 'sans-serif'] },
                    boxShadow: {
                        soft: '0 24px 80px rgba(15, 23, 42, 0.22)'
                    }
                }
            }
        }
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
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
    <style>
        body,
        .swal2-popup {
            font-family: 'Nunito Sans', ui-sans-serif, system-ui, sans-serif !important;
        }
        input:-webkit-autofill {
            -webkit-box-shadow: 0 0 0 1000px #ffffff inset !important;
            -webkit-text-fill-color: #0f172a !important;
        }
    </style>
</head>
<body class="min-h-screen bg-slate-950 font-sans text-slate-950 antialiased">
    <main class="relative min-h-screen overflow-hidden">
        <?php if (!empty($bg_login_url)) : ?>
            <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('<?= htmlspecialchars($bg_login_url, ENT_QUOTES, 'UTF-8') ?>');"></div>
            <div class="absolute inset-0 bg-slate-950/55"></div>
            <div class="absolute inset-0 bg-gradient-to-br from-emerald-950/65 via-slate-950/35 to-emerald-700/20"></div>
        <?php else : ?>
            <div class="absolute inset-0 bg-gradient-to-br from-emerald-950 via-slate-950 to-emerald-700"></div>
        <?php endif; ?>

        <div class="relative z-10 flex min-h-screen items-center justify-center px-4 py-8 sm:px-6">
            <div class="grid w-full max-w-5xl overflow-hidden rounded-lg border border-white/20 bg-white shadow-soft lg:grid-cols-[0.9fr_1.1fr]">
                <section class="hidden bg-gradient-to-br from-emerald-600 via-emerald-500 to-emerald-700 p-10 text-white lg:flex lg:flex-col lg:justify-between">
                    <div>
                        <div class="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1.5 text-sm font-bold ring-1 ring-white/20">
                            <i class="mdi mdi-shield-check-outline text-lg"></i>
                            SiBayar
                        </div>
                        <h1 class="mt-8 text-4xl font-extrabold leading-tight tracking-normal">
                            Sistem Pembayaran Siswa
                        </h1>
                        <p class="mt-4 max-w-sm text-base font-semibold leading-7 text-emerald-50">
                            Tertib, Transparan, dan Terpercaya
                        </p>
                    </div>
                    <div class="rounded-lg bg-white/12 p-4 ring-1 ring-white/20">
                        <p class="text-sm font-semibold text-emerald-50"><?= htmlspecialchars($nama_sekolah, ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="mt-1 text-xs font-medium text-emerald-100">Aplikasi Pengelolaan Pembayaran Siswa</p>
                    </div>
                </section>

                <section class="bg-white/95 px-6 py-8 backdrop-blur sm:px-10 sm:py-10">
                    <div class="mx-auto w-full max-w-md">
                        <div class="mb-8 text-center">
                            <?php if (!empty($logo_sekolah_url)): ?>
                                <div class="mx-auto mb-4 grid h-20 w-20 place-items-center rounded-lg bg-emerald-50 ring-1 ring-emerald-100">
                                    <img src="<?= htmlspecialchars($logo_sekolah_url, ENT_QUOTES, 'UTF-8') ?>" alt="Logo sekolah" class="h-16 w-16 object-contain">
                                </div>
                            <?php endif; ?>
                            <p class="text-sm font-extrabold uppercase tracking-normal text-emerald-700">SIBAYAR</p>
                            <h2 class="mt-2 text-2xl font-extrabold tracking-normal text-slate-950"><?= htmlspecialchars($nama_sekolah, ENT_QUOTES, 'UTF-8') ?></h2>
                            
                        </div>

                        <form action="" method="post" class="space-y-5">
                            <div>
                                <label class="mb-2 block text-sm font-extrabold text-slate-700" for="login-username">Username</label>
                                <div class="relative">
                                    <i class="mdi mdi-account-outline pointer-events-none absolute left-4 top-1/2 z-10 -translate-y-1/2 text-xl text-emerald-600" aria-hidden="true"></i>
                                    <input id="login-username" type="text" name="username" class="h-12 w-full rounded-lg border border-slate-200 bg-white py-3 pl-12 pr-4 text-base font-bold text-slate-950 outline-none transition placeholder:font-semibold placeholder:text-slate-400 hover:border-slate-300 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/15" placeholder="Masukkan username" required autocomplete="username">
                                </div>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-extrabold text-slate-700" for="login-password">Password</label>
                                <div class="relative">
                                    <i class="mdi mdi-lock-outline pointer-events-none absolute left-4 top-1/2 z-10 -translate-y-1/2 text-xl text-emerald-600" aria-hidden="true"></i>
                                    <input id="login-password" type="password" name="password" class="h-12 w-full rounded-lg border border-slate-200 bg-white py-3 pl-12 pr-12 text-base font-bold text-slate-950 outline-none transition placeholder:font-semibold placeholder:text-slate-400 hover:border-slate-300 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/15" placeholder="Masukkan password" required autocomplete="current-password">
                                    <button type="button" id="togglePassword" class="absolute right-2 top-1/2 grid h-9 w-9 -translate-y-1/2 place-items-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Tampilkan password">
                                        <i class="mdi mdi-eye-outline text-xl"></i>
                                    </button>
                                </div>
                            </div>

                            <button type="submit" name="login" class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-lg bg-emerald-500 px-5 text-sm font-extrabold uppercase tracking-normal text-white shadow-lg shadow-emerald-500/25 transition hover:bg-emerald-600 focus:outline-none focus:ring-4 focus:ring-emerald-500/25">
                                <i class="mdi mdi-login text-lg"></i>
                                Masuk Aplikasi
                            </button>
                        </form>
                    </div>
                </section>
            </div>
        </div>
    </main>
    <script>
        document.getElementById('togglePassword')?.addEventListener('click', function () {
            const input = document.getElementById('login-password');
            const icon = this.querySelector('.mdi');
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            icon.classList.toggle('mdi-eye-outline', !isHidden);
            icon.classList.toggle('mdi-eye-off-outline', isHidden);
            this.setAttribute('aria-label', isHidden ? 'Sembunyikan password' : 'Tampilkan password');
        });
    </script>
    <?= $script ?>
</body>
</html>
