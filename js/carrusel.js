document.addEventListener('DOMContentLoaded', () => {

  const track   = document.getElementById('carTrack');
  const section = document.getElementById('carousel-section');
  if (!track || typeof gsap === 'undefined') return;

  const slides = Array.from(track.querySelectorAll('.car-slide'));
  const dots   = Array.from(document.querySelectorAll('.car-dot'));
  const prevBtn = document.getElementById('carPrev');
  const nextBtn = document.getElementById('carNext');
  if (!slides.length) return;

  let hasScrollTrigger = typeof ScrollTrigger !== 'undefined';
  if (hasScrollTrigger) gsap.registerPlugin(ScrollTrigger);

  function slideStep() {
    const style = getComputedStyle(track);
    const gap = parseFloat(style.columnGap || style.gap || 0);
    return slides[0].getBoundingClientRect().width + gap;
  }

  function maxScroll() {
    return Math.max(0, slideStep() * (slides.length - 1));
  }

  function setActiveDot(index) {
    dots.forEach((d, i) => d.classList.toggle('active', i === index));
  }

  let current = 0;
  let scrollDriven = false;

  function goTo(index, animate = true) {
    current = gsap.utils.clamp(0, slides.length - 1, index);
    const x = -current * slideStep();
    gsap.to(track, { x, duration: animate ? .6 : 0, ease: 'power3.out' });
    setActiveDot(current);
  }

  prevBtn && prevBtn.addEventListener('click', () => goTo(current - 1));
  nextBtn && nextBtn.addEventListener('click', () => goTo(current + 1));
  dots.forEach((dot, i) => dot.addEventListener('click', () => goTo(i)));

  if (hasScrollTrigger && slides.length > 1) {
    scrollDriven = true;
    ScrollTrigger.create({
      trigger: section,
      start: 'top top+=80',
      end: () => '+=' + (maxScroll() + window.innerHeight * 0.6),
      pin: true,
      scrub: 0.6,
      onUpdate: self => {
        const x = -self.progress * maxScroll();
        gsap.set(track, { x });
        const idx = Math.round(self.progress * (slides.length - 1));
        setActiveDot(idx);
        current = idx;
      }
    });
  } else {
    goTo(0, false);
  }

  window.addEventListener('resize', () => {
    if (hasScrollTrigger) ScrollTrigger.refresh();
    else goTo(current, false);
  });
});
