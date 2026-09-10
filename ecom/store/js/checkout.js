/**
 * SSRINI Luxury Checkout Engine
 * Maps directly to MySQL database schema (customers, orders, order_items, invoices)
 */

document.addEventListener('DOMContentLoaded', () => {
  initCheckoutPage();
});

function initCheckoutPage() {
  const cart = getCart();
  const emptyView = document.getElementById('checkout-empty-state');
  const activeGrid = document.getElementById('checkout-active-grid');
  const form = document.getElementById('checkout-form');

  if (!cart || cart.length === 0) {
    if (emptyView) emptyView.style.display = 'block';
    if (activeGrid) activeGrid.style.display = 'none';
    return;
  }

  if (emptyView) emptyView.style.display = 'none';
  if (activeGrid) activeGrid.style.display = 'grid';

  // Prefill from Session
  try {
    const user = JSON.parse(localStorage.getItem('ssrini_user') || 'null');
    if (user) {
      const nameInput = document.getElementById('customer-name');
      const emailInput = document.getElementById('customer-email');
      const phoneInput = document.getElementById('customer-phone');
      if (nameInput && user.first_name) nameInput.value = user.first_name + ' ' + (user.last_name || '');
      if (emailInput && user.email) emailInput.value = user.email;
      if (phoneInput && user.phone) phoneInput.value = user.phone;
    }
  } catch(e) {}

  // Render Order Summary in Right Column
  renderOrderSummary(cart);

  // Payment Method Selection listeners
  initPaymentMethodSelector();

  // Form submission handler
  if (form) {
    form.addEventListener('submit', handleOrderSubmission);
  }
}

// Get Cart from localStorage
function getCart() {
  try {
    return JSON.parse(localStorage.getItem('ssrini_cart') || '[]');
  } catch (e) {
    return [];
  }
}

// Render Order Summary
function renderOrderSummary(cart) {
  const countEl = document.getElementById('summary-items-count');
  const listEl = document.getElementById('summary-items-list');
  const subtotalEl = document.getElementById('summary-subtotal');
  const totalUsdEl = document.getElementById('summary-total-usd');
  const totalInrEl = document.getElementById('summary-total-inr');

  const totalQty = cart.reduce((sum, item) => sum + (item.qty || 1), 0);
  const subtotal = cart.reduce((sum, item) => sum + ((item.price || 0) * (item.qty || 1)), 0);
  const inrTotal = Math.round(subtotal * 83); // USD to INR conversion approximation

  if (countEl) countEl.textContent = `${totalQty} ${totalQty === 1 ? 'Piece' : 'Pieces'}`;
  if (subtotalEl) subtotalEl.textContent = `$${subtotal.toFixed(2)}`;
  if (totalUsdEl) totalUsdEl.textContent = `$${subtotal.toFixed(2)}`;
  if (totalInrEl) totalInrEl.textContent = `₹${inrTotal.toLocaleString('en-IN')}`;

  if (listEl) {
    listEl.innerHTML = cart.map(item => {
      const lineTotal = ((item.price || 0) * (item.qty || 1)).toFixed(2);
      return `
        <div class="summary-item-row">
          <div class="summary-item-thumb-wrapper">
            <img src="${item.image}" alt="${item.title}" class="summary-item-thumb">
            <span class="summary-item-qty-badge">${item.qty || 1}</span>
          </div>
          <div class="summary-item-info">
            <h4 class="summary-item-title">${item.title}</h4>
            <div class="summary-item-meta">Size: ${item.size || 'Free'} · ${item.weight || '1 LBS'}</div>
          </div>
          <div class="summary-item-price">$${lineTotal}</div>
        </div>
      `;
    }).join('');
  }
}

// Payment Method Selection Toggle
function initPaymentMethodSelector() {
  const cards = document.querySelectorAll('.payment-method-card');
  cards.forEach(card => {
    card.addEventListener('click', () => {
      cards.forEach(c => c.classList.remove('active'));
      card.classList.add('active');
    });
  });
}

