/**
 * 1:1 Juri Slide-Over Cart Drawer Module
 * Global Cart Management & Drawer Engine with ArrowBigLeftDashIcon & Persistent Controls
 */

(function () {
  'use strict';

  // Helper to compute correct paths relative to current directory
  function getStorePath(file) {
    const p = window.location.pathname;
    if (p.includes('/store/')) {
      return './' + file;
    }
    const segments = p.split('/').filter(Boolean);
    if (segments.length > 0 && segments[segments.length - 1].endsWith('.html')) {
      segments.pop();
    }
    if (segments.length >= 1) {
      return '../store/' + file;
    }
    return 'store/' + file;
  }

  // Helper to load CSS dynamically if not present
  function ensureCSS() {
    if (!document.getElementById('cart-drawer-css')) {
      const link = document.createElement('link');
      link.id = 'cart-drawer-css';
      link.rel = 'stylesheet';
      link.href = getStorePath('css/cart-drawer.css');
      document.head.appendChild(link);
    }
  }

  // Get Cart from localStorage
  function getCart() {
    try {
      return JSON.parse(localStorage.getItem('ssrini_cart') || '[]');
    } catch (e) {
      return [];
    }
  }

  // Save Cart to localStorage
  function saveCart(cart) {
    localStorage.setItem('ssrini_cart', JSON.stringify(cart));
    updateGlobalCounters();
    renderCartDrawer();
  }

  // Sync Cart Counter badges across navbar
  function updateGlobalCounters() {
    const cart = getCart();
    const totalQty = cart.reduce((sum, item) => sum + (item.qty || 1), 0);
    const countEls = document.querySelectorAll('.cart-count, .cart-counter');
    countEls.forEach(el => {
      el.textContent = totalQty;
    });
  }

  function updateGlobalAuthState() {
    const user = JSON.parse(localStorage.getItem('ssrini_user') || 'null');
    const authBtn = document.getElementById('navAuthBtn');
    
    if (user && authBtn) {
      authBtn.innerHTML = `
        <svg class="nav-auth-icon" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
          <circle cx="12" cy="7" r="4"></circle>
        </svg>
        <span>${user.first_name || 'My Account'}</span>
      `;
    }
  }

  // Inject Drawer DOM Structure into <body>
  function injectDrawerHTML() {
    if (document.getElementById('cart-drawer-panel')) return;

    const backdrop = document.createElement('div');
    backdrop.id = 'cart-drawer-backdrop';
    backdrop.className = 'cart-drawer-backdrop';

    const panel = document.createElement('div');
    panel.id = 'cart-drawer-panel';
    panel.className = 'cart-drawer-panel';
    panel.setAttribute('role', 'dialog');
    panel.setAttribute('aria-label', 'Shopping Cart');

    panel.innerHTML = `
      <div class="cart-drawer-header">
        <button type="button" class="cart-drawer-close-btn" id="cart-drawer-close" aria-label="Return / Close Cart">
          <svg xmlns="http://www.w3.org/2000/svg" width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shadcn-hover-icon icon-arrow-left-dash">
            <path class="arrow" d="M13 9a1 1 0 0 1-1-1V5.061a1 1 0 0 0-1.811-.75l-6.835 6.836a1.207 1.207 0 0 0 0 1.707l6.835 6.835a1 1 0 0 0 1.811-.75V16a1 1 0 0 1 1-1h2a1 1 0 0 0 1-1v-4a1 1 0 0 0-1-1z" style="transform-origin: center;" />
            <path class="dash" d="M20 9v6" />
          </svg>
        </button>
        <h2 class="cart-drawer-title">Your Cart</h2>
      </div>

      <div class="cart-drawer-top-actions" id="cart-drawer-top-actions">
        <button type="button" class="cart-drawer-btn cart-drawer-btn-secondary" id="btn-keep-shopping">Keep Shopping</button>
        <button type="button" class="cart-drawer-btn cart-drawer-btn-primary" id="btn-top-checkout">Proceed to Checkout</button>
      </div>

      <div class="cart-drawer-body" id="cart-drawer-body">
        <!-- Rendered dynamically -->
      </div>

      <div class="cart-drawer-footer" id="cart-drawer-footer">
        <button type="button" class="cart-checkout-btn-full" id="btn-bottom-checkout">Proceed to Checkout</button>
      </div>
    `;

    document.body.appendChild(backdrop);
    document.body.appendChild(panel);

    // Event listeners for close & backdrop
    backdrop.addEventListener('click', closeCart);
    document.getElementById('cart-drawer-close').addEventListener('click', closeCart);
    document.getElementById('btn-keep-shopping').addEventListener('click', closeCart);

    const checkoutAction = (e) => {
      const cart = getCart();
      if (!cart || cart.length === 0) {
        if (e) {
          e.preventDefault();
          e.stopPropagation();
        }
        return false;
      }
      window.location.href = getStorePath('checkout.html');
    };

    document.getElementById('btn-top-checkout').addEventListener('click', checkoutAction);
    document.getElementById('btn-bottom-checkout').addEventListener('click', checkoutAction);
  }

  // Open Cart Drawer
  window.openCart = function () {
    injectDrawerHTML();
    renderCartDrawer();
    const backdrop = document.getElementById('cart-drawer-backdrop');
    const panel = document.getElementById('cart-drawer-panel');
    if (backdrop && panel) {
      backdrop.classList.add('active');
      panel.classList.add('active');
      document.body.style.overflow = 'hidden';
    }
  };

  // Close Cart Drawer
  window.closeCart = function () {
    const backdrop = document.getElementById('cart-drawer-backdrop');
    const panel = document.getElementById('cart-drawer-panel');
    if (backdrop && panel) {
      backdrop.classList.remove('active');
      panel.classList.remove('active');
      document.body.style.overflow = '';
    }
  };

  // Add Item to Cart
  window.addToCart = function (product) {
    const cart = getCart();
    const existingIndex = cart.findIndex(item => item.id === product.id);

    if (existingIndex > -1) {
      cart[existingIndex].qty = (cart[existingIndex].qty || 1) + (product.qty || 1);
    } else {
      cart.push({
        id: product.id || 'berry-ring-s1',
        title: product.title || 'Berry Ring Silver',
        price: product.price || product.priceUSD || 523.00,
        image: product.image || '../store/assets/images/IMG_0685.jpg',
        size: product.size || 'free',
        weight: product.weight || '1 LBS',
        qty: product.qty || 1
      });
    }

    saveCart(cart);
    openCart();
  };

  // Remove Item from Cart
  function removeItem(index) {
    const cart = getCart();
    cart.splice(index, 1);
    saveCart(cart);
  }

  // Update Item Quantity
  function updateQty(index, delta) {
    const cart = getCart();
    if (cart[index]) {
      cart[index].qty = (cart[index].qty || 1) + delta;
      if (cart[index].qty <= 0) {
        cart.splice(index, 1);
      }
      saveCart(cart);
    }
  }

  // Render Cart Contents inside Drawer
  function renderCartDrawer() {
    const bodyEl = document.getElementById('cart-drawer-body');
    const topActionsEl = document.getElementById('cart-drawer-top-actions');
    const footerEl = document.getElementById('cart-drawer-footer');
    const topCheckoutBtn = document.getElementById('btn-top-checkout');
    const bottomCheckoutBtn = document.getElementById('btn-bottom-checkout');

    if (!bodyEl) return;

    // Top action buttons ALWAYS stay visible at top of drawer
    if (topActionsEl) topActionsEl.style.display = 'flex';

    const cart = getCart();
    const totalQty = cart.reduce((sum, item) => sum + (item.qty || 1), 0);
    const subtotal = cart.reduce((sum, item) => sum + ((item.price || 0) * (item.qty || 1)), 0);

    const storeUrl = getStorePath('index.html');

    if (cart.length === 0) {
      // LOCK CHECKOUT BUTTONS (disabled state, grey styling, blocked click)
      if (topCheckoutBtn) {
        topCheckoutBtn.disabled = true;
        topCheckoutBtn.classList.add('disabled');
        topCheckoutBtn.setAttribute('aria-disabled', 'true');
        topCheckoutBtn.title = 'Shopping bag is empty. Add items to checkout.';
      }
      if (bottomCheckoutBtn) {
        bottomCheckoutBtn.disabled = true;
        bottomCheckoutBtn.classList.add('disabled');
        bottomCheckoutBtn.setAttribute('aria-disabled', 'true');
        bottomCheckoutBtn.title = 'Shopping bag is empty. Add items to checkout.';
      }

      // EMPTY CART VIEW
      if (footerEl) footerEl.style.display = 'none';

      bodyEl.innerHTML = `
        <div class="cart-drawer-order-count">0 Items in your order</div>
        <div class="cart-drawer-empty-box">
          <a href="${storeUrl}" onclick="closeCart();" class="cart-drawer-empty-link">
            YOUR SHOPPING CART IS EMPTY. CLICK HERE TO RETURN TO THE STORE.
          </a>
        </div>
        <div class="cart-drawer-summary-box" style="margin-top: auto;">
          <h3 class="cart-summary-title">Order Summary</h3>
          <div class="cart-summary-row">
            <span>Subtotal</span>
            <span>$0.00</span>
          </div>
          <div class="cart-summary-row">
            <span>Shipping & Handling</span>
            <span>TBD</span>
          </div>
          <div class="cart-summary-row total-row">
            <span>Order Total:</span>
            <span>$0.00</span>
          </div>
        </div>
      `;
    } else {
      // UNLOCK CHECKOUT BUTTONS (active state, restore luxury black aesthetic)
      if (topCheckoutBtn) {
        topCheckoutBtn.disabled = false;
        topCheckoutBtn.classList.remove('disabled');
        topCheckoutBtn.removeAttribute('aria-disabled');
        topCheckoutBtn.title = 'Proceed to Checkout';
      }
      if (bottomCheckoutBtn) {
        bottomCheckoutBtn.disabled = false;
        bottomCheckoutBtn.classList.remove('disabled');
        bottomCheckoutBtn.removeAttribute('aria-disabled');
        bottomCheckoutBtn.title = 'Proceed to Checkout';
      }

      // POPULATED CART VIEW
      if (footerEl) footerEl.style.display = 'block';

      let itemsHTML = '';
      cart.forEach((item, index) => {
        const itemTotal = ((item.price || 0) * (item.qty || 1)).toFixed(2);
        itemsHTML += `
          <div class="cart-drawer-item-row" data-index="${index}">
            <img src="${item.image}" alt="${item.title}" class="cart-item-thumb">
            <div class="cart-item-details">
              <h4 class="cart-item-title">${item.title}</h4>
              <div class="cart-item-meta">size: ${item.size || 'free'}</div>
              <div class="cart-item-meta">Weight: ${item.weight || '1 LBS'}</div>
              <button type="button" class="cart-item-remove-link" data-remove-index="${index}">REMOVE THIS ITEM</button>
              <div class="cart-item-price-qty">
                <span class="cart-item-price">$${itemTotal}</span>
                <div class="cart-qty-selector">
                  <button type="button" class="cart-qty-btn" data-qty-dec="${index}">-</button>
                  <span class="cart-qty-val">${item.qty || 1}</span>
                  <button type="button" class="cart-qty-btn" data-qty-inc="${index}">+</button>
                </div>
              </div>
            </div>
          </div>
        `;
      });

      bodyEl.innerHTML = `
        <div class="cart-drawer-order-count">${totalQty} ${totalQty === 1 ? 'Item' : 'Items'} in your order</div>
        
        <div class="cart-drawer-items-list">
          ${itemsHTML}
        </div>

        <div class="cart-drawer-shipping-box">
          <h4 class="cart-shipping-title">Calculate Shipping</h4>
          <div class="cart-shipping-fields">
            <input type="text" class="cart-input-field" placeholder="PIN Code" id="cart-pin-code">
            <select class="cart-input-field" id="cart-country-select">
              <option value="IN">India</option>
              <option value="US">United States</option>

              <option value="UK">United Kingdom</option>
              <option value="CA">Canada</option>
              <option value="AU">Australia</option>
            </select>
          </div>
        </div>

        <div class="cart-drawer-summary-box">
          <h3 class="cart-summary-title">Order Summary</h3>
          <div class="cart-summary-row">
            <span>Subtotal</span>
            <span>$${subtotal.toFixed(2)}</span>
          </div>
          <div class="cart-summary-row">
            <span>Shipping & Handling</span>
            <span>TBD</span>
          </div>
          <div class="cart-summary-row total-row">
            <span>Order Total:</span>
            <span>$${subtotal.toFixed(2)}</span>
          </div>
        </div>
      `;

      // Attach item actions
      bodyEl.querySelectorAll('[data-remove-index]').forEach(btn => {
        btn.addEventListener('click', (e) => {
          const idx = parseInt(e.target.getAttribute('data-remove-index'), 10);
          removeItem(idx);
        });
      });

      bodyEl.querySelectorAll('[data-qty-dec]').forEach(btn => {
        btn.addEventListener('click', (e) => {
          const idx = parseInt(e.target.getAttribute('data-qty-dec'), 10);
          updateQty(idx, -1);
        });
      });

      bodyEl.querySelectorAll('[data-qty-inc]').forEach(btn => {
        btn.addEventListener('click', (e) => {
          const idx = parseInt(e.target.getAttribute('data-qty-inc'), 10);
          updateQty(idx, 1);
        });
      });
    }
  }

  // Initialize Global Event Listeners
  document.addEventListener('DOMContentLoaded', () => {
    ensureCSS();
    injectDrawerHTML();
    updateGlobalCounters();
    updateGlobalAuthState();

    // Delegate clicks on cart triggers
    document.addEventListener('click', (e) => {
      const trigger = e.target.closest('.cart-icon-wrapper, .cart-indicator, [data-open-cart]');
      if (trigger) {
        e.preventDefault();
        openCart();
      }
    });
  });

})();
