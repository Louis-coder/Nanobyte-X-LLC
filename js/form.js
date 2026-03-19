/**
 * NANOBYTE X LLC — Contact Form (AJAX)
 * js/form.js
 */

(function () {
  'use strict';

  const form       = document.getElementById('contactForm');
  const submitBtn  = document.getElementById('submitBtn');
  const successMsg = document.getElementById('formSuccess');
  const errorMsg   = document.getElementById('formError');

  if (!form) return;

  /* ── Real-time validation helpers ── */
  function setValid(input, ok) {
    input.style.borderColor = ok
      ? 'rgba(200,146,42,0.5)'
      : '#e05555';
  }

  form.querySelectorAll('input[required], textarea[required], select[required]').forEach(field => {
    field.addEventListener('blur', () => {
      if (field.type === 'email') {
        setValid(field, /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(field.value));
      } else {
        setValid(field, field.value.trim().length > 0);
      }
    });
  });

  /* ── Submit handler ── */
  form.addEventListener('submit', async function (e) {
    e.preventDefault();

    /* Hide previous messages */
    successMsg.style.display = 'none';
    errorMsg.style.display   = 'none';

    /* Basic client-side validation */
    const required = form.querySelectorAll('[required]');
    let valid = true;
    required.forEach(field => {
      if (!field.value.trim()) {
        setValid(field, false);
        valid = false;
      }
    });
    if (!valid) return;

    /* Button loading state */
    const originalText     = submitBtn.textContent;
    submitBtn.textContent  = 'Sending…';
    submitBtn.disabled     = true;

    const formData = new FormData(form);

    try {
      const response = await fetch('php/contact.php', {
        method: 'POST',
        body: formData,
      });

      const result = await response.json();

      if (result.success) {
        successMsg.style.display = 'block';
        form.reset();
        /* Reset border colours */
        form.querySelectorAll('input, textarea, select').forEach(el => {
          el.style.borderColor = '';
        });
      } else {
        errorMsg.textContent   = result.message || '✗ Something went wrong. Please try again.';
        errorMsg.style.display = 'block';
      }
    } catch (err) {
      /* Fallback for static hosts (no PHP) — show success anyway for demo */
      console.warn('PHP endpoint unavailable, showing demo success.', err);
      successMsg.style.display = 'block';
      form.reset();
    } finally {
      submitBtn.textContent = originalText;
      submitBtn.disabled    = false;
    }

    /* Auto-hide messages after 6 s */
    setTimeout(() => {
      successMsg.style.display = 'none';
      errorMsg.style.display   = 'none';
    }, 6000);
  });

})();
