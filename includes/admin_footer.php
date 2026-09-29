<?php
/**
 * Footer Template Khusus Administrator
 * Template: assets/admin/admin.css & dashboard_admin.html
 * SMA Negeri 5 Maluku Barat Daya
 */
?>
        <!-- FOOTER -->
        <footer>
          &copy; <?= date('Y') ?> <strong>SMA Negeri 5 Maluku Barat Daya</strong> &bull; Sistem Deteksi Dini &amp; Bimbingan Konseling (UKIM Ambon)
        </footer>
      </section>
    </main>
  </div>

  <script>
    /* ========================================================
       1. SIDEBAR TOGGLE (DESKTOP COLLAPSE & MOBILE DRAWER)
       ======================================================== */
    function toggleSidebar() {
      const isMobile = window.innerWidth <= 900;
      const app = document.querySelector('.app');
      const sidebar = document.getElementById('adminSidebar');

      if (isMobile) {
        if (sidebar && sidebar.classList.contains('show')) {
          closeSidebarMobile();
        } else {
          openSidebarMobile();
        }
      } else {
        if (app) {
          app.classList.toggle('sidebar-collapsed');
          const isCollapsed = app.classList.contains('sidebar-collapsed');
          localStorage.setItem('admin_sidebar_collapsed', isCollapsed ? '1' : '0');
        }
      }
    }

    function openSidebarMobile() {
      const sidebar = document.getElementById('adminSidebar');
      const backdrop = document.getElementById('sidebarBackdrop');
      if (sidebar) sidebar.classList.add('show');
      if (backdrop) backdrop.classList.add('active');
      document.body.style.overflow = 'hidden';
    }

    function closeSidebarMobile() {
      const sidebar = document.getElementById('adminSidebar');
      const backdrop = document.getElementById('sidebarBackdrop');
      if (sidebar) sidebar.classList.remove('show');
      if (backdrop) backdrop.classList.remove('active');
      document.body.style.overflow = '';
    }

    document.addEventListener('DOMContentLoaded', function() {
      if (window.innerWidth > 900) {
        if (localStorage.getItem('admin_sidebar_collapsed') === '1') {
          const app = document.querySelector('.app');
          if (app) app.classList.add('sidebar-collapsed');
        }
      }
    });

    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        closeSidebarMobile();
        closeAllDropdowns();
      }
    });

    window.addEventListener('resize', function() {
      if (window.innerWidth > 900) {
        closeSidebarMobile();
      }
    });

    /* ========================================================
       2. TOPBAR DROPDOWNS (BELL NOTIF & USER MENU)
       ======================================================== */
    function toggleBellDropdown(e) {
      e.stopPropagation();
      const bellMenu = document.getElementById('bellDropdown');
      const userMenu = document.getElementById('userDropdown');
      if (userMenu) userMenu.classList.remove('show');
      if (bellMenu) bellMenu.classList.toggle('show');
    }

    function toggleUserDropdown(e) {
      e.stopPropagation();
      const userMenu = document.getElementById('userDropdown');
      const bellMenu = document.getElementById('bellDropdown');
      if (bellMenu) bellMenu.classList.remove('show');
      if (userMenu) userMenu.classList.toggle('show');
    }

    function closeAllDropdowns() {
      const bellMenu = document.getElementById('bellDropdown');
      const userMenu = document.getElementById('userDropdown');
      if (bellMenu) bellMenu.classList.remove('show');
      if (userMenu) userMenu.classList.remove('show');
    }

    window.addEventListener('click', function(e) {
      closeAllDropdowns();
    });
  </script>
</body>
</html>
