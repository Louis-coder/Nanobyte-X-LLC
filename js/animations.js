/**
 * NANOBYTE X LLC — Scroll Animations & Counter
 * js/animations.js
 */

(function () {
  'use strict';

  /* ═══════════════════════════════
     INTERSECTION OBSERVER — REVEAL
  ═══════════════════════════════ */
  const revealEls = document.querySelectorAll('.reveal');
  const cardEls   = document.querySelectorAll('.service-card, .exec-card, .mission-card');

  const revealObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        revealObserver.unobserve(entry.target);
      }
    });
  }, { threshold: 0.12 });

  revealEls.forEach(el => revealObserver.observe(el));

  /* Staggered card reveal */
  const cardObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const el     = entry.target;
        const parent = el.parentElement;
        const index  = Array.from(parent.children).indexOf(el);
        setTimeout(() => {
          el.classList.add('visible');
        }, index * 90);
        cardObserver.unobserve(el);
      }
    });
  }, { threshold: 0.1 });

  cardEls.forEach(el => cardObserver.observe(el));

  /* ═══════════════════════════════
     COUNTER ANIMATION
  ═══════════════════════════════ */
  const counters = document.querySelectorAll('[data-count]');

  const counterObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) return;

      const el     = entry.target;
      const target = parseInt(el.getAttribute('data-count'), 10);
      let current  = 0;
      const steps  = 55;
      const step   = target / steps;
      const delay  = 22; // ms per frame

      const timer = setInterval(() => {
        current += step;
        if (current >= target) {
          el.textContent = target;
          clearInterval(timer);
        } else {
          el.textContent = Math.floor(current);
        }
      }, delay);

      counterObserver.unobserve(el);
    });
  }, { threshold: 0.5 });

  counters.forEach(c => counterObserver.observe(c));

  /* ═══════════════════════════════
     ACTIVE NAV LINK ON SCROLL
  ═══════════════════════════════ */
  const sections  = document.querySelectorAll('section[id]');
  const navLinks  = document.querySelectorAll('.nav-links a');

  const sectionObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const id = entry.target.getAttribute('id');
        navLinks.forEach(link => {
          link.classList.toggle('active', link.getAttribute('href') === `#${id}`);
        });
      }
    });
  }, { rootMargin: '-40% 0px -50% 0px' });

  sections.forEach(sec => sectionObserver.observe(sec));

})();
