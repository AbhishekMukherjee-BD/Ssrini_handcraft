/**
 * SSRINI HANDCRAFTS — 1:1 JURI JEWELRY MOBILE NAVIGATION ENGINE
 * Implements the exact mobile menu from https://www.jurijewelry.com/rivulet:
 * - Minimal floating capsule pill navbar with "Menu", brand emblem, and "Cart (count)".
 * - Toggling "Menu" switches text to "Close" with underline selector.
 * - Full-screen editorial light overlay with centered Cinzel typography and expandable collections.
 * - Contact footer: contact@ssrini.com & +91 9836081994.
 */
(function() {
  'use strict';

  function getCartCount() {
    try {
      // 1. Check existing cart count in DOM if available
      const domCount = document.querySelector('.cart-count, #cart-count');
      if (domCount && domCount.textContent.trim() !== '') {
        const num = parseInt(domCount.textContent.trim(), 10);
        if (!isNaN(num)) return num;
      }

      // 2. Check localStorage
      const cartData = localStorage.getItem('ssrini_cart') || localStorage.getItem('cart');
      if (cartData) {
        const parsed = JSON.parse(cartData);
        if (Array.isArray(parsed)) {
          return parsed.reduce((sum, item) => sum + (parseInt(item.quantity, 10) || 1), 0);
        } else if (typeof parsed === 'object' && parsed !== null) {
          return Object.values(parsed).reduce((sum, item) => sum + (parseInt(item.quantity, 10) || 1), 0);
        }
      }
    } catch (e) {
      // silent fallback
    }
    return 0;
  }

  function updateAllCartCounts() {
    const count = getCartCount();
    document.querySelectorAll('.juri-cart-count').forEach(el => {
      el.textContent = count;
    });
  }

  function initJuriMobileNav() {
    // 1. Remove old burger drawer elements if present
    const oldDrawer = document.getElementById('ssrini-mobile-drawer');
    if (oldDrawer) oldDrawer.remove();
    document.querySelectorAll('.ssrini-mobile-burger-btn').forEach(btn => btn.remove());

    // 2. Locate navbar
    const navBar = document.getElementById('main-navbar') || document.querySelector('.navbar');
    if (!navBar) return;

    // 3. Inject Juri Mobile Pill Bar inside navbar
    let pillBar = navBar.querySelector('.juri-mobile-pill-bar');
    if (!pillBar) {
      pillBar = document.createElement('div');
      pillBar.className = 'juri-mobile-pill-bar';
      pillBar.innerHTML = `
        <div class="juri-nav-btn-wrapper menu-wrapper" id="juriMenuWrapper">
          <div class="selector"></div>
          <a href="#" id="juriMenuToggle" role="button" aria-label="Toggle Menu">Menu</a>
        </div>
        <a href="/ecom/index.php" class="juri-nav-center-brand" aria-label="SSRINI Home">
          <img src="/ecom/store/assets/images/logo.png" alt="SSRINI" onerror="this.src='/ecom/landing/assets/images/logo.png'">
        </a>
        <div class="juri-nav-btn-wrapper cart-wrapper" id="juriCartWrapper">
          <div class="selector"></div>
          <a href="#" id="juriCartToggle" role="button" aria-label="View Shopping Bag">Cart (<span class="juri-cart-count">0</span>)</a>
        </div>
      `;

      // Insert as first child or append
      const container = navBar.querySelector('.nav-container');
      if (container) {
        container.appendChild(pillBar);
      } else {
        navBar.appendChild(pillBar);
      }
    }

    // 4. Inject 1:1 Juri Fullscreen Menu Overlay into document.body
    let overlay = document.getElementById('juriFullscreenMenu');
    if (!overlay) {
      overlay = document.createElement('aside');
      overlay.id = 'juriFullscreenMenu';
      overlay.className = 'juri-fullscreen-menu';
      overlay.setAttribute('aria-label', 'Mobile Editorial Navigation');
      overlay.setAttribute('role', 'dialog');
      overlay.setAttribute('aria-modal', 'true');

      const currentPath = window.location.pathname;
      const isHome = currentPath.endsWith('/ecom/') || currentPath.endsWith('/ecom/index.php');
      const isCollection = currentPath.includes('/collection/');
      const isStore = currentPath.includes('/store/') && !currentPath.includes('account.php');
      const isAbout = currentPath.includes('/about/');
      const isContact = currentPath.includes('/contact/');
      const isAccount = currentPath.includes('account.php') || currentPath.includes('/auth/');

      overlay.innerHTML = `
        <div class="juri-fullscreen-links">
          <a href="/ecom/auth/index.php?redirect=${encodeURIComponent(window.location.pathname + window.location.search)}" class="juri-fullscreen-link ${isAccount ? 'active' : ''}" id="juriMenuAccountLink">Account</a>
          
          <div class="juri-collections-accordion" id="juriCollectionsAccordion">
            <div class="juri-collections-header" id="juriCollectionsHeader">
              <span class="juri-fullscreen-link ${isCollection ? 'active' : ''}">Collections</span>
              <svg class="juri-collections-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 12 15 18 9"></polyline>
              </svg>
            </div>
            <div class="juri-collections-sublist" id="juriCollectionsSublist">
              <a href="/ecom/collection/index.php" class="juri-subcollection-link">All Collections</a>
              <a href="/ecom/collection/index.php#heritage" class="juri-subcollection-link">Rivulet Heritage</a>
              <a href="/ecom/collection/index.php#terracotta" class="juri-subcollection-link">Terracotta & Earth</a>
              <a href="/ecom/collection/index.php#filigree" class="juri-subcollection-link">Silver Filigree</a>
              <a href="/ecom/collection/index.php#silk" class="juri-subcollection-link">Silk & Metal Weave</a>
            </div>
          </div>

          <a href="/ecom/store/index.php" class="juri-fullscreen-link ${isStore ? 'active' : ''}">Shop</a>
          <a href="/ecom/about/index.php" class="juri-fullscreen-link ${isAbout ? 'active' : ''}">About</a>
          <a href="/ecom/contact/index.php" class="juri-fullscreen-link ${isContact ? 'active' : ''}">Contacts</a>
        </div>

        <div class="juri-fullscreen-bottom">
          <a href="mailto:contact@ssrini.com" class="juri-bottom-link">contact@ssrini.com</a>
          <a href="https://wa.me/919836081994" target="_blank" rel="noopener noreferrer" class="juri-bottom-link">+91 9836081994</a>
        </div>
      `;

      document.body.appendChild(overlay);
    }

    // 5. Setup Toggle and State Handling
    const menuToggle = document.getElementById('juriMenuToggle');
    const menuWrapper = document.getElementById('juriMenuWrapper');
    const cartToggle = document.getElementById('juriCartToggle');
    const collectionsHeader = document.getElementById('juriCollectionsHeader');
    const collectionsAccordion = document.getElementById('juriCollectionsAccordion');
    const accountLink = document.getElementById('juriMenuAccountLink');

    let isMenuOpen = false;

    function openMenu() {
      isMenuOpen = true;
      overlay.classList.add('is-open');
      menuWrapper.classList.add('active');
      menuToggle.textContent = 'Close';
      document.body.classList.add('juri-menu-open');
    }

    function closeMenu() {
      isMenuOpen = false;
      overlay.classList.remove('is-open');
      menuWrapper.classList.remove('active');
      menuToggle.textContent = 'Menu';
      document.body.classList.remove('juri-menu-open');
    }

    if (menuToggle) {
      menuToggle.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        if (isMenuOpen) {
          closeMenu();
        } else {
          openMenu();
        }
      });
    }

    // Cart link click: close menu and open cart drawer
    if (cartToggle) {
      cartToggle.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        if (isMenuOpen) {
          closeMenu();
        }
        if (typeof window.openCart === 'function') {
          window.openCart();
        } else {
          const shopCartBtn = document.getElementById('cart-indicator-link') || document.querySelector('.cart-indicator');
          if (shopCartBtn) {
            shopCartBtn.click();
          } else {
            window.location.href = '/ecom/store/index.php';
          }
        }
      });
    }

    // Collections Accordion Toggle
    if (collectionsHeader && collectionsAccordion) {
      collectionsHeader.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        collectionsAccordion.classList.toggle('is-expanded');
      });
    }

    // Close menu when clicking any link inside overlay (except accordion toggle)
    overlay.querySelectorAll('a').forEach(link => {
      link.addEventListener('click', function() {
        closeMenu();
      });
    });

    // Close on Escape key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && isMenuOpen) {
        closeMenu();
      }
    });

    // Close on resize to desktop
    window.addEventListener('resize', function() {
      if (window.innerWidth > 860 && isMenuOpen) {
        closeMenu();
      }
    });

    // 6. Check Auth status for Private Vault
    fetch('/ecom/api/auth.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'session_status' })
    })
    .then(res => res.json())
    .then(data => {
      if (data && data.loggedIn && data.customer && accountLink) {
        const firstName = data.customer.name.split(' ')[0];
        accountLink.href = '/ecom/store/account.php';
        accountLink.textContent = `My Vault (${firstName})`;
      }
    })
    .catch(() => {});

    // 7. Initialize and observe Cart Count
    updateAllCartCounts();
    window.addEventListener('storage', updateAllCartCounts);
    document.addEventListener('cart:updated', updateAllCartCounts);
    
    // Also periodically poll or observe changes to .cart-count in case cart sync updates it
    const observerTarget = document.querySelector('.cart-count');
    if (observerTarget) {
      const observer = new MutationObserver(updateAllCartCounts);
      observer.observe(observerTarget, { childList: true, characterData: true, subtree: true });
    }
  }

  // Self initialize on DOM readiness
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initJuriMobileNav);
  } else {
    initJuriMobileNav();
  }
})();
