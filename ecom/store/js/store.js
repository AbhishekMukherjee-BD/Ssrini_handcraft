/**
 * SSRINI Store Catalog Management, Filtering, Sorting & QuickView Engine
 */

// Master Products Dataset
const PRODUCTS_DATA = window.PRODUCTS_DATA || window.PHP_PRODUCTS_DATA || [];

// State
let activeFilters = {
  collections: [],
  categories: [],
  materials: [],
  search: ''
};
let currentSort = 'featured';
let currentCols = 4;

document.addEventListener('DOMContentLoaded', () => {
  initCart();
  initControls();
  generateFilters();
  renderProducts();
  initFilterDrawer();
  initQuickViewModal();
});

function generateFilters() {
  const collections = new Set();
  const categories = new Set();
  const materials = new Set();

  PRODUCTS_DATA.forEach(p => {
    if (p.collection && p.collection !== 'All') collections.add(p.collection);
    if (p.category && p.category !== 'All') categories.add(p.category);
    if (p.material) materials.add(p.material);
  });

  const buildCheckboxHTML = (name, set) => {
    return Array.from(set).sort().map(val => `
      <label class="filter-checkbox-label">
        <input type="checkbox" name="${name}" value="${val}">
        <span>${val}</span>
      </label>
    `).join('');
  };

  const colContainer = document.querySelector('#filter-list-collections');
  if (colContainer) colContainer.innerHTML = buildCheckboxHTML('filter-collection', collections);

  const catContainer = document.querySelector('#filter-list-categories');
  if (catContainer) catContainer.innerHTML = buildCheckboxHTML('filter-category', categories);

  const matContainer = document.querySelector('#filter-list-materials');
  if (matContainer) matContainer.innerHTML = buildCheckboxHTML('filter-material', materials);
}

// Initialize Cart Count from LocalStorage
function initCart() {
  const cart = JSON.parse(localStorage.getItem('ssrini_cart') || '[]');
  const countEls = document.querySelectorAll('.cart-count, .cart-counter');
  countEls.forEach(el => {
    el.textContent = cart.reduce((sum, item) => sum + (item.qty || 1), 0);
  });
}

// Controls & Event Listeners
function initControls() {
  const searchInput = document.querySelector('#store-search-input');
  const suggestionsBox = document.querySelector('#search-suggestions');
  
  if (searchInput && suggestionsBox) {
    // Populate suggestions dynamically from unique categories
    const categories = window.PHP_PRODUCTS_DATA 
      ? [...new Set(window.PHP_PRODUCTS_DATA.map(p => p.categoryName || p.category_id || p.category))]
      : [];
      
    // Create a mapping or extract names
    let availableCategories = categories.filter(c => c && typeof c === 'string');
    
    // Fallback static if needed, but this uses dynamic
    if (availableCategories.length === 0 && window.PHP_CATEGORIES_DATA) {
      availableCategories = window.PHP_CATEGORIES_DATA.map(c => c.name);
    }
    
    // Default list if nothing could be found (to ensure suggestion feature works)
    if (availableCategories.length === 0) {
       availableCategories = ["Sculptures", "Paintings", "Metal Craft", "Wood Craft", "Home Decor"];
    }

    searchInput.addEventListener('input', (e) => {
      const val = e.target.value.toLowerCase().trim();
      activeFilters.search = val;
      renderProducts();
      
      // Update suggestions
      if (val.length > 0) {
        const matches = availableCategories.filter(c => c.toLowerCase().includes(val));
        if (matches.length > 0) {
          suggestionsBox.innerHTML = matches.map(c => `<li>${c}</li>`).join('');
          suggestionsBox.classList.add('active');
        } else {
          suggestionsBox.classList.remove('active');
        }
      } else {
        suggestionsBox.classList.remove('active');
      }
    });

    // Also show all suggestions on focus if empty
    searchInput.addEventListener('focus', (e) => {
      const val = e.target.value.toLowerCase().trim();
      if (val.length === 0 && availableCategories.length > 0) {
        suggestionsBox.innerHTML = availableCategories.map(c => `<li>${c}</li>`).join('');
        suggestionsBox.classList.add('active');
      }
    });

    // Handle clicking a suggestion
    suggestionsBox.addEventListener('click', (e) => {
      if (e.target.tagName === 'LI') {
        const selected = e.target.textContent;
        searchInput.value = selected;
        activeFilters.search = selected.toLowerCase();
        suggestionsBox.classList.remove('active');
        renderProducts();
      }
    });

    // Hide suggestions when clicking outside
    document.addEventListener('click', (e) => {
      if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
        suggestionsBox.classList.remove('active');
      }
    });
  }

  const sortBtns = document.querySelectorAll('.sort-button');
  sortBtns.forEach(btn => {
    btn.addEventListener('click', (e) => {
      sortBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      currentSort = btn.dataset.sort;
      renderProducts();
    });
  });

  const priceMin = document.querySelector('#price-min');
  const priceMax = document.querySelector('#price-max');
  const priceGoBtn = document.querySelector('#price-go-btn');
  if (priceMin && priceMax && priceGoBtn) {
    const applyPriceRange = () => {
      activeFilters.minPrice = priceMin.value ? parseFloat(priceMin.value) : null;
      activeFilters.maxPrice = priceMax.value ? parseFloat(priceMax.value) : null;
      renderProducts();
    };
    priceGoBtn.addEventListener('click', applyPriceRange);
    priceMin.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') applyPriceRange();
    });
    priceMax.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') applyPriceRange();
    });
  }

  const layoutBtns = document.querySelectorAll('.cols-selector');
  const grid = document.querySelector('#product-grid');
  layoutBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      layoutBtns.forEach(b => b.classList.remove('cols-active'));
      btn.classList.add('cols-active');
      const cols = parseInt(btn.dataset.cols, 10);
      currentCols = cols;
      if (grid) {
        grid.className = `product-grid cols-${cols}`;
      }
    });
  });
}

