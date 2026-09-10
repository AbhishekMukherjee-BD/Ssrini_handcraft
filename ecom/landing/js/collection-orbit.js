/**
 * SSRINI Luxury Handcrafts - Collection Orbit Showcase Engine
 * Recreates the exact mathematical radial positioning from track-lurch-60325667.figma.site
 * with dynamic image cycling and guaranteed center logo breathing room.
 */

(function () {
  'use strict';

  // 1. DYNAMIC ASSET REGISTRY (The 7 preloader craftsmanship images)
  const CRAFTSMANSHIP_IMAGES = [
    'preloader_images/1.jpg',
    'preloader_images/2.jpeg',
    'preloader_images/3.jpeg',
    'preloader_images/4.jpeg',
    'preloader_images/5.jpeg',
    'preloader_images/6.jpeg',
    'preloader_images/7.jpeg'
  ];

  // 2. DEFAULT STATE MATCHING FIGMA SYSTEM
  const state = {
    amount: 25,
    layoutType: 'Radial',
    imageRatio: '1:1',
    alignment: 'on', // 'on' | 'inside' | 'outside'
    sizeProfile: 'center', // 'center' | 'linear' | 'edges' | 'random'
    images: [...CRAFTSMANSHIP_IMAGES],
    layoutParams: {
      radius: 380,
      spacing: 50,
      sizeMin: 0.4,
      sizeMax: 1.6,
      rotation: 0,
      variance: 0
    },
    // Viewport dimensions
    viewportWidth: window.innerWidth,
    viewportHeight: window.innerHeight
  };

  // DOM Elements cache
  let containerEl = null;
  let clearanceRingEl = null;
  let cardElements = [];

  // 3. EXACT MATHEMATICAL POSITIONING FUNCTION FROM FIGMA COMPONENT
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
    const results = [];
    let cardW = 120;
    let cardH = 120;

    const [u, h] = imageRatio.split(':').map(Number);
    if (u && h) {
      if (u > h) {
        cardW = 120;
        cardH = (120 * h) / u;
      } else {
        cardH = 120;
        cardW = (120 * u) / h;
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

    const sizeMax = params.sizeMax ?? 1.5;
    const sizeMin = params.sizeMin ?? 0.5;
    const variance = params.variance ?? 0;
    const sizeRange = sizeMax - sizeMin;

    for (let c = 0; c < amount; c++) {
      const progress = amount > 1 ? c / (amount - 1) : 0;
      let x = 0, y = 0, rot = 0, scale = 1, zIndex = c;

      // Size profile evaluation (Figma exact switch)
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

      // Variance perturbation
      const varianceOffset = (pseudoRandom(c * 42) - 0.5) * 2 * variance * sizeRange;
      scale = Math.max(0.1, scale + varianceOffset);

      let normX = 0, normY = 0;

      // Radial layout calculation
      if (layoutType === 'Radial') {
        const angle = amount > 1 ? (c / amount) * 2 * Math.PI : 0;
        const effectiveRadius = (params.radius || 380) * ((params.spacing || 50) / 50);
        x = Math.cos(angle) * effectiveRadius;
        y = Math.sin(angle) * effectiveRadius;
        normX = Math.cos(angle);
        normY = Math.sin(angle);
      }

      rot += params.rotation || 0;

      // Card alignment offset relative to circle circumference
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

    for (const item of rawItems) {
      results.push({
        ...item,
        x: centerX + item.x - offsetX,
        y: centerY + item.y - offsetY
      });
    }

    return results;
  }

  // 4. RESPONSIVE RADIUS CALCULATION (Guaranteeing logo breathing gap on any display)
  function computeAdaptiveRadius() {
    const w = window.innerWidth;
    const h = window.innerHeight;
    const minDim = Math.min(w, h);

    // Adaptive radius scales gracefully with viewport dimensions
    // Desktop: ~380-420px. Laptop (1366x768): ~300-340px.
    const idealRadius = Math.round(minDim * 0.38);
    return Math.max(280, Math.min(idealRadius, 440));
  }

  // 5. DOM CREATION & MOUNTING
  function buildCardDOM(index, item, imageUrl) {
    const card = document.createElement('div');
    card.className = 'orbit-card';
    card.id = item.id;
    card.setAttribute('data-index', index);

    // Apply exact 3D positioning transform
    applyCardTransform(card, item);

    const inner = document.createElement('div');
    inner.className = 'orbit-card-inner';

    const img = document.createElement('img');
    img.src = imageUrl;
    img.alt = `Rivulet Artifact ${index + 1}`;
    img.className = 'orbit-card-img';
    img.loading = 'eager';

    const indexTag = document.createElement('span');
    indexTag.className = 'card-index-tag';
    indexTag.textContent = String(index + 1).padStart(2, '0');

    const shine = document.createElement('div');
    shine.className = 'card-glass-shine';

    inner.appendChild(img);
    inner.appendChild(shine);
    inner.appendChild(indexTag);
    card.appendChild(inner);

    return card;
  }

  function applyCardTransform(cardEl, item) {
    const left = item.x - item.width / 2;
    const top = item.y - item.height / 2;

    cardEl.style.transform = `translate3d(${left.toFixed(2)}px, ${top.toFixed(2)}px, 0px) rotate(${item.rotation}deg) scale(${item.scale.toFixed(3)})`;
    cardEl.style.width = `${item.width}px`;
    cardEl.style.height = `${item.height}px`;
    cardEl.style.zIndex = item.zIndex;
  }

  // 6. RENDER & UPDATE PIPELINE
  function renderOrbit() {
    if (!containerEl) return;

    state.viewportWidth = window.innerWidth;
    state.viewportHeight = window.innerHeight;

    // Calculate item positions via algorithm
    const positions = calculatePositions(
      state.amount,
      state.viewportWidth,
      state.viewportHeight,
      state.layoutType,
      state.imageRatio,
      state.alignment,
      state.sizeProfile,
      state.layoutParams
    );

    // If card elements count doesn't match amount, rebuild DOM list
    if (cardElements.length !== state.amount) {
      containerEl.innerHTML = '';
      cardElements = [];

      positions.forEach((item, idx) => {
        // Dynamic cycling: 7 images repeated to fill the 25 spots (images[idx % images.length])
        const imgUrl = state.images[idx % Math.max(1, state.images.length)];
        const cardEl = buildCardDOM(idx, item, imgUrl);
        containerEl.appendChild(cardEl);
        cardElements.push(cardEl);
      });
    } else {
      // Update transforms smoothly without rebuilding DOM
      positions.forEach((item, idx) => {
        const cardEl = cardElements[idx];
        if (cardEl) {
          applyCardTransform(cardEl, item);
        }
      });
    }

    // Update clearance ring diameter visually
    if (clearanceRingEl) {
      const ringDiameter = (state.layoutParams.radius * 2) - 100;
      clearanceRingEl.style.width = `${Math.max(220, ringDiameter)}px`;
      clearanceRingEl.style.height = `${Math.max(220, ringDiameter)}px`;
    }
  }

  // 7. INTERACTIVE PARAMETER CONTROLS SETUP
  function setupControls() {
    const radiusSlider = document.getElementById('control-radius');
    const radiusVal = document.getElementById('val-radius');
    const spacingSlider = document.getElementById('control-spacing');
    const spacingVal = document.getElementById('val-spacing');
    const scaleMaxSlider = document.getElementById('control-scalemax');
    const scaleMaxVal = document.getElementById('val-scalemax');
    const resetBtn = document.getElementById('btn-reset-orbit');

    if (radiusSlider && radiusVal) {
      radiusSlider.value = state.layoutParams.radius;
      radiusVal.textContent = `${state.layoutParams.radius}px`;

      radiusSlider.addEventListener('input', (e) => {
        const val = parseInt(e.target.value, 10);
        state.layoutParams.radius = val;
        radiusVal.textContent = `${val}px`;
        renderOrbit();
      });
    }

    if (spacingSlider && spacingVal) {
      spacingSlider.value = state.layoutParams.spacing;
      spacingVal.textContent = state.layoutParams.spacing;

      spacingSlider.addEventListener('input', (e) => {
        const val = parseInt(e.target.value, 10);
        state.layoutParams.spacing = val;
        spacingVal.textContent = val;
        renderOrbit();
      });
    }

    if (scaleMaxSlider && scaleMaxVal) {
      scaleMaxSlider.value = state.layoutParams.sizeMax;
      scaleMaxVal.textContent = state.layoutParams.sizeMax.toFixed(1);

      scaleMaxSlider.addEventListener('input', (e) => {
        const val = parseFloat(e.target.value);
        state.layoutParams.sizeMax = val;
        scaleMaxVal.textContent = val.toFixed(1);
        renderOrbit();
      });
    }

    if (resetBtn) {
      resetBtn.addEventListener('click', () => {
        state.layoutParams.radius = computeAdaptiveRadius();
        state.layoutParams.spacing = 50;
        state.layoutParams.sizeMax = 1.6;
        state.layoutParams.sizeMin = 0.4;

        if (radiusSlider) radiusSlider.value = state.layoutParams.radius;
        if (radiusVal) radiusVal.textContent = `${state.layoutParams.radius}px`;
        if (spacingSlider) spacingSlider.value = 50;
        if (spacingVal) spacingVal.textContent = '50';
        if (scaleMaxSlider) scaleMaxSlider.value = 1.6;
        if (scaleMaxVal) scaleMaxVal.textContent = '1.6';

        renderOrbit();
      });
    }
  }

  // 8. INITIALIZATION
  function init() {
    containerEl = document.getElementById('cards-orbit-container');
    clearanceRingEl = document.getElementById('center-clearance-ring');

    if (!containerEl) {
      console.warn('[CollectionOrbit] Container #cards-orbit-container not found in DOM.');
      return;
    }

    // Set initial adaptive radius
    state.layoutParams.radius = computeAdaptiveRadius();

    // Render cards
    renderOrbit();

    // Setup interactive HUD
    setupControls();

    // Responsive resize listener
    let resizeTimer = null;
    window.addEventListener('resize', () => {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(() => {
        state.layoutParams.radius = computeAdaptiveRadius();
        const radiusSlider = document.getElementById('control-radius');
        const radiusVal = document.getElementById('val-radius');
        if (radiusSlider && radiusVal) {
          radiusSlider.value = state.layoutParams.radius;
          radiusVal.textContent = `${state.layoutParams.radius}px`;
        }
        renderOrbit();
      }, 60);
    });

    // Expose public API on window for external scripting / future animation hooks
    window.SSRINIOrbitCollection = {
      getState: () => ({ ...state }),
      setImages: (newImages) => {
        if (Array.isArray(newImages) && newImages.length > 0) {
          state.images = [...newImages];
          cardElements.length = 0; // Force full re-render with new images
          renderOrbit();
        }
      },
      setParams: (newParams) => {
        Object.assign(state.layoutParams, newParams);
        renderOrbit();
      },
      recalculate: renderOrbit
    };

    console.log('[SSRINI Collection Orbit] Engine successfully mounted with 25 cards.');
  }

  // Run on DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
