/**
 * SSRINI HANDCRAFTS — Global Auth Session Manager
 * Checks localStorage for a logged-in customer and updates the nav auth button.
 * Include this script on every page that has the #navAuthBtn element.
 */
(function () {
  'use strict';

  const authBtn = document.getElementById('navAuthBtn');
  if (!authBtn) return;

  // 1. Set the redirect URL on the button so when clicked, it remembers where we came from
  authBtn.href = '/ecom/auth/index.php?redirect=' + encodeURIComponent(window.location.pathname + window.location.search);

  // 2. Fetch secure session from PHP backend
  fetch('/ecom/api/auth.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'session_status' })
  })
  .then(res => res.json())
  .then(data => {
    if (data.loggedIn && data.customer) {
      setupLoggedInUI(data.customer);
    }
  })
  .catch(err => console.error('Auth session error:', err));

  function setupLoggedInUI(user) {


    const firstName = user.name.split(' ')[0];

    // Replace the inner HTML with user icon + name + dropdown
    authBtn.innerHTML = `
      <svg class="nav-auth-icon" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
        <circle cx="12" cy="7" r="4"></circle>
      </svg>
      <span>${firstName}</span>
    `;
    authBtn.title = `Signed in as ${user.name}`;
    authBtn.style.cursor = 'pointer';

    // Prevent navigation to auth page; instead show a mini dropdown
    authBtn.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();

      // Toggle dropdown
      let dropdown = document.getElementById('ssrini-auth-dropdown');
      if (dropdown) {
        dropdown.remove();
        return;
      }

      dropdown = document.createElement('div');
      dropdown.id = 'ssrini-auth-dropdown';
      dropdown.style.cssText = `
        position: absolute;
        top: calc(100% + 8px);
        right: 0;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.12), 0 2px 8px rgba(0,0,0,0.06);
        padding: 16px 20px;
        min-width: 220px;
        max-width: 90vw;
        z-index: 99999;
        font-family: 'Inter', sans-serif;
        box-sizing: border-box;
        animation: ssriniDropIn 0.2s ease;
      `;
      dropdown.innerHTML = `
        <div style="margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid #f0f0f0;">
          <div style="font-weight: 600; font-size: 14px; color: #1a1a1a; text-transform: capitalize;">${user.name}</div>
          <div style="font-size: 12px; color: #888; margin-top: 2px; text-transform: lowercase;">${user.email}</div>
        </div>
        <button id="ssrini-vault-btn" style="
          display: block;
          box-sizing: border-box;
          width: 100%;
          margin: 0 0 8px 0;
          padding: 10px 16px;
          background: #f8f8f8;
          color: #1a1a1a;
          border: 1px solid #e0e0e0;
          border-radius: 8px;
          font-size: 13px;
          font-weight: 500;
          cursor: pointer;
          font-family: 'Inter', sans-serif;
          transition: background 0.2s;
        ">My Vault / Orders</button>
        <button id="ssrini-logout-btn" style="
          display: block;
          box-sizing: border-box;
          width: 100%;
          margin: 0;
          padding: 10px 16px;
          background: #1a1a1a;
          color: #fff;
          border: none;
          border-radius: 8px;
          font-size: 13px;
          font-weight: 500;
          cursor: pointer;
          font-family: 'Inter', sans-serif;
          transition: background 0.2s;
        ">Sign Out</button>
      `;

      // Position relative to the auth button
      authBtn.style.position = 'relative';
      authBtn.appendChild(dropdown);

      // Stop propagation on dropdown container itself
      dropdown.addEventListener('click', function (evt) {
        evt.stopPropagation();
      });

      // Vault handler
      const vaultBtn = document.getElementById('ssrini-vault-btn');
      if (vaultBtn) {
        vaultBtn.addEventListener('click', function (evt) {
          evt.preventDefault();
          evt.stopPropagation();
          dropdown.remove();
          window.location.href = '/ecom/store/account.php';
        });
      }

      // Logout handler
      const logoutBtn = document.getElementById('ssrini-logout-btn');
      if (logoutBtn) {
        logoutBtn.addEventListener('click', function (evt) {
          evt.preventDefault();
          evt.stopPropagation();
          logoutBtn.textContent = 'Signing out...';
          logoutBtn.disabled = true;
          fetch('/ecom/api/auth.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'logout' })
          }).then(() => {
            try { localStorage.removeItem('ssrini_auth_user'); } catch (e) {}
            window.location.href = '/ecom/index.php';
          }).catch(() => {
            window.location.href = '/ecom/index.php';
          });
        });
      }

      // Close dropdown when clicking anywhere else
      setTimeout(() => {
        document.addEventListener('click', function closeDropdown(evt) {
          if (!dropdown.contains(evt.target) && evt.target !== authBtn) {
            dropdown.remove();
            document.removeEventListener('click', closeDropdown);
          }
        });
      }, 10);
    });
  }

  // Inject keyframe animation for dropdown
  if (!document.getElementById('ssrini-auth-styles')) {
    const style = document.createElement('style');
    style.id = 'ssrini-auth-styles';
    style.textContent = `
      @keyframes ssriniDropIn {
        from { opacity: 0; transform: translateY(-6px); }
        to { opacity: 1; transform: translateY(0); }
      }
    `;
    document.head.appendChild(style);
  }

})();
