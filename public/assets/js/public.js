document.addEventListener('DOMContentLoaded', () => {
  const nav = document.getElementById('pubNavbar');
  const toggleBtn = document.getElementById('pubMenuToggle');
  const mobileMenu = document.getElementById('pubMobileMenu');
  const iconOpen = document.getElementById('pubMenuIconOpen');
  const iconClose = document.getElementById('pubMenuIconClose');

  function setMenuOpen(open) {
    if (!mobileMenu || !toggleBtn) return;
    mobileMenu.classList.toggle('open', open);
    toggleBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
    if (iconOpen && iconClose) {
      iconOpen.style.display = open ? 'none' : 'block';
      iconClose.style.display = open ? 'block' : 'none';
    }
  }

  if (toggleBtn && mobileMenu) {
    toggleBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      const next = !mobileMenu.classList.contains('open');
      setMenuOpen(next);
    });

    mobileMenu.querySelectorAll('a').forEach((link) => {
      link.addEventListener('click', () => setMenuOpen(false));
    });

    document.addEventListener('click', (e) => {
      if (mobileMenu.classList.contains('open') && !mobileMenu.contains(e.target) && !toggleBtn.contains(e.target)) {
        setMenuOpen(false);
      }
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && mobileMenu.classList.contains('open')) {
        setMenuOpen(false);
      }
    });

    window.addEventListener('resize', () => {
      if (window.innerWidth > 900 && mobileMenu.classList.contains('open')) {
        setMenuOpen(false);
      }
    });
  }

  if (nav) {
    const syncScroll = () => {
      nav.classList.toggle('is-scrolled', window.scrollY > 8);
    };
    window.addEventListener('scroll', syncScroll, { passive: true });
    syncScroll();
  }

  // FAQ Accordion
  const faqItems = document.querySelectorAll('[data-faq-toggle]');
  faqItems.forEach((btn) => {
    btn.addEventListener('click', () => {
      const body = btn.nextElementSibling;
      const expanded = btn.getAttribute('aria-expanded') === 'true';
      btn.setAttribute('aria-expanded', expanded ? 'false' : 'true');
      if (body) {
        body.style.display = expanded ? 'none' : 'block';
      }
    });
  });
});
