/**
 * Home Page Interactions, Clocks, Weather, and Scroll Reveals
 */
document.addEventListener('DOMContentLoaded', () => {
  // 1. World Clocks (Paris & Tokyo)
  const cities = [
    { name: 'Paris', timezone: 'Europe/Paris', id: 'paris' },
    { name: 'Tokyo', timezone: 'Asia/Tokyo', id: 'tokyo' }
  ];

  function updateClocks() {
    cities.forEach((city) => {
      try {
        const now = new Date().toLocaleTimeString('en-GB', {
          hour: '2-digit',
          minute: '2-digit',
          timeZone: city.timezone,
        });
        const el = document.getElementById(`${city.id}-hour`);
        if (el) el.textContent = now;
      } catch (e) {
        const fallback = new Date().toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
        const el = document.getElementById(`${city.id}-hour`);
        if (el) el.textContent = fallback;
      }
    });
  }

  updateClocks();
  setInterval(updateClocks, 30000);

  // 2. Navigation Active State & Hover Effect
  const navs = document.querySelectorAll('.nav-link-wrapper:not(.w-locales-list)');
  const path = window.location.pathname;

  let defaultActiveNav = null;
  navs.forEach((nav) => {
    const link = nav.querySelector('a');
    if (!link) return;

    const href = link.getAttribute('href');
    if (path === href || (path === '/' && href && href.includes('index.html'))) {
      defaultActiveNav = nav;
      nav.classList.add('active');
    }

    nav.addEventListener('mouseenter', () => {
      navs.forEach((n) => n.classList.remove('active'));
      nav.classList.add('active');
    });

    nav.addEventListener('mouseleave', () => {
      navs.forEach((n) => n.classList.remove('active'));
      if (defaultActiveNav) defaultActiveNav.classList.add('active');
    });
  });

  // 3. Mobile Menu Toggle
  const mobileToggle = document.querySelector('.nav-link-wrapper.mobile');
  const mobileMenu = document.querySelector('.nav-menu-open-mobile');

  if (mobileToggle && mobileMenu) {
    let menuOpen = false;
    mobileToggle.addEventListener('click', (e) => {
      e.preventDefault();
      menuOpen = !menuOpen;
      mobileMenu.style.display = menuOpen ? 'flex' : 'none';
      mobileMenu.style.opacity = menuOpen ? '1' : '0';
    });
  }

  // 4. Ensure all collection titles and images are visible by default
  document.querySelectorAll('.juri-collection-title').forEach(title => {
    title.style.opacity = '1';
    title.style.visibility = 'visible';
  });

  // 5. GSAP ScrollTrigger Reveals (if GSAP is available)
  if (typeof gsap !== 'undefined' && typeof ScrollTrigger !== 'undefined') {
    try {
      gsap.registerPlugin(ScrollTrigger);

      const collectionCards = gsap.utils.toArray('.collection-grid');
      collectionCards.forEach((card) => {
        const img = card.querySelector('.image-container img:not(.additional-image)');
        const title = card.querySelector('.juri-collection-title');

        if (img) {
          gsap.from(img, {
            scale: 1.05,
            duration: 1.2,
            ease: 'power2.out',
            scrollTrigger: {
              trigger: card,
              start: 'top 85%',
              toggleActions: 'play none none none'
            }
          });
        }
      });
    } catch (e) {
      console.warn('ScrollTrigger reveal notice:', e);
    }
  }

  // 6. Smooth Scroll for Anchor Links (e.g. Collections)
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
      const targetId = this.getAttribute('href').slice(1);
      if (!targetId) return;
      const target = document.getElementById(targetId);
      if (target) {
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth' });
        if (mobileMenu && mobileMenu.style.display === 'flex') {
          mobileMenu.style.display = 'none';
        }
      }
    });
  });
});
