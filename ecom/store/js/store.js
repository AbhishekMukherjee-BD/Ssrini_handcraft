/**
 * SSRINI Store Catalog Management, Filtering, Sorting & QuickView Engine
 */

// Master Products Dataset
let PRODUCTS_DATA = [];

// State
let activeFilters = {
  collections: [],
  categories: [],
  materials: [],
  search: ''
};
let currentSort = 'newest'; // Changed default to match API
let currentCols = 4;

document.addEventListener('DOMContentLoaded', () => {
  initCart();
  initControls();
  initFilterDrawer();
  fetchProducts(); // Fetch from API on load
});

function fetchProducts() {
  const grid = document.querySelector('#product-grid');
  if (grid) {
    grid.innerHTML = '<div style="grid-column: 1 / -1; text-align: center; padding: 40px;"><p>Loading masterworks...</p></div>';
  }

  fetch('http://localhost:8080/api/store-products.php')
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        // Map backend product structure to frontend structure
        PRODUCTS_DATA = data.data.map(p => ({
          id: p.id,
          title: p.name,
          collection: p.collection_name || 'Heritage Collection',
          category: p.category_name || 'Uncategorized',
          material: p.material || 'Premium Materials',
          priceUSD: p.price_usd || Math.round(p.price / 80),
          priceINR: p.price,
          badge: p.badge || '',
          images: (p.gallery_images && p.gallery_images.length > 0) ? 
                    p.gallery_images.map(img => `http://localhost:8080/uploads/products/${img}`) : 
                    [ p.image ? `http://localhost:8080/uploads/products/${p.image}` : '../collection/assets/images/placeholder.jpg' ],
          description: p.description || '',
          specs: {
            craftHours: p.craft_hours ? `${p.craft_hours} Hours` : 'N/A',
            origin: p.origin || 'Studio SSRINI',
            dimensions: p.dimensions || 'Standard',
            weight: p.weight ? `${p.weight} kg` : 'N/A'
          }
        }));
        
        // Populate filter options dynamically based on available data
        populateFilterOptions();
        renderProducts();
      } else {
        if (grid) grid.innerHTML = '<div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: red;"><p>Failed to load products.</p></div>';
      }
    })
    .catch(err => {
      console.error('Error fetching products:', err);
      if (grid) grid.innerHTML = '<div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: red;"><p>Network error loading products.</p></div>';
    });
}