// Validate Form Fields
function validateCheckoutForm(form) {
  let isValid = true;

  const nameInput = form.querySelector('#customer-name');
  const phoneInput = form.querySelector('#customer-phone');
  const addressInput = form.querySelector('#customer-address');
  const cityInput = form.querySelector('#customer-city');
  const stateInput = form.querySelector('#customer-state');
  const pincodeInput = form.querySelector('#customer-pincode');

  // Clear previous errors
  form.querySelectorAll('.form-input').forEach(input => input.classList.remove('invalid'));

  if (!nameInput.value.trim()) {
    nameInput.classList.add('invalid');
    isValid = false;
  }

  const phoneVal = phoneInput.value.trim().replace(/\D/g, '');
  if (!phoneVal || phoneVal.length < 10) {
    phoneInput.classList.add('invalid');
    isValid = false;
  }

  if (!addressInput.value.trim()) {
    addressInput.classList.add('invalid');
    isValid = false;
  }

  if (!cityInput.value.trim()) {
    cityInput.classList.add('invalid');
    isValid = false;
  }

  if (!stateInput.value) {
    stateInput.classList.add('invalid');
    isValid = false;
  }

  const pinVal = pincodeInput.value.trim().replace(/\D/g, '');
  if (!pinVal || pinVal.length < 6) {
    pincodeInput.classList.add('invalid');
    isValid = false;
  }

  const termsCheckbox = form.querySelector('#checkoutTermsCheckbox');
  const termsError = form.querySelector('#error-checkout-terms');
  if (termsCheckbox && !termsCheckbox.checked) {
    if (termsError) termsError.style.display = 'block';
    isValid = false;
  } else if (termsError) {
    termsError.style.display = 'none';
  }

  return isValid;
}

// Handle Order Submission
function handleOrderSubmission(e) {
  e.preventDefault();
  const form = e.target;

  if (!validateCheckoutForm(form)) {
    const firstInvalid = form.querySelector('.form-input.invalid');
    if (firstInvalid) {
      firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
      firstInvalid.focus();
    }
    return;
  }

  const submitBtn = document.getElementById('btn-place-order');
  const btnText = submitBtn.querySelector('.btn-text');
  const btnSpinner = submitBtn.querySelector('.btn-spinner');

  if (submitBtn) {
    submitBtn.disabled = true;
    if (btnText) btnText.textContent = 'AUTHORIZING ORDER...';
  }

  const cart = getCart();
  const subtotal = cart.reduce((sum, item) => sum + ((item.price || 0) * (item.qty || 1)), 0);
  const inrTotal = Math.round(subtotal * 83);

  // Generate Unique Order Number
  const randomSuffix = Math.floor(10000 + Math.random() * 90000);
  const orderNumber = `SSR-2026-${randomSuffix}`;

  // Gather Customer Data
  const customerData = {
    name: form.querySelector('#customer-name').value.trim(),
    phone: '+91 ' + form.querySelector('#customer-phone').value.trim().replace(/\D/g, ''),
    email: form.querySelector('#customer-email').value.trim() || 'N/A',
    address: form.querySelector('#customer-address').value.trim(),
    city: form.querySelector('#customer-city').value.trim(),
    state: form.querySelector('#customer-state').value,
    pincode: form.querySelector('#customer-pincode').value.trim(),
    notes: form.querySelector('#order-notes').value.trim() || ''
  };

  const paymentMethodInput = form.querySelector('input[name="payment_method"]:checked');
  const paymentMethod = paymentMethodInput ? paymentMethodInput.value : 'cod';

  // Construct Final Order Transaction Payload (MySQL mapped)
  const orderPayload = {
    order_number: orderNumber,
    customer: customerData,
    items: cart,
    total_amount_usd: subtotal.toFixed(2),
    total_amount_inr: inrTotal,
    payment_method: paymentMethod,
    payment_status: paymentMethod === 'online' ? 'paid' : 'pending',
    order_status: 'pending',
    created_at: new Date().toISOString()
  };

  // Asynchronously dispatch order to backend API
  fetch('http://localhost:8080/api/create-order.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        email: customerData.email,
        name: customerData.name,
        phone: customerData.phone,
        shipping: {
            address: customerData.address,
            city: customerData.city,
            state: customerData.state,
            pincode: customerData.pincode
        },
        items: cart,
        payment_method: paymentMethod,
        shipping_cost: 0,
        discount_amount: 0,
        order_notes: customerData.notes
    })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      console.log('[SSRINI Order Placed]:', data);
      orderPayload.order_number = data.order_number; // Update with real order number
    } else {
      console.error('[SSRINI API Error]:', data.message);
      // Fallback/continue with local payload if error
    }
  })
  .catch(err => {
    console.error('[SSRINI Fetch Error]:', err);
  })
  .finally(() => {
    // Clear cart from localStorage
    localStorage.removeItem('ssrini_cart');
    if (typeof updateGlobalCounters === 'function') {
      updateGlobalCounters();
    }

    // Render Order Confirmation Screen
    showOrderConfirmation(orderPayload);
  });
}

