// Minimal enhancements: live clock update, safe resize polish
(function(){
  function tick(){
    const el = document.getElementById('clock');
    const sub = document.getElementById('date-sub');
    const now = new Date();
    if (el) el.textContent = now.toLocaleTimeString([], {hour:'numeric', minute:'2-digit'});
    if (sub) sub.textContent = now.toLocaleDateString([], {weekday:'long', month:'long', day:'numeric'});
  }
  tick();
  setInterval(tick, 1000);
})();