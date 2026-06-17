                </div>
                <!-- content-wrapper ends -->
                <footer class="footer">
                    <div class="container-fluid clearfix">
                        <span class="text-muted d-block text-center text-sm-left d-sm-inline-block">
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
        <div class="modal fade" id="modalUpdateSistem" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Konfirmasi Update Sistem</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning mb-0">
                            Proses ini akan mengunduh versi terbaru dari GitHub dan menimpa file sistem.
                            File konfigurasi server tidak akan ditimpa.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <form action="<?= base_url('pengaturan/update_sistem.php') ?>" method="get" class="d-inline">
                            <input type="hidden" name="do" value="1">
                            <input type="hidden" name="token" value="<?= htmlspecialchars($update_token, ENT_QUOTES) ?>">
                            <input type="hidden" name="return_to" value="<?= htmlspecialchars($return_to, ENT_QUOTES) ?>">
                            <button type="submit" class="btn btn-primary">Lanjutkan Update</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="modalUpdateProses" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Update Sistem</h5>
                    </div>
                    <div class="modal-body">
                        <div class="d-flex align-items-center" style="gap: .75rem;">
                            <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
                            <div>Sedang memproses update. Jangan tutup halaman ini.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <script src="<?= base_url('assets/vendors/js/vendor.bundle.base.js') ?>"></script>
    <script src="<?= base_url('assets/js/off-canvas.js') ?>"></script>
    <script src="<?= base_url('assets/js/misc.js') ?>"></script>
    <!-- Chart.js -->
    <script src="<?= base_url('assets/vendors/chart.js/chart.umd.js') ?>"></script>
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap4.min.js"></script>
    <script>
        $(document).ready(function() {
            // Jangan init DataTables pada <table> di dalam modal (tersembunyi saat init)
            // — lebar kolom salah → kolom terpotong di hosting / layar sempit.
            $('table.table').each(function() {
                if ($(this).closest('.modal').length) {
                    return;
                }
                if ($.fn.DataTable && $.fn.DataTable.isDataTable(this)) {
                    return;
                }
                var enableScrollX = $(this).attr('data-dt-scroll-x') === '1';
                $(this).DataTable({
                    /* scrollX memecah thead/tbody → header tidak selaras; hanya pakai di tabel bertanda data-dt-scroll-x */
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
        (function () {
            var form = document.querySelector('#modalUpdateSistem form');
            if (!form || !window.fetch || !window.bootstrap) {
                return;
            }
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                var confirmModalEl = document.getElementById('modalUpdateSistem');
                var processModalEl = document.getElementById('modalUpdateProses');
                if (!confirmModalEl || !processModalEl) {
                    form.submit();
                    return;
                }
                var confirmModal = bootstrap.Modal.getOrCreateInstance(confirmModalEl);
                var processModal = bootstrap.Modal.getOrCreateInstance(processModalEl);
                confirmModal.hide();
                processModal.show();
                var params = new URLSearchParams(new FormData(form));
                params.set('ajax', '1');
                fetch(form.action + '?' + params.toString(), { method: 'GET', cache: 'no-store' })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        processModal.hide();
                        var title = data && data.title ? data.title : 'Info';
                        var text = data && data.text ? data.text : '';
                        var icon = data && data.icon ? data.icon : 'info';
                        Swal.fire({ title: title, text: text, icon: icon }).then(function () {
                            if (params.get('return_to')) {
                                window.location.href = <?= json_encode(rtrim(base_url(), '/') . '/') ?> + params.get('return_to');
                            } else {
                                window.location.reload();
                            }
                        });
                    })
                    .catch(function () {
                        processModal.hide();
                        Swal.fire({ title: 'Gagal', text: 'Update gagal dijalankan. Coba ulang atau cek koneksi server.', icon: 'error' });
                    });
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
