            </div>
        </main>
    </div>
    <footer class="bg-white text-dark text-center py-4 mt-4 border-top">
        <div class="container">
            <p class="mb-0">&copy; 2026 School Management System. All rights reserved.</p>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- DataTables Core -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <!-- DataTables Buttons -->
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const csrfToken = <?php echo json_encode($_SESSION['csrf_token'] ?? ''); ?>;
            const body = document.body;
            const sidebar = document.getElementById('appSidebar');
            const menuToggle = document.getElementById('appMenuToggle');
            const sidebarOverlay = document.getElementById('appSidebarOverlay');

            const closeSidebar = function() {
                if (!body.classList.contains('sidebar-open')) {
                    return;
                }

                body.classList.remove('sidebar-open');
                if (menuToggle) {
                    menuToggle.setAttribute('aria-expanded', 'false');
                }
            };

            const openSidebar = function() {
                body.classList.add('sidebar-open');
                if (menuToggle) {
                    menuToggle.setAttribute('aria-expanded', 'true');
                }
            };

            if (sidebarOverlay) {
                sidebarOverlay.hidden = false;
                sidebarOverlay.addEventListener('click', closeSidebar);
            }

            if (menuToggle) {
                menuToggle.addEventListener('click', function() {
                    if (body.classList.contains('sidebar-open')) {
                        closeSidebar();
                        return;
                    }

                    openSidebar();
                });
            }

            if (sidebar) {
                sidebar.querySelectorAll('a.nav-link').forEach(function(link) {
                    link.addEventListener('click', function() {
                        if (window.matchMedia('(max-width: 991.98px)').matches) {
                            closeSidebar();
                        }
                    });
                });
            }

            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    closeSidebar();
                }
            });

            const desktopMedia = window.matchMedia('(min-width: 992px)');
            const handleDesktopChange = function(event) {
                if (event.matches) {
                    closeSidebar();
                }
            };

            if (desktopMedia.addEventListener) {
                desktopMedia.addEventListener('change', handleDesktopChange);
            } else if (desktopMedia.addListener) {
                desktopMedia.addListener(handleDesktopChange);
            }

            const ensureCsrfToken = function(form) {
                if (!csrfToken) {
                    return;
                }

                const method = (form.getAttribute('method') || '').toLowerCase();
                if (method !== 'post') {
                    return;
                }

                if (!form.querySelector('input[name="_csrf_token"]')) {
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = '_csrf_token';
                    hidden.value = csrfToken;
                    form.appendChild(hidden);
                }
            };

            document.querySelectorAll('form').forEach(function(form) {
                ensureCsrfToken(form);
            });

            document.addEventListener('click', function(event) {
                const confirmLink = event.target.closest('a[data-confirm]');
                if (!confirmLink) {
                    return;
                }

                const message = confirmLink.getAttribute('data-confirm') || 'Are you sure you want to continue?';
                if (!window.confirm(message)) {
                    event.preventDefault();
                }
            });

            document.addEventListener('submit', function(event) {
                const form = event.target;
                if (form && form.tagName === 'FORM') {
                    const confirmMessage = form.getAttribute('data-confirm');
                    if (confirmMessage && form.dataset.confirmed !== 'true') {
                        const approved = window.confirm(confirmMessage);
                        if (!approved) {
                            event.preventDefault();
                            return;
                        }
                        form.dataset.confirmed = 'true';
                    }

                    ensureCsrfToken(form);

                    const method = (form.getAttribute('method') || '').toLowerCase();
                    if (method === 'post') {
                        const submitter = event.submitter || form.querySelector('button[type="submit"], input[type="submit"]');
                        if (submitter && !submitter.classList.contains('is-loading')) {
                            if (submitter.tagName === 'BUTTON') {
                                submitter.dataset.originalHtml = submitter.innerHTML;
                                submitter.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Saving...';
                            } else {
                                submitter.dataset.originalValue = submitter.value;
                                submitter.value = 'Saving...';
                            }

                            submitter.classList.add('is-loading');
                            submitter.setAttribute('disabled', 'disabled');
                            submitter.setAttribute('aria-disabled', 'true');
                        }
                    }
                }
            }, true);

            // Auto-hide alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                if (alert.classList.contains('alert-dismissible')) {
                    setTimeout(function() {
                        const bsAlert = new bootstrap.Alert(alert);
                        bsAlert.close();
                    }, 5000);
                }
            });
        });

        // Initialize DataTables globally
        $(document).ready(function() {
            if ($('.datatable').length > 0) {
                $('.datatable').DataTable({
                    "pageLength": 10,
                    "ordering": true,
                    "responsive": true,
                    "dom": "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                           "<'row'<'col-sm-12'<'text-center mt-2 mb-2'B>>>" +
                           "<'row'<'col-sm-12'tr>>" +
                           "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                    "buttons": [
                        { extend: 'copy', className: 'btn btn-sm btn-outline-secondary', text: 'Copy' },
                        { extend: 'csv', className: 'btn btn-sm btn-outline-success', text: 'CSV' },
                        { extend: 'excel', className: 'btn btn-sm btn-outline-success', text: 'Excel' },
                        { extend: 'pdf', className: 'btn btn-sm btn-outline-danger', text: 'PDF' },
                        { extend: 'print', className: 'btn btn-sm btn-outline-primary', text: 'Print' }
                    ]
                });
            }
        });
    </script>
</body>
</html>
