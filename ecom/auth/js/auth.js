/**
 * SSRINI HANDCRAFTS — LUXURY AUTH SECTION SCRIPT
 * Faithful recreation & interactive dynamics of SolaceUI Auth Section 2
 */

(function () {
  'use strict';

  // State
  let currentSlide = 0;
  let carouselInterval = null;
  let currentMode = 'signin'; // 'signin' | 'signup'

  // DOM Elements - Carousel
  const slides = document.querySelectorAll('.showcase-slide');
  const pills = document.querySelectorAll('.pill-dot');
  const promptTag = document.getElementById('promptTag');
  const promptText = document.getElementById('promptText');
  const promptCycleBtn = document.getElementById('promptCycleBtn');
  const showcaseHeadline = document.getElementById('showcaseHeadline');
  const promptCard = document.getElementById('showcasePromptCard');

  // DOM Elements - Auth Mode Switching
  const tabSignIn = document.getElementById('tabSignIn');
  const tabSignUp = document.getElementById('tabSignUp');
  const authTitle = document.getElementById('authTitle');
  const authSubtitle = document.getElementById('authSubtitle');
  const nameRow = document.getElementById('nameRow');
  const signInExtras = document.getElementById('signInExtras');
  const signUpConsents = document.getElementById('signUpConsents');
  const submitBtn = document.getElementById('submitBtn');
  const submitBtnText = document.getElementById('submitBtnText');
  const footerPromptText = document.getElementById('footerPromptText');
  const switchModeLink = document.getElementById('switchModeLink');

  // DOM Elements - Inputs & Controls
  const authForm = document.getElementById('authForm');
  const firstNameInput = document.getElementById('firstNameInput');
  const lastNameInput = document.getElementById('lastNameInput');
  const emailInput = document.getElementById('emailInput');
  const passwordInput = document.getElementById('passwordInput');
  const passwordToggleBtn = document.getElementById('passwordToggleBtn');
  const eyeIcon = document.getElementById('eyeIcon');
  const termsCheckbox = document.getElementById('termsCheckbox');
  const authToast = document.getElementById('authToast');
  const toastMessage = document.getElementById('toastMessage');
  const btnGoogleAuth = document.getElementById('btnGoogleAuth');
  const btnAppleAuth = document.getElementById('btnAppleAuth');
  const forgotPasswordLink = document.getElementById('forgotPasswordLink');

  // Slide Data
  const slideData = [
    {
      tag: "✦ /heritage_masterpiece",
      prompt: "Imperial Kundan Necklace in 22k Gold Filigree with Natural Zambian Emeralds",
      headline: "Every jewel is born from a legacy of <span>patience & royal devotion</span>"
    },
    {
      tag: "✦ /artisan_chronicles",
      prompt: "Master Goldsmith Hand-setting Cabochon Gemstones in Chased Antique Brass",
      headline: "Preserving 500-year-old <span>craft traditions</span> for modern collectors"
    },
    {
      tag: "✦ /bespoke_creations",
      prompt: "Royal Heritage Choker with Meenakari Enameling & Polki Uncut Diamonds",
      headline: "Crafted by human hands, sculpted for <span>generations of grace</span>"
    },
    {
      tag: "✦ /temple_goldsmith",
      prompt: "Intricate Filigree Bangles Cast in Sacred Temple Geometry",
      headline: "Timeless allure designed to become your family's <span>heirloom treasure</span>"
    }
  ];

  /* --------------------------------------------------------------------------
     CAROUSEL LOGIC
     -------------------------------------------------------------------------- */
  function goToSlide(index) {
    if (index < 0) index = slides.length - 1;
    if (index >= slides.length) index = 0;

    currentSlide = index;

    // Update slides
    slides.forEach((slide, idx) => {
      if (idx === currentSlide) {
        slide.classList.add('active');
      } else {
        slide.classList.remove('active');
      }
    });

    // Update indicators
    pills.forEach((pill, idx) => {
      if (idx === currentSlide) {
        pill.classList.add('active');
        pill.setAttribute('aria-selected', 'true');
      } else {
        pill.classList.remove('active');
        pill.setAttribute('aria-selected', 'false');
      }
    });

    // Update prompt card with micro-fade
    if (promptCard) {
      promptCard.style.opacity = '0.6';
      promptCard.style.transform = 'translateY(4px)';
      setTimeout(() => {
        const data = slideData[currentSlide];
        if (promptTag) promptTag.textContent = data.tag;
        if (promptText) promptText.textContent = data.prompt;
        promptCard.style.opacity = '1';
        promptCard.style.transform = 'translateY(0)';
      }, 160);
    }

    // Update headline with subtle fade
    if (showcaseHeadline) {
      showcaseHeadline.style.opacity = '0.4';
      showcaseHeadline.style.transform = 'translateY(3px)';
      showcaseHeadline.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
      setTimeout(() => {
        showcaseHeadline.innerHTML = slideData[currentSlide].headline;
        showcaseHeadline.style.opacity = '1';
        showcaseHeadline.style.transform = 'translateY(0)';
      }, 160);
    }
  }

  function startCarouselTimer() {
    stopCarouselTimer();
    carouselInterval = setInterval(() => {
      goToSlide(currentSlide + 1);
    }, 5200);
  }

  function stopCarouselTimer() {
    if (carouselInterval) {
      clearInterval(carouselInterval);
      carouselInterval = null;
    }
  }

  // Next slide action button on prompt card
  if (promptCycleBtn) {
    promptCycleBtn.addEventListener('click', (e) => {
      e.preventDefault();
      promptCycleBtn.style.transform = 'scale(0.92) rotate(180deg)';
      setTimeout(() => {
        promptCycleBtn.style.transform = '';
      }, 350);
      goToSlide(currentSlide + 1);
      startCarouselTimer();
    });
  }

  // Pill dot controls
  pills.forEach((pill) => {
    pill.addEventListener('click', () => {
      const targetIndex = parseInt(pill.getAttribute('data-target'), 10);
      goToSlide(targetIndex);
      startCarouselTimer();
    });
  });

  /* --------------------------------------------------------------------------
     MODE SWITCHING (Sign In <-> Sign Up)
     -------------------------------------------------------------------------- */
  function setAuthMode(mode) {
    currentMode = mode;

    if (mode === 'signin') {
      tabSignIn.classList.add('active');
      tabSignIn.setAttribute('aria-checked', 'true');
      tabSignUp.classList.remove('active');
      tabSignUp.setAttribute('aria-checked', 'false');

      authTitle.textContent = 'Welcome Back';
      authSubtitle.textContent = 'Enter your credentials to access your private vault and bespoke orders.';

      if (nameRow) nameRow.style.display = 'none';
      if (signInExtras) signInExtras.style.display = 'flex';
      if (signUpConsents) signUpConsents.style.display = 'none';

      submitBtnText.textContent = 'Sign In to Account';
      footerPromptText.textContent = "Don't have an account?";
      switchModeLink.textContent = 'Create one now';

      passwordInput.setAttribute('autocomplete', 'current-password');
    } else {
      tabSignUp.classList.add('active');
      tabSignUp.setAttribute('aria-checked', 'true');
      tabSignIn.classList.remove('active');
      tabSignIn.setAttribute('aria-checked', 'false');

      authTitle.textContent = 'Create an Account';
      authSubtitle.textContent = 'Join the SSRINI circle for exclusive previews, bespoke creations & concierge access.';

      if (nameRow) nameRow.style.display = 'grid';
      if (signInExtras) signInExtras.style.display = 'none';
      if (signUpConsents) signUpConsents.style.display = 'flex';

      submitBtnText.textContent = 'Create SSRINI Account';
      footerPromptText.textContent = 'Already have an account?';
      switchModeLink.textContent = 'Sign in here';

      passwordInput.setAttribute('autocomplete', 'new-password');
    }
  }

  if (tabSignIn) {
    tabSignIn.addEventListener('click', () => setAuthMode('signin'));
  }

  if (tabSignUp) {
    tabSignUp.addEventListener('click', () => setAuthMode('signup'));
  }

  if (switchModeLink) {
    switchModeLink.addEventListener('click', (e) => {
      e.preventDefault();
      setAuthMode(currentMode === 'signin' ? 'signup' : 'signin');
    });
  }

  /* --------------------------------------------------------------------------
     PASSWORD VISIBILITY TOGGLE
     -------------------------------------------------------------------------- */
  if (passwordToggleBtn && passwordInput && eyeIcon) {
    passwordToggleBtn.addEventListener('click', () => {
      const isPassword = passwordInput.getAttribute('type') === 'password';
      passwordInput.setAttribute('type', isPassword ? 'text' : 'password');

      if (isPassword) {
        // Eye with slash icon (hide)
        eyeIcon.innerHTML = `
          <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
          <line x1="1" y1="1" x2="23" y2="23"></line>
        `;
      } else {
        // Normal eye icon (show)
        eyeIcon.innerHTML = `
          <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
          <circle cx="12" cy="12" r="3"></circle>
        `;
      }
    });
  }

  /* --------------------------------------------------------------------------
     TOAST NOTIFICATIONS
     -------------------------------------------------------------------------- */
  let toastTimeout = null;
  function showToast(msg, type = 'success') {
    if (!authToast || !toastMessage) return;

    clearTimeout(toastTimeout);
    toastMessage.textContent = msg;

    authToast.className = 'auth-toast';
    authToast.classList.add(type === 'error' ? 'error' : 'success');
    authToast.classList.add('show');

    toastTimeout = setTimeout(() => {
      authToast.classList.remove('show');
    }, 3800);
  }

  /* --------------------------------------------------------------------------
     FORM VALIDATION & SUBMISSION
     -------------------------------------------------------------------------- */
  if (authForm) {
    authForm.addEventListener('submit', (e) => {
      e.preventDefault();

      const email = emailInput ? emailInput.value.trim().toLowerCase() : '';
      const password = passwordInput ? passwordInput.value.trim() : '';

      // Email validation
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!email || !emailRegex.test(email)) {
        showToast('Please enter a valid email address.', 'error');
        if (emailInput) emailInput.focus();
        return;
      }

      // Password validation
      if (!password || password.length < 6) {
        showToast('Password must contain at least 6 characters.', 'error');
        if (passwordInput) passwordInput.focus();
        return;
      }

      // Sign up additional checks
      if (currentMode === 'signup') {
        const firstName = firstNameInput ? firstNameInput.value.trim() : '';
        if (!firstName) {
          showToast('Please enter your first name.', 'error');
          if (firstNameInput) firstNameInput.focus();
          return;
        }

        if (termsCheckbox && !termsCheckbox.checked) {
          showToast('Please accept the Terms of Service to proceed.', 'error');
          termsCheckbox.focus();
          return;
        }
      }

      // Loading state
      submitBtn.classList.add('loading');
      const originalText = submitBtnText.textContent;
      submitBtnText.textContent = currentMode === 'signin' ? 'Authenticating...' : 'Creating Account...';

      // Build request payload for the real backend API
      const lastName = lastNameInput ? lastNameInput.value.trim() : '';
      const fullName = currentMode === 'signup'
        ? (firstNameInput ? firstNameInput.value.trim() : '') + (lastName ? ' ' + lastName : '')
        : '';

      const payload = currentMode === 'signup'
        ? { action: 'register', name: fullName, email: email, password: password }
        : { action: 'login', email: email, password: password };

      fetch('/ecom/api/auth.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      })
      .then(res => res.json())
      .then(data => {
        submitBtn.classList.remove('loading');
        submitBtnText.textContent = originalText;

        if (!data.success) {
          showToast(data.error || data.message || 'Authentication failed.', 'error');
          return;
        }

        // We no longer strictly need localStorage because PHP handles the secure session!
        // But we can keep a tiny footprint if needed.
        try {
          localStorage.setItem('ssrini_auth_user', JSON.stringify({ name: data.customer_name }));
          
          // Sync local cart to database
          const localCartStr = localStorage.getItem('ssrini_cart');
          if (localCartStr) {
            const localCart = JSON.parse(localCartStr);
            if (localCart.length > 0) {
              fetch('/ecom/api/cart-sync.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'push', cart: localCart })
              }).catch(e => console.warn('Cart sync failed:', e));
            }
          }
        } catch (err) {
          console.warn('LocalStorage/Sync error:', err);
        }

        if (currentMode === 'signin') {
          showToast(`Welcome back, ${data.customer_name}! Redirecting...`, 'success');
        } else {
          showToast(`Welcome to the SSRINI Circle, ${data.customer_name}! Redirecting...`, 'success');
        }

        setTimeout(() => {
          const urlParams = new URLSearchParams(window.location.search);
          const redirect = urlParams.get('redirect');
          if (redirect) {
            window.location.href = redirect;
          } else {
            window.location.href = '/ecom/index.php';
          }
        }, 1400);
      })
      .catch(err => {
        submitBtn.classList.remove('loading');
        submitBtnText.textContent = originalText;
        console.error('Auth API error:', err);
        showToast('Network error. Please try again.', 'error');
      });
    });
  }

  /* --------------------------------------------------------------------------
     SOCIAL OAUTH CLICK HANDLERS
     -------------------------------------------------------------------------- */
  if (btnGoogleAuth) {
    btnGoogleAuth.addEventListener('click', () => {
      showToast('Redirecting to Google Secure Authentication...', 'success');
    });
  }

  if (btnAppleAuth) {
    btnAppleAuth.addEventListener('click', () => {
      showToast('Connecting to Apple ID Secure Sign In...', 'success');
    });
  }

  if (forgotPasswordLink) {
    forgotPasswordLink.addEventListener('click', () => {
      const email = emailInput ? emailInput.value.trim() : '';
      if (!email) {
        showToast('Please enter your email above to receive a reset link.', 'error');
        if (emailInput) emailInput.focus();
      } else {
        showToast(`Password recovery link dispatched to ${email}`, 'success');
      }
    });
  }

  // Dynamic Back Home button resolution based on redirect parameter
  try {
    const urlParams = new URLSearchParams(window.location.search);
    const redirectParam = urlParams.get('redirect');
    const backBtn = document.getElementById('backHomeBtn');
    const backBtnText = document.getElementById('backHomeBtnText');
    if (redirectParam && backBtn) {
      backBtn.href = redirectParam;
      if (backBtnText) {
        if (redirectParam.includes('collection')) {
          backBtnText.textContent = 'Return to Atelier';
        } else {
          backBtnText.textContent = 'Return to Store';
        }
      }
    }
  } catch (err) {
    console.warn('Redirect resolve error:', err);
  }

  // Initialize
  setAuthMode('signin');
  goToSlide(0);
  startCarouselTimer();

})();
