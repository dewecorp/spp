                </div>
                <!-- content-wrapper ends -->
                <footer class="footer">
                    <div class="container-fluid clearfix">
                        <span class="text-gray-500 block text-center text-sm-left sm:inline-block">
                            Copyright © <?= date('Y') ?> Sistem Pembayaran Siswa -
                            <a href="https://misultanfattah.sch.id/" target="_blank" style="text-decoration: none !important;">MI Sultan Fattah Sukosono</a>
                        </span>
                    </div>
                </footer>
                <!-- partial -->
            </div>
            <!-- main-panel ends -->
        </div>
        <!-- page-body-wrapper ends -->
    </div>
    <!-- container-scroller -->
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') : ?>
        <?php
        if (!isset($_SESSION['update_token']) || !is_string($_SESSION['update_token']) || $_SESSION['update_token'] === '') {
            $_SESSION['update_token'] = bin2hex(random_bytes(16));
        }
        $update_token = $_SESSION['update_token'];
        $base_path = (string)(parse_url(base_url(), PHP_URL_PATH) ?? '');
        $req_path = (string)(parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?? '');
        $req_query = (string)($_SERVER['QUERY_STRING'] ?? '');
        if ($base_path !== '' && $req_path !== '' && strpos($req_path, $base_path) === 0) {
            $rel_path = substr($req_path, strlen($base_path));
        } else {
            $rel_path = ltrim($req_path, '/');
        }
        $return_to = ltrim((string)$rel_path, '/');
        if ($req_query !== '') {
            $return_to .= '?' . $req_query;
        }
        ?>
        <div x-show="$store.updateSystem.showUpdateModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="$store.updateSystem.showUpdateModal = false"></div>
                <div class="relative bg-white rounded-xl shadow-xl max-w-md w-full">
                    <div class="flex items-center justify-between p-6 border-b">
                        <h5 class="text-lg font-semibold">Konfirmasi Update Sistem</h5>
                        <button type="button" class="text-gray-400 hover:text-gray-600" @click="$store.updateSystem.showUpdateModal = false">
                            <i class="mdi mdi-close text-xl"></i>
                        </button>
                    </div>
                    <div class="p-6">
                        <div class="p-4 rounded-lg bg-amber-50 text-amber-800 border border-amber-200">
                            Proses ini akan mengunduh versi terbaru.
                            File konfigurasi server tidak akan ditimpa.
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-3 p-6 border-t">
                        <button type="button" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition" @click="$store.updateSystem.showUpdateModal = false">Batal</button>
                        <form action="<?= base_url('pengaturan/update_sistem.php') ?>" method="get" @submit.prevent="$store.updateSystem.startUpdate($event)">
                            <input type="hidden" name="do" value="1">
                            <input type="hidden" name="token" value="<?= htmlspecialchars($update_token, ENT_QUOTES) ?>">
                            <input type="hidden" name="return_to" value="<?= htmlspecialchars($return_to, ENT_QUOTES) ?>">
                            <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition">Lanjutkan Update</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div x-show="$store.updateSystem.showUpdateProses" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black bg-opacity-50"></div>
                <div class="relative bg-white rounded-xl shadow-xl max-w-md w-full">
                    <div class="p-6 border-b">
                        <h5 class="text-lg font-semibold">Update Sistem</h5>
                    </div>
                    <div class="p-6">
                        <div class="flex items-center gap-3">
                            <div class="animate-spin h-5 w-5 border-2 border-primary border-t-transparent rounded-full"></div>
                            <div>Sedang memproses update. Jangan tutup halaman ini.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Chart.js -->
    <script src="<?= base_url('assets/vendors/chart.js/chart.umd.js') ?>"></script>
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            $('table.table').each(function() {
                if ($(this).closest('.modal').length) {
                    return;
                }
                if ($.fn.DataTable && $.fn.DataTable.isDataTable(this)) {
                    return;
                }
                var enableScrollX = $(this).attr('data-dt-scroll-x') === '1';
                $(this).DataTable({
                    scrollX: enableScrollX,
                    autoWidth: false
                });
            });
        });
    </script>
    <script>
        function confirmLogout(event) {
            event.preventDefault();
            const url = event.currentTarget.getAttribute('href');
            Swal.fire({
                title: 'Konfirmasi Logout',
                text: "Apakah anda yakin ingin keluar dari aplikasi?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Logout!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
        }

        function updateDateTime() {
            const now = new Date();
            const dateOptions = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                timeZone: 'Asia/Jakarta'
            };
            const timeOptions = { 
                hour: '2-digit', 
                minute: '2-digit', 
                second: '2-digit', 
                hour12: false,
                timeZone: 'Asia/Jakarta'
            };
            
            const dateStr = now.toLocaleDateString('id-ID', dateOptions);
            const timeStr = now.toLocaleTimeString('id-ID', timeOptions).replace(/\./g, ':');
            
            const element = document.getElementById('current-datetime');
            if (element) {
                element.textContent = `${dateStr} - ${timeStr} WIB`;
            }
        }
        
        setInterval(updateDateTime, 1000);
        updateDateTime();
    </script>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('updateSystem', {
                showUpdateModal: false,
                showUpdateProses: false,
                async startUpdate(e) {
                    this.showUpdateModal = false;
                    this.showUpdateProses = true;
                    const form = e.target;
                    const params = new URLSearchParams(new FormData(form));
                    params.set('ajax', '1');
                    try {
                        const r = await fetch(form.action + '?' + params.toString(), { method: 'GET', cache: 'no-store' });
                        const data = await r.json();
                        this.showUpdateProses = false;
                        Swal.fire({
                            title: data.title || 'Info',
                            text: data.text || '',
                            icon: data.icon || 'info'
                        }).then(() => {
                            if (params.get('return_to')) {
                                window.location.href = <?= json_encode(rtrim(base_url(), '/') . '/') ?> + params.get('return_to');
                            } else {
                                window.location.reload();
                            }
                        });
                    } catch (err) {
                        this.showUpdateProses = false;
                        Swal.fire({ title: 'Gagal', text: 'Update gagal dijalankan. Coba ulang atau cek koneksi server.', icon: 'error' });
                    }
                }
            });
        });
    </script>
    <?php if (isset($_SESSION['flash_swal']) && is_array($_SESSION['flash_swal'])) : ?>
        <?php
        $flash = $_SESSION['flash_swal'];
        unset($_SESSION['flash_swal']);
        $flash_title = isset($flash['title']) ? (string)$flash['title'] : '';
        $flash_text = isset($flash['text']) ? (string)$flash['text'] : '';
        $flash_icon = isset($flash['icon']) ? (string)$flash['icon'] : 'info';
        ?>
        <script>
            Swal.fire({
                title: <?= json_encode($flash_title) ?>,
                text: <?= json_encode($flash_text) ?>,
                icon: <?= json_encode($flash_icon) ?>
            });
        </script>
    <?php endif; ?>
</body>
</html>
