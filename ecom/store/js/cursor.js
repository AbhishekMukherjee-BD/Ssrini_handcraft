/**
 * 1:1 Juri Exact Smooth Physics Lerp & Difference Inversion Cursor Engine
 */
(function () {
  'use strict';

  function initCursor() {
    const cursor = document.querySelector('.cursor');
    if (!cursor || matchMedia('(pointer: coarse)').matches) {
      if (cursor) cursor.style.display = 'none';
      return;
    }

    let mouseX = window.innerWidth / 2;
    let mouseY = window.innerHeight / 2;
    let currentX = mouseX;
    let currentY = mouseY;
    let isVisible = false;

    window.addEventListener('mousemove', (e) => {
      mouseX = e.clientX;
      mouseY = e.clientY;
      if (!isVisible) {
        isVisible = true;
        cursor.style.opacity = '1';
      }
    });

    document.addEventListener('mouseleave', () => {
      isVisible = false;
      cursor.style.opacity = '0';
    });

    // Smooth Lerp Follow Engine
    if (typeof gsap !== 'undefined') {
      // Use GSAP ticker with exact Juri 0.15 inertia factor
      gsap.ticker.add(() => {
        currentX += (mouseX - currentX) * 0.15;
        currentY += (mouseY - currentY) * 0.15;
        gsap.set(cursor, {
          x: currentX,
          y: currentY
        });
      });
    } else {
      function step() {
        currentX += (mouseX - currentX) * 0.15;
        currentY += (mouseY - currentY) * 0.15;
        cursor.style.transform = `translate(${currentX}px, ${currentY}px) translate(-50%, -50%)`;
        requestAnimationFrame(step);
      }
      requestAnimationFrame(step);
    }

    // Comprehensive Interactive Hover Elements for 2.2x Scale Expansion
    const HOVER_SELECTORS = [
      'a',
      'button',
      'input',
      'select',
      'textarea',
      'label',
      '[role="button"]',
      '.product-card',
      '.product-image-box',
      '.quickview-btn',
      '.filter-btn-toggle',
      '.filter-pill',
      '.layout-btn',
      '.pdp-thumbnail-item',
      '.pdp-carousel-nav-btn',
      '.qty-btn',
      '.btn-add-to-bag',
      '.btn-apply-filters',
      '.btn-reset-filters',
      '.accordion-header',
      '.cart-indicator',
      '.cart-icon-wrapper',
      '.nav-link-wrapper',
      '.brand',
      '.footer-social-btn',
      '.watermark-link-trigger',
      '.arc-icon-item',
      '.hero-origin-container',
      '.spread-card-item',
      '.footer-nav-link',
      '.footer-legal-btn',
      '.footer-brand-logo',
      '.link-mobile'
    ].join(',');

    document.addEventListener('mouseover', (e) => {
      const target = e.target.closest(HOVER_SELECTORS);
      if (target && typeof gsap !== 'undefined') {
        gsap.to(cursor, { scale: 2.2, duration: 0.3, ease: 'power2.out', overwrite: 'auto' });
      }
    });

    document.addEventListener('mouseout', (e) => {
      const target = e.target.closest(HOVER_SELECTORS);
      if (target && typeof gsap !== 'undefined') {
        const related = e.relatedTarget;
        if (!related || !related.closest(HOVER_SELECTORS)) {
          gsap.to(cursor, { scale: 1, duration: 0.3, ease: 'power2.out', overwrite: 'auto' });
        }
      }
    });

    // Subtle click squeeze micro-interaction
    document.addEventListener('mousedown', () => {
      if (typeof gsap !== 'undefined') {
        gsap.to(cursor, { scale: 0.85, duration: 0.15, ease: 'power2.out' });
      }
    });

    document.addEventListener('mouseup', () => {
      if (typeof gsap !== 'undefined') {
        gsap.to(cursor, { scale: 1, duration: 0.3, ease: 'elastic.out(1, 0.4)' });
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCursor);
  } else {
    initCursor();
  }
})();
