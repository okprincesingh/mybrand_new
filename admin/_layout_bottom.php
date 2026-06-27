      </section>
    </main>
    <div class="admin-sidebar-overlay" id="sidebarOverlay"></div>
  </div>
</div>

<!-- Dark Mode Toggle -->
<button class="theme-toggle" id="themeToggle" aria-label="Toggle dark mode">
  <svg id="themeIcon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
  </svg>
</button>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Theme Toggle Functionality
  (function() {
    const html = document.documentElement;
    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = document.getElementById('themeIcon');
    
    // Check for saved theme preference or default to light
    const savedTheme = localStorage.getItem('admin-theme') || 'light';
    html.setAttribute('data-theme', savedTheme);
    updateIcon(savedTheme);
    
    // Toggle theme on button click
    themeToggle.addEventListener('click', function() {
      const currentTheme = html.getAttribute('data-theme');
      const newTheme = currentTheme === 'light' ? 'dark' : 'light';
      
      html.setAttribute('data-theme', newTheme);
      localStorage.setItem('admin-theme', newTheme);
      updateIcon(newTheme);
    });
    
    function updateIcon(theme) {
      if (theme === 'dark') {
        themeIcon.innerHTML = '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>';
      } else {
        themeIcon.innerHTML = '<circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>';
      }
    }
  })();
  
  // Mobile Sidebar Toggle
  (function() {
    const sidebar = document.getElementById('adminSidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const body = document.body;
    function setSidebarState(isOpen) {
      sidebar.classList.toggle('open', isOpen);
      body.classList.toggle('sidebar-open', isOpen);
      sidebarToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    }
    if (sidebar && sidebarToggle && sidebarOverlay) {
      sidebarToggle.addEventListener('click', function() {
        setSidebarState(!sidebar.classList.contains('open'));
      });
      sidebarOverlay.addEventListener('click', function() {
        setSidebarState(false);
      });
      // Close sidebar when clicking on links (mobile only)
      const navLinks = sidebar.querySelectorAll('.admin-nav-link');
      navLinks.forEach(link => {
        link.addEventListener('click', function() {
          if (window.innerWidth < 992) {
            setSidebarState(false);
          }
        });
      });
      // Handle window resize
      window.addEventListener('resize', function() {
        if (window.innerWidth >= 992) {
          setSidebarState(false);
        }
      });
      document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && sidebar.classList.contains('open')) {
          setSidebarState(false);
        }
      });
    }
  })();
  
  // Loading overlay functionality
  (function() {
    const loadingOverlay = document.getElementById('loadingOverlay');
    
    // Show loading on form submissions
    document.addEventListener('submit', function(e) {
      const form = e.target;
      if (form.tagName === 'FORM') {
        loadingOverlay.style.display = 'flex';
      }
    });
    
    // Hide loading after page load
    window.addEventListener('load', function() {
      setTimeout(function() {
        loadingOverlay.style.display = 'none';
      }, 500);
    });
  })();
</script>
</body>
</html>


