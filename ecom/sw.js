/**
 * SSRINI Handcrafts - Luxury PWA Service Worker
 * Implements "Download Once, Cache Forever" Architecture
 * Strategy:
 *  - Cache-First for media assets, hero animation frames, fonts, images
 *  - Stale-While-Revalidate for CSS, JS, and app shell
 *  - Strict Network-Only / Bypass for dynamic APIs, checkout, payments, and admin
 */

const CACHE_VERSION = 'ssrini-v2';
const STATIC_CACHE = `${CACHE_VERSION}-static`;
const MEDIA_CACHE = `${CACHE_VERSION}-media`;

// Core App Shell to pre-cache on install
const APP_SHELL = [
  '/ecom/',
  '/ecom/index.html',
  '/ecom/store/index.html',
  '/ecom/store/js/products-data.js',
  '/ecom/collection/js/collection-data.js',
  '/ecom/store/assets/images/logo.png',
  '/ecom/js/app.js?v=20260909_timed',
  '/ecom/js/gsap.min.js',
  '/ecom/js/ScrollTrigger.min.js',
  '/ecom/js/cursor.js',
  '/ecom/store/js/cart-drawer.js',
  '/ecom/landing/js/home.js',
  '/ecom/landing/preloader_images/1.jpg',
  '/ecom/landing/preloader_images/2.jpeg',
  '/ecom/landing/preloader_images/3.jpeg',
  '/ecom/landing/preloader_images/4.jpeg',
  '/ecom/landing/preloader_images/5.jpeg',
  '/ecom/landing/preloader_images/6.jpeg',
  '/ecom/landing/preloader_images/7.jpeg'
];

// Patterns that MUST NEVER be cached (Dynamic & Sensitive)
const NEVER_CACHE_PATTERNS = [
  /\/api\//i,
  /\/admin\//i,
  /create-order\.php/i,
  /verify-payment\.php/i,
  /razorpay-webhook\.php/i,
  /auth\.php/i,
  /cart-sync\.php/i,
  /login\.php/i,
  /send-order-email\.php/i
];

// 1. INSTALL: Pre-cache App Shell
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(STATIC_CACHE).then((cache) => {
      // Add each item with fallback so failure of 1 file doesn't break install
      return Promise.allSettled(
        APP_SHELL.map((url) =>
          fetch(url, { cache: 'no-cache' })
            .then((res) => {
              if (res.ok) return cache.put(url, res);
            })
            .catch(() => {})
        )
      );
    }).then(() => self.skipWaiting())
  );
});

// 2. ACTIVATE: Clean Up Outdated Cache Versions
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.map((key) => {
          if (key !== STATIC_CACHE && key !== MEDIA_CACHE) {
            return caches.delete(key);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// 3. FETCH INTERCEPTION: The Routing Engine
self.addEventListener('fetch', (event) => {
  const request = event.request;
  const url = new URL(request.url);

  // Only handle GET requests and same-origin (or font CDN) requests
  if (request.method !== 'GET') {
    return;
  }

  // A. Strict Bypass for APIs, Auth, Checkout, and Admin
  for (const pattern of NEVER_CACHE_PATTERNS) {
    if (pattern.test(url.pathname)) {
      return; // Hand over to browser network directly
    }
  }

  // B. Hero Animation Frames & Heavy Visual Media: CACHE-FIRST
  const isHeroFrame = url.pathname.includes('/hero_frames/') || url.pathname.includes('/preloader_images/');
  const isImageOrFont = /\.(jpg|jpeg|png|webp|svg|gif|woff2|woff|ttf)$/i.test(url.pathname);

  if (isHeroFrame || isImageOrFont) {
    event.respondWith(
      caches.match(request).then((cachedResponse) => {
        if (cachedResponse) {
          // Instant 0ms response from local Cache Storage
          return cachedResponse;
        }

        // Not cached yet: Fetch once from network, clone, and save forever
        return fetch(request).then((networkResponse) => {
          if (!networkResponse || networkResponse.status !== 200) {
            return networkResponse;
          }

          const responseClone = networkResponse.clone();
          caches.open(MEDIA_CACHE).then((cache) => {
            cache.put(request, responseClone);
          });

          return networkResponse;
        }).catch(() => {
          // Offline fallback if needed
          return cachedResponse;
        });
      })
    );
    return;
  }

  // C. Stylesheets, JavaScript & App Shell: STALE-WHILE-REVALIDATE
  const isCodeAsset = /\.(css|js)$/i.test(url.pathname);
  if (isCodeAsset) {
    event.respondWith(
      caches.match(request).then((cachedResponse) => {
        const fetchPromise = fetch(request).then((networkResponse) => {
          if (networkResponse && networkResponse.status === 200) {
            const responseClone = networkResponse.clone();
            caches.open(STATIC_CACHE).then((cache) => {
              cache.put(request, responseClone);
            });
          }
          return networkResponse;
        }).catch(() => cachedResponse);

        return cachedResponse || fetchPromise;
      })
    );
    return;
  }

  // D. HTML Page Documents: Network-First with Cache Fallback
  if (request.mode === 'navigate' || request.headers.get('accept')?.includes('text/html')) {
    event.respondWith(
      fetch(request)
        .then((networkResponse) => {
          if (networkResponse && networkResponse.status === 200) {
            const responseClone = networkResponse.clone();
            caches.open(STATIC_CACHE).then((cache) => {
              cache.put(request, responseClone);
            });
          }
          return networkResponse;
        })
        .catch(() => caches.match(request) || caches.match('/ecom/'))
    );
  }
});
