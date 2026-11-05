/**
 * 1:1 Juri Product Detail Page (PDP) Engine
 * Handles dynamic product rendering, vertical image scroll stack, sticky info panel, and accordions.
 */

const PRODUCTS_DATA = window.PRODUCTS_DATA || window.PHP_PRODUCTS_DATA || [];

document.addEventListener('DOMContentLoaded', () => {
  initCartCount();
  loadProductDetails();
  initAccordions();
});

function initCartCount() {
  const cart = JSON.parse(localStorage.getItem('ssrini_cart') || '[]');
  const countEls = document.querySelectorAll('.cart-count, .cart-counter');
  countEls.forEach(el => {
    el.textContent = cart.reduce((sum, item) => sum + (item.qty || 1), 0);
  });
}

function loadProductDetails() {
  const urlParams = new URLSearchParams(window.location.search);
  const urlId = urlParams.get('id');
  const productId = urlId || (PRODUCTS_DATA.length > 0 ? PRODUCTS_DATA[0].id : null);

  // Find product from PRODUCTS_DATA array (by slug, id, or db_id)
  let product = PRODUCTS_DATA.find(p => p.id === productId || String(p.db_id) === String(productId) || p.slug === productId);
  
  // Redirect or handle empty product if not found
  if (!product) {
    document.body.innerHTML = '<h1 style="text-align:center; padding:100px;">Product not found in database.</h1>';
    return;
  }

  // Update Page Meta & Breadcrumbs
  document.title = `${product.title} | SSRINI Handcrafts`;
  const crumbCollection = document.querySelector('#crumb-collection');
  const crumbTitle = document.querySelector('#crumb-title');
  if (crumbCollection) crumbCollection.textContent = product.collection;
  if (crumbTitle) crumbTitle.textContent = product.title;

  // Update Product Details
  const titleEl = document.querySelector('#pdp-title');
  const priceINREl = document.querySelector('#pdp-price-inr');
  const collectionTagEl = document.querySelector('#pdp-collection-tag');
  const descEl = document.querySelector('#pdp-desc');

  if (titleEl) titleEl.textContent = product.title;
  if (priceINREl) priceINREl.textContent = `₹${product.priceINR.toLocaleString()}`;
  if (collectionTagEl) collectionTagEl.textContent = product.collection;
  if (descEl) descEl.textContent = product.description;

  // Specs
  const specHours = document.querySelector('#spec-hours');
  const specOrigin = document.querySelector('#spec-origin');
  const specDim = document.querySelector('#spec-dim');
  const specWeight = document.querySelector('#spec-weight');

  if (specHours) specHours.textContent = product.specs.craftHours;
  if (specOrigin) specOrigin.textContent = product.specs.origin;
  if (specDim) specDim.textContent = product.specs.dimensions;
  if (specWeight) specWeight.textContent = product.specs.weight;

  // Accordions
  const accCraft = document.querySelector('#acc-craft');
  const accCare = document.querySelector('#acc-care');
  const accDelivery = document.querySelector('#acc-delivery');
  
  if (accCraft && product.accordions.craftsmanship) accCraft.textContent = product.accordions.craftsmanship;
  if (accCare && product.accordions.care) accCare.textContent = product.accordions.care;
  if (accDelivery && product.accordions.delivery) accDelivery.textContent = product.accordions.delivery;

  // Render Vertical Image Scroll Stack (NO zoom, NO carousel)
  setupVerticalGallery(product.images, product.title);

  // Add to Cart Logic
  setupAddToCart(product);

  // Mobile Sticky Buy Bar
  setupMobileStickyBar(product);

  // Related Products Grid
  setupRelated(product.id);
}

// Mobile Sticky Buy Bar Engine
function setupMobileStickyBar(product) {
  let stickyBar = document.getElementById('mobileStickyBuyBar');
  if (!stickyBar) {
    stickyBar = document.createElement('div');
    stickyBar.id = 'mobileStickyBuyBar';
    stickyBar.className = 'mobile-pdp-sticky-bar';
    stickyBar.innerHTML = `
      <div class="sticky-bar-info">
        <span class="sticky-bar-title">${product.title}</span>
        <span class="sticky-bar-price">₹${product.priceINR.toLocaleString()}</span>
      </div>
      <button type="button" class="sticky-bar-buy-btn" id="stickyBarBuyBtn">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
          <line x1="3" y1="6" x2="21" y2="6"></line>
          <path d="M16 10a4 4 0 0 1-8 0"></path>
        </svg>
        <span>Add to Bag</span>
      </button>
    `;
    document.body.appendChild(stickyBar);

    const stickyBuyBtn = document.getElementById('stickyBarBuyBtn');
    if (stickyBuyBtn) {
      stickyBuyBtn.addEventListener('click', () => {
        const addBtn = document.querySelector('#btn-add-to-bag');
        if (addBtn) addBtn.click();
        if (window.openCart) window.openCart();
      });
    }

    const mainActionBtn = document.querySelector('#btn-add-to-bag');
    if (mainActionBtn && 'IntersectionObserver' in window) {
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (!entry.isIntersecting && entry.boundingClientRect.top < 0) {
            stickyBar.classList.add('visible');
          } else {
            stickyBar.classList.remove('visible');
          }
        });
      }, { threshold: 0.1 });
      observer.observe(mainActionBtn);
    }
  }
}


