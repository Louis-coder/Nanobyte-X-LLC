/**
 * NANOBYTE X LLC — Particle System
 * js/particles.js
 */

(function () {
  'use strict';

  const canvas = document.getElementById('particles');
  if (!canvas) return;

  const ctx = canvas.getContext('2d');
  let W, H, animId;
  const particles = [];
  const PARTICLE_COUNT = 100;

  /* ── Resize ── */
  function resize() {
    W = canvas.width  = window.innerWidth;
    H = canvas.height = window.innerHeight;
  }

  window.addEventListener('resize', resize);
  resize();

  /* ── Particle Class ── */
  class Particle {
    constructor() { this.reset(true); }

    reset(random = false) {
      this.x     = Math.random() * W;
      this.y     = random ? Math.random() * H : (Math.random() > 0.5 ? -5 : H + 5);
      this.size  = Math.random() * 1.5 + 0.3;
      this.alpha = Math.random() * 0.35 + 0.05;
      this.vx    = (Math.random() - 0.5) * 0.22;
      this.vy    = (Math.random() - 0.5) * 0.22;
      this.life  = 1;
      this.decay = Math.random() * 0.002 + 0.001;
    }

    update() {
      this.x     += this.vx;
      this.y     += this.vy;
      this.life  -= this.decay;
      if (
        this.x < -10 || this.x > W + 10 ||
        this.y < -10 || this.y > H + 10 ||
        this.life <= 0
      ) {
        this.reset();
      }
    }

    draw() {
      ctx.save();
      ctx.globalAlpha = this.alpha * this.life;
      ctx.fillStyle   = '#C8922A';
      ctx.beginPath();
      ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
      ctx.fill();
      ctx.restore();
    }
  }

  /* ── Init ── */
  for (let i = 0; i < PARTICLE_COUNT; i++) {
    particles.push(new Particle());
  }

  /* ── Draw connections ── */
  function drawConnections() {
    const maxDist = 100;
    for (let i = 0; i < particles.length; i++) {
      for (let j = i + 1; j < particles.length; j++) {
        const dx   = particles[i].x - particles[j].x;
        const dy   = particles[i].y - particles[j].y;
        const dist = Math.sqrt(dx * dx + dy * dy);
        if (dist < maxDist) {
          const alpha = (1 - dist / maxDist) * 0.06;
          ctx.save();
          ctx.globalAlpha = alpha;
          ctx.strokeStyle = '#C8922A';
          ctx.lineWidth   = 0.5;
          ctx.beginPath();
          ctx.moveTo(particles[i].x, particles[i].y);
          ctx.lineTo(particles[j].x, particles[j].y);
          ctx.stroke();
          ctx.restore();
        }
      }
    }
  }

  /* ── Animation loop ── */
  function loop() {
    ctx.clearRect(0, 0, W, H);
    drawConnections();
    particles.forEach(p => { p.update(); p.draw(); });
    animId = requestAnimationFrame(loop);
  }

  loop();

  /* ── Mouse parallax nudge ── */
  let mouseX = W / 2, mouseY = H / 2;
  document.addEventListener('mousemove', e => {
    mouseX = e.clientX;
    mouseY = e.clientY;
    // Gently attract nearest few particles
    particles.slice(0, 8).forEach(p => {
      const dx = mouseX - p.x;
      const dy = mouseY - p.y;
      const d  = Math.sqrt(dx * dx + dy * dy);
      if (d < 180) {
        p.vx += dx * 0.00015;
        p.vy += dy * 0.00015;
      }
    });
  });

})();