function populateFilterOptions() {
  const collectionList = document.querySelector('.filter-group:nth-child(1) .filter-options-list');
  const categoryList = document.querySelector('.filter-group:nth-child(2) .filter-options-list');
  const materialList = document.querySelector('.filter-group:nth-child(3) .filter-options-list');

  if (!collectionList || !categoryList || !materialList) return;

  const collections = [...new Set(PRODUCTS_DATA.map(p => p.collection).filter(Boolean))].sort();
  const categories = [...new Set(PRODUCTS_DATA.map(p => p.category).filter(Boolean))].sort();
  
  // Materials might be comma-separated or complex strings, let's just get unique strings for now
  // or we can split them if they contain '&' or ',' but for simplicity, exact match
  const materials = [...new Set(PRODUCTS_DATA.map(p => p.material).filter(Boolean))].sort();

  const createCheckbox = (name, value) => `
    <label class="filter-checkbox-label">
      <input type="checkbox" name="filter-${name}" value="${value}">
      <span>${value}</span>
    </label>
  `;

  collectionList.innerHTML = collections.map(c => createCheckbox('collection', c)).join('');
  categoryList.innerHTML = categories.map(c => createCheckbox('category', c)).join('');
  materialList.innerHTML = materials.map(m => createCheckbox('material', m)).join('');

  // Re-attach event listeners to new checkboxes
  const checkboxes = document.querySelectorAll('.filter-checkbox-label input');
  checkboxes.forEach(cb => {
    cb.addEventListener('change', () => {
      updateFilterState();
    });
  });
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
  // Sort Buttons (Name / Price)
  const sortBtns = document.querySelectorAll('.sort-button');
  sortBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      const type = btn.dataset.sort;
      if (currentSort === type) {
        // Toggle direction
        if (type.startsWith('name')) {
          currentSort = currentSort === 'name-asc' ? 'name-desc' : 'name-asc';
          btn.querySelector('.sort-dir').textContent = currentSort === 'name-asc' ? '(A-Z)' : '(Z-A)';
        } else if (type.startsWith('price')) {
          currentSort = currentSort === 'price-asc' ? 'price-desc' : 'price-asc';
          btn.querySelector('.sort-dir').textContent = currentSort === 'price-asc' ? '(ASC)' : '(DESC)';
        }
      } else {
        currentSort = type;
      }

      sortBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      renderProducts();
    });
  });

  // Grid Layout Switchers matching Juri (#three-cols, #four-cols)
  const colsSelectors = document.querySelectorAll('.cols-selector');
  const grid = document.querySelector('#product-grid');

  colsSelectors.forEach(selector => {
    selector.addEventListener('click', () => {
      colsSelectors.forEach(s => s.classList.remove('cols-active'));
      selector.classList.add('cols-active');
      const cols = parseInt(selector.dataset.cols, 10);
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

  // Checkbox inputs
  const checkboxes = document.querySelectorAll('.filter-checkbox-label input');
  checkboxes.forEach(cb => {
    cb.addEventListener('change', () => {
      updateFilterState();
    });
  });

  if (applyBtn && overlay) {
    applyBtn.addEventListener('click', () => {
      overlay.classList.remove('active');
      renderProducts();
    });
  }

  if (resetBtn) {
    resetBtn.addEventListener('click', () => {
      checkboxes.forEach(cb => cb.checked = false);
      activeFilters.collections = [];
      activeFilters.categories = [];
      activeFilters.materials = [];
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
    if (activeFilters.search && !item.title.toLowerCase().includes(activeFilters.search) && !item.collection.toLowerCase().includes(activeFilters.search)) {
      return false;
    }
    return true;
  });

  // Sort
  if (currentSort === 'price-asc') {
    list.sort((a, b) => a.priceUSD - b.priceUSD);
  } else if (currentSort === 'price-desc') {
    list.sort((a, b) => b.priceUSD - a.priceUSD);
  } else if (currentSort === 'name-asc') {
    list.sort((a, b) => a.title.localeCompare(b.title));
  }

  if (countBadge) {
    countBadge.textContent = `${list.length} Masterpiece${list.length === 1 ? '' : 's'}`;
  }

  if (list.length === 0) {
    grid.innerHTML = `
      <div style="grid-column: 1 / -1; text-align: center; padding: 80px 20px;">
        <p style="font-family: var(--sans-serif); font-size: 20px; color: var(--dark); margin-bottom: 12px;">No matching artifacts found</p>
        <p style="color: var(--light); font-size: 13px;">Try clearing filters or searching for different materials.</p>
      </div>
    `;
    return;
  }

  grid.innerHTML = list.map(item => `
    <article class="product-card">
      <div class="product-image-box">
        <span class="product-badge">${item.badge}</span>
        <a href="product.html?id=${item.id}">
          <img src="${item.images[0]}" alt="${item.title}" class="img-primary" loading="lazy">
          <img src="${item.images[1] || item.images[0]}" alt="${item.title} Detail" class="img-secondary" loading="lazy">
        </a>
        <button class="quickview-btn" onclick="openQuickView('${item.id}')">Quick View</button>
      </div>
      <div class="product-info">
        <span class="product-collection-tag">${item.collection}</span>
        <a href="product.html?id=${item.id}" class="product-title">${item.title}</a>
        <p class="product-material-desc">${item.material}</p>
        <div class="product-price-row">
          <span class="product-price">$${item.priceUSD}</span>
          <span class="product-currency-inr">₹${item.priceINR.toLocaleString()}</span>
        </div>
      </div>
    </article>
  `).join('');
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
        <span style="font-family: var(--sans-serif); font-size: 24px; font-weight: 700; color: var(--dark);">$${item.priceUSD}</span>
        <span style="color: var(--light); font-size: 13px;">₹${item.priceINR.toLocaleString()}</span>
      </div>
      <p style="font-size: 13px; line-height: 1.6; color: #555;">${item.description}</p>
      <div style="display: flex; flex-direction: column; gap: 6px; font-size: 12px; color: var(--dark); background: var(--card-bg); padding: 14px; border-radius: 4px;">
        <div><strong>Craft Time:</strong> ${item.specs.craftHours}</div>
        <div><strong>Origin:</strong> ${item.specs.origin}</div>
        <div><strong>Dimensions:</strong> ${item.specs.dimensions}</div>
      </div>
      <div style="display: flex; gap: 12px; margin-top: 8px;">
        <button class="btn-add-to-bag" onclick="addToCartQuick('${item.id}')">Add To Bag</button>
        <a href="product.html?id=${item.id}" class="filter-btn-toggle" style="padding: 14px 20px;">Full Details →</a>
      </div>
    </div>
  `;

  modalOverlay.classList.add('active');
};

window.closeQuickView = function() {
  const modalOverlay = document.querySelector('#quickview-modal-overlay');
  if (modalOverlay) modalOverlay.classList.remove('active');
};

window.addToCartQuick = function(id) {
  const item = PRODUCTS_DATA.find(p => p.id === id);
  if (!item) return;

  let cart = JSON.parse(localStorage.getItem('ssrini_cart') || '[]');
  const existing = cart.find(c => c.id === id);
  if (existing) {
    existing.qty = (existing.qty || 1) + 1;
  } else {
    cart.push({ id: item.id, title: item.title, priceUSD: item.priceUSD, qty: 1, image: item.images[0] });
  }

  localStorage.setItem('ssrini_cart', JSON.stringify(cart));
  initCart();

  const btn = document.querySelector('#quickview-modal-content .btn-add-to-bag');
  if (btn) {
    btn.textContent = '✓ Added to Bag';
    btn.classList.add('added');
    setTimeout(() => {
      closeQuickView();
    }, 800);
  }
};
