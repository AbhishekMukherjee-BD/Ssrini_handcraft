/**
 * About Us Page Slide Animations, Pinning, and Hash Syncing
 */
document.addEventListener('DOMContentLoaded', () => {
  if (typeof gsap === 'undefined' || typeof ScrollTrigger === 'undefined') return;
  gsap.registerPlugin(ScrollTrigger);

  // 1. Close Button Navigation
  const closeBtns = document.querySelectorAll('.close, .collection-close');
  closeBtns.forEach((btn) => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      window.location.href = '../index.html';
    });
  });

  // 2. Smooth Hash Scrolling
  let suppressPushState = false;
  document.querySelectorAll('a[href^="#"]').forEach((link) => {
    link.addEventListener('click', (e) => {
      const id = link.getAttribute('href');
      if (id === '#') return;
      e.preventDefault();

      const target = document.querySelector(id);
      if (target) {
        suppressPushState = true;
        setTimeout(() => { suppressPushState = false; }, 1000);

        const targetPos = target.getBoundingClientRect().top + window.pageYOffset - 60;
        window.scrollTo({
          top: targetPos,
          behavior: 'smooth'
        });

        // Update active menu link immediately
        document.querySelectorAll('.collection-menu-item').forEach((el) => el.classList.remove('active'));
        const menuLink = document.getElementById(`link-${id.replace('#', '')}`);
        if (menuLink) menuLink.classList.add('active');
      }
    });
  });

  // 3. ScrollTrigger Hash Updating
  const sections = gsap.utils.toArray('.slide').filter((el) => !!el.id);
  sections.forEach((el) => {
    ScrollTrigger.create({
      trigger: el,
      start: 'top center',
      end: 'bottom center',
      onEnter: () => {
        if (!suppressPushState) history.replaceState({}, '', `#${el.id}`);
        updateActiveMenu(el.id);
      },
      onEnterBack: () => {
        if (!suppressPushState) history.replaceState({}, '', `#${el.id}`);
        updateActiveMenu(el.id);
      }
    });
  });

  function updateActiveMenu(id) {
    document.querySelectorAll('.collection-menu-item').forEach((el) => {
      if (el.id === `link-${id}`) {
        el.classList.add('active');
      } else {
        el.classList.remove('active');
      }
    });
  }

  // 4. Slide Animations: Clip-Path Reveals, Scaling & Text Blur-In
  const slides = gsap.utils.toArray('.slide');
  slides.forEach((slide) => {
    const bigImages = Array.from(slide.querySelectorAll('.image:not(img)'));
    const imgTags = bigImages.map((img) => img.querySelector('img')).filter(Boolean);
    const texts = slide.querySelectorAll('p, .paragraph-13, .paragraph-14');

    gsap.set(bigImages, {
      clipPath: 'polygon(0% 0%, 100% 0%, 100% 0%, 0% 0%)'
    });

    gsap.set(imgTags, {
      scale: 1.15
    });

    gsap.set(texts, {
      autoAlpha: 0,
      filter: 'blur(8px)',
      y: 12,
    });

    const tl = gsap.timeline({
      scrollTrigger: {
        trigger: slide,
        start: 'top 75%',
        toggleActions: 'play none none none'
      }
    });

    tl.to(bigImages, {
      clipPath: 'polygon(0% 100%, 100% 100%, 100% 0%, 0% 0%)',
      duration: 1.2,
      ease: 'power4.inOut',
      stagger: 0.15
    })
    .to(imgTags, {
      scale: 1,
      duration: 1.2,
      ease: 'power4.inOut',
      stagger: 0.15
    }, '<')
    .to(texts, {
      autoAlpha: 1,
      filter: 'blur(0px)',
      y: 0,
      duration: 0.9,
      ease: 'power3.out',
      stagger: 0.08
    }, '<+0.2');
  });
});
