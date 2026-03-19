/**
 * NANOBYTE X LLC — Navigation
 * js/nav.js
 */

(function () {
  'use strict';

  const nav       = document.getElementById('mainNav');
  const mobileNav = document.getElementById('mobileNav');

  /* ── Sticky nav background on scroll ── */
  window.addEventListener('scroll', () => {
    if (window.scrollY > 60) {
      nav.style.background = 'rgba(8,8,8,0.97)';
    } else {
      nav.style.background = 'rgba(8,8,8,0.85)';
    }
  }, { passive: true });

  /* ── Mobile menu open/close ── */
  window.openMobile = function () {
    mobileNav.classList.add('open');
    document.body.style.overflow = 'hidden';
  };

  window.closeMobile = function () {
    mobileNav.classList.remove('open');
    document.body.style.overflow = '';
  };

  /* ── Close mobile menu on Escape key ── */
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeMobile();
  });

  /* ── Smooth scroll for all anchor links ── */
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
      const targetId = this.getAttribute('href');
      if (targetId === '#') return;
      const target = document.querySelector(targetId);
      if (!target) return;
      e.preventDefault();
      const navH   = nav ? nav.offsetHeight : 72;
      const top    = target.getBoundingClientRect().top + window.scrollY - navH;
      window.scrollTo({ top, behavior: 'smooth' });
    });
  });

})();
