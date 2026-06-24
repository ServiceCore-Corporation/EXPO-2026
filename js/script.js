gsap.registerPlugin(ScrollTrigger);

const tl = gsap.timeline({ defaults: { ease: 'power3.out' } });

tl.from('#nav',     { y: -32, opacity: 0, duration: 0.7 })
  .from('#badge',   { y: 26,  opacity: 0, duration: 0.6 }, '-=0.3')
  .from('#h1',      { y: 44,  opacity: 0, duration: 0.85 }, '-=0.4')
  .from('#hdesc',   { y: 30,  opacity: 0, duration: 0.65 }, '-=0.5')
  .from('#hbtns',   { y: 24,  opacity: 0, duration: 0.6  }, '-=0.45')
  .from('#srow .stat-box', { y: 22, opacity: 0, duration: 0.55, stagger: 0.13 }, '-=0.35')
  /* Cards 3D */
  .from('#cw1',  { y: 70,  opacity: 0, duration: 1,   ease: 'power4.out' }, '-=0.7')
  .from('#cw2',  { y: 90,  opacity: 0, duration: 1,   ease: 'power4.out' }, '-=0.8')
  .from('#cw3',  { y: 110, opacity: 0, duration: 1,   ease: 'power4.out' }, '-=0.8')
  .from('#sbadges .sbadge', { x: 28, opacity: 0, duration: 0.55, stagger: 0.14 }, '-=0.55');

/* Barra de progreso al cargar */
setTimeout(() => {
  const b = document.getElementById('barfill');
  if (b) b.style.width = '65%';
}, 1300);

/* ── Parallax 3D con el mouse ── */
const scene   = document.getElementById('scene');
const fcards  = document.querySelectorAll('.fcard');

if (scene) {
  scene.addEventListener('mousemove', e => {
    const r = scene.getBoundingClientRect();
    const x = ((e.clientX - r.left) / r.width  - 0.5) * 2;
    const y = ((e.clientY - r.top)  / r.height - 0.5) * 2;

    fcards.forEach(card => {
      gsap.to(card, {
        rotateY:      x *  9,
        rotateX:      y * -9,
        translateX:   x * 11,
        translateY:   y * -9,
        duration: 0.55,
        ease: 'power2.out',
        transformPerspective: 1200,
      });
    });

    gsap.to('.orb-3', { x: x * 45, y: y * 32, duration: 1.3, ease: 'power1.out' });
  });

  scene.addEventListener('mouseleave', () => {
    fcards.forEach(card => {
      gsap.to(card, {
        rotateY: 0, rotateX: 0, translateX: 0, translateY: 0,
        duration: 0.9, ease: 'power3.out',
      });
    });
    gsap.to('.orb-3', { x: 0, y: 0, duration: 1.2, ease: 'power2.out' });
  });
}

/* ── ScrollTrigger – Features ── */
gsap.from('#feathead', {
  scrollTrigger: { trigger: '#feathead', start: 'top 82%' },
  y: 40, opacity: 0, duration: 0.85, ease: 'power3.out',
});

gsap.from('.feat-item', {
  scrollTrigger: { trigger: '.features-grid', start: 'top 80%' },
  y: 52, opacity: 0, duration: 0.7, ease: 'power3.out', stagger: 0.11,
});

/* ── Efecto magnético en botones ── */
document.querySelectorAll('.btn-primary, .btn-ghost, .nav-cta').forEach(btn => {
  btn.addEventListener('mousemove', e => {
    const r = btn.getBoundingClientRect();
    gsap.to(btn, {
      x: (e.clientX - r.left - r.width  / 2) * 0.28,
      y: (e.clientY - r.top  - r.height / 2) * 0.28,
      duration: 0.35, ease: 'power2.out',
    });
  });
  btn.addEventListener('mouseleave', () => {
    gsap.to(btn, { x: 0, y: 0, duration: 0.55, ease: 'elastic.out(1, 0.5)' });
  });
});

/* ── Hover sutil en las stat-box ── */
document.querySelectorAll('.stat-box').forEach(box => {
  box.addEventListener('mouseenter', () => gsap.to(box, { scale: 1.04, duration: 0.3, ease: 'power2.out' }));
  box.addEventListener('mouseleave', () => gsap.to(box, { scale: 1,    duration: 0.4, ease: 'power2.out' }));
});