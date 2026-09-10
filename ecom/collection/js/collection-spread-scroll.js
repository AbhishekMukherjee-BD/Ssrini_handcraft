/**
 * SSRINI HAUTE JOAILLERIE — PURE VERTICAL PARALLAX SCROLL ORCHESTRATOR
 * 
 * Symmetrical Forward & Reverse Flow:
 * 1. Top of Page (progress <= 0.03):
 *    - 100vh raw orbit constellation.
 *    - Spot 6 sits on the EXACT vertical center axis (theta = 90 deg, X = 50vw).
 * 2. Scrolling Down (0.03 < progress < 0.95):
 *    - The single card detaches from spot 6 and glides DOWNWARD strictly along the vertical center axis.
 *    - Horizontal X is locked to 50% (translate3d(-50%, Y, 0)) with zero lateral drift.
 *    - Card scales smoothly from ~170px up to ~280px.
 * 3. Reaching Section 2 (progress >= 0.95):
 *    - Card aligns in the exact center of Section 2.
 *    - Triggers the 3.0s card-dealing breakout into the 3 rows.
 *    - 3 rows enter continuous opposing momentum drift ribbons (remains 100% visible, never white).
 * 4. Scrolling Back Up:
 *    - As user scrolls up while in Section 2, the 3 rows reverse gather into the center card:
 *      • Bottom row cards slide UP & IN to center deck
 *      • Top row cards slide DOWN & IN to center deck
 *      • Middle row cards slide HORIZONTALLY IN to center deck
 *      • Hero necklace expands back to standalone 1:1 container
 *    - Once gathered, an uninterrupted RAF-driven smooth glide carries the viewport to Section 1.
 *    - The single card glides strictly vertically UPWARD along the center axis (X = 50%).
 *    - Docks seamlessly back into spot 6 of the orbit wheel.
 *    - Spot 6 is restored in the orbit wheel with full interactivity.
 */

window.__SPREAD_ROWS_DEFERRED__ = true;