// Render vertical high-res image scroll stack matching Juri PDP
function setupVerticalGallery(images, title) {
  const stackContainer = document.querySelector('#pdp-scroll-gallery');
  if (!stackContainer) return;

  if (!images || images.length === 0) {
    stackContainer.innerHTML = `<img src="assets/images/IMG_0685.jpg" alt="${title}" class="pdp-vertical-image-item">`;
    return;
  }

  stackContainer.innerHTML = images.map((imgSrc, idx) => `
    <img src="${imgSrc}" alt="${title} View ${idx + 1}" class="pdp-vertical-image-item" loading="${idx === 0 ? 'eager' : 'lazy'}">
  `).join('');
}

// Quantity & Add to Cart
function setupAddToCart(product) {
  let qty = 1;
  const qtyVal = document.querySelector('#qty-val');
  const decBtn = document.querySelector('#qty-dec');
  const incBtn = document.querySelector('#qty-inc');
  const addBtn = document.querySelector('#btn-add-to-bag');

  if (decBtn && qtyVal) {
    decBtn.addEventListener('click', () => {
      if (qty > 1) {
        qty--;
        qtyVal.textContent = qty;
      }
    });
  }

  if (incBtn && qtyVal) {
    incBtn.addEventListener('click', () => {
      qty++;
      qtyVal.textContent = qty;
    });
  }

  if (addBtn) {
    addBtn.addEventListener('click', () => {
      let cart = JSON.parse(localStorage.getItem('ssrini_cart') || '[]');
      const existing = cart.find(c => c.id === product.id);
      if (existing) {
        existing.qty += qty;
      } else {
        cart.push({
          id: product.id,
          title: product.title,
          priceINR: product.priceINR,
          qty: qty,
          image: product.images[0]
        });
      }

      localStorage.setItem('ssrini_cart', JSON.stringify(cart));
      initCartCount();

      addBtn.innerHTML = '<span>✓ ADDED TO CART</span>';
      addBtn.classList.add('added');

      setTimeout(() => {
        addBtn.innerHTML = '<span>ADD TO CART</span>';
        addBtn.classList.remove('added');
      }, 2000);
    });
  }
}

// Juri Accordion Expand / Collapse
function initAccordions() {
  const accordionItems = document.querySelectorAll('.accordion-item');
  accordionItems.forEach(item => {
    const header = item.querySelector('.accordion-header');
    if (header) {
      header.addEventListener('click', () => {
        const isActive = item.classList.contains('active');
        accordionItems.forEach(i => i.classList.remove('active'));
        if (!isActive) {
          item.classList.add('active');
        }
      });
    }
  });
}

// Related Masterpieces Grid
function setupRelated(currentId) {
  const relatedGrid = document.querySelector('#pdp-related-grid');
  if (!relatedGrid) return;

  const others = PRODUCTS_DATA.filter(p => p.id !== currentId).slice(0, 4);

  relatedGrid.innerHTML = others.map(item => `
    <article class="product-card">
      <div class="product-image-box">
        <span class="product-badge">${item.badge}</span>
        <a href="product.php?id=${item.id}">
          <img src="${item.images[0]}" alt="${item.title}" class="img-primary" loading="lazy">
          <img src="${item.images[1] || item.images[0]}" alt="${item.title} Detail" class="img-secondary" loading="lazy">
        </a>
        <button class="quickview-btn" onclick="window.openQuickView('${item.id}')">Quick View</button>
      </div>
      <div class="product-info">
        <span class="product-category">${item.category}</span>
        <h3 class="product-title">
          <a href="product.php?id=${item.id}">${item.title}</a>
        </h3>
        <p class="product-collection">${item.collection}</p>
        <div class="product-price-row">
          <span class="price-inr">₹${item.priceINR.toLocaleString()}</span>
        </div>
      </div>
    </article>
  `).join('');
}
