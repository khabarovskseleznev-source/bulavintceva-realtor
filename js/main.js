/* ═══════════════════════════════════════════════════════
   MAIN.JS — Елена Булавинская
════════════════════════════════════════════════════════ */

document.addEventListener('DOMContentLoaded', () => {

  // ─── SCROLL PROGRESS BAR ─────────────────────────────
  const progressBar = document.getElementById('scrollProgress');

  function updateProgress() {
    const scrolled = window.scrollY;
    const total    = document.documentElement.scrollHeight - window.innerHeight;
    const ratio    = total > 0 ? scrolled / total : 0;
    progressBar.style.transform = `scaleX(${ratio})`;
  }


  // ─── NAVBAR SCROLL STATE ──────────────────────────────
  const navbar = document.getElementById('navbar');

  function updateNavbar() {
    if (window.scrollY > 40) {
      navbar.classList.add('scrolled');
    } else {
      navbar.classList.remove('scrolled');
    }
  }


  // ─── CURSOR GLOW ─────────────────────────────────────
  const cursorGlow = document.getElementById('cursorGlow');
  let cx = -999, cy = -999;
  let targetX = -999, targetY = -999;
  let glowRaf = null;

  if (window.matchMedia('(hover: hover)').matches) {
    document.addEventListener('mousemove', e => {
      targetX = e.clientX;
      targetY = e.clientY;
    });

    function animateGlow() {
      // Плавное следование с инерцией
      cx += (targetX - cx) * 0.08;
      cy += (targetY - cy) * 0.08;
      cursorGlow.style.left = cx + 'px';
      cursorGlow.style.top  = cy + 'px';
      glowRaf = requestAnimationFrame(animateGlow);
    }

    animateGlow();
  }


  // ─── PARALLAX HERO PHOTO ─────────────────────────────
  const heroParallax = document.getElementById('heroParallax');

  function updateParallax() {
    if (!heroParallax) return;
    if (!window.matchMedia('(prefers-reduced-motion: no-preference)').matches) return;
    const scrollY = window.scrollY;
    heroParallax.style.transform = `translateY(${scrollY * 0.12}px)`;
  }


  // ─── SCROLL HANDLER (объединяем в один RAF) ───────────
  let ticking = false;

  function onScroll() {
    if (!ticking) {
      requestAnimationFrame(() => {
        updateProgress();
        updateNavbar();
        updateParallax();
        ticking = false;
      });
      ticking = true;
    }
  }

  window.addEventListener('scroll', onScroll, { passive: true });
  updateNavbar(); // Инициализация


  // ─── INTERSECTION OBSERVER — REVEAL ANIMATIONS ───────
  const revealSelectors = [
    '.clip-reveal',
    '.reveal-fade',
    '.reveal-up',
    '.word-reveal',
    '.blur-reveal',
  ];

  const revealEls = document.querySelectorAll(revealSelectors.join(', '));

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('is-visible');
        observer.unobserve(entry.target);
      }
    });
  }, {
    threshold: 0.15,
    rootMargin: '0px 0px -40px 0px',
  });

  revealEls.forEach(el => observer.observe(el));


  // ─── SVG LINE DRAW (Вариант 3) ────────────────────────
  const drawLines = document.querySelectorAll('.draw-line');

  const lineObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('is-drawn');
        lineObserver.unobserve(entry.target);
      }
    });
  }, { threshold: 0.3 });

  drawLines.forEach(el => lineObserver.observe(el));


  // ─── STICKY PHOTO SCALE (Вариант 2) ──────────────────
  const stickyPhotoFrame = document.querySelector('.about-v2__photo-frame');

  if (stickyPhotoFrame) {
    const photoObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          stickyPhotoFrame.classList.add('in-view');
        }
      });
    }, { threshold: 0.2 });

    photoObserver.observe(stickyPhotoFrame);
  }


  // ─── COUNTER ANIMATION ───────────────────────────────
  const counters = document.querySelectorAll('.counter, .counter2');

  function animateCounter(el) {
    const target   = parseInt(el.dataset.to, 10);
    const duration = 1400; // ms
    const start    = performance.now();

    function easeOutQuart(t) {
      return 1 - Math.pow(1 - t, 4);
    }

    function tick(now) {
      const elapsed  = now - start;
      const progress = Math.min(elapsed / duration, 1);
      const value    = Math.floor(easeOutQuart(progress) * target);
      el.textContent = value;
      if (progress < 1) requestAnimationFrame(tick);
      else el.textContent = target;
    }

    requestAnimationFrame(tick);
  }

  const counterObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        animateCounter(entry.target);
        counterObserver.unobserve(entry.target);
      }
    });
  }, { threshold: 0.5 });

  counters.forEach(el => counterObserver.observe(el));


  // ─── MAGNETIC BUTTON ─────────────────────────────────
  const magnetics = document.querySelectorAll('.magnetic');

  magnetics.forEach(btn => {
    btn.addEventListener('mousemove', e => {
      const rect = btn.getBoundingClientRect();
      const cx   = rect.left + rect.width  / 2;
      const cy   = rect.top  + rect.height / 2;
      const dx   = (e.clientX - cx) * 0.28;
      const dy   = (e.clientY - cy) * 0.28;
      btn.style.transform = `translate(${dx}px, ${dy}px)`;
    });

    btn.addEventListener('mouseleave', () => {
      btn.style.transform = '';
    });
  });

});
