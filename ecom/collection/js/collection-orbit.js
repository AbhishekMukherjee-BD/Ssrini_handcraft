/**
 * SSRINI Luxury Handcrafts — Collection Radial Orbit Engine
 * Exact Figma positioning algorithm with fixed settings:
 * Radius: 300, Spacing: 40, Max Scale: 2.4, Min Scale: 0.4, Spots: 25.
 * Pure image presentation with no number tags.
 */

(function () {
  'use strict';

  // 1. DYNAMIC CRAFTSMANSHIP ASSET REGISTRY (1000x1000 WebP at Quality 92 with Lanczos & Unsharp Mask)
  const CRAFTSMANSHIP_IMAGES = [
    'assets/images/craftsmanship/1.webp',
    'assets/images/craftsmanship/2.webp',
    'assets/images/craftsmanship/3.webp',
    'assets/images/craftsmanship/4.webp',
    'assets/images/craftsmanship/5.webp',
    'assets/images/craftsmanship/6.webp',
    'assets/images/craftsmanship/7.webp'
  ];

  // 2. FIXED ENGINE STATE (Settings permanently locked to 300 / 40 / 2.4)
  const state = {
    amount: 25,
    layoutType: 'Radial',
    imageRatio: '1:1',
    alignment: 'on',
    sizeProfile: 'center',
    images: [...CRAFTSMANSHIP_IMAGES],
    layoutParams: {
      radius: 340,        // Increased Setting: 340
      spacing: 45,       // Setting: 45
      sizeMin: 0.4,       // Min scale for edge cards
      sizeMax: 2.4,       // Fixed Setting: Max Scale 2.4
      rotation: 0,
      variance: 0
    },
    viewportWidth: window.innerWidth,
    viewportHeight: window.innerHeight
  };

  // DOM Elements Cache
  let containerEl = null;
  let cardElements = [];

  // 3. EXACT MATHEMATICAL POSITIONING FUNCTION FROM FIGMA ENGINE
  function calculatePositions(
    amount,
    width,
    height,
    layoutType,
    imageRatio,
    alignment,
    sizeProfile,
    params
  ) {
    const minDim = Math.min(width, height);
    // Dynamic scale factor relative to approved desktop baseline (minDim = 900)
    // Clamped between 0.38 (compact mobile) and 1.15 (4K displays)
    const scaleFactor = Math.max(0.38, Math.min(minDim / 900, 1.15));

    // Base card dimensions dynamically scaled with crisp minimum floor
    const baseCardSize = Math.max(80, Math.round(124 * scaleFactor));
    let cardW = baseCardSize;
    let cardH = baseCardSize;

    const [u, h] = imageRatio.split(':').map(Number);
    if (u && h) {
      if (u > h) {
        cardW = baseCardSize;
        cardH = Math.round((baseCardSize * h) / u);
      } else {
        cardH = baseCardSize;
        cardW = Math.round((baseCardSize * u) / h);
      }
    }

    const centerX = width / 2;
    const centerY = height / 2;

    const pseudoRandom = (seed) => {
      const k = Math.sin(seed++) * 1e4;
      return k - Math.floor(k);
    };

    const rawItems = [];
    let minX = Infinity, maxX = -Infinity, minY = Infinity, maxY = -Infinity;

    const sizeMax = params.sizeMax ?? 2.4;
    const sizeMin = params.sizeMin ?? 0.4;
    const variance = params.variance ?? 0;
    const sizeRange = sizeMax - sizeMin;

    for (let c = 0; c < amount; c++) {
      const progress = amount > 1 ? c / (amount - 1) : 0;
      let x = 0, y = 0, rot = 0, scale = 1, zIndex = c;

      // Center-weighted sizing curve
      switch (sizeProfile) {
        case 'linear':
          scale = sizeMin + progress * sizeRange;
          break;
        case 'center':
          scale = sizeMax - Math.abs(progress - 0.5) * 2 * sizeRange;
          break;
        case 'edges':
          scale = sizeMin + Math.abs(progress - 0.5) * 2 * sizeRange;
          break;
        case 'random':
          scale = sizeMin + pseudoRandom(c * 99) * sizeRange;
          break;
        default:
          scale = 1;
      }

      // Variance offset
      const varianceOffset = (pseudoRandom(c * 42) - 0.5) * 2 * variance * sizeRange;
      scale = Math.max(0.1, scale + varianceOffset);

      let normX = 0, normY = 0;

      // Radial layout trigonometry with proportional scaling across all devices
      if (layoutType === 'Radial') {
        // Rotate so that spot 6 sits PRECISELY at 90 deg (pi / 2, 6 o'clock bottom apex on center axis)
        const angle = Math.PI / 2 + ((c - 6) / amount) * 2 * Math.PI;
        const baseEffectiveRadius = (params.radius || 340) * ((params.spacing || 45) / 50);
        const effectiveRadius = Math.round(baseEffectiveRadius * scaleFactor);
        x = Math.cos(angle) * effectiveRadius;
        y = Math.sin(angle) * effectiveRadius;
        normX = Math.cos(angle);
        normY = Math.sin(angle);
      }

      rot += params.rotation || 0;

      // Alignment offset
      if (alignment !== 'on' && (normX !== 0 || normY !== 0)) {
        const offset = alignment === 'outside' ? (cardH * scale) / 2 : -(cardH * scale) / 2;
        x += normX * offset;
        y += normY * offset;
      }

      minX = Math.min(minX, x);
      maxX = Math.max(maxX, x);
      minY = Math.min(minY, y);
      maxY = Math.max(maxY, y);

      rawItems.push({
        id: `orbit-item-${c}`,
        imageIndex: c,
        x,
        y,
        rotation: rot,
        scale,
        zIndex,
        width: cardW,
        height: cardH
      });
    }

    // Figma normalization: centers the bounding box of items at (centerX, centerY)
    const offsetX = minX === Infinity ? 0 : (minX + maxX) / 2;
    const offsetY = minY === Infinity ? 0 : (minY + maxY) / 2;

    const results = [];
    for (const item of rawItems) {
      results.push({
        ...item,
        x: centerX + item.x - offsetX,
        y: centerY + item.y - offsetY
      });
    }

    return results;
  }

  // 4. DOM CARD BUILDER (With Rivulet glass shine, edge glow, and 2-second hold reveal)
  function buildCardDOM(index, item, imageUrl) {
    const card = document.createElement('div');
    card.className = 'orbit-card';
    card.id = item.id;
    card.setAttribute('data-index', index);

    applyCardTransform(card, item);

    const inner = document.createElement('div');
    inner.className = 'orbit-card-inner';

    const img = document.createElement('img');
    img.src = imageUrl;
    img.alt = `SSRINI Masterpiece ${index + 1}`;
    img.className = 'orbit-card-img';
    img.loading = 'eager';
    img.onerror = function() {
      const baseName = imageUrl.split('/').pop().replace('.webp', '');
      const ext = baseName === '1' ? 'jpg' : 'jpeg';
      this.onerror = () => { this.src = `../preloader_images/${baseName}.${ext}`; };
      this.src = `assets/images/craftsmanship/${baseName}.${ext}`;
    };

    // Crystal glass shine reflection (from rivulet.html)
    const shine = document.createElement('div');
    shine.className = 'card-glass-shine';

    inner.appendChild(img);
    inner.appendChild(shine);
    card.appendChild(inner);

    // Two-tier interactive state machine:
    // Tier 1: Immediate Rivulet hover (edge glow, subtle lift, image breathing)
    // Tier 2: Sustained hold (>= 1.0s) unlocks the full velvety Master Zoom
    let holdTimer = null;

    const startHold = () => {
      card.classList.add('is-hovered');
      if (holdTimer) clearTimeout(holdTimer);
      holdTimer = setTimeout(() => {
        card.classList.add('is-held-active');
      }, 1000);
    };

    const cancelHold = () => {
      if (holdTimer) {
        clearTimeout(holdTimer);
        holdTimer = null;
      }
      card.classList.remove('is-hovered');
      card.classList.remove('is-held-active');
    };

    card.addEventListener('mouseenter', startHold);
    card.addEventListener('mouseleave', cancelHold);

    // Smooth touch support for mobile & tablet (press & hold 1s)
    card.addEventListener('touchstart', startHold, { passive: true });
    card.addEventListener('touchend', cancelHold, { passive: true });
    card.addEventListener('touchcancel', cancelHold, { passive: true });

    return card;
  }

  // 5. TRANSFORM APPLIER (Sets positioning & CSS custom variables for uniform hover zooming)
  function applyCardTransform(el, item) {
    el.style.width = `${item.width}px`;
    el.style.height = `${item.height}px`;
    el.style.left = '0px';
    el.style.top = '0px';

    const x = Math.round(item.x - item.width / 2);
    const y = Math.round(item.y - item.height / 2);
    const scale = Number(item.scale.toFixed(3));
    const rot = Number(item.rotation.toFixed(2));
    // Uniform hover target scale across all 25 cards
    const hoverScale = Number((state.layoutParams.sizeMax || 2.4).toFixed(2));

    el.style.setProperty('--card-x', `${x}px`);
    el.style.setProperty('--card-y', `${y}px`);
    el.style.setProperty('--card-scale', scale);
    el.style.setProperty('--card-rot', `${rot}deg`);
    el.style.setProperty('--card-z', item.zIndex);
    el.style.setProperty('--hover-scale', hoverScale);

    el.style.zIndex = item.zIndex;
    el.style.transform = `translate3d(${x}px, ${y}px, 0) scale(${scale}) rotate(${rot}deg)`;
  }

  // 6. RENDER ORBIT PIPELINE
  function renderOrbit() {
    if (!containerEl) return;

    const viewportW = containerEl.clientWidth || window.innerWidth;
    const viewportH = containerEl.clientHeight || window.innerHeight;

    const positions = calculatePositions(
      state.amount,
      viewportW,
      viewportH,
      state.layoutType,
      state.imageRatio,
      state.alignment,
      state.sizeProfile,
      state.layoutParams
    );

    // Mount or reposition cards
    if (cardElements.length !== state.amount) {
      containerEl.innerHTML = '';
      cardElements = [];

      for (let i = 0; i < state.amount; i++) {
        const item = positions[i];
        // Spot 6 is at the bottom apex (theta ≈ 86.4 deg, X ≈ 0, Y ≈ +R), naturally pointing down
        const isHeroSpot = (i === 6);
        const imageUrl = isHeroSpot
          ? 'assets/images/craftsmanship/necklace-hero.webp'
          : state.images[i % state.images.length];
        const cardEl = buildCardDOM(i, item, imageUrl);
        if (isHeroSpot) {
          cardEl.id = 'orbit-hero-necklace-card';
          cardEl.setAttribute('data-hero', 'true');
        }
        containerEl.appendChild(cardEl);
        cardElements.push(cardEl);
      }
    } else {
      for (let i = 0; i < state.amount; i++) {
        applyCardTransform(cardElements[i], positions[i]);
      }
    }
  }

  // 7. INITIALIZATION
  function init() {
    containerEl = document.getElementById('cards-orbit-container');

    if (!containerEl) {
      console.warn('[CollectionOrbit] #cards-orbit-container not found in DOM.');
      return;
    }

    // Initial render of cards with fixed settings (Radius: 340, Spacing: 45, Max Scale: 2.4)
    renderOrbit();
    window.requestAnimationFrame(() => renderOrbit());
    window.addEventListener('load', () => renderOrbit(), { once: true });

    // Handle responsive resize gracefully across all devices and orientation changes
    let resizeTimer = null;
    const handleViewportChange = () => {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(() => {
        window.requestAnimationFrame(() => {
          renderOrbit();
        });
      }, 35);
    };

    window.addEventListener('resize', handleViewportChange, { passive: true });
    window.addEventListener('orientationchange', handleViewportChange, { passive: true });

    // Public API for future dynamic additions or scroll integration
    window.SSRINIOrbitCollection = {
      getState: () => ({ ...state }),
      setImages: (newImages) => {
        if (Array.isArray(newImages) && newImages.length > 0) {
          state.images = [...newImages];
          cardElements.length = 0;
          renderOrbit();
        }
      },
      recalculate: renderOrbit,
      getHeroCard: () => document.getElementById('orbit-hero-necklace-card') || cardElements[6],
      getHeroCardRect: () => {
        const el = document.getElementById('orbit-hero-necklace-card') || cardElements[6];
        if (!el) return null;
        return el.getBoundingClientRect();
      },
      setHeroCardVisibility: (visible) => {
        const el = document.getElementById('orbit-hero-necklace-card') || cardElements[6];
        if (el) {
          el.style.opacity = visible ? '1' : '0';
          el.style.pointerEvents = visible ? 'auto' : 'none';
        }
      }
    };

    console.log('[SSRINI Collection] Clean raw orbit mounted (Radius: 340, Spacing: 45, Max Scale: 2.4).');
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