// Filter Drawer Controller
function initFilterDrawer() {
  const toggleBtn = document.querySelector('#toggle-filter-drawer');
  const overlay = document.querySelector('#filter-drawer-overlay');
  const closeBtn = document.querySelector('#close-filter-drawer');
  const applyBtn = document.querySelector('#apply-filters');
  const resetBtn = document.querySelector('#reset-filters');

  if (toggleBtn && overlay) {
    toggleBtn.addEventListener('click', () => overlay.classList.add('active'));
  }

  if (closeBtn && overlay) {
    closeBtn.addEventListener('click', () => overlay.classList.remove('active'));
  }

  if (overlay) {
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) overlay.classList.remove('active');
    });
  }

  // Checkbox inputs (using event delegation since they are dynamic now)
  if (overlay) {
    overlay.addEventListener('change', (e) => {
      if (e.target.matches('.filter-checkbox-label input')) {
        updateFilterState();
      }
    });
  }

  if (applyBtn && overlay) {
    applyBtn.addEventListener('click', () => {
      overlay.classList.remove('active');
      renderProducts();
    });
  }

  if (resetBtn) {
    resetBtn.addEventListener('click', () => {
      const checkboxes = document.querySelectorAll('.filter-checkbox-label input');
      checkboxes.forEach(cb => cb.checked = false);
      
      const priceMin = document.querySelector('#price-min');
      const priceMax = document.querySelector('#price-max');
      if (priceMin) priceMin.value = '';
      if (priceMax) priceMax.value = '';

      const searchInput = document.querySelector('#store-search-input');
      if (searchInput) searchInput.value = '';

      activeFilters.collections = [];
      activeFilters.categories = [];
      activeFilters.materials = [];
      activeFilters.minPrice = null;
      activeFilters.maxPrice = null;
      activeFilters.search = '';
      
      renderFilterPills();
      renderProducts();
    });
  }
}

function updateFilterState() {
  const collectionCbs = document.querySelectorAll('input[name="filter-collection"]:checked');
  const categoryCbs = document.querySelectorAll('input[name="filter-category"]:checked');
  const materialCbs = document.querySelectorAll('input[name="filter-material"]:checked');

  activeFilters.collections = Array.from(collectionCbs).map(cb => cb.value);
  activeFilters.categories = Array.from(categoryCbs).map(cb => cb.value);
  activeFilters.materials = Array.from(materialCbs).map(cb => cb.value);

  const totalCount = activeFilters.collections.length + activeFilters.categories.length + activeFilters.materials.length;
  const badge = document.querySelector('#filter-badge-count');
  if (badge) {
    badge.textContent = totalCount;
    badge.style.display = totalCount > 0 ? 'inline-block' : 'none';
  }

  renderFilterPills();
  renderProducts();
}

