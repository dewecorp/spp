            <nav class="sidebar sidebar-offcanvas" id="sidebar">
                <ul class="nav">
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
                    <li class="nav-item">
                        <a class="nav-link" href="<?= base_url('pengaturan/backup_restore.php') ?>">
                            <i class="menu-icon mdi mdi-database"></i>
                            <span class="menu-title">Backup Restore</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= base_url('auth/logout.php') ?>" onclick="confirmLogout(event)">
                            <i class="menu-icon mdi mdi-logout"></i>
                            <span class="menu-title">Logout</span>
                        </a>
                    </li>
                </ul>
            </nav>
            <div class="main-panel">
                <div class="content-wrapper">
