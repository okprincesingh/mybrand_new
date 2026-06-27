/**
 * Categories sidebar (header-area-1): open/close when .header-area-1__categories-btn is clicked.
 * Only runs on pages that have #categories-sidebar and #categories-sidebar-overlay (index + inner pages except index2, index3).
 */
(function () {
  function initCategoriesSidebar() {
    var DESKTOP_BREAKPOINT = 1200;
    var sidebar = document.getElementById('categories-sidebar');
    var overlay = document.getElementById('categories-sidebar-overlay');
    var openBtn = document.querySelector('.header-area-1__categories-btn');
    var closeBtn = document.getElementById('categories-sidebar-close');

    if (!sidebar || !overlay) return;

    function isDesktop() {
      return window.innerWidth >= DESKTOP_BREAKPOINT;
    }

    function openCategoriesSidebar() {
      sidebar.classList.add('header-area-1__categories-sidebar--open');
      overlay.classList.add('header-area-1__categories-overlay--open');
      sidebar.setAttribute('aria-hidden', 'false');
      overlay.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
      document.body.style.position = 'fixed';
      document.body.style.width = '100%';
    }

    function closeCategoriesSidebar() {
      sidebar.classList.remove('header-area-1__categories-sidebar--open');
      overlay.classList.remove('header-area-1__categories-overlay--open');
      sidebar.setAttribute('aria-hidden', 'true');
      overlay.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
      document.body.style.position = '';
      document.body.style.width = '';
    }

    if (openBtn) {
      openBtn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        if (!isDesktop()) return;
        if (sidebar.classList.contains('header-area-1__categories-sidebar--open')) {
          closeCategoriesSidebar();
        } else {
          openCategoriesSidebar();
        }
      });
    }

    if (closeBtn) {
      closeBtn.addEventListener('click', function (e) {
        e.preventDefault();
        closeCategoriesSidebar();
      });
    }

    if (overlay) {
      overlay.addEventListener('click', function () {
        closeCategoriesSidebar();
      });
    }

    document.addEventListener('keydown', function (e) {
      if ((e.key === 'Escape' || e.keyCode === 27) && sidebar.classList.contains('header-area-1__categories-sidebar--open')) {
        closeCategoriesSidebar();
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCategoriesSidebar);
  } else {
    initCategoriesSidebar();
  }
})();
