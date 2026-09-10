/**
 * SSRINI Product Detail Page (PDP) Media Carousel, Zoom, Accordions & Cart Engine
 */

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
  const productId = urlParams.get('id');

  if (!productId) {
    document.body.innerHTML = '<div style="text-align:center; padding:50px;">Product not found.</div>';
    return;
  }

  fetch(`http://localhost:8080/api/store-products.php?id=${productId}`)
    .then(res => res.json())
    .then(data => {
      if (data.success && data.data.length > 0) {
        const p = data.data[0];
        const product = {
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
          },
          stock: p.stock_quantity !== undefined ? parseInt(p.stock_quantity) : 1
        };

        renderProductUI(product);
        // We can't fetch related directly without another call, so we'll fetch all active products for related
        fetchRelatedProducts(product.id);
      } else {
        document.body.innerHTML = '<div style="text-align:center; padding:50px;">Product not found.</div>';
      }
    })
    .catch(err => {
      console.error('Error fetching product:', err);
      document.body.innerHTML = '<div style="text-align:center; padding:50px; color:red;">Network error loading product.</div>';
    });
}

function renderProductUI(product) {
  // Update Page Meta & Breadcrumbs
  document.title = `${product.title} | SSRINI Handcrafts`;
  const crumbCollection = document.querySelector('#crumb-collection');
  const crumbTitle = document.querySelector('#crumb-title');
  if (crumbCollection) crumbCollection.textContent = product.collection;
  if (crumbTitle) crumbTitle.textContent = product.title;

  // Update Content
  const colTag = document.querySelector('#pdp-collection-tag');
  const title = document.querySelector('#pdp-title');
  const priceUSD = document.querySelector('#pdp-price-usd');
  const priceINR = document.querySelector('#pdp-price-inr');
  const desc = document.querySelector('#pdp-desc');

  if (colTag) colTag.textContent = product.collection;
  if (title) title.textContent = product.title;
  if (priceUSD) priceUSD.textContent = `$${product.priceUSD}`;
  if (priceINR) priceINR.textContent = `₹${product.priceINR.toLocaleString()}`;
  if (desc) desc.textContent = product.description;

  // Specs
  const specHours = document.querySelector('#spec-hours');
  const specOrigin = document.querySelector('#spec-origin');
  const specDim = document.querySelector('#spec-dim');
  const specWeight = document.querySelector('#spec-weight');

  if (specHours) specHours.textContent = product.specs.craftHours;
  if (specOrigin) specOrigin.textContent = product.specs.origin;
  if (specDim) specDim.textContent = product.specs.dimensions;
  if (specWeight) specWeight.textContent = product.specs.weight;

  // Gallery Setup
  setupGallery(product.images, product.title);

  // Setup Add to Cart
  setupAddToCart(product);
}

// Editorial Scroll Gallery Setup (Vertical Photo Stack)
function setupGallery(images, title) {
  const gallery = document.querySelector('#pdp-scroll-gallery');
  if (!gallery) return;

  gallery.innerHTML = images.map((imgSrc, i) => `
    <div class="pdp-gallery-card" data-index="${i}">
      <img src="${imgSrc}" alt="${title} View ${i + 1}" loading="${i === 0 ? 'eager' : 'lazy'}">
    </div>
  `).join('');
}

// Quantity & Add to Cart
function setupAddToCart(product) {
  let qty = 1;
  const qtyVal = document.querySelector('#qty-val');
  const decBtn = document.querySelector('#qty-dec');
  const incBtn = document.querySelector('#qty-inc');
  const addBtn = document.querySelector('#btn-add-to-bag');

  if (addBtn) {
    if (product.stock <= 0) {
      addBtn.textContent = 'Out of Stock';
      addBtn.disabled = true;
      addBtn.style.opacity = '0.5';
      addBtn.style.cursor = 'not-allowed';
      if (incBtn) incBtn.disabled = true;
      if (decBtn) decBtn.disabled = true;
      return;
    }
  }

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
      if (qty < product.stock) {
        qty++;
        qtyVal.textContent = qty;
      }
    });
  }

  if (addBtn) {
    addBtn.addEventListener('click', () => {
      if (window.addToCart) {
        window.addToCart({
          id: product.id,
          title: product.title,
          price: product.priceUSD,
          image: product.images[0],
          qty: qty
        });
      } else {
        let cart = JSON.parse(localStorage.getItem('ssrini_cart') || '[]');
        const existing = cart.find(c => c.id === product.id);
        if (existing) {
          existing.qty += qty;
        } else {
          cart.push({ id: product.id, title: product.title, price: product.priceUSD, qty: qty, image: product.images[0] });
        }
        localStorage.setItem('ssrini_cart', JSON.stringify(cart));
        initCartCount();
      }

      addBtn.innerHTML = '<span>✓ Added to Bag</span>';
      addBtn.classList.add('added');

      setTimeout(() => {
        addBtn.innerHTML = '<span>Add to Bag</span>';
        addBtn.classList.remove('added');
      }, 1500);
    });
  }
}

// Accordion Toggles
function initAccordions() {
  const items = document.querySelectorAll('.accordion-item');
  items.forEach(item => {
    const header = item.querySelector('.accordion-header');
    if (header) {
      header.addEventListener('click', () => {
        const isActive = item.classList.contains('active');
        items.forEach(i => i.classList.remove('active'));
        if (!isActive) {
          item.classList.add('active');
        }
      });
    }
  });
}

// Related Products
function setupRelated(currentId) {
  const relatedGrid = document.querySelector('#pdp-related-grid');
  if (!relatedGrid) return;

  const others = PRODUCTS_DATA.filter(p => p.id !== currentId).slice(0, 4);

  relatedGrid.innerHTML = others.map(item => `
    <article class="product-card">
      <div class="product-image-box">
        <span class="product-badge">${item.badge}</span>
        <a href="product.html?id=${item.id}">
          <img src="${item.images[0]}" alt="${item.title}" class="img-primary" loading="lazy">
          <img src="${item.images[1] || item.images[0]}" alt="${item.title} Detail" class="img-secondary" loading="lazy">
        </a>
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

function fetchRelatedProducts(currentId) {
  fetch('http://localhost:8080/api/store-products.php')
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        const others = data.data.filter(p => String(p.id) !== String(currentId)).slice(0, 4).map(p => ({
          id: p.id,
          title: p.name,
          collection: p.collection_name || 'Heritage Collection',
          material: p.material || 'Premium Materials',
          priceUSD: p.price_usd || Math.round(p.price / 80),
          priceINR: p.price,
          badge: p.badge || '',
          images: (p.gallery_images && p.gallery_images.length > 0) ? 
                    p.gallery_images.map(img => `http://localhost:8080/uploads/products/${img}`) : 
                    [ p.image ? `http://localhost:8080/uploads/products/${p.image}` : '../collection/assets/images/placeholder.jpg' ]
        }));
        
        const relatedGrid = document.querySelector('#pdp-related-grid');
        if (!relatedGrid) return;
        
        relatedGrid.innerHTML = others.map(item => `
          <article class="product-card">
            <div class="product-image-box">
              <span class="product-badge">${item.badge}</span>
              <a href="product.html?id=${item.id}">
                <img src="${item.images[0]}" alt="${item.title}" class="img-primary" loading="lazy">
                <img src="${item.images[1] || item.images[0]}" alt="${item.title} Detail" class="img-secondary" loading="lazy">
              </a>
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
    })
    .catch(err => console.error('Error fetching related products:', err));
}
