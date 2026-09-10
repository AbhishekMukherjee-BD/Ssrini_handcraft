/**
 * 21st.dev @uilayout.contact / About Section — Ultra-Smooth Motion Engine
 * Driven by GSAP 60/120fps hardware-accelerated ticker
 * Replicates framer-motion TimelineContent blur/translation + VerticalCutReveal spring physics
 */

document.addEventListener('DOMContentLoaded', () => {
  setupVerticalCutReveal();
  initSmoothAnimation();
});

let masterTimeline = null;

/**
 * Splits the headline into individual words wrapped in overflow-hidden masks
 * to replicate the exact "VerticalCutReveal" component from 21st.dev
 */
function setupVerticalCutReveal() {
  const headline = document.getElementById('editorial-headline-text');
  if (!headline) return;

  const originalText = "Crafting Words That Make a Difference.";
  const words = originalText.split(' ');
  headline.innerHTML = '';

  words.forEach((word) => {
    const mask = document.createElement('span');
    mask.className = 'vertical-cut-word-mask';

    const inner = document.createElement('span');
    inner.className = 'vertical-cut-word-inner';
    inner.textContent = word;

    mask.appendChild(inner);
    headline.appendChild(mask);
  });
}

/**
 * Orchestrates the master timeline with GSAP for buttery smooth rendering
 */
function initSmoothAnimation() {
  if (typeof gsap === 'undefined') {
    // Fallback if CDN is unreachable
    runCssFallback();
    return;
  }

  // Set initial states with GPU layer promotion
  gsap.set('.timeline-item', {
    y: -22,
    opacity: 0,
    filter: 'blur(12px)',
    force3D: true
  });

  gsap.set('.timeline-figure', {
    opacity: 0,
    filter: 'blur(14px)',
    scale: 0.985,
    force3D: true
  });

  gsap.set('.vertical-cut-word-inner', {
    yPercent: -102,
    force3D: true
  });

  // Build fluid master timeline
  masterTimeline = gsap.timeline({
    paused: false,
    defaults: {
      ease: 'power3.out',
      duration: 0.75
    }
  });

  // Step 0: Asterisk label & Facebook icon (0.0s)
  const items0 = document.querySelectorAll('[data-anim-num="0"]');
  masterTimeline.to(items0, {
    y: 0,
    opacity: 1,
    filter: 'blur(0px)',
    stagger: 0.08
  }, 0.1);

  // Step 1: Instagram (0.35s)
  const item1 = document.querySelector('[data-anim-num="1"]');
  if (item1) {
    masterTimeline.to(item1, { y: 0, opacity: 1, filter: 'blur(0px)' }, 0.35);
  }

  // Step 2: LinkedIn (0.65s)
  const item2 = document.querySelector('[data-anim-num="2"]');
  if (item2) {
    masterTimeline.to(item2, { y: 0, opacity: 1, filter: 'blur(0px)' }, 0.65);
  }

  // Step 3: YouTube (0.95s)
  const item3 = document.querySelector('[data-anim-num="3"]');
  if (item3) {
    masterTimeline.to(item3, { y: 0, opacity: 1, filter: 'blur(0px)' }, 0.95);
  }

  // Step 4: Hero Geometric Figure (1.25s) - Soft cinematic zoom + blur clearance
  const figure = document.querySelector('.timeline-figure');
  if (figure) {
    masterTimeline.to(figure, {
      opacity: 1,
      filter: 'blur(0px)',
      scale: 1,
      duration: 1.25,
      ease: 'power2.out'
    }, 1.25);
  }

  // Step 5: Sub-stats left ("10+ years of experience | 3 million words") (1.75s)
  const item5 = document.querySelector('[data-anim-num="5"]');
  if (item5) {
    masterTimeline.to(item5, { y: 0, opacity: 1, filter: 'blur(0px)' }, 1.75);
  }

  // Step 6: 100+ BRANDS (2.1s)
  const item6 = document.querySelector('[data-anim-num="6"]');
  if (item6) {
    masterTimeline.to(item6, { y: 0, opacity: 1, filter: 'blur(0px)' }, 2.1);
  }

  // Step 7: 30% higher engagement (2.45s)
  const item7 = document.querySelector('[data-anim-num="7"]');
  if (item7) {
    masterTimeline.to(item7, { y: 0, opacity: 1, filter: 'blur(0px)' }, 2.45);
  }

  // Step 8: Headline VerticalCutReveal spring drop (2.8s)
  const wordInners = document.querySelectorAll('.vertical-cut-word-inner');
  if (wordInners.length > 0) {
    masterTimeline.to(wordInners, {
      yPercent: 0,
      duration: 0.85,
      ease: 'back.out(1.18)',
      stagger: 0.08
    }, 2.8);
  }

  // Step 9: Paragraphs wrapper (3.4s)
  const item9 = document.querySelector('[data-anim-num="9"]');
  if (item9) {
    masterTimeline.to(item9, { y: 0, opacity: 1, filter: 'blur(0px)' }, 3.4);
  }

  // Step 10 & 11: Body paragraphs 1 & 2 (3.7s)
  const item10 = document.querySelector('[data-anim-num="10"]');
  const item11 = document.querySelector('[data-anim-num="11"]');
  if (item10 && item11) {
    masterTimeline.to([item10, item11], {
      y: 0,
      opacity: 1,
      filter: 'blur(0px)',
      stagger: 0.22,
      duration: 0.8
    }, 3.7);
  }

  // Step 12: SANGVI author title (4.3s)
  const item12 = document.querySelector('[data-anim-num="12"]');
  if (item12) {
    masterTimeline.to(item12, { y: 0, opacity: 1, filter: 'blur(0px)', duration: 0.75 }, 4.3);
  }

  // Step 13: Subtitle (4.6s)
  const item13 = document.querySelector('[data-anim-num="13"]');
  if (item13) {
    masterTimeline.to(item13, { y: 0, opacity: 1, filter: 'blur(0px)', duration: 0.75 }, 4.6);
  }

  // Step 14: Prompt text (4.95s)
  const item14 = document.querySelector('[data-anim-num="14"]');
  if (item14) {
    masterTimeline.to(item14, { y: 0, opacity: 1, filter: 'blur(0px)', duration: 0.75 }, 4.95);
  }

  // Step 15: LET'S COLLABORATE button with bouncy arrival (5.3s)
  const item15 = document.querySelector('[data-anim-num="15"]');
  if (item15) {
    masterTimeline.to(item15, {
      y: 0,
      opacity: 1,
      filter: 'blur(0px)',
      duration: 0.85,
      ease: 'back.out(1.25)'
    }, 5.3);
  }

  // Replay Button interaction
  const replayBtn = document.getElementById('btn-replay-anim');
  if (replayBtn) {
    replayBtn.addEventListener('click', () => {
      masterTimeline.restart();
    });
  }
}

/**
 * Robust CSS-only fallback if GSAP is unavailable
 */
function runCssFallback() {
  const animItems = document.querySelectorAll('.timeline-item, .timeline-figure');
  animItems.forEach((el) => {
    const num = parseInt(el.getAttribute('data-anim-num'), 10) || 0;
    setTimeout(() => {
      el.style.opacity = '1';
      el.style.transform = 'translateY(0)';
      el.style.filter = 'blur(0px)';
    }, num * 350);
  });

  const wordInners = document.querySelectorAll('.vertical-cut-word-inner');
  wordInners.forEach((w, i) => {
    setTimeout(() => {
      w.style.transform = 'translateY(0%)';
    }, 2600 + (i * 90));
  });
}
