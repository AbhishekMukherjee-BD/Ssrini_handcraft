/**
 * SSRINI Handcrafts - Service Worker Registration
 * Provides robust offline PWA support and persistent asset caching
 */

(function () {
  'use strict';

  if (!('serviceWorker' in navigator)) {
    return;
  }

  // Register the Service Worker after page loads to preserve maximum initial bandwidth
  window.addEventListener('load', function () {
    navigator.serviceWorker
      .register('/ecom/sw.js', { scope: '/ecom/' })
      .catch(function (error) {
        console.warn('SSRINI SW Registration notice:', error);
      });
  });
})();
