// assets/dashboard-exact.js
// Small helpers: clock + preview-upload for hero gradient (stores dataURL in localStorage for testing)

(function(){
  // Clock
  function updateClock(){
    const el = document.getElementById('clock');
    const sub = document.getElementById('date-sub');
    const now = new Date();
    if (el) el.textContent = now.toLocaleTimeString([], {hour:'numeric', minute:'2-digit'});
    if (sub) sub.textContent = now.toLocaleDateString([], {weekday:'long', months:'long', day:'numeric'});
  }
  updateClock();
  setInterval(updateClock, 1000);

  // Gradient upload preview (temporary)
  const bgInput = document.getElementById('bg-file');
  const editBtn = document.getElementById('edit-bg-btn');
  const heroLeft = document.getElementById('hero-left');

  function loadStoredBg(){
    try {
      const data = localStorage.getItem('ip_hero_bg');
      if (data && heroLeft) heroLeft.style.backgroundImage = `url('${data}')`;
    } catch(e){}
  }
  loadStoredBg();

  editBtn?.addEventListener('click', (e) => {
    e.preventDefault();
    bgInput?.click();
  });

  bgInput?.addEventListener('change', function(){
    const f = this.files && this.files[0];
    if (!f) return;
    const reader = new FileReader();
    reader.onload = function(ev){
      const data = ev.target.result;
      if (heroLeft) heroLeft.style.backgroundImage = `url('${data}')`;
      try { localStorage.setItem('ip_hero_bg', data); } catch(e){}
    };
    reader.readAsDataURL(f);
  });
})();