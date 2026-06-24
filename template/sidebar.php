            <?php
            $current_script = '/' . ltrim(str_replace('\\', '/', strtolower($_SERVER['SCRIPT_NAME'] ?? '')), '/');

            function sidebar_active($patterns)
            {
                global $current_script;
                foreach ((array) $patterns as $pattern) {
                    if (strpos($current_script, strtolower($pattern)) !== false) {
                        return true;
                    }
                }
                return false;
            }

            function sidebar_link_class($is_active)
            {
                return 'app-nav-link' . ($is_active ? ' active' : '');
            }

            function sidebar_current_attr($is_active)
            {
                return $is_active ? ' aria-current="page"' : '';
            }

            $is_dashboard = preg_match('#/index\.php$#', $current_script) === 1;
            $is_data_master = sidebar_active(['/data/siswa.php', '/data/kelas.php', '/data/jenis_bayar.php']);
            $is_transaksi = sidebar_active('/transaksi/transaksi.php');
            $is_tagihan = sidebar_active('/tagihan/');
            $is_laporan = sidebar_active('/laporan/');
            $is_riwayat = sidebar_active('/transaksi/riwayat.php');
            $is_pengguna = sidebar_active('/pengaturan/pengguna.php');
            $is_pengaturan = sidebar_active('/pengaturan/pengaturan.php');
            $is_backup = sidebar_active('/pengaturan/backup_restore.php');
            ?>
            <nav class="app-sidebar app-sidebar-drawer" id="sidebar" x-data="{ openMenu: <?= $is_data_master ? 'true' : 'false' ?> }">
                <ul class="app-nav">
                    <li class="app-nav-item">
                        <a class="<?= sidebar_link_class($is_dashboard) ?>" href="<?= base_url('index.php') ?>"<?= sidebar_current_attr($is_dashboard) ?>>
                            <i class="app-menu-icon mdi mdi-television"></i>
                            <span class="app-menu-title">Dashboard</span>
                        </a>
                    </li>
                    <?php if ($_SESSION['role'] == 'admin') : ?>
                    <li class="app-nav-item">
                        <a href="#" class="<?= sidebar_link_class($is_data_master) ?>" @click.prevent="openMenu = !openMenu" :class="{ 'active': openMenu }" :aria-expanded="openMenu">
                            <i class="app-menu-icon mdi mdi-content-copy"></i>
                            <span class="app-menu-title">Data Master</span>
                            <i class="app-menu-arrow mdi mdi-chevron-right" :class="{ 'rotated': openMenu }" aria-hidden="true"></i>
                        </a>
                        <div x-show="openMenu" x-collapse x-cloak>
                            <ul class="app-nav flex-column app-submenu">
                                <li class="app-nav-item">
                                    <a class="<?= sidebar_link_class(sidebar_active('/data/siswa.php')) ?>" href="<?= base_url('data/siswa.php?v=1') ?>"<?= sidebar_current_attr(sidebar_active('/data/siswa.php')) ?>>Data Siswa</a>
                                </li>
                                <li class="app-nav-item">
                                    <a class="<?= sidebar_link_class(sidebar_active('/data/kelas.php')) ?>" href="<?= base_url('data/kelas.php?v=1') ?>"<?= sidebar_current_attr(sidebar_active('/data/kelas.php')) ?>>Data Kelas</a>
                                </li>
                                <li class="app-nav-item">
                                    <a class="<?= sidebar_link_class(sidebar_active('/data/jenis_bayar.php')) ?>" href="<?= base_url('data/jenis_bayar.php?v=1') ?>"<?= sidebar_current_attr(sidebar_active('/data/jenis_bayar.php')) ?>>Jenis Bayar</a>
                                </li>
                            </ul>
                        </div>
                    </li>
                    <?php endif; ?>
                    <?php if ($_SESSION['role'] == 'petugas') : ?>
                    <li class="app-nav-item">
                        <a class="<?= sidebar_link_class(sidebar_active('/data/jenis_bayar.php')) ?>" href="<?= base_url('data/jenis_bayar.php?v=1') ?>"<?= sidebar_current_attr(sidebar_active('/data/jenis_bayar.php')) ?>>
                            <i class="app-menu-icon mdi mdi-receipt"></i>
                            <span class="app-menu-title">Jenis Bayar</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="app-nav-item">
                        <a class="<?= sidebar_link_class($is_transaksi) ?>" href="<?= base_url('transaksi/transaksi.php?v=1') ?>"<?= sidebar_current_attr($is_transaksi) ?>>
                            <i class="app-menu-icon mdi mdi-backup-restore"></i>
                            <span class="app-menu-title">Transaksi Bayar</span>
                        </a>
                    </li>
                    <li class="app-nav-item">
                        <a class="<?= sidebar_link_class($is_tagihan) ?>" href="<?= base_url('tagihan/tagihan.php?v=1') ?>"<?= sidebar_current_attr($is_tagihan) ?>>
                            <i class="app-menu-icon mdi mdi-file-document"></i>
                            <span class="app-menu-title">Tagihan Siswa</span>
                        </a>
                    </li>
                    <li class="app-nav-item">
                        <a class="<?= sidebar_link_class($is_laporan) ?>" href="<?= base_url('laporan/laporan.php?v=1') ?>"<?= sidebar_current_attr($is_laporan) ?>>
                            <i class="app-menu-icon mdi mdi-printer"></i>
                            <span class="app-menu-title">Laporan</span>
                        </a>
                    </li>
                    <li class="app-nav-item">
                        <a class="<?= sidebar_link_class($is_riwayat) ?>" href="<?= base_url('transaksi/riwayat.php?v=1') ?>"<?= sidebar_current_attr($is_riwayat) ?>>
                            <i class="app-menu-icon mdi mdi-chart-line"></i>
                            <span class="app-menu-title">Riwayat Bayar</span>
                        </a>
                    </li>
                    <?php if ($_SESSION['role'] == 'admin') : ?>
                    <li class="app-nav-item">
                        <a class="<?= sidebar_link_class($is_pengguna) ?>" href="<?= base_url('pengaturan/pengguna.php?v=1') ?>"<?= sidebar_current_attr($is_pengguna) ?>>
                            <i class="app-menu-icon mdi mdi-account-multiple"></i>
                            <span class="app-menu-title">Pengguna</span>
                        </a>
                    </li>
                    <li class="app-nav-item">
                        <a class="<?= sidebar_link_class($is_pengaturan) ?>" href="<?= base_url('pengaturan/pengaturan.php?v=1') ?>"<?= sidebar_current_attr($is_pengaturan) ?>>
                            <i class="app-menu-icon mdi mdi-cogs"></i>
                            <span class="app-menu-title">Pengaturan</span>
                        </a>
                    </li>
                    <li class="app-nav-item">
                        <a class="<?= sidebar_link_class($is_backup) ?>" href="<?= base_url('pengaturan/backup_restore.php?v=1') ?>"<?= sidebar_current_attr($is_backup) ?>>
                            <i class="app-menu-icon mdi mdi-database"></i>
                            <span class="app-menu-title">Backup Restore</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="app-nav-item">
                        <a class="app-nav-link" href="<?= base_url('auth/logout.php?v=1') ?>" onclick="confirmLogout(event)">
                            <i class="app-menu-icon mdi mdi-logout"></i>
                            <span class="app-menu-title">Logout</span>
                        </a>
                    </li>
                </ul>
            </nav>
            <div class="app-main">
                <div class="app-content">
