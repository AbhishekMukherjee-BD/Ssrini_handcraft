/**
 * SSRINI Handcrafts - Service Worker Registration & Progressive Asset Pre-Cacher
 * Automatically registers the Service Worker and progressively streams
 * all 480 hero animation frames into browser Cache Storage during idle time.
 */

(function () {
  'use strict';

  if (!('serviceWorker' in navigator)) {
    return;
  }

  // 1. Register the Service Worker
  window.addEventListener('load', function () {
    navigator.serviceWorker
      .register('/ecom/sw.js', { scope: '/ecom/' })
      .then(function (registration) {
        // Registration successful
        initBackgroundFramePrecache();
      })
      .catch(function (error) {
        console.warn('SSRINI SW Registration failed:', error);
      });
  });

  // 2. Progressive Hero Frame Pre-Cacher
  function initBackgroundFramePrecache() {
    // Only run precache once per session to preserve CPU
    if (sessionStorage.getItem('ssrini_frames_precached') === 'true') {
      return;
    }

    const TOTAL_FRAMES = 480;
    const BATCH_SIZE = 6;
    let currentIndex = 1;

    // Use requestIdleCallback or setTimeout to run without blocking UI rendering
    const scheduleNext = window.requestIdleCallback || function (cb) { setTimeout(cb, 100); };

    function fetchBatch() {
      if (currentIndex > TOTAL_FRAMES) {
        sessionStorage.setItem('ssrini_frames_precached', 'true');
        return;
      }

      const batchPromises = [];
      const limit = Math.min(currentIndex + BATCH_SIZE, TOTAL_FRAMES + 1);

      for (let i = currentIndex; i < limit; i++) {
        const frameNum = String(i).padStart(4, '0');
        const frameUrl = `/ecom/assets/home/hero_frames/frame_${frameNum}.webp`;
        batchPromises.push(
          fetch(frameUrl, { priority: 'low' }).catch(function () {})
        );
      }

      currentIndex = limit;

      Promise.all(batchPromises).then(function () {
        // Schedule next batch on browser idle
        scheduleNext(fetchBatch);
      });
    }

    // Delay start until 2 seconds after initial load to prioritize hero viewport rendering
    setTimeout(function () {
      scheduleNext(fetchBatch);
    }, 2000);
  }
})();
