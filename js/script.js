document.addEventListener('DOMContentLoaded', () => {

  if (typeof gsap === 'undefined') return;

  
  const tl = gsap.timeline({ defaults: { ease: 'power3.out' } });

  tl.to('#badge', { opacity: 1, y: 0, duration: .6 }, .1)
    .from('#h1', { opacity: 0, y: 30, duration: .8 }, .2)
    .to('#hdesc', { opacity: 1, y: 0, duration: .7 }, .45)
    .to('#hbtns', { opacity: 1, y: 0, duration: .7 }, .55)
    .to('#srow', { opacity: 1, y: 0, duration: .7 }, .65)
    .from('#cw1', { opacity: 0, y: 24, duration: .8 }, .5)
    .from('#cw2', { opacity: 0, y: 24, duration: .8 }, .65)
    .from('#cw3', { opacity: 0, y: 24, duration: .8 }, .8)
    .from('#sbadges', { opacity: 0, x: 24, duration: .8 }, .9);

  gsap.set(['#hdesc', '#hbtns', '#srow'], { y: 14 });
  gsap.set('#badge', { y: -10 });

  
  const barfillEl = document.getElementById('barfill');
  if (barfillEl) {
    const pct = barfillEl.dataset.pct || 0;
    gsap.to(barfillEl, { width: pct + '%', duration: 1.4, ease: 'power2.out', delay: 1.1 });
  }

  
  document.querySelectorAll('.stat-num[data-count]').forEach(el => {
    const end = parseFloat(el.dataset.count);
    const counter = { val: 0 };
    gsap.to(counter, {
      val: end, duration: 1.6, delay: 1, ease: 'power2.out',
      onUpdate: () => {
        el.firstChild.textContent = Number.isInteger(end)
          ? Math.round(counter.val)
          : counter.val.toFixed(1);
      }
    });
  });

  
  const scene = document.getElementById('scene');
  if (scene) {
    const orbit = document.createElement('div');
    orbit.className = 'ticket-orbit';
    orbit.innerHTML = `
      <div class="ticket ticket-a" id="ticketA">
        <div>
          <div class="ticket-eyebrow">SERVICECORE</div>
          <div class="ticket-label">Ticket #A104</div>
        </div>
        <div class="ticket-notch"></div>
      </div>
      <div class="ticket ticket-b" id="ticketB">
        <div>
          <div class="ticket-eyebrow">RESUELTO</div>
          <div class="ticket-label">Ticket #A098</div>
        </div>
        <div class="ticket-notch"></div>
      </div>`;
    scene.appendChild(orbit);

    const radiusX = 150, radiusY = 90;
    const state = { angle: -40 };

    function place(el, angleDeg, depthPhase) {
      const rad = angleDeg * Math.PI / 180;
      const x = Math.cos(rad) * radiusX;
      const y = Math.sin(rad) * radiusY;
      const depth = (Math.sin(rad + depthPhase) + 1) / 2;
      const scale = gsap.utils.mapRange(0, 1, .78, 1.12, depth);
      const z = gsap.utils.mapRange(0, 1, 0, 4, depth);
      gsap.set(el, {
        x, y, scale,
        zIndex: Math.round(depth * 10),
        rotation: y * 0.05,
        filter: `brightness(${gsap.utils.mapRange(0, 1, .85, 1.05, depth)})`
      });
    }

    gsap.to(state, {
      angle: 260,
      duration: 7,
      ease: 'sine.inOut',
      yoyo: true,
      repeat: -1,
      onUpdate: () => {
        place(document.getElementById('ticketA'), state.angle, 0);
        place(document.getElementById('ticketB'), state.angle + 180, Math.PI);
      }
    });
  }

  
  gsap.to('.orb-1', { x: 40, y: 30, duration: 9, repeat: -1, yoyo: true, ease: 'sine.inOut' });
  gsap.to('.orb-2', { x: -30, y: -24, duration: 11, repeat: -1, yoyo: true, ease: 'sine.inOut' });
});
