    </div> <!-- Close main-content container from header -->
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery (optional, for additional functionality) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Chart.js (if you use charts) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <!-- DataTables (for advanced table functionality) -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Custom Admin Scripts -->
    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });

        // Initialize popovers
        var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
        var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl)
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            document.querySelectorAll('.alert').forEach(function(alert) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Confirm delete actions
        document.querySelectorAll('.confirm-delete').forEach(function(button) {
            button.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                    e.preventDefault();
                }
            });
        });

        // Toggle sidebar on mobile (if you have sidebar)
        document.getElementById('sidebarToggle')?.addEventListener('click', function() {
            document.querySelector('.sidebar')?.classList.toggle('show');
        });

        // Dark mode toggle for admin panel (optional)
        document.getElementById('darkModeToggle')?.addEventListener('click', function() {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('admin-dark-mode', document.body.classList.contains('dark-mode'));
            
            // Update icon
            const icon = this.querySelector('i');
            if (document.body.classList.contains('dark-mode')) {
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
            } else {
                icon.classList.remove('fa-sun');
                icon.classList.add('fa-moon');
            }
        });

        // Load dark mode preference
        document.addEventListener('DOMContentLoaded', function() {
            if (localStorage.getItem('admin-dark-mode') === 'true') {
                document.body.classList.add('dark-mode');
                const icon = document.querySelector('#darkModeToggle i');
                if (icon) {
                    icon.classList.remove('fa-moon');
                    icon.classList.add('fa-sun');
                }
            }
            
            // Initialize DataTables if table exists
            if (document.querySelector('.dataTable')) {
                $('.dataTable').DataTable({
                    pageLength: 25,
                    responsive: true,
                    language: {
                        search: "_INPUT_",
                        searchPlaceholder: "Search...",
                        lengthMenu: "Show _MENU_ entries",
                        info: "Showing _START_ to _END_ of _TOTAL_ entries",
                        paginate: {
                            first: '<i class="fa-solid fa-angles-left"></i>',
                            previous: '<i class="fa-solid fa-angle-left"></i>',
                            next: '<i class="fa-solid fa-angle-right"></i>',
                            last: '<i class="fa-solid fa-angles-right"></i>'
                        }
                    }
                });
            }
        });

        // Handle responsive tables
        document.querySelectorAll('.table-responsive').forEach(function(table) {
            table.addEventListener('scroll', function() {
                if (this.scrollLeft > 0) {
                    this.classList.add('scrolled');
                } else {
                    this.classList.remove('scrolled');
                }
            });
        });

        // Form validation
        document.querySelectorAll('.needs-validation').forEach(function(form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });

        // Export table data to CSV
        document.getElementById('exportCSV')?.addEventListener('click', function() {
            const table = document.querySelector('.dataTable');
            if (!table) return;
            
            const rows = table.querySelectorAll('tr');
            let csv = [];
            
            rows.forEach(function(row) {
                const cols = row.querySelectorAll('td, th');
                const rowData = [];
                cols.forEach(function(col) {
                    rowData.push('"' + col.innerText.replace(/"/g, '""') + '"');
                });
                csv.push(rowData.join(','));
            });
            
            const csvContent = csv.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'export_' + new Date().toISOString().slice(0,10) + '.csv';
            a.click();
        });

        // Print table
        document.getElementById('printTable')?.addEventListener('click', function() {
            window.print();
        });

        // Refresh data periodically (for dashboards)
        if (document.querySelector('.auto-refresh')) {
            setInterval(function() {
                location.reload();
            }, 300000); // Refresh every 5 minutes
        }

        // Handle AJAX requests with loading indicator
        function showLoading() {
            document.getElementById('loadingOverlay')?.classList.remove('d-none');
        }

        function hideLoading() {
            document.getElementById('loadingOverlay')?.classList.add('d-none');
        }

        // Format numbers with commas
        function formatNumber(num) {
            return num.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,');
        }

        // Format currency
        function formatCurrency(num) {
            return '$' + formatNumber(parseFloat(num).toFixed(2));
        }

        // Copy to clipboard
        document.querySelectorAll('.copy-to-clipboard').forEach(function(button) {
            button.addEventListener('click', function() {
                const text = this.getAttribute('data-copy');
                navigator.clipboard.writeText(text).then(function() {
                    const originalText = button.innerHTML;
                    button.innerHTML = '<i class="fa-regular fa-check"></i> Copied!';
                    setTimeout(function() {
                        button.innerHTML = originalText;
                    }, 2000);
                });
            });
        });

        // Handle window resize
        let resizeTimer;
        window.addEventListener('resize', function() {
            document.body.classList.add('resize-animation-stopper');
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                document.body.classList.remove('resize-animation-stopper');
            }, 400);
        });
    </script>

    <!-- Additional custom scripts can be added here -->
    
    <!-- Footer -->
    <footer class="admin-footer mt-5 py-3" style="background: #f8f9fa; border-top: 1px solid #dee2e6;">
        <div class="container-fluid px-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0 text-muted small">
                        &copy; <?php echo date('Y'); ?> Crowdfunding Platform. All rights reserved.
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0 text-muted small">
                        <i class="fa-regular fa-clock me-1"></i>
                        Last updated: <?php echo date('F j, Y, g:i a'); ?>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Loading Overlay (hidden by default) -->
    <div id="loadingOverlay" class="d-none" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.8); z-index: 9999; display: flex; align-items: center; justify-content: center;">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <style>
        /* Admin Footer Styles */
        .admin-footer {
            background: white;
            border-top: 1px solid #e9ecef;
            margin-top: 2rem;
        }

        /* Dark mode footer */
        body.dark-mode .admin-footer {
            background: #2d2d2d !important;
            border-top-color: #444 !important;
        }

        body.dark-mode .admin-footer .text-muted {
            color: #aaa !important;
        }

        /* Print styles */
        @media print {
            .admin-navbar,
            .admin-footer,
            .btn,
            .dataTables_filter,
            .dataTables_length,
            .dataTables_paginate {
                display: none !important;
            }
            
            .card {
                border: none !important;
                box-shadow: none !important;
            }
        }

        /* Responsive table indicator */
        .table-responsive {
            position: relative;
        }
        
        .table-responsive::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            height: 100%;
            width: 30px;
            background: linear-gradient(to right, transparent, rgba(0,0,0,0.05));
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .table-responsive.scrolled::after {
            opacity: 1;
        }

        /* Loading overlay */
        #loadingOverlay {
            backdrop-filter: blur(5px);
        }

        /* Animation stopper during resize */
        .resize-animation-stopper * {
            animation: none !important;
            transition: none !important;
        }

        /* DataTables customization */
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #dee2e6;
            border-radius: 50px;
            padding: 0.375rem 1rem;
            margin-left: 0.5rem;
        }
        
        .dataTables_wrapper .dataTables_filter input:focus {
            border-color: #00c6ff;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0,198,255,0.1);
        }
        
        .dataTables_wrapper .dataTables_length select {
            border: 1px solid #dee2e6;
            border-radius: 50px;
            padding: 0.375rem 2rem 0.375rem 1rem;
            margin: 0 0.5rem;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            border-radius: 50px !important;
            margin: 0 2px;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: linear-gradient(135deg, #141e30, #243b55) !important;
            border-color: transparent !important;
            color: white !important;
        }

        /* Dark mode DataTables */
        body.dark-mode .dataTables_wrapper .dataTables_filter input,
        body.dark-mode .dataTables_wrapper .dataTables_length select {
            background: #2d2d2d;
            border-color: #444;
            color: #eee;
        }
        
        body.dark-mode .dataTables_wrapper .dataTables_filter input:focus,
        body.dark-mode .dataTables_wrapper .dataTables_length select:focus {
            border-color: #00c6ff;
            background: #2d2d2d;
            color: #eee;
        }
        
        body.dark-mode .dataTables_wrapper .dataTables_paginate .paginate_button {
            color: #ddd !important;
        }
        
        body.dark-mode .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: linear-gradient(135deg, #2c3e50, #3498db) !important;
            color: white !important;
        }
    </style>
</body>
</html>