// Display Order Confirmation View
function showOrderConfirmation(order) {
  const activeGrid = document.getElementById('checkout-active-grid');
  const successView = document.getElementById('checkout-success-view');

  if (activeGrid) activeGrid.style.display = 'none';
  if (successView) successView.style.display = 'block';

  window.scrollTo({ top: 0, behavior: 'smooth' });

  // Hydrate Confirmation Fields
  document.getElementById('confirmed-order-number').textContent = order.order_number;
  document.getElementById('confirmed-customer-name').textContent = order.customer.name;
  document.getElementById('confirmed-customer-phone').textContent = order.customer.phone;
  document.getElementById('confirmed-customer-address').textContent = order.customer.address;
  document.getElementById('confirmed-customer-citystate').textContent = `${order.customer.city}, ${order.customer.state} - ${order.customer.pincode}`;

  const methodLabel = order.payment_method === 'cod' ? 'Cash on Delivery (COD)' : 'Online Payment (Prepaid)';
  document.getElementById('confirmed-payment-method').textContent = methodLabel;

  const statusPill = document.getElementById('confirmed-payment-status');
  if (order.payment_status === 'paid') {
    statusPill.textContent = 'Paid';
    statusPill.className = 'status-pill paid';
  } else {
    statusPill.textContent = 'Pending (COD)';
    statusPill.className = 'status-pill pending';
  }

  document.getElementById('confirmed-total-amount').textContent = `$${order.total_amount_usd} (₹${order.total_amount_inr.toLocaleString('en-IN')})`;

  // Render Purchased Items List
  const itemsListEl = document.getElementById('confirmed-items-list');
  if (itemsListEl) {
    itemsListEl.innerHTML = order.items.map(item => `
      <div class="confirmed-item-row">
        <span>${item.qty || 1}x ${item.title}</span>
        <strong>$${((item.price || 0) * (item.qty || 1)).toFixed(2)}</strong>
      </div>
    `).join('');
  }

  // Configure Direct WhatsApp Concierge Button
  const waBtn = document.getElementById('btn-whatsapp-concierge');
  if (waBtn) {
    const waText = encodeURIComponent(
      `Hello SSRINI Atelier! I have placed an order #${order.order_number}.\n\n` +
      `Name: ${order.customer.name}\n` +
      `Total: $${order.total_amount_usd} (₹${order.total_amount_inr.toLocaleString('en-IN')})\n` +
      `Delivery To: ${order.customer.city}, ${order.customer.state}\n\n` +
      `Please provide dispatch and tracking updates.`
    );
    waBtn.href = `https://wa.me/919836081994?text=${waText}`;
  }
}