function renderFilterPills() {
  const container = document.querySelector('#active-filter-pills');
  if (!container) return;

  const allActive = [
    ...activeFilters.collections.map(v => ({ type: 'collection', label: v })),
    ...activeFilters.categories.map(v => ({ type: 'category', label: v })),
    ...activeFilters.materials.map(v => ({ type: 'material', label: v }))
  ];

  if (allActive.length === 0) {
    container.innerHTML = '';
    return;
  }

  container.innerHTML = allActive.map(item => `
    <div class="filter-pill" onclick="removeFilter('${item.type}', '${item.label}')">
      <span>${item.label}</span>
      <span class="remove-pill">×</span>
    </div>
  `).join('');
}

window.removeFilter = function(type, value) {
  const cb = document.querySelector(`input[name="filter-${type}"][value="${value}"]`);
  if (cb) cb.checked = false;
  updateFilterState();
};

// Render Products Grid
function renderProducts() {
  const grid = document.querySelector('#product-grid');
  const countBadge = document.querySelector('#item-count-display');
  if (!grid) return;

  // Filter
  let list = PRODUCTS_DATA.filter(item => {
    if (activeFilters.collections.length > 0 && !activeFilters.collections.includes(item.collection)) {
      return false;
    }
    if (activeFilters.categories.length > 0 && !activeFilters.categories.includes(item.category)) {
      return false;
    }
    if (activeFilters.materials.length > 0 && !activeFilters.materials.some(m => item.material.includes(m))) {
      return false;
    }
    
    // Price range filters
    const price = parseFloat(item.priceINR);
    if (activeFilters.minPrice !== undefined && activeFilters.minPrice !== null && price < activeFilters.minPrice) {
      return false;
    }
    if (activeFilters.maxPrice !== undefined && activeFilters.maxPrice !== null && price > activeFilters.maxPrice) {
      return false;
    }

    if (activeFilters.search) {
      const searchStr = activeFilters.search;
      const titleMatch = item.title && item.title.toLowerCase().includes(searchStr);
      const collectionMatch = item.collection && item.collection.toLowerCase().includes(searchStr);
      const categoryMatch = item.category && item.category.toLowerCase().includes(searchStr);
      if (!titleMatch && !collectionMatch && !categoryMatch) {
        return false;
      }
    }
    return true;
  });

  // Sort
  if (currentSort === 'price-asc') {
    list.sort((a, b) => a.priceINR - b.priceINR);
  } else if (currentSort === 'price-desc') {
    list.sort((a, b) => b.priceINR - a.priceINR);
  } else if (currentSort === 'name-asc') {
    list.sort((a, b) => a.title.localeCompare(b.title));
  }

  if (countBadge) {
    countBadge.textContent = `${list.length} Masterpiece${list.length === 1 ? '' : 's'}`;
  }

  if (list.length === 0) {
    grid.innerHTML = `
      <div class="empty-state-container">
        <svg class="empty-state-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"></circle>
          <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
        <h3 class="empty-state-title">No Masterpieces Found</h3>
        <p class="empty-state-desc">We couldn't find any creations matching your search criteria. Please try adjusting your filters.</p>
        <button class="empty-state-btn" onclick="document.querySelector('#reset-filters').click();">Clear All Filters</button>
      </div>
    `;
    return;
  }

  grid.innerHTML = list.map(item => {
    const isWishlisted = window.PHP_WISHLIST_DATA && window.PHP_WISHLIST_DATA.includes(item.id);
    return `
    <article class="product-card">
      <div class="product-image-box">
        <span class="product-badge">${item.badge}</span>
        <button class="wishlist-btn ${isWishlisted ? 'active' : ''}" onclick="toggleWishlist('${item.id}', this)" aria-label="Toggle Wishlist">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="${isWishlisted ? 'currentColor' : 'none'}" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
          </svg>
        </button>
        <a href="product.php?id=${item.id}">
          <img src="${item.images[0]}" alt="${item.title}" class="img-primary skeleton-shimmer" loading="lazy" onload="this.classList.remove('skeleton-shimmer')">
          <img src="${item.images[1] || item.images[0]}" alt="${item.title} Detail" class="img-secondary skeleton-shimmer" loading="lazy" onload="this.classList.remove('skeleton-shimmer')">
        </a>
        <button class="quickview-btn" onclick="openQuickView('${item.id}')">Quick View</button>
      </div>
      <div class="product-info">
        <span class="product-collection-tag">${item.collection}</span>
        <a href="product.php?id=${item.id}" class="product-title">${item.title}</a>
        <p class="product-material-desc">${item.material}</p>
        <div class="product-price-row">
          <span class="product-currency-inr">₹${item.priceINR.toLocaleString()}</span>
        </div>
      </div>
    </article>
    `;
  }).join('');
}

