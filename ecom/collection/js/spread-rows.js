/**
 * SSRINI HAUTE JOAILLERIE — SPREAD ROWS MOTION ENGINE
 * Pure Code Recreation of animos.app "Spread Rows"
 * 
 * Authentic Card-Dealing Distribution Pattern:
 * - 0.00s - 0.70s: 1st Container (Handcrafted Royal Necklace) centered at 1:1 ratio
 * - Distribution Cascade (Continuous, Controlled Dealing Wave):
 *   • All cards are physically stacked in the center deck underneath the 1st container (opacity: 1)
 *   • Step 1: Middle row cards deal horizontally outward from the center deck (0ms - 200ms)
 *   • Step 2: Top row cards deal out from the deck, sliding UP and OUT in mirror symmetry (180ms - 380ms)
 *   • Step 3: Bottom row cards deal out from the deck, sliding DOWN and OUT in mirror symmetry (340ms - 540ms)
 *   • Smooth overlapping cascade: all rows are in fluid, controlled motion together!
 *   • Physical card glide easing: cubic-bezier(0.16, 1, 0.3, 1) over 0.95s per card
 * - Seamless Momentum Drift (1.50s+): Gentle smoothstep ramp into continuous opposing drift ribbons
 * 
 * Lightweight & Crisp:
 * - 100% GPU-accelerated CSS translate3d transforms, zero video/canvas overhead.
 * - 1000px WebP assets with 5x Retina supersampling for luxury jewelry clarity.
 */

