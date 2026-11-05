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
  const totalInrEl = document.getElementById('summary-total-inr');

  const totalQty = cart.reduce((sum, item) => sum + (item.qty || 1), 0);
  const subtotal = cart.reduce((sum, item) => sum + ((item.priceINR || item.price || 0) * (item.qty || 1)), 0);
  const inrTotal = subtotal; 

  if (countEl) countEl.textContent = `${totalQty} ${totalQty === 1 ? 'Piece' : 'Pieces'}`;
  if (subtotalEl) subtotalEl.textContent = `₹${subtotal.toLocaleString('en-IN')}`;
  if (totalInrEl) totalInrEl.textContent = `₹${inrTotal.toLocaleString('en-IN')}`;

  if (listEl) {
    listEl.innerHTML = cart.map(item => {
      const lineTotal = ((item.priceINR || item.price || 0) * (item.qty || 1));
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
          <div class="summary-item-price">₹${lineTotal.toLocaleString('en-IN')}</div>
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
  const subtotal = cart.reduce((sum, item) => sum + ((item.priceINR || item.price || 0) * (item.qty || 1)), 0);
  const inrTotal = subtotal;

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
    total_amount_inr: inrTotal,
    payment_method: paymentMethod,
    payment_status: paymentMethod === 'online' ? 'paid' : 'pending',
    order_status: 'pending',
    created_at: new Date().toISOString()
  };

  // Helper to persist order into local storage for static Vercel demo
  const saveOrderLocally = (order) => {
    try {
      const existing = JSON.parse(localStorage.getItem('ssrini_orders') || '[]');
      existing.unshift(order);
      localStorage.setItem('ssrini_orders', JSON.stringify(existing));
      localStorage.setItem('ssrini_last_order', JSON.stringify(order));
    } catch (e) {
      console.warn('Could not save order to localStorage:', e);
    }
  };

  // Process the order via API (falls back gracefully to client-side order completion on static host)
  fetch('../api/create-order.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(orderPayload)
  })
  .then(response => {
    if (!response.ok) throw new Error('Static host: backend PHP not available');
    return response.json();
  })
  .then(data => {
    if (data.success) {
      saveOrderLocally(orderPayload);
      if (data.razorpay_order_id) {
        // ONLINE PAYMENT: Initialize Razorpay Checkout
        const options = {
          "key": data.razorpay_key_id, 
          "amount": inrTotal * 100, 
          "currency": "INR",
          "name": "SSRINI Handcrafts",
          "description": "Bespoke Order #" + data.order_number,
          "order_id": data.razorpay_order_id,
          "handler": function (response) {
            verifyRazorpayPayment(response, data.order_id, orderPayload, submitBtn, btnText);
          },
          "prefill": {
            "name": customerData.name,
            "email": customerData.email,
            "contact": customerData.phone.replace('+91 ', '')
          },
          "theme": {
            "color": "#111111"
          },
          "modal": {
            "ondismiss": function() {
              if (submitBtn) {
                submitBtn.disabled = false;
                if (btnText) btnText.textContent = 'PLACE ORDER SECURELY';
              }
            }
          }
        };
        const rzp = new Razorpay(options);
        rzp.on('payment.failed', function (response){
          console.error("Razorpay Payment Failed: ", response.error);
          alert("Payment Failed: " + response.error.description);
          if (submitBtn) {
            submitBtn.disabled = false;
            if (btnText) btnText.textContent = 'PLACE ORDER SECURELY';
          }
        });
        rzp.open();
      } else {
        // COD PAYMENT: Complete immediately
        completeOrderSuccess(orderPayload);
      }
    } else {
      throw new Error(data.error || 'Failed to process order.');
    }
  })
  .catch(error => {
    console.warn('Static deployment mode active. Completing order client-side:', error);
    saveOrderLocally(orderPayload);
    completeOrderSuccess(orderPayload);
  });
}

// Verify Payment Integrity via Backend
function verifyRazorpayPayment(paymentData, internalOrderId, orderPayload, submitBtn, btnText) {
  if (btnText) btnText.textContent = 'VERIFYING PAYMENT...';
  
  fetch('../api/verify-payment.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      razorpay_payment_id: paymentData.razorpay_payment_id,
      razorpay_order_id: paymentData.razorpay_order_id,
      razorpay_signature: paymentData.razorpay_signature,
      internal_order_id: internalOrderId
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      // Payment Verified! 
      orderPayload.payment_status = 'paid'; // Force UI to show paid
      completeOrderSuccess(orderPayload);
    } else {
      throw new Error(data.error || 'Signature verification failed.');
    }
  })
  .catch(error => {
    console.error('Verification error:', error);
    alert('Payment verification failed. Please contact support if amount was deducted.');
    if (submitBtn) {
      submitBtn.disabled = false;
      if (btnText) btnText.textContent = 'PLACE ORDER SECURELY';
    }
  });
}

// Complete the UI Order Success Flow
function completeOrderSuccess(orderPayload) {
  // Fire and forget email notification
  fetch('../api/send-order-email.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(orderPayload)
  }).catch(err => console.error('Email dispatch failed:', err));

  // Clear cart from localStorage
  localStorage.removeItem('ssrini_cart');
  if (typeof updateGlobalCounters === 'function') {
    updateGlobalCounters();
  }

  // Render Order Confirmation
  showOrderConfirmation(orderPayload);
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

  document.getElementById('confirmed-total-amount').textContent = `₹${order.total_amount_inr.toLocaleString('en-IN')}`;

  // Render Purchased Items List
  const itemsListEl = document.getElementById('confirmed-items-list');
  if (itemsListEl) {
    itemsListEl.innerHTML = order.items.map(item => `
      <div class="confirmed-item-row">
        <span>${item.qty || 1}x ${item.title}</span>
        <strong>₹${((item.priceINR || item.price || 0) * (item.qty || 1)).toLocaleString('en-IN')}</strong>
      </div>
    `).join('');
  }

  // Configure Direct WhatsApp Concierge Button
  const waBtn = document.getElementById('btn-whatsapp-concierge');
  if (waBtn) {
    const waText = encodeURIComponent(
      `Hello SSRINI Atelier! I have placed an order #${order.order_number}.\n\n` +
      `Name: ${order.customer.name}\n` +
      `Total: ₹${order.total_amount_inr.toLocaleString('en-IN')}\n` +
      `Delivery To: ${order.customer.city}, ${order.customer.state}\n\n` +
      `Please provide dispatch and tracking updates.`
    );
    const baseWa = window.STORE_WHATSAPP_URL || '';
    if (!baseWa) {
      waBtn.style.display = 'none';
      return;
    }
    const separator = baseWa.includes('?') ? '&' : '?';
    waBtn.href = `${baseWa}${separator}text=${waText}`;
  }

  // Configure Direct Invoice CTA
  const invBtn = document.getElementById('btn-view-invoice');
  if (invBtn) {
    invBtn.href = `invoice.html?order=${encodeURIComponent(order.order_number)}`;
  }
}
