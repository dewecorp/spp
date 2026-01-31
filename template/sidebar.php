            <nav class="sidebar sidebar-offcanvas" id="sidebar">
                <ul class="nav">
                    <li class="nav-item nav-profile">
                        <div class="nav-link">
                            <div class="user-wrapper">
                                <div class="profile-image">
                                    <img src="<?= base_url('assets/images/faces/face1.jpg') ?>" alt="profile image">
                                </div>
                                <div class="text-wrapper">
                                    <p class="profile-name"><?= $_SESSION['nama_lengkap'] ?></p>
                                    <div>
                                        <small class="designation text-muted"><?= ucfirst($_SESSION['role']) ?></small>
                                        <span class="status-indicator online"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= base_url('index.php') ?>">
                            <i class="menu-icon mdi mdi-television"></i>
                            <span class="menu-title">Dashboard</span>
                        </a>
                    </li>
                    <?php if ($_SESSION['role'] == 'admin') : ?>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="collapse" href="#ui-basic" data-bs-target="#ui-basic" aria-expanded="false" aria-controls="ui-basic">
                            <i class="menu-icon mdi mdi-content-copy"></i>
                            <span class="menu-title">Data Master</span>
                            <i class="menu-arrow"></i>
                        </a>
                        <div class="collapse" id="ui-basic">
                            <ul class="nav flex-column sub-menu">
                                <li class="nav-item">
                                    <a class="nav-link" href="<?= base_url('data/siswa.php') ?>">Data Siswa</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="<?= base_url('data/kelas.php') ?>">Data Kelas</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="<?= base_url('data/jenis_bayar.php') ?>">Jenis Bayar</a>
                                </li>
                            </ul>
                        </div>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= base_url('transaksi/transaksi.php') ?>">
                            <i class="menu-icon mdi mdi-backup-restore"></i>
                            <span class="menu-title">Transaksi Bayar</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= base_url('transaksi/riwayat.php') ?>">
                            <i class="menu-icon mdi mdi-chart-line"></i>
                            <span class="menu-title">Riwayat Bayar</span>
                        </a>
                    </li>
                    <?php if ($_SESSION['role'] == 'admin') : ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= base_url('pengaturan/pengguna.php') ?>">
                            <i class="menu-icon mdi mdi-account-multiple"></i>
                            <span class="menu-title">Pengguna</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= base_url('pengaturan/pengaturan.php') ?>">
                            <i class="menu-icon mdi mdi-cogs"></i>
                            <span class="menu-title">Pengaturan</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= base_url('auth/logout.php') ?>">
                            <i class="menu-icon mdi mdi-logout"></i>
                            <span class="menu-title">Logout</span>
                        </a>
                    </li>
                </ul>
            </nav>
            <div class="main-panel">
                <div class="content-wrapper">
