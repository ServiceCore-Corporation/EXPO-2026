document.addEventListener('DOMContentLoaded', function () {
  const nav = document.getElementById('nav');
  const SCROLL_THRESHOLD = 40; 

  function handleScroll() {
    if (window.scrollY > SCROLL_THRESHOLD) {
      nav.classList.add('scrolled');
    } else {
      nav.classList.remove('scrolled');
    }
  }

  window.addEventListener('scroll', handleScroll, { passive: true });
  handleScroll(); 
});