// QuickView Modal Trigger
window.openQuickView = function(id) {
  const item = PRODUCTS_DATA.find(p => p.id === id);
  if (!item) return;

  const modalOverlay = document.querySelector('#quickview-modal-overlay');
  const modalContainer = document.querySelector('#quickview-modal-content');

  if (!modalOverlay || !modalContainer) return;

  modalContainer.innerHTML = `
    <button class="modal-close-btn" onclick="closeQuickView()">✕</button>
    <div style="position: relative; border-radius: 6px; overflow: hidden; background: #eaeef1;">
      <img src="${item.images[0]}" alt="${item.title}" style="width: 100%; height: 100%; object-fit: cover;">
    </div>
    <div style="display: flex; flex-direction: column; justify-content: center; gap: 20px;">
      <div>
        <span style="font-size: 10px; font-weight: 700; letter-spacing: 2px; color: var(--accent); text-transform: uppercase;">${item.collection}</span>
        <h2 style="font-family: var(--sans-serif); font-size: 26px; font-weight: 700; margin-top: 6px; color: var(--dark);">${item.title}</h2>
      </div>
      <div style="display: flex; align-items: baseline; gap: 12px;">
        <span style="font-family: var(--sans-serif); font-size: 24px; font-weight: 700; color: var(--dark);">₹${item.priceINR.toLocaleString()}</span>
      </div>
      <p style="font-size: 13px; line-height: 1.6; color: #555;">${item.description}</p>
      <div style="display: flex; flex-direction: column; gap: 6px; font-size: 12px; color: var(--dark); background: var(--card-bg); padding: 14px; border-radius: 4px;">
        <div><strong>Craft Time:</strong> ${item.specs.craftHours}</div>
        <div><strong>Origin:</strong> ${item.specs.origin}</div>
        <div><strong>Dimensions:</strong> ${item.specs.dimensions}</div>
      </div>
      <div style="display: flex; gap: 12px; margin-top: 8px;">
        <button class="btn-add-to-bag" onclick="addToCartQuick('${item.id}')">Add To Bag</button>
        <a href="product.php?id=${item.id}" class="filter-btn-toggle" style="padding: 14px 20px;">Full Details →</a>
      </div>
    </div>
  `;

  modalOverlay.classList.add('active');
};

window.closeQuickView = function() {
  const modalOverlay = document.querySelector('#quickview-modal-overlay');
  if (modalOverlay) modalOverlay.classList.remove('active');
};

function initQuickViewModal() {
  const modalOverlay = document.querySelector('#quickview-modal-overlay');
  if (modalOverlay) {
    modalOverlay.addEventListener('click', (e) => {
      if (e.target === modalOverlay) window.closeQuickView();
    });
  }
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') window.closeQuickView();
  });
}

window.addToCartQuick = function(id) {
  const item = PRODUCTS_DATA.find(p => p.id === id);
  if (!item) return;

  let cart = JSON.parse(localStorage.getItem('ssrini_cart') || '[]');
  const existing = cart.find(c => c.id === id);
  if (existing) {
    existing.qty = (existing.qty || 1) + 1;
  } else {
    cart.push({ id: item.id, title: item.title, price: item.priceINR, priceINR: item.priceINR, qty: 1, image: item.images[0] });
  }

  localStorage.setItem('ssrini_cart', JSON.stringify(cart));
  initCart();

  const btn = document.querySelector('#quickview-modal-content .btn-add-to-bag');
  if (btn) {
    btn.textContent = '✓ Added to Bag';
    btn.classList.add('added');
    setTimeout(() => {
      closeQuickView();
    }, 1000);
  }
};

window.toggleWishlist = async function(productId, btn) {
  try {
    const res = await fetch('../api/toggle_wishlist.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ product_id: productId })
    });
    const data = await res.json();
    if (data.success) {
      if (data.status === 'added') {
        btn.classList.add('active');
        btn.querySelector('svg').setAttribute('fill', 'currentColor');
        if (!window.PHP_WISHLIST_DATA.includes(productId)) {
          window.PHP_WISHLIST_DATA.push(productId);
        }
      } else {
        btn.classList.remove('active');
        btn.querySelector('svg').setAttribute('fill', 'none');
        window.PHP_WISHLIST_DATA = window.PHP_WISHLIST_DATA.filter(id => id !== productId);
      }
    } else {
      if (data.error === 'not_logged_in') {
        window.location.href = '../auth/login.php';
      } else {
        console.error('Wishlist error:', data.error);
      }
    }
  } catch (err) {
    console.error('Error toggling wishlist:', err);
  }
};
