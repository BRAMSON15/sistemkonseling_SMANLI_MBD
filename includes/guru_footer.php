<?php
/**
 * Footer Template Khusus Guru BK
 * Template: assets/guruBK/style-guru-bk.css & dashboard_guru_bk.html
 * SMA Negeri 5 Maluku Barat Daya
 */
?>
      <footer>
        &copy; <?= date('Y') ?> SMAN 5 MBD &bull; Sistem Bimbingan dan Konseling &bull; Data siswa bersifat rahasia
      </footer>
    </div>
  </main>
</div>

<script>
  /* ========================================================
     1. GURU BK SIDEBAR TOGGLE (DESKTOP COLLAPSE & MOBILE DRAWER)
     ======================================================== */
  function toggleGuruSidebar() {
    const isMobile = window.innerWidth <= 950;
    const app = document.querySelector('.app');
    const sidebar = document.getElementById('guruSidebar');

    if (isMobile) {
      if (sidebar && sidebar.classList.contains('show')) {
        closeGuruSidebarMobile();
      } else {
        openGuruSidebarMobile();
      }
    } else {
      if (app) {
        app.classList.toggle('sidebar-collapsed');
        const isCollapsed = app.classList.contains('sidebar-collapsed');
        localStorage.setItem('guru_sidebar_collapsed', isCollapsed ? '1' : '0');
      }
    }
  }

  function openGuruSidebarMobile() {
    const sidebar = document.getElementById('guruSidebar');
    const backdrop = document.getElementById('guruSidebarBackdrop');
    if (sidebar) sidebar.classList.add('show');
    if (backdrop) backdrop.classList.add('active');
    document.body.style.overflow = 'hidden';
  }

  function closeGuruSidebarMobile() {
    const sidebar = document.getElementById('guruSidebar');
    const backdrop = document.getElementById('guruSidebarBackdrop');
    if (sidebar) sidebar.classList.remove('show');
    if (backdrop) backdrop.classList.remove('active');
    document.body.style.overflow = '';
  }

  document.addEventListener('DOMContentLoaded', function() {
    if (window.innerWidth > 950) {
      if (localStorage.getItem('guru_sidebar_collapsed') === '1') {
        const app = document.querySelector('.app');
        if (app) app.classList.add('sidebar-collapsed');
      }
    }
  });

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      closeGuruSidebarMobile();
      closeAllGuruDropdowns();
      closeAllGuruModals();
    }
  });

  window.addEventListener('resize', function() {
    if (window.innerWidth > 950) {
      closeGuruSidebarMobile();
    }
  });

  /* ========================================================
     2. GURU BK DROPDOWNS (BELL NOTIF & USER MENU)
     ======================================================== */
  function toggleGuruBellDropdown(e) {
    e.stopPropagation();
    const bellMenu = document.getElementById('guruBellDropdown');
    const userMenu = document.getElementById('guruUserDropdown');
    if (userMenu) userMenu.classList.remove('show');
    if (bellMenu) bellMenu.classList.toggle('show');
  }

  function toggleGuruUserDropdown(e) {
    e.stopPropagation();
    const userMenu = document.getElementById('guruUserDropdown');
    const bellMenu = document.getElementById('guruBellDropdown');
    if (bellMenu) bellMenu.classList.remove('show');
    if (userMenu) userMenu.classList.toggle('show');
  }

  function closeAllGuruDropdowns() {
    const bellMenu = document.getElementById('guruBellDropdown');
    const userMenu = document.getElementById('guruUserDropdown');
    if (bellMenu) bellMenu.classList.remove('show');
    if (userMenu) userMenu.classList.remove('show');
  }

  /* ========================================================
     3. GURU BK MODAL DIALOGS
     ======================================================== */
  function openGuruModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.classList.add('show');
      document.body.style.overflow = 'hidden';
    }
  }

  function closeGuruModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.classList.remove('show');
      document.body.style.overflow = '';
    }
  }

  function closeAllGuruModals() {
    document.querySelectorAll('.guru-modal-backdrop.show').forEach(m => {
      m.classList.remove('show');
    });
    document.body.style.overflow = '';
  }

  window.addEventListener('click', function(e) {
    closeAllGuruDropdowns();
    if (e.target.classList.contains('guru-modal-backdrop')) {
      e.target.classList.remove('show');
      document.body.style.overflow = '';
    }
  });
</script>
</body>
</html>
