/**
 * Contact Page Engine — Canvas Video Scroll Sequence & Watermark Cover Link Arc Menu
 */

document.addEventListener('DOMContentLoaded', () => {
  initCartCount();
  initCanvasVideoScroll();
  initWatermarkLinkMenu();
});

function initCartCount() {
  const cart = JSON.parse(localStorage.getItem('ssrini_cart') || '[]');
  const countEls = document.querySelectorAll('.cart-count, .cart-counter');
  countEls.forEach(el => {
    el.textContent = cart.reduce((sum, item) => sum + (item.qty || 1), 0);
  });
}

function initWatermarkLinkMenu() {
  const container = document.getElementById('watermark-cover-container');
  const trigger = document.getElementById('watermark-link-trigger');

  if (!container || !trigger) return;

  trigger.addEventListener('click', (e) => {
    e.stopPropagation();
    const isExpanded = container.classList.contains('expanded');
    
    if (isExpanded) {
      container.classList.remove('expanded');
      trigger.classList.remove('active');
    } else {
      container.classList.add('expanded');
      trigger.classList.add('active');

      // Enhanced GSAP popout animation on inner SVGs (prevents parent translate flickering)
      if (typeof gsap !== 'undefined') {
        const svgIcons = container.querySelectorAll('.arc-icon-item .shadcn-hover-icon');
        gsap.fromTo(svgIcons, 
          { scale: 0.2, opacity: 0 },
          { 
            scale: 1, 
            opacity: 1, 
            duration: 0.45, 
            stagger: 0.04, 
            ease: 'back.out(2)',
            clearProps: 'scale,opacity'
          }
        );
      }
    }
  });

  // Close menu if clicked outside
  document.addEventListener('click', (e) => {
    if (!container.contains(e.target)) {
      container.classList.remove('expanded');
      trigger.classList.remove('active');
    }
  });
}

function initCanvasVideoScroll() {
  const canvas = document.getElementById('contact-canvas');
  if (!canvas) return;

  const ctx = canvas.getContext('2d');
  const frameCount = 60;
  const frames = [];
  const seq = { currentFrame: 0 };

  // Adjust Canvas Resolution
  function setCanvasDimensions() {
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
    renderFrame(Math.floor(seq.currentFrame));
  }

  window.addEventListener('resize', setCanvasDimensions);

  // Preload Images
  for (let i = 1; i <= frameCount; i++) {
    const img = new Image();
    const formattedNum = String(i).padStart(3, '0');
    img.src = `frames/frame_${formattedNum}.webp`;
    img.onload = () => {
      if (i === 1) {
        setCanvasDimensions();
      }
    };
    frames.push(img);
  }

  // Draw current frame with object-fit: cover behavior
  function renderFrame(index) {
    const img = frames[index];
    if (!img || !img.complete || img.naturalWidth === 0) return;

    ctx.clearRect(0, 0, canvas.width, canvas.height);

    const cW = canvas.width;
    const cH = canvas.height;
    const iW = img.naturalWidth;
    const iH = img.naturalHeight;

    const ratio = Math.max(cW / iW, cH / iH);
    const newWidth = iW * ratio;
    const newHeight = iH * ratio;
    const x = (cW - newWidth) / 2;
    const y = (cH - newHeight) / 2;

    ctx.drawImage(img, x, y, newWidth, newHeight);
  }

  // GSAP ScrollTrigger Sequence Scrubbing & Icon Badge Shift Animation
  if (typeof gsap !== 'undefined' && typeof ScrollTrigger !== 'undefined') {
    gsap.registerPlugin(ScrollTrigger);

    // 1. Scrub canvas video frames on scroll
    gsap.to(seq, {
      currentFrame: frameCount - 1,
      snap: 'currentFrame',
      ease: 'none',
      scrollTrigger: {
        trigger: '.contact-hero-section',
        start: 'top top',
        end: 'bottom top',
        scrub: 0.5,
        onUpdate: () => renderFrame(Math.floor(seq.currentFrame))
      }
    });

    // 2. Animate main watermark cover badge: starts shifted UP above white line in frame 1,
    // and as scroll starts, glides smoothly down to its original position!
    gsap.fromTo('#watermark-cover-container',
      { y: -95 }, // Shifted up above the white line at frame 1
      {
        y: 0,     // Returns to original bottom-right position as scroll starts
        ease: 'power1.out',
        scrollTrigger: {
          trigger: '.contact-hero-section',
          start: 'top top',
          end: '25% top', // Completes transition in the first 25% of scroll
          scrub: 0.3
        }
      }
    );

  } else {
    // Fallback if GSAP is not present
    window.addEventListener('scroll', () => {
      const scrollRatio = Math.min(1, Math.max(0, window.scrollY / (window.innerHeight * 1.2)));
      const targetIndex = Math.floor(scrollRatio * (frameCount - 1));
      renderFrame(targetIndex);
    });
  }

  // Initial draw
  renderFrame(0);
}
