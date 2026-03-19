/**
 * NANOBYTE X LLC — Custom Cursor
 * js/cursor.js
 */

(function () {
  'use strict';

  const cursor     = document.getElementById('cursor');
  const cursorRing = document.getElementById('cursor-ring');
  if (!cursor || !cursorRing) return;

  let mx = 0, my = 0;
  let rx = 0, ry = 0;

  /* ── Follow mouse exactly (cursor dot) ── */
  document.addEventListener('mousemove', e => {
    mx = e.clientX;
    my = e.clientY;
    cursor.style.transform = `translate(${mx}px, ${my}px) translate(-50%, -50%)`;
  });

  /* ── Lag-follow (ring) ── */
  function animateRing() {
    rx += (mx - rx) * 0.12;
    ry += (my - ry) * 0.12;
    cursorRing.style.transform = `translate(${rx}px, ${ry}px) translate(-50%, -50%)`;
    requestAnimationFrame(animateRing);
  }
  animateRing();

  /* ── Hover effects on interactive elements ── */
  const interactives = document.querySelectorAll(
    'a, button, .service-card, .exec-card, .mission-card, input, textarea, select'
  );

  interactives.forEach(el => {
    el.addEventListener('mouseenter', () => {
      cursor.style.width        = '20px';
      cursor.style.height       = '20px';
      cursorRing.style.width    = '52px';
      cursorRing.style.height   = '52px';
      cursorRing.style.opacity  = '0.85';
    });
    el.addEventListener('mouseleave', () => {
      cursor.style.width        = '12px';
      cursor.style.height       = '12px';
      cursorRing.style.width    = '36px';
      cursorRing.style.height   = '36px';
      cursorRing.style.opacity  = '0.5';
    });
  });

  /* ── Click feedback ── */
  document.addEventListener('mousedown', () => {
    cursor.style.transform += ' scale(0.7)';
  });
  document.addEventListener('mouseup', () => {
    cursor.style.transform = cursor.style.transform.replace(' scale(0.7)', '');
  });

  /* ── Hide on touch devices ── */
  document.addEventListener('touchstart', () => {
    cursor.style.display     = 'none';
    cursorRing.style.display = 'none';
  }, { once: true });

})();
