                </div>
                <!-- content-wrapper ends -->
                <footer class="app-footer">
                    <div class="app-container flow-root">
                        <span class="text-gray-500 block text-center sm:text-left sm:inline-block">
                            Copyright &copy; <?= date('Y') ?> Sistem Pembayaran Siswa -
                            <a href="https://misultanfattah.sch.id/" target="_blank" style="text-decoration: none !important;">MI Sultan Fattah Sukosono</a>
                        </span>
                    </div>
                </footer>
                <!-- partial -->
            </div>
            <!-- main-panel ends -->
        </div>
        <!-- app body ends -->
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
        <form id="updateSystemForm" action="<?= base_url('pengaturan/update_sistem.php') ?>" method="get" class="hidden" style="display: none;">
            <input type="hidden" name="do" value="1">
            <input type="hidden" name="token" value="<?= htmlspecialchars($update_token, ENT_QUOTES) ?>">
            <input type="hidden" name="return_to" value="<?= htmlspecialchars($return_to, ENT_QUOTES) ?>">
        </form>
        <div id="updateSystemProcessModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
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
    <script>
        window.AppModal = {
            open(target) {
                const modal = typeof target === 'string' ? document.querySelector(target) : target;
                if (!modal) return;
                modal.classList.remove('hidden');
                modal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('overflow-hidden');
                modal.scrollTop = 0;
                modal.dispatchEvent(new CustomEvent('app:modal-open'));
            },
            close(target) {
                const modal = typeof target === 'string' ? document.querySelector(target) : target;
                if (!modal) return;
                modal.classList.add('hidden');
                modal.setAttribute('aria-hidden', 'true');
                modal.dispatchEvent(new CustomEvent('app:modal-close'));
                if (!document.querySelector('[data-tailwind-modal]:not(.hidden)')) {
                    document.body.classList.remove('overflow-hidden');
                }
            }
        };

        document.addEventListener('click', function(event) {
            const trigger = event.target.closest('[data-tailwind-modal-target]');
            if (trigger) {
                event.preventDefault();
                AppModal.open(trigger.getAttribute('data-tailwind-modal-target'));
                return;
            }

            const closeButton = event.target.closest('[data-tailwind-modal-close]');
            if (closeButton) {
                event.preventDefault();
                AppModal.close(closeButton.closest('[data-tailwind-modal]'));
                return;
            }

            if (event.target.matches('[data-tailwind-modal]')) {
                AppModal.close(event.target);
            }
        });

        document.addEventListener('keydown', function(event) {
            if (event.key !== 'Escape') return;
            const openModals = document.querySelectorAll('[data-tailwind-modal]:not(.hidden)');
            AppModal.close(openModals[openModals.length - 1]);
        });
    </script>
    <!-- Chart.js -->
    <script src="<?= base_url('assets/vendors/chart.js/chart.umd.js') ?>"></script>
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            $('table.app-data-table').each(function() {
                if ($(this).closest('[data-tailwind-modal]').length) {
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
        (function() {
            const baseUrl = <?= json_encode(rtrim(base_url(), '/') . '/') ?>;

            function setUpdateProcessVisible(visible) {
                const modal = document.getElementById('updateSystemProcessModal');
                if (!modal) return;
                modal.style.display = visible ? 'block' : 'none';
                document.body.classList.toggle('overflow-hidden', visible);
            }

            async function runUpdateSystem() {
                const form = document.getElementById('updateSystemForm');
                if (!form) return;

                const params = new URLSearchParams(new FormData(form));
                params.set('ajax', '1');
                setUpdateProcessVisible(true);

                try {
                    const response = await fetch(form.action + '?' + params.toString(), { method: 'GET', cache: 'no-store' });
                    const data = await response.json();
                    setUpdateProcessVisible(false);
                    Swal.fire({
                        title: data.title || 'Info',
                        text: data.text || '',
                        icon: data.icon || 'info'
                    }).then(() => {
                        if (params.get('return_to')) {
                            window.location.href = baseUrl + params.get('return_to');
                        } else {
                            window.location.reload();
                        }
                    });
                } catch (err) {
                    setUpdateProcessVisible(false);
                    Swal.fire({
                        title: 'Gagal',
                        text: 'Update gagal dijalankan. Coba ulang atau cek koneksi server.',
                        icon: 'error'
                    });
                }
            }

            window.confirmUpdateSystem = function(event) {
                if (event) event.preventDefault();
                Swal.fire({
                    title: 'Konfirmasi Update Sistem',
                    text: 'Proses ini akan mengunduh versi terbaru. File konfigurasi server tidak akan ditimpa.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Lanjutkan Update'
                }).then((result) => {
                    if (result.isConfirmed) {
                        runUpdateSystem();
                    }
                });
            };

            document.addEventListener('click', function(event) {
                const trigger = event.target.closest('[data-update-system-trigger]');
                if (trigger) {
                    window.confirmUpdateSystem(event);
                }
            });
        })();
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
