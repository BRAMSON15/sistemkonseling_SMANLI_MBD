<?php
/**
 * Footer Template
 * Sistem Konseling SMA Negeri 5 Maluku Barat Daya
 */
?>
    <?php if (is_logged_in()): ?>
            </main>
            <footer class="footer bg-white border-top py-3 px-4 text-center text-muted small mt-auto">
                <div class="container-fluid d-flex flex-column flex-sm-row justify-content-between align-items-center">
                    <div>
                        &copy; <?= date('Y') ?> <strong>SMA Negeri 5 Maluku Barat Daya</strong> &bull; Sistem Deteksi Dini & Bimbingan Konseling
                    </div>
                    <div class="text-secondary mt-1 mt-sm-0">
                        Penelitian Skripsi UKIM Ambon &bull; Agatha Natalia Laipeny
                    </div>
                </div>
            </footer>
        </div>
    </div>
    <?php else: ?>
        </main>
    </div>
    <?php endif; ?>

    <!-- Bootstrap 5.3 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        // Toggle mobile sidebar
        const sidebarToggleBtn = document.getElementById('sidebarToggleBtn');
        const appSidebar = document.getElementById('appSidebar');
        if (sidebarToggleBtn && appSidebar) {
            sidebarToggleBtn.addEventListener('click', () => {
                appSidebar.classList.toggle('show');
            });
        }
    </script>
</body>
</html>
