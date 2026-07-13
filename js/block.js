document.addEventListener('DOMContentLoaded', () => {

  const items = Array.from(document.querySelectorAll('.feat-item'));
  if (!items.length) return;

  const lb = document.createElement('div');
  lb.className = 'gallery-lightbox';
  lb.innerHTML = `
    <div class="gallery-lightbox-backdrop"></div>
    <div class="gallery-lightbox-card">
      <button class="lb-close" aria-label="Cerrar">&times;</button>
      <div class="lb-img-wrap"><img alt=""></div>
      <div class="lb-body"><h3></h3><p></p></div>
    </div>`;
  document.body.appendChild(lb);

  const backdrop = lb.querySelector('.gallery-lightbox-backdrop');
  const card     = lb.querySelector('.gallery-lightbox-card');
  const img      = lb.querySelector('img');
  const title    = lb.querySelector('.lb-body h3');
  const desc     = lb.querySelector('.lb-body p');
  const closeBtn = lb.querySelector('.lb-close');

  let isOpen = false;

  function open(item) {
    if (isOpen) return;
    isOpen = true;

    const srcImg = item.querySelector('.feat-img');
    const srcTitle = item.querySelector('h3');
    const srcText = item.querySelector('p');
    img.src = srcImg ? srcImg.src : '';
    img.alt = srcImg ? srcImg.alt : '';
    title.textContent = srcTitle ? srcTitle.textContent : '';
    desc.textContent = srcText ? srcText.textContent : '';

    document.body.style.overflow = 'hidden';
    lb.classList.add('is-open');

    if (typeof gsap === 'undefined') return;

    const rect = item.getBoundingClientRect();
    const target = card.getBoundingClientRect();
    const scaleX = rect.width / target.width;
    const scaleY = rect.height / target.height;
    const originX = rect.left + rect.width / 2 - (target.left + target.width / 2);
    const originY = rect.top + rect.height / 2 - (target.top + target.height / 2);

    gsap.set(card, { x: originX, y: originY, scaleX, scaleY, opacity: 1 });
    gsap.to(backdrop, { opacity: 1, duration: .35, ease: 'power2.out' });
    gsap.to(card, {
      x: 0, y: 0, scaleX: 1, scaleY: 1,
      duration: .55, ease: 'power4.out'
    });
  }

  function close() {
    if (!isOpen) return;
    isOpen = false;
    document.body.style.overflow = '';

    if (typeof gsap === 'undefined') {
      lb.classList.remove('is-open');
      return;
    }
    gsap.to(backdrop, { opacity: 0, duration: .3 });
    gsap.to(card, {
      scale: .4, opacity: 0, duration: .35, ease: 'power2.in',
      onComplete: () => lb.classList.remove('is-open')
    });
  }

  items.forEach(item => item.addEventListener('click', () => open(item)));
  closeBtn.addEventListener('click', close);
  backdrop.addEventListener('click', close);
  document.addEventListener('keydown', e => { if (e.key === 'Escape') close(); });
});