(function () {
  'use strict';

  /* ==========================================================================
     1. MASTER CURATED CRAFTSMANSHIP DATA (8 HIGH-DPI ASSETS)
     ========================================================================== */
  const COLLECTION = [
    {
      id: 'necklace-hero',
      src: 'assets/images/craftsmanship/necklace-hero.webp',
      title: 'Royal Polki & Pearl Heritage Collar',
      tag: 'Haute Joaillerie · Hero Centerpiece',
      desc: 'A magnificent royal statement piece crafted in 22kt hallmarked gold, encrusted with syndicate uncut polki diamonds, natural Zambian emerald beads, and hand-strung South Sea baroque pearls.'
    },
    {
      id: 'piece-1',
      src: 'assets/images/craftsmanship/1.webp',
      title: 'Heritage Filigree Solitaire Band',
      tag: 'Artisan Atelier · Ring',
      desc: 'Precision hand-engraved gold filigree band centered around a certified brilliant-cut diamond solitaire, finished with antique millgrain edging.'
    },
    {
      id: 'piece-2',
      src: 'assets/images/craftsmanship/2.webp',
      title: 'Imperial Jadau Choker & Cascades',
      tag: 'Royal Court · Choker',
      desc: 'Intricate closed-back Kundan setting featuring floral meenakari enameling on reverse, set with untreated Burmese rubies and faceted emerald drops.'
    },
    {
      id: 'piece-3',
      src: 'assets/images/craftsmanship/3.webp',
      title: 'Nizam Heritage Polki Kada',
      tag: 'Dynasty Collection · Bangle',
      desc: 'Sculpted solid gold kada with screw clasp, lined with natural unheated rubies and diamond polki clusters.'
    },
    {
      id: 'piece-4',
      src: 'assets/images/craftsmanship/4.webp',
      title: 'Temple Architecture Jhumka Pendants',
      tag: 'Heritage South · Earrings',
      desc: 'Intricately articulated chandelier earrings inspired by Dravidian temple gopuram carvings and seed pearl hangings.'
    },
    {
      id: 'piece-5',
      src: 'assets/images/craftsmanship/5.webp',
      title: 'Peacock Motif Navratna Choker',
      tag: 'Masterpiece · Multi-Gem',
      desc: 'Featuring nine sacred astrological gemstones arranged in cosmic alignment with feather-carved gold plumage.'
    },
    {
      id: 'piece-6',
      src: 'assets/images/craftsmanship/6.webp',
      title: 'Mughal Blossom Royal Collar',
      tag: 'Imperial Archival · Collar',
      desc: 'A flexible mesh gold collar woven with micro-pearls and rose-cut diamonds, radiating regal majesty.'
    },
    {
      id: 'piece-7',
      src: 'assets/images/craftsmanship/7.webp',
      title: 'Artisan Hand-Engraved Wedding Band',
      tag: 'Timeless · Band',
      desc: 'Subtle high-polish brushed gold with hand-chiseled millgrain edges and pavé set micro diamonds.'
    }
  ];

  /* Mapping pieces across columns:
     - Row 0 (Top): Centered on Solitaire Ring (Piece 1)
     - Row 1 (Middle): Centered on Hero Necklace (Piece 0 - The Centerpiece Anchor)
     - Row 2 (Bottom): Centered on Imperial Jadau Choker (Piece 2)
  */
  function getPieceForCol(rowIndex, col) {
    if (rowIndex === 1 && col === 0) {
      return COLLECTION[0]; // Middle Row Center is strictly the Hero Necklace
    }
    if (rowIndex === 0 && col === 0) {
      return COLLECTION[1]; // Top Row Center
    }
    if (rowIndex === 2 && col === 0) {
      return COLLECTION[2]; // Bottom Row Center
    }

    if (rowIndex === 0) {
      const order = [COLLECTION[1], COLLECTION[3], COLLECTION[5], COLLECTION[7], COLLECTION[4], COLLECTION[6], COLLECTION[2], COLLECTION[0]];
      const idx = ((col % order.length) + order.length) % order.length;
      return order[idx];
    } else if (rowIndex === 1) {
      const order = [COLLECTION[0], COLLECTION[1], COLLECTION[2], COLLECTION[3], COLLECTION[4], COLLECTION[5], COLLECTION[6], COLLECTION[7]];
      const idx = ((col % order.length) + order.length) % order.length;
      return order[idx];
    } else {
      const order = [COLLECTION[2], COLLECTION[4], COLLECTION[6], COLLECTION[1], COLLECTION[3], COLLECTION[5], COLLECTION[7], COLLECTION[0]];
      const idx = ((col % order.length) + order.length) % order.length;
      return order[idx];
    }
  }

  /* ==========================================================================
     2. DOM ELEMENTS & APPLICATION STATE
     ========================================================================== */
  const viewport = document.getElementById('spreadViewport');
  const heroCard = document.getElementById('heroOriginCard');
  const breakoutLayer = document.getElementById('breakoutLayer');
  const rowsContainer = document.getElementById('spreadRowsContainer');

  const ribbonRow1 = document.getElementById('ribbonRow1');
  const ribbonRow2 = document.getElementById('ribbonRow2');
  const ribbonRow3 = document.getElementById('ribbonRow3');

  // HUD Controls
  const btnPlayPause = document.getElementById('btnPlayPause');
  const iconPause = document.getElementById('iconPause');
  const iconPlay = document.getElementById('iconPlay');
  const labelPlayPause = document.getElementById('labelPlayPause');
  const btnReplay = document.getElementById('btnReplay');
  const btnSpeed = document.getElementById('btnSpeed');
  const labelSpeed = document.getElementById('labelSpeed');
  const btnResetCenter = document.getElementById('btnResetCenter');

  // Modal Lightbox
  const modalOverlay = document.getElementById('spreadModal');
  const modalCloseBtn = document.getElementById('modalCloseBtn');
  const modalImg = document.getElementById('modalImg');
  const modalTag = document.getElementById('modalTag');
  const modalTitle = document.getElementById('modalTitle');
  const modalDesc = document.getElementById('modalDesc');

  // Engine State
  const State = {
    mode: 'HERO', // 'HERO' | 'BREAKOUT' | 'STREAMING'
    isPlaying: true,
    speedMultiplier: 1.0,
    hoveredRow: null,
    lastTimestamp: 0,
    streamingStartTime: 0,
    // Horizontal ribbon positions
    row1Offset: 0,
    row2Offset: 0,
    row3Offset: 0,
    // Drift Velocities (px/ms)
    v1: -0.045, // Row 1: Left
    v2: 0.045,  // Row 2: Right
    v3: -0.045, // Row 3: Left
    // Loop metrics
    cycleWidth: 0,
    baseCenterOffset: 0,
    halfCols: 14 // total 29 cards per ribbon
  };

  /* Helper to calculate responsive card dimensions */
  function getCardMetrics() {
    const cardSize = Math.min(Math.max(window.innerWidth * 0.16, 140), 190);
    const gap = 20; // 1.25rem in CSS
    const pitch = cardSize + gap;
    return { cardSize, gap, pitch };
  }

  /* ==========================================================================
     3. BUILD PERMANENT MOMENTUM RIBBONS
     ========================================================================== */
  function createCardElement(piece, rowIndex, col) {
    const card = document.createElement('div');
    card.className = 'spread-card';
    card.setAttribute('data-id', piece.id);
    card.setAttribute('data-row', rowIndex);
    card.setAttribute('data-col', col);
    card.setAttribute('role', 'button');
    card.setAttribute('tabindex', '0');
    card.setAttribute('aria-label', piece.title);

    card.innerHTML = `
      <div class="spread-card-inner">
        <img 
          src="${piece.src}" 
          alt="${piece.title}" 
          class="spread-card-img" 
          loading="eager"
          decoding="async"
        >
        <div class="spread-card-glint"></div>
      </div>
    `;

    card.addEventListener('mouseenter', () => {
      State.hoveredRow = rowIndex;
    });

    card.addEventListener('mouseleave', () => {
      if (State.hoveredRow === rowIndex) {
        State.hoveredRow = null;
      }
    });

    card.addEventListener('click', () => {
      openModal(piece);
    });

    return card;
  }

  function populateRibbons() {
    [ribbonRow1, ribbonRow2, ribbonRow3].forEach(r => (r.innerHTML = ''));

    const ribbons = [
      { element: ribbonRow1, rowIndex: 0 },
      { element: ribbonRow2, rowIndex: 1 },
      { element: ribbonRow3, rowIndex: 2 }
    ];

    const { halfCols } = State;

    ribbons.forEach(({ element, rowIndex }) => {
      // Build cards symmetrically around col 0
      for (let c = -halfCols; c <= halfCols; c++) {
        const piece = getPieceForCol(rowIndex, c);
        const card = createCardElement(piece, rowIndex, c);
        element.appendChild(card);
      }
    });

    measureRibbonMetrics();
  }

  function measureRibbonMetrics() {
    const { cardSize, pitch } = getCardMetrics();
    const { halfCols } = State;

    const col0Position = halfCols * pitch;
    const viewportCenter = window.innerWidth / 2;
    const baseOffset = (viewportCenter - cardSize / 2) - col0Position;

    State.baseCenterOffset = baseOffset;
    State.cycleWidth = 8 * pitch; // Period of 8 pieces

    State.row1Offset = baseOffset;
    State.row2Offset = baseOffset;
    State.row3Offset = baseOffset;

    ribbonRow1.style.transform = `translate3d(${State.row1Offset}px, 0, 0)`;
    ribbonRow2.style.transform = `translate3d(${State.row2Offset}px, 0, 0)`;
    ribbonRow3.style.transform = `translate3d(${State.row3Offset}px, 0, 0)`;
  }

  /* ==========================================================================
     4. AUTHENTIC CARD-DEALING DISTRIBUTION CASCADE
     Pattern:
     - Center Card (Necklace) sits on top of deck
     - Step 1: Middle row cards deal horizontally outward
     - Step 2: Top row cards deal up & outward in mirror symmetry
     - Step 3: Bottom row cards deal down & outward in mirror symmetry
     - Overlapping, controlled physical card glide
     ========================================================================== */
  // Track active timeouts to prevent rogue timers across forward and reverse transitions
  let activeTimers = [];
  function safeTimeout(fn, delay) {
    const id = setTimeout(() => {
      activeTimers = activeTimers.filter(t => t !== id);
      fn();
    }, delay);
    activeTimers.push(id);
    return id;
  }
  function clearAllTimers() {
    activeTimers.forEach(id => clearTimeout(id));
    activeTimers = [];
  }

  /* ==========================================================================
     4. AUTHENTIC CARD-DEALING DISTRIBUTION CASCADE (FORWARD: 3.0 SECONDS)
     Pattern:
     - Center Card (Necklace) sits on top of deck
     - Step 1: Middle row cards deal horizontally outward (200ms - 530ms)
     - Step 2: Top row cards deal up & outward in mirror symmetry (600ms - 1040ms)
     - Step 3: Bottom row cards deal down & outward in mirror symmetry (1100ms - 1540ms)
     - Overlapping, controlled physical card glide: 1.45s cubic-bezier(0.16, 1, 0.3, 1)
     - Total duration: exactly 3.0 seconds into streaming ribbons
     ========================================================================== */
  function triggerBreakout() {
    if (State.mode === 'BREAKOUT' || State.mode === 'STREAMING') return;
    clearAllTimers();
    State.mode = 'BREAKOUT';
    State.isPlaying = true;

    // Clear previous unpack layer
    breakoutLayer.innerHTML = '';
    breakoutLayer.style.display = 'block';

    // Hide flowing ribbons during card dealing
    rowsContainer.classList.remove('is-visible');
    rowsContainer.style.opacity = '0';

    const { cardSize, pitch } = getCardMetrics();
    const heroRect = heroCard.getBoundingClientRect();
    const viewportRect = viewport.getBoundingClientRect();

    const originCenterX = heroRect.left - viewportRect.left + heroRect.width / 2;
    const originCenterY = heroRect.top - viewportRect.top + heroRect.height / 2;

    const visibleHalfCols = Math.min(Math.ceil((viewportRect.width / 2) / pitch) + 1, 6);

    const initialHeroSize = heroRect.width;
    const targetScale = cardSize / initialHeroSize;

    // 3.0-Second Luxurious Card-Dealing Distribution Physics
    const dealSpring = 'cubic-bezier(0.16, 1, 0.3, 1)';
    const dealDuration = '1.45s';

    heroCard.classList.remove('is-hidden');
    heroCard.style.opacity = '1';
    heroCard.style.pointerEvents = 'none';
    heroCard.style.transition = `transform 2.0s ${dealSpring}, box-shadow 0.8s ease`;
    heroCard.style.transform = `translate(-50%, -50%) scale(${targetScale})`;
    heroCard.style.zIndex = '50';

    // Helper: Creates a card pre-stacked in the center deck
    function createDeckCard(piece, zIdx) {
      const el = document.createElement('div');
      el.className = 'unpack-stage-card';
      el.style.width = `${cardSize}px`;
      el.style.height = `${cardSize}px`;
      el.style.left = `${originCenterX - cardSize / 2}px`;
      el.style.top = `${originCenterY - cardSize / 2}px`;
      el.style.zIndex = `${zIdx}`;
      el.style.opacity = '1';
      // Pre-stacked in center deck
      el.style.transform = 'translate3d(0, 0, 0) scale(0.96)';
      el.innerHTML = `
        <img src="${piece.src}" alt="${piece.title}" class="unpack-stage-img">
        <div class="spread-card-glint"></div>
      `;
      breakoutLayer.appendChild(el);
      return el;
    }

    const cardsToDeal = [];

    // Step 1: Middle row cards deal horizontally outward (200ms - 530ms)
    for (let c = -visibleHalfCols; c <= visibleHalfCols; c++) {
      if (c === 0) continue; // Center is the hero necklace
      const d = Math.abs(c);
      const piece = getPieceForCol(1, c);
      const el = createDeckCard(piece, 35 - d);
      const delay = 200 + (d - 1) * 110;
      cardsToDeal.push({ el, targetX: c * pitch, targetY: 0, delay });
    }

    // Step 2: Top row cards deal up & out (600ms - 1040ms)
    for (let c = -visibleHalfCols; c <= visibleHalfCols; c++) {
      const d = Math.abs(c);
      const piece = getPieceForCol(0, c);
      const el = createDeckCard(piece, 25 - d);
      const delay = 600 + d * 110;
      cardsToDeal.push({ el, targetX: c * pitch, targetY: -pitch, delay });
    }

    // Step 3: Bottom row cards deal down & out (1100ms - 1540ms)
    for (let c = -visibleHalfCols; c <= visibleHalfCols; c++) {
      const d = Math.abs(c);
      const piece = getPieceForCol(2, c);
      const el = createDeckCard(piece, 15 - d);
      const delay = 1100 + d * 110;
      cardsToDeal.push({ el, targetX: c * pitch, targetY: pitch, delay });
    }

    // Force DOM reflow so deck stacked state is registered
    void breakoutLayer.offsetWidth;

    // Launch the dealing cascade
    requestAnimationFrame(() => {
      cardsToDeal.forEach(({ el, targetX, targetY, delay }) => {
        safeTimeout(() => {
          if (State.mode !== 'BREAKOUT') return;
          el.style.transition = `transform ${dealDuration} ${dealSpring}, box-shadow 0.6s ease`;
          el.style.transform = `translate3d(${targetX}px, ${targetY}px, 0) scale(1)`;
        }, delay);
      });
    });

    // Seamless handoff at exactly 3.0 seconds (3000ms)
    safeTimeout(() => {
      if (State.mode !== 'BREAKOUT') return;

      measureRibbonMetrics();

      // Ensure ribbon track visibility is completely restored (never white screen)
      rowsContainer.classList.add('is-visible');
      rowsContainer.style.visibility = 'visible';
      rowsContainer.style.pointerEvents = 'auto';
      rowsContainer.style.transition = 'opacity 0.45s ease';
      rowsContainer.style.opacity = '1';

      heroCard.style.transition = 'opacity 0.4s ease';
      heroCard.style.opacity = '0';
      heroCard.style.pointerEvents = 'none';

      cardsToDeal.forEach(({ el }) => {
        el.style.transition = 'opacity 0.4s ease';
        el.style.opacity = '0';
      });

      safeTimeout(() => {
        if (State.mode !== 'BREAKOUT') return;
        breakoutLayer.style.display = 'none';
        breakoutLayer.innerHTML = '';
        heroCard.style.visibility = 'hidden';
        State.mode = 'STREAMING';
        State.streamingStartTime = performance.now();
      }, 450);

    }, 3000);
  }

  /* ==========================================================================
     5. REVERSE COLLAPSE FROM 3 ROWS BACK TO SINGLE CENTER CONTAINER
     Exact mirror reversal of breakout dealing cascade:
     - Step 1: Bottom row cards slide UP & IN to center deck
     - Step 2: Top row cards slide DOWN & IN to center deck
     - Step 3: Middle row cards slide HORIZONTALLY IN to center deck
     - Hero necklace container scales back up to standalone 1:1 scale
     - Silky smooth, responsive, and exact wave symmetry
     ========================================================================== */
  function reverseCollapseToCenter(onComplete) {
    if (State.mode === 'HERO' || State.mode === 'COLLAPSING') {
      if (typeof onComplete === 'function') onComplete();
      return;
    }

    clearAllTimers();
    State.mode = 'COLLAPSING';
    State.isPlaying = false;

    const { cardSize, pitch } = getCardMetrics();
    const heroRect = heroCard.getBoundingClientRect();
    const viewportRect = viewport.getBoundingClientRect();

    const originCenterX = heroRect.left - viewportRect.left + heroRect.width / 2;
    const originCenterY = heroRect.top - viewportRect.top + heroRect.height / 2;
    const visibleHalfCols = Math.min(Math.ceil((viewportRect.width / 2) / pitch) + 1, 6);

    const initialHeroSize = heroRect.width;
    const targetScale = cardSize / initialHeroSize;

    // Calculate current drift deltas so cards do not jump when transitioning from ribbons
    const delta1 = (State.row1Offset - State.baseCenterOffset) || 0;
    const delta2 = (State.row2Offset - State.baseCenterOffset) || 0;
    const delta3 = (State.row3Offset - State.baseCenterOffset) || 0;

    // Prepare unpack breakout layer
    breakoutLayer.innerHTML = '';
    breakoutLayer.style.display = 'block';

    // Instantly and strictly conceal the flowing ribbons
    rowsContainer.classList.remove('is-visible');
    rowsContainer.style.opacity = '0';
    rowsContainer.style.visibility = 'hidden';
    rowsContainer.style.pointerEvents = 'none';

    // Hero card starts at small row scale and prepares to expand back up to 1:1
    heroCard.classList.remove('is-hidden');
    heroCard.style.opacity = '1';
    heroCard.style.visibility = 'visible';
    heroCard.style.pointerEvents = 'none';
    heroCard.style.transform = `translate(-50%, -50%) scale(${targetScale})`;
    heroCard.style.zIndex = '50';

    function createReturnCard(piece, startX, startY, zIdx) {
      const el = document.createElement('div');
      el.className = 'unpack-stage-card';
      el.style.width = `${cardSize}px`;
      el.style.height = `${cardSize}px`;
      el.style.left = `${originCenterX - cardSize / 2}px`;
      el.style.top = `${originCenterY - cardSize / 2}px`;
      el.style.zIndex = `${zIdx}`;
      el.style.opacity = '1';
      // Starts at exact expanded row position accounting for drift
      el.style.transform = `translate3d(${startX}px, ${startY}px, 0) scale(1)`;
      el.innerHTML = `
        <img src="${piece.src}" alt="${piece.title}" class="unpack-stage-img">
        <div class="spread-card-glint"></div>
      `;
      breakoutLayer.appendChild(el);
      return el;
    }

    const cardsToReturn = [];

    // Reverse Step 1: Bottom row cards fold first (UP & IN) - dealt last in forward!
    for (let c = -visibleHalfCols; c <= visibleHalfCols; c++) {
      const d = Math.abs(c);
      const piece = getPieceForCol(2, c);
      const el = createReturnCard(piece, c * pitch + delta3, pitch, 15 - d);
      const delay = 80 + (visibleHalfCols - d) * 75;
      cardsToReturn.push({ el, delay });
    }

    // Reverse Step 2: Top row cards fold second (DOWN & IN)
    for (let c = -visibleHalfCols; c <= visibleHalfCols; c++) {
      const d = Math.abs(c);
      const piece = getPieceForCol(0, c);
      const el = createReturnCard(piece, c * pitch + delta1, -pitch, 25 - d);
      const delay = 320 + (visibleHalfCols - d) * 75;
      cardsToReturn.push({ el, delay });
    }

    // Reverse Step 3: Middle row cards fold last (HORIZONTALLY IN) - dealt first in forward!
    for (let c = -visibleHalfCols; c <= visibleHalfCols; c++) {
      if (c === 0) continue;
      const d = Math.abs(c);
      const piece = getPieceForCol(1, c);
      const el = createReturnCard(piece, c * pitch + delta2, 0, 35 - d);
      const delay = 560 + (visibleHalfCols - d) * 75;
      cardsToReturn.push({ el, delay });
    }

    // Force layout reflow so starting positions register
    void breakoutLayer.offsetWidth;

    const dealSpring = 'cubic-bezier(0.16, 1, 0.3, 1)';
    const dealDuration = '1.05s';

    // Animate cards gliding symmetrically back into center deck (0, 0)
    requestAnimationFrame(() => {
      cardsToReturn.forEach(({ el, delay }) => {
        safeTimeout(() => {
          if (State.mode !== 'COLLAPSING') return;
          el.style.transition = `transform ${dealDuration} ${dealSpring}, opacity 0.4s ease`;
          el.style.transform = 'translate3d(0, 0, 0) scale(0.96)';
          safeTimeout(() => {
            el.style.opacity = '0';
          }, 650);
        }, delay);
      });

      // Hero necklace scales back up to standalone 1:1 container over 1.2s
      safeTimeout(() => {
        if (State.mode !== 'COLLAPSING') return;
        heroCard.style.transition = `transform 1.2s ${dealSpring}, box-shadow 0.6s ease`;
        heroCard.style.transform = 'translate(-50%, -50%) scale(1)';
      }, 450);
    });

    // Cleanup after exact 1.6s reverse gather completes
    safeTimeout(() => {
      if (State.mode !== 'COLLAPSING') return;

      breakoutLayer.style.display = 'none';
      breakoutLayer.innerHTML = '';
      State.mode = 'HERO';
      State.isPlaying = false;
      rowsContainer.classList.remove('is-visible');
      rowsContainer.style.opacity = '0';
      rowsContainer.style.visibility = 'hidden';
      rowsContainer.style.pointerEvents = 'none';
      heroCard.style.zIndex = '50';
      heroCard.style.opacity = '1';
      heroCard.style.transform = 'translate(-50%, -50%) scale(1)';
      heroCard.style.pointerEvents = 'auto';

      if (typeof onComplete === 'function') {
        onComplete();
      }
    }, 1600);
  }

  /* ==========================================================================
     6. INSTANT RESET TO 1:1 CENTER CONTAINER
     ========================================================================== */
  function resetToCenter() {
    clearAllTimers();
    State.mode = 'HERO';
    State.isPlaying = false;
    State.streamingStartTime = 0;

    rowsContainer.classList.remove('is-visible');
    rowsContainer.style.opacity = '0';
    rowsContainer.style.visibility = 'hidden';
    rowsContainer.style.pointerEvents = 'none';

    breakoutLayer.style.display = 'none';
    breakoutLayer.innerHTML = '';

    heroCard.classList.remove('is-hidden');
    heroCard.style.transition = 'transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.3s ease';
    heroCard.style.transform = 'translate(-50%, -50%) scale(1)';
    heroCard.style.opacity = '1';
    heroCard.style.visibility = 'visible';
    heroCard.style.pointerEvents = 'auto';
    heroCard.style.zIndex = '50';
  }

  /* ==========================================================================
     6. CONTINUOUS MOMENTUM STREAMING LOOP (60/120 FPS requestAnimationFrame)
     ========================================================================== */
  function updateMomentum(now) {
    if (!State.lastTimestamp) State.lastTimestamp = now;
    const delta = Math.min(now - State.lastTimestamp, 64);
    State.lastTimestamp = now;

    if (State.mode === 'STREAMING' && State.isPlaying) {
      // Velvety smooth acceleration ramp over 900ms when drift begins
      let ramp = 1.0;
      if (State.streamingStartTime > 0) {
        const elapsed = now - State.streamingStartTime;
        const progress = Math.min(elapsed / 900, 1.0);
        ramp = progress * progress * (3 - 2 * progress); // smoothstep
      }

      const speed = State.speedMultiplier * ramp;

      // Row 1 (Top: Drift Left)
      const v1Effective = (State.hoveredRow === 0) ? State.v1 * 0.15 : State.v1;
      State.row1Offset += v1Effective * delta * speed;
      applyRibbonModulo(0, ribbonRow1);

      // Row 2 (Middle: Drift Right - Centered on the Royal Necklace)
      const v2Effective = (State.hoveredRow === 1) ? State.v2 * 0.15 : State.v2;
      State.row2Offset += v2Effective * delta * speed;
      applyRibbonModulo(1, ribbonRow2);

      // Row 3 (Bottom: Drift Left)
      const v3Effective = (State.hoveredRow === 2) ? State.v3 * 0.15 : State.v3;
      State.row3Offset += v3Effective * delta * speed;
      applyRibbonModulo(2, ribbonRow3);
    }

    requestAnimationFrame(updateMomentum);
  }

  function applyRibbonModulo(rowIndex, ribbonEl) {
    const cycle = State.cycleWidth;
    const base = State.baseCenterOffset;
    if (!cycle || cycle <= 0) return;

    let offset = rowIndex === 0 ? State.row1Offset : rowIndex === 1 ? State.row2Offset : State.row3Offset;

    if (offset < base - cycle) {
      offset += cycle;
    } else if (offset > base + cycle) {
      offset -= cycle;
    }

    if (rowIndex === 0) State.row1Offset = offset;
    else if (rowIndex === 1) State.row2Offset = offset;
    else State.row3Offset = offset;

    ribbonEl.style.transform = `translate3d(${offset}px, 0, 0)`;
  }

  /* ==========================================================================
     7. LUXURY LIGHTBOX MODAL INSPECTOR
     ========================================================================== */
  function openModal(piece) {
    modalImg.src = piece.src;
    modalImg.alt = piece.title;
    modalTag.textContent = piece.tag;
    modalTitle.textContent = piece.title;
    modalDesc.textContent = piece.desc;

    modalOverlay.classList.add('is-active');
    document.body.style.overflow = 'hidden';
  }

  function closeModal() {
    modalOverlay.classList.remove('is-active');
    document.body.style.overflow = '';
  }

  /* ==========================================================================
     8. HUD CONTROLS & EVENT LISTENERS
     ========================================================================== */
  function setupEventListeners() {
    if (heroCard) {
      heroCard.addEventListener('click', () => {
        triggerBreakout();
      });
    }

    if (btnPlayPause) {
      btnPlayPause.addEventListener('click', () => {
        State.isPlaying = !State.isPlaying;
        if (State.isPlaying) {
          if (iconPause) iconPause.style.display = 'block';
          if (iconPlay) iconPlay.style.display = 'none';
          if (labelPlayPause) labelPlayPause.textContent = 'Drift Active';
          btnPlayPause.classList.add('is-active');
        } else {
          if (iconPause) iconPause.style.display = 'none';
          if (iconPlay) iconPlay.style.display = 'block';
          if (labelPlayPause) labelPlayPause.textContent = 'Drift Paused';
          btnPlayPause.classList.remove('is-active');
        }
      });
    }

    if (btnReplay) {
      btnReplay.addEventListener('click', () => {
        resetToCenter();
        setTimeout(() => {
          triggerBreakout();
        }, 400);
      });
    }

    if (btnSpeed) {
      btnSpeed.addEventListener('click', () => {
        if (State.speedMultiplier === 1.0) {
          State.speedMultiplier = 1.75;
          if (labelSpeed) labelSpeed.textContent = '1.75x';
          btnSpeed.classList.add('is-active');
        } else if (State.speedMultiplier === 1.75) {
          State.speedMultiplier = 0.5;
          if (labelSpeed) labelSpeed.textContent = '0.5x';
          btnSpeed.classList.remove('is-active');
        } else {
          State.speedMultiplier = 1.0;
          if (labelSpeed) labelSpeed.textContent = '1.0x';
          btnSpeed.classList.remove('is-active');
        }
      });
    }

    if (btnResetCenter) {
      btnResetCenter.addEventListener('click', () => {
        resetToCenter();
      });
    }

    if (modalCloseBtn) modalCloseBtn.addEventListener('click', closeModal);
    if (modalOverlay) {
      modalOverlay.addEventListener('click', (e) => {
        if (e.target === modalOverlay) closeModal();
      });
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modalOverlay.classList.contains('is-active')) {
          closeModal();
        }
      });
    }

    window.addEventListener('resize', () => {
      measureRibbonMetrics();
    });
  }

  /* ==========================================================================
     9. INITIALIZATION & PUBLIC API
     ========================================================================== */
  function init() {
    if (!document.getElementById('spreadViewport')) {
      return; // Not on a spread rows page
    }

    populateRibbons();
    setupEventListeners();
    requestAnimationFrame(updateMomentum);

    // Public API for programmatic control and scroll integration
    window.SSRINISpreadRows = {
      triggerBreakout,
      reverseCollapseToCenter,
      resetToCenter,
      getState: () => ({ ...State }),
      setSpeedMultiplier: (m) => { State.speedMultiplier = m; },
      setIsPlaying: (p) => { State.isPlaying = p; }
    };

    // Auto-trigger breakout on standalone pages unless deferred by scroll orchestrator
    if (!window.__SPREAD_ROWS_DEFERRED__) {
      setTimeout(() => {
        if (State.mode === 'HERO') {
          triggerBreakout();
        }
      }, 1100);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
