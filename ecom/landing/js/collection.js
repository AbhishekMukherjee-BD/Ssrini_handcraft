/**
 * Collection Page Parallax Layering, ClipPath Transitions, and Interactive Tab Switcher
 */
document.addEventListener('DOMContentLoaded', () => {
  // 1. Close Button Navigation
  const closeBtns = document.querySelectorAll('.close, .collection-close');
  closeBtns.forEach((btn) => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      window.location.href = '../index.html?skip-preloader=true';
    });
  });

  // 2. Collection Data Dictionary
  const COLLECTIONS_DATA = {
    rivulet: {
      title: 'Rivulet Heritage',
      discoverText: 'Discover Rivulet Collection',
      leftImages: [
        'assets/images/IMG_0685.jpg',
        'assets/images/IMG_0686.jpg',
        'assets/images/IMG_0688.jpg'
      ],
      rightImages: [
        'assets/images/IMG_0689.jpg',
        'assets/images/IMG_0690.jpg',
        'assets/images/IMG_0691.jpg'
      ],
      descriptions: [
        'The inaugural collection from SSRINI embodies the uninterrupted cadence of nature, infused with centuries of regal Indian craft tradition. Each artifact transcends rigid geometry, flowing organically like sacred streams across polished stone.',
        'Light and shadow converse across porous terracotta and burnished brass, blurring boundaries between functional adornment and spiritual art.',
        'Natural motifs, hand-sculpted clay, and gold leaf accents define the series. Individually fired and finished by master hands, every piece serves as a timeless centerpiece that radiates quiet grandeur.'
      ]
    },
    terracotta: {
      title: 'Terracotta Art',
      discoverText: 'Discover Terracotta Collection',
      leftImages: [
        'assets/images/IMG_0689.jpg',
        'assets/images/IMG_0691.jpg',
        'assets/images/IMG_0692.jpg'
      ],
      rightImages: [
        'assets/images/IMG_0693.jpg',
        'assets/images/IMG_0688.jpg',
        'assets/images/IMG_0685.jpg'
      ],
      descriptions: [
        'Born of alluvial riverbeds and ancestral wood-fired kilns, our Terracotta collection celebrates earth in its purest elemental form. Each vessel and sculptural artifact is hand-thrown and burnished by master potters.',
        'Rich ochre, deep umber, and subtle smoke-fired patinas evoke ancient riverbank temples and eternal architectural friezes.',
        'Every contour is shaped entirely by intuition and tactile memory, preserving the warmth and grounding presence of sacred clay.'
      ]
    },
    silk: {
      title: 'Silk & Weave',
      discoverText: 'Discover Silk & Weave Collection',
      leftImages: [
        'assets/images/IMG_0694.jpg',
        'assets/images/IMG_0695.jpg',
        'assets/images/IMG_0696.jpg'
      ],
      rightImages: [
        'assets/images/IMG_0690.jpg',
        'assets/images/IMG_0691.jpg',
        'assets/images/IMG_0692.jpg'
      ],
      descriptions: [
        'Loomed by multi-generational weaver families, our pure silk artifacts intertwine heritage motifs with contemporary weight and drape. Hand-dyed using organic botanical pigments and pure silver-gilt zari.',
        'Each textile panel requires over two hundred hours of meticulous handloom choreography, resulting in a luminous fabric that shimmers in changing light.',
        'Durable, lustrous, and profoundly tactile, our silk creations bridge wearable heritage and fine wall art.'
      ]
    },
    filigree: {
      title: 'Brass & Filigree',
      discoverText: 'Discover Brass & Filigree Collection',
      leftImages: [
        'assets/images/IMG_0694.jpg',
        'assets/images/IMG_0697.jpg',
        'assets/images/IMG_0698.jpg'
      ],
      rightImages: [
        'assets/images/IMG_0699.jpg',
        'assets/images/IMG_0693.jpg',
        'assets/images/IMG_0686.jpg'
      ],
      descriptions: [
        'Tarakasi wirework and lost-wax brass casting come together in ethereal filigree compositions. Microscopic strands of beaten alloy are twisted and soldered into ornate mandalas and sculptural vessels.',
        'The interplay of openwork lace metal and solid antique gold casting gives each artifact an air of celestial lightness.',
        'Crafted in limited small-batch editions by master guild metalsmiths with lineage tracing to royal imperial courts.'
      ]
    },
    ceramic: {
      title: 'Ceramic Glyphs',
      discoverText: 'Discover Ceramic Glyphs Collection',
      leftImages: [
        'assets/images/IMG_0695.jpg',
        'assets/images/IMG_0700.jpg',
        'assets/images/IMG_0701.jpg'
      ],
      rightImages: [
        'assets/images/IMG_0696.jpg',
        'assets/images/IMG_0702.jpg',
        'assets/images/IMG_0697.jpg'
      ],
      descriptions: [
        'High-fired stoneware glazed in reactive earth minerals, featuring ancient geometric inscriptions and tactile relief carvings. Each piece represents an artifact recovered from an imagined lost civilization.',
        'Subtle crater glazes, iron oxide washes, and matte porcelain finishes create a landscape of geological textures across the surface.',
        'Designed to stand as solitary monoliths or assembled into curated sculptural constellations within modern interiors.'
      ]
    },
    royal: {
      title: 'Royal Atelier',
      discoverText: 'Discover Royal Atelier Collection',
      leftImages: [
        'assets/images/IMG_0700.jpg',
        'assets/images/IMG_0707.jpg',
        'assets/images/IMG_0708.jpg'
      ],
      rightImages: [
        'assets/images/IMG_0709.jpg',
        'assets/images/IMG_0710.jpg',
        'assets/images/IMG_0701.jpg'
      ],
      descriptions: [
        'The pinnacle of bespoke artisanal mastery, reserved for patron commissions and monumental collector pieces. Adorned with semi-precious inlays, hand-hammered metals, and royal crest emblems.',
        'Every commission is documented in the Atelier Register with individualized provenance seals and certificates of artisanal heritage.',
        'A testament to uncompromising grandeur and the living continuation of royal palace craftsmanship in the modern era.'
      ]
    }
  };

  // 3. Interactive Collection Menu Switcher
  const menuItems = document.querySelectorAll('.collection-menu-item');
  const titleEl = document.querySelector('.about-collection-title');
  const discoverTextEl = document.querySelector('.link.discover');
  const leftImgEls = document.querySelectorAll('.collection-left-image-wrapper .image img');
  const rightImgEls = document.querySelectorAll('.current-collection-image-wrapper .image img');
  const descEls = document.querySelectorAll('.collection-right-description-text.desktop');

  function switchCollection(key) {
    const data = COLLECTIONS_DATA[key];
    if (!data) return;

    // Update active tab styling
    menuItems.forEach((item) => {
      const link = item.querySelector('.collection-link');
      const itemKey = item.getAttribute('data-collection') || (link ? link.textContent.trim().toLowerCase() : '');
      if (itemKey.includes(key) || key.includes(itemKey)) {
        item.classList.add('active');
        if (link) link.classList.add('active');
      } else {
        item.classList.remove('active');
        if (link) link.classList.remove('active');
      }
    });

    // Update Text & Discover Link
    if (titleEl) {
      if (typeof gsap !== 'undefined') {
        gsap.fromTo(titleEl, { opacity: 0, y: -10 }, { opacity: 1, y: 0, duration: 0.4 });
      }
      titleEl.textContent = data.title;
    }

    if (discoverTextEl) {
      discoverTextEl.textContent = data.discoverText;
    }

    // Update Left Images
    leftImgEls.forEach((img, idx) => {
      if (data.leftImages[idx]) {
        img.src = data.leftImages[idx];
      }
    });

    // Update Right Images
    rightImgEls.forEach((img, idx) => {
      if (data.rightImages[idx]) {
        img.src = data.rightImages[idx];
      }
    });

    // Update Descriptions
    descEls.forEach((p, idx) => {
      if (data.descriptions[idx]) {
        p.textContent = data.descriptions[idx];
        p.style.opacity = '1';
        p.style.visibility = 'visible';
      }
    });
  }

  menuItems.forEach((item) => {
    const link = item.querySelector('.collection-link');
    if (!link) return;

    link.addEventListener('click', (e) => {
      e.preventDefault();
      const text = link.textContent.trim().toLowerCase();
      let key = 'rivulet';
      if (text.includes('terracotta')) key = 'terracotta';
      else if (text.includes('silk') || text.includes('weave')) key = 'silk';
      else if (text.includes('filigree') || text.includes('brass')) key = 'filigree';
      else if (text.includes('ceramic')) key = 'ceramic';
      else if (text.includes('royal')) key = 'royal';

      switchCollection(key);
    });
  });

  // 4. Parallax Scroll / Scrubbing (if GSAP available)
  if (typeof gsap !== 'undefined' && typeof ScrollTrigger !== 'undefined') {
    try {
      gsap.registerPlugin(ScrollTrigger);

      const leftImages = gsap.utils.toArray('.collection-left-image-wrapper > .image');
      const rightImages = gsap.utils.toArray('.current-collection-image-wrapper > .image');
      const collectionSpacers = gsap.utils.toArray('.collection-spacer');

      if (collectionSpacers.length > 1 && leftImages.length > 1) {
        leftImages.forEach((img, i) => {
          gsap.set(img, { zIndex: leftImages.length - i });
        });
        rightImages.forEach((img, i) => {
          gsap.set(img, { zIndex: rightImages.length - i });
        });
      }
    } catch (e) {
      console.warn('Collection GSAP notice:', e);
    }
  }
});
