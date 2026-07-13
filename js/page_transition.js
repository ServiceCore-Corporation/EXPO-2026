document.addEventListener('DOMContentLoaded', () => {

  const overlay = document.getElementById('pageTransition');
  if (!overlay) return;

  if (typeof gsap === 'undefined') {
    overlay.remove();
    return;
  }

  const left = overlay.querySelector('.pt-panel--l');
  const center = overlay.querySelector('.pt-panel--c');
  const right = overlay.querySelector('.pt-panel--r');
  const bars = overlay.querySelectorAll('.pt-logo img');
  const wordmark = overlay.querySelector('.pt-wordmark');

  gsap.set(bars, { scaleY: .7, transformOrigin: '50% 100%' });
  gsap.set([left, right, center], { x: 0, opacity: 1 });

  function pulseBars() {
    return gsap.to(bars, {
      scaleY: 1, duration: .35, ease: 'power2.out',
      stagger: { each: .08, from: 'center', yoyo: true, repeat: 1 }
    });
  }

  function reveal() {
    const tl = gsap.timeline();
    tl.add(pulseBars())
      .to(wordmark, { opacity: 0, duration: .3 }, '-=0.1')
      .to(left, { xPercent: -100, duration: .7, ease: 'power4.inOut' }, '-=0.15')
      .to(right, { xPercent: 100, duration: .7, ease: 'power4.inOut' }, '<')
      .to(center, { opacity: 0, duration: .5, ease: 'power2.inOut' }, '<')
      .set(overlay, { pointerEvents: 'none' });
    return tl;
  }

  function cover(onComplete) {
    gsap.set(overlay, { pointerEvents: 'auto' });
    gsap.set([left, right], { xPercent: 0 });
    gsap.set(center, { opacity: 1 });
    gsap.set(wordmark, { opacity: 1 });
    gsap.set(bars, { scaleY: .7 });
    const tl = gsap.timeline({ onComplete });
    tl.set(left, { xPercent: -100 })
      .set(right, { xPercent: 100 })
      .to(left, { xPercent: 0, duration: .6, ease: 'power4.inOut' })
      .to(right, { xPercent: 0, duration: .6, ease: 'power4.inOut' }, '<')
      .add(pulseBars(), '-=0.15');
  }

  reveal();

  const topLevelPattern = /^(index\.php|login\.php|registrarse\.php)?(\?[^#]*)?(#.*)?$/;

  document.querySelectorAll('a[href]').forEach(link => {
    const href = link.getAttribute('href');
    if (!href) return;
    if (href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:')) return;
    if (href.startsWith('http') || link.target === '_blank') return;
    if (!topLevelPattern.test(href)) return;

    link.addEventListener('click', e => {
      e.preventDefault();
      cover(() => { window.location.href = href; });
    });
  });
});
