/**
 * Luxury Preloader Animation Sequence with Robust Native Fallbacks
 */
document.addEventListener('DOMContentLoaded', () => {
  const urlParams = new URLSearchParams(window.location.search);
  const skipPreloader = urlParams.has('skip-preloader');

  if (skipPreloader) {
    try {
      window.history.replaceState({}, '', window.location.pathname);
    } catch (e) {}
  }

  const preloader = document.querySelector('.preloader');
  const preloaderLogo = document.querySelector('.preloader-logo');
  const preloaderFakeImages = document.querySelector('.preloader-fake-images');
  const navbar = document.querySelector('.navbar');

  if (!preloader) return;

  // Immediate fallback dismiss function
  function dismissPreloaderDirectly() {
    document.body.style.overflow = 'auto';
    if (navbar) {
      navbar.style.opacity = '1';
      navbar.style.visibility = 'visible';
    }
    if (preloader) {
      preloader.style.opacity = '0';
      preloader.style.transition = 'opacity 0.4s ease';
      setTimeout(() => {
        preloader.style.display = 'none';
        if (preloaderFakeImages) preloaderFakeImages.remove();
      }, 400);
    }
  }

  if (skipPreloader || typeof gsap === 'undefined') {
    dismissPreloaderDirectly();
    return;
  }

  // Preload images before kicking off GSAP timeline
  function preloadImages() {
    return new Promise((resolve) => {
      const images = document.querySelectorAll('.collection-grid .image-container img:not(.additional-image)');
      if (images.length === 0) {
        resolve();
        return;
      }

      let loadedCount = 0;
      const totalImages = images.length;

      const checkAllLoaded = () => {
        loadedCount++;
        if (loadedCount >= totalImages) {
          resolve();
        }
      };

      images.forEach((img) => {
        if (img.complete) {
          checkAllLoaded();
        } else {
          img.addEventListener('load', checkAllLoaded);
          img.addEventListener('error', checkAllLoaded);
        }
      });

      // Safety timeout
      setTimeout(resolve, 1800);
    });
  }

  preloadImages().then(() => {
    if (typeof gsap === 'undefined') {
      dismissPreloaderDirectly();
      return;
    }

    try {
      gsap.set(preloaderLogo, {
        filter: 'blur(8px)',
        autoAlpha: 0,
      });

      document.body.style.overflow = 'hidden';

      function onTimelineComplete() {
        document.body.style.overflow = 'auto';

        if (preloaderFakeImages) {
          preloaderFakeImages.remove();
        }

        gsap.to(preloader, {
          autoAlpha: 0,
          duration: 0.6,
          ease: 'power3.inOut',
          onComplete: () => {
            if (preloader) preloader.style.display = 'none';
          }
        });
      }

      const tl = gsap.timeline({
        defaults: { duration: 1.2 },
        onComplete: onTimelineComplete,
      });

      tl.to(preloaderLogo, {
        autoAlpha: 1,
        filter: 'blur(0px)',
        scale: 1.05,
        duration: 0.8,
        ease: 'power2.out'
      })
      .to(preloaderLogo, {
        autoAlpha: 0,
        filter: 'blur(6px)',
        scale: 0.95,
        duration: 0.5,
        ease: 'power2.in'
      }, '+=0.4');

    } catch (err) {
      dismissPreloaderDirectly();
    }
  });

  // Global safety fallback so page is NEVER locked
  setTimeout(() => {
    if (preloader && preloader.style.display !== 'none') {
      dismissPreloaderDirectly();
    }
  }, 3000);
});
