document.addEventListener('DOMContentLoaded', function () {
  const slides = Array.from(document.querySelectorAll('.car-slide'));
  const dots = Array.from(document.querySelectorAll('.car-dot'));
  const prevBtn = document.getElementById('carPrev');
  const nextBtn = document.getElementById('carNext');

  if (!slides.length) return;

  let current = 0;
  const total = slides.length;
  let autoplay;
  const AUTOPLAY_DELAY = 4000;

  function render() {
    slides.forEach((slide, i) => {
      slide.classList.remove('is-active', 'is-prev', 'is-next', 'is-prev2', 'is-next2');

      const diff = (i - current + total) % total;

      if (diff === 0) {
        slide.classList.add('is-active');
      } else if (diff === 1) {
        slide.classList.add('is-next');
      } else if (diff === total - 1) {
        slide.classList.add('is-prev');
      } else if (diff === 2) {
        slide.classList.add('is-next2');
      } else if (diff === total - 2) {
        slide.classList.add('is-prev2');
      }
    });

    dots.forEach((dot, i) => dot.classList.toggle('active', i === current));
  }

  function goTo(index) {
    current = (index + total) % total;
    render();
  }

  function next() { goTo(current + 1); }
  function prev() { goTo(current - 1); }

  function startAutoplay() {
    stopAutoplay();
    autoplay = setInterval(next, AUTOPLAY_DELAY);
  }

  function stopAutoplay() {
    if (autoplay) clearInterval(autoplay);
  }

  nextBtn.addEventListener('click', () => { next(); startAutoplay(); });
  prevBtn.addEventListener('click', () => { prev(); startAutoplay(); });

  dots.forEach(dot => {
    dot.addEventListener('click', () => {
      goTo(parseInt(dot.dataset.index, 10));
      startAutoplay();
    });
  });

  slides.forEach(slide => {
    slide.addEventListener('click', () => {
      goTo(parseInt(slide.dataset.index, 10));
      startAutoplay();
    });
  });

  const wrap = document.getElementById('carouselWrap');
  wrap.addEventListener('mouseenter', stopAutoplay);
  wrap.addEventListener('mouseleave', startAutoplay);

  render();
  startAutoplay();
});