(function () {
  'use strict';

  let orbitSection = null;
  let spreadSection = null;
  let proxyCard = null;
  let heroOriginCard = null;

  let hasTriggeredBreakout = false;
  let isReversing = false;
  let isTicking = false;
  let lastScrollY = 0;
  let isScrollingUp = false;
  let touchStartY = 0;
  let smoothScrollRAF = null;

  // Smoothstep cubic easing for position interpolation
  function smoothstep(t) {
    const clamped = Math.max(0, Math.min(1, t));
    return clamped * clamped * (3 - 2 * clamped);
  }

  function getMetrics() {
    const vw = window.innerWidth;
    const vh = window.innerHeight;

    const orbitBaseY = orbitSection ? (orbitSection.getBoundingClientRect().top + window.scrollY) : 0;

    // 1. Source (Spot 6 in Orbit)
    let orbitDocY = orbitBaseY + vh * 0.78;
    let orbitCardSize = 170;

    if (window.SSRINIOrbitCollection && typeof window.SSRINIOrbitCollection.getHeroCardRect === 'function') {
      const r = window.SSRINIOrbitCollection.getHeroCardRect();
      if (r && r.height > 0) {
        orbitDocY = r.top + window.scrollY;
        orbitCardSize = r.width;
      }
    }

    // 2. Target (Center of Section 2)
    const spreadDocY = spreadSection ? (spreadSection.getBoundingClientRect().top + window.scrollY) : (orbitBaseY + vh);
    const spreadTop = spreadDocY - orbitBaseY;
    const minDim = Math.min(vw, vh);
    const heroCardSize = Math.min(Math.max(minDim * 0.30, 230), 320);
    const spreadCenterDocY = spreadDocY + (vh - heroCardSize) / 2;

    return {
      vh,
      vw,
      orbitBaseY,
      spreadDocY,
      spreadTop,
      orbitDocY,
      orbitCardSize,
      spreadCenterDocY,
      heroCardSize
    };
  }

  /**
   * Custom requestAnimationFrame smooth scroll animator.
   * Runs uninterrupted at 60/120fps with a luxury cubic easing curve.
   * Unlike browser scrollTo(behavior: 'smooth'), this CANNOT get stuck mid-scroll!
   */
  function smoothScrollTo(targetY, duration = 1000, onComplete) {
    if (smoothScrollRAF) {
      cancelAnimationFrame(smoothScrollRAF);
      smoothScrollRAF = null;
    }

    const startY = window.scrollY;
    const dist = targetY - startY;

    if (Math.abs(dist) < 1) {
      window.scrollTo(0, targetY);
      if (typeof onComplete === 'function') onComplete();
      return;
    }

    const startTime = performance.now();

    function easeInOutCubic(t) {
      return t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2;
    }

    function step(now) {
      const elapsed = now - startTime;
      const progress = Math.min(elapsed / duration, 1);
      const ease = easeInOutCubic(progress);
      const currentY = startY + dist * ease;

      window.scrollTo(0, currentY);

      if (progress < 1) {
        smoothScrollRAF = requestAnimationFrame(step);
      } else {
        smoothScrollRAF = null;
        window.scrollTo(0, targetY);
        if (typeof onComplete === 'function') onComplete();
      }
    }

    smoothScrollRAF = requestAnimationFrame(step);
  }

  /**
   * Triggers the exact mirror reverse collapse from 3 rows back to single center card,
   * then smoothly glides the viewport back to Section 1 and docks into spot 6.
   */
  function triggerReverseSequence() {
    if (isReversing) return;

    const sr = window.SSRINISpreadRows;
    if (!sr) return;

    const state = sr.getState();
    if (state.mode !== 'STREAMING' && state.mode !== 'BREAKOUT') return;

    isReversing = true;
    const m = getMetrics();

    // 1. Fold the 3 rows in Section 2 back into the center 1:1 hero container (1.6s)
    sr.reverseCollapseToCenter(() => {
      // 2. Once cards are safely inside the single card, smoothly glide viewport up to Section 1 (1.0s)
      smoothScrollTo(m.orbitBaseY, 1000, () => {
        isReversing = false;
        hasTriggeredBreakout = false;

        if (window.SSRINIOrbitCollection && typeof window.SSRINIOrbitCollection.setHeroCardVisibility === 'function') {
          window.SSRINIOrbitCollection.setHeroCardVisibility(true);
        }

        if (proxyCard) {
          proxyCard.style.display = 'none';
        }
      });
    });
  }

  function update() {
    isTicking = false;

    if (!spreadSection || !proxyCard) return;

    const m = getMetrics();
    const scrollY = window.scrollY;
    const relativeScrollY = scrollY - m.orbitBaseY;
    const totalDist = m.spreadTop; // scroll distance between Section 1 and Section 2

    if (totalDist <= 0) return;

    const progress = Math.min(Math.max(relativeScrollY / totalDist, 0), 1);
    const sr = window.SSRINISpreadRows;
    const srState = sr ? sr.getState() : null;

    const hint = document.getElementById('orbitScrollHint');
    if (hint) {
      hint.style.opacity = String(Math.max(0, 0.75 - progress * 4));
    }

    // ------------------------------------------------------------------------
    // CASE A: ACTIVELY COLLAPSING IN SECTION 2
    // ------------------------------------------------------------------------
    if (srState && srState.mode === 'COLLAPSING') {
      proxyCard.style.display = 'none';

      if (window.SSRINIOrbitCollection && typeof window.SSRINIOrbitCollection.setHeroCardVisibility === 'function') {
        window.SSRINIOrbitCollection.setHeroCardVisibility(false);
      }

      if (heroOriginCard) {
        heroOriginCard.style.opacity = '1';
      }
      return;
    }

    // ------------------------------------------------------------------------
    // STATE 1: TOP AT ORBIT (progress <= 0.03)
    // ------------------------------------------------------------------------
    if (progress <= 0.03) {
      proxyCard.style.display = 'none';

      if (window.SSRINIOrbitCollection && typeof window.SSRINIOrbitCollection.setHeroCardVisibility === 'function') {
        window.SSRINIOrbitCollection.setHeroCardVisibility(true);
      }

      if (heroOriginCard && (!sr || srState.mode === 'HERO')) {
        heroOriginCard.style.opacity = '0';
        heroOriginCard.style.visibility = 'hidden';
        heroOriginCard.style.pointerEvents = 'none';
      }

      if (hasTriggeredBreakout && sr && !isReversing) {
        sr.resetToCenter();
        hasTriggeredBreakout = false;
      }

    // ------------------------------------------------------------------------
    // STATE 2: VERTICAL PARALLAX GLIDE (0.03 < progress < 0.95)
    // ------------------------------------------------------------------------
    } else if (progress > 0.03 && progress < 0.95) {
      // Hide static card in orbit
      if (window.SSRINIOrbitCollection && typeof window.SSRINIOrbitCollection.setHeroCardVisibility === 'function') {
        window.SSRINIOrbitCollection.setHeroCardVisibility(false);
      }

      // Hide hero origin card in Section 2 while proxy is flying
      if (heroOriginCard && sr && srState.mode === 'HERO') {
        heroOriginCard.style.opacity = '0';
        heroOriginCard.style.visibility = 'hidden';
        heroOriginCard.style.pointerEvents = 'none';
      }

      // Check if user is scrolling up while breakout rows are still active in Section 2
      if (isScrollingUp && hasTriggeredBreakout && sr && (srState.mode === 'STREAMING' || srState.mode === 'BREAKOUT')) {
        triggerReverseSequence();
        return;
      }

      const t = (progress - 0.03) / (0.95 - 0.03);
      const ease = smoothstep(t);

      // Interpolate document coordinates strictly along center vertical axis (X = 50%)
      const curDocY = m.orbitDocY + (m.spreadCenterDocY - m.orbitDocY) * ease;
      const curSize = m.orbitCardSize + (m.heroCardSize - m.orbitCardSize) * ease;
      const curScreenY = curDocY - scrollY;

      proxyCard.style.display = 'block';
      proxyCard.style.width = `${Math.round(curSize)}px`;
      proxyCard.style.height = `${Math.round(curSize)}px`;
      proxyCard.style.left = '50%';
      proxyCard.style.top = '0px';
      // Strictly vertical translation along 50% center axis (zero lateral X deviation)
      proxyCard.style.transform = `translate3d(-50%, ${Math.round(curScreenY)}px, 0)`;

    // ------------------------------------------------------------------------
    // STATE 3: ALIGNED IN CENTER OF SECTION 2 (progress >= 0.95)
    // ------------------------------------------------------------------------
    } else {
      proxyCard.style.display = 'none';

      // Reveal hero origin container ONLY if still in HERO mode (prior to breakout)
      if (heroOriginCard) {
        if (!sr || !srState || srState.mode === 'HERO') {
          heroOriginCard.style.display = 'block';
          heroOriginCard.style.opacity = '1';
          heroOriginCard.style.visibility = 'visible';
          heroOriginCard.style.pointerEvents = 'auto';
        } else if (srState.mode === 'STREAMING') {
          heroOriginCard.style.opacity = '0';
          heroOriginCard.style.visibility = 'hidden';
          heroOriginCard.style.pointerEvents = 'none';
        }
      }

      // Trigger the 3.0s card-dealing breakout into the 3 rows when scrolling down
      if (!hasTriggeredBreakout && !isScrollingUp && sr && !isReversing) {
        if (srState && srState.mode === 'HERO') {
          hasTriggeredBreakout = true;
          sr.triggerBreakout();
        }
      }
    }
  }

  function onScroll() {
    const scrollY = window.scrollY;
    isScrollingUp = scrollY < lastScrollY;
    lastScrollY = scrollY;

    if (!isTicking) {
      isTicking = true;
      requestAnimationFrame(update);
    }
  }

  // Intercept scroll up when in Section 2 and rows are active
  function onWheel(e) {
    if (isReversing) {
      e.preventDefault();
      return;
    }

    const m = getMetrics();
    const inSection2 = window.scrollY >= m.spreadDocY - 40;

    if (inSection2 && e.deltaY < -4) {
      const sr = window.SSRINISpreadRows;
      if (sr) {
        const state = sr.getState();
        if (state.mode === 'STREAMING' || state.mode === 'BREAKOUT') {
          e.preventDefault();
          triggerReverseSequence();
        }
      }
    }
  }

  function onTouchStart(e) {
    if (e.touches.length === 1) {
      touchStartY = e.touches[0].clientY;
    }
  }

  function onTouchMove(e) {
    if (isReversing) {
      e.preventDefault();
      return;
    }

    if (e.touches.length === 1) {
      const touchY = e.touches[0].clientY;
      const deltaY = touchY - touchStartY; // positive deltaY means dragging down = scrolling up
      const m = getMetrics();
      const inSection2 = window.scrollY >= m.spreadDocY - 40;

      if (inSection2 && deltaY > 15) {
        const sr = window.SSRINISpreadRows;
        if (sr) {
          const state = sr.getState();
          if (state.mode === 'STREAMING' || state.mode === 'BREAKOUT') {
            e.preventDefault();
            triggerReverseSequence();
          }
        }
      }
    }
  }

  function onKeyDown(e) {
    if (isReversing) {
      if (['ArrowUp', 'ArrowDown', 'PageUp', 'PageDown', 'Home', 'End', ' '].includes(e.key)) {
        e.preventDefault();
      }
      return;
    }

    if (['ArrowUp', 'PageUp'].includes(e.key)) {
      const m = getMetrics();
      const inSection2 = window.scrollY >= m.spreadDocY - 40;

      if (inSection2) {
        const sr = window.SSRINISpreadRows;
        if (sr) {
          const state = sr.getState();
          if (state.mode === 'STREAMING' || state.mode === 'BREAKOUT') {
            e.preventDefault();
            triggerReverseSequence();
          }
        }
      }
    }
  }

  function init() {
    orbitSection = document.getElementById('collection-orbit-section');
    spreadSection = document.getElementById('collectionSpreadSection');
    proxyCard = document.getElementById('parallaxGlideProxy');
    heroOriginCard = document.getElementById('heroOriginCard');

    if (!orbitSection || !spreadSection) return;

    lastScrollY = window.scrollY;

    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onScroll, { passive: true });
    window.addEventListener('wheel', onWheel, { passive: false });
    window.addEventListener('touchstart', onTouchStart, { passive: true });
    window.addEventListener('touchmove', onTouchMove, { passive: false });
    window.addEventListener('keydown', onKeyDown, { passive: false });

    // Smooth scroll down when clicking spot 6 in the orbit
    setTimeout(() => {
      update();
      const heroInOrbit = document.getElementById('orbit-hero-necklace-card');
      if (heroInOrbit) {
        heroInOrbit.addEventListener('click', () => {
          if (!heroInOrbit.classList.contains('is-held-active')) {
            const m = getMetrics();
            smoothScrollTo(m.spreadDocY, 1100);
          }
        });
      }

      // Smooth reverse when clicking center card in Section 2 while rows are streaming
      if (heroOriginCard) {
        heroOriginCard.addEventListener('click', () => {
          const sr = window.SSRINISpreadRows;
          if (sr) {
            const state = sr.getState();
            if (state.mode === 'STREAMING' || state.mode === 'BREAKOUT') {
              triggerReverseSequence();
            }
          }
        });
      }
    }, 250);

    console.log('[SSRINI Parallax] Reversible vertical center orchestrator active.');
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
