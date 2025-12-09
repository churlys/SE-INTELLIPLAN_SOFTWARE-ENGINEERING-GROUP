// assets/calendar.js
// Initializes FullCalendar on calendar.php and provides simple image-edit replacement (stored in localStorage).
(function(){
  // Clock
  function updateClock(){
    const el = document.getElementById('clock');
    const sub = document.getElementById('date-sub');
    const now = new Date();
    const opts = { hour: 'numeric', minute: '2-digit' };
    if (el) el.textContent = now.toLocaleTimeString([], opts);
    if (sub) sub.textContent = now.toLocaleDateString([], { weekday: 'long', month:'long', day:'numeric' });
  }
  updateClock();
  setInterval(updateClock, 1000);

  // Init FullCalendar with timeGridDay default
  const Calendar = FullCalendar.Calendar;
  const calendarEl = document.getElementById('main-calendar');

  const calendar = new Calendar(calendarEl, {
    initialView: 'timeGridDay',
    headerToolbar: false,
    nowIndicator: true,
    allDaySlot: false,
    slotMinTime: "00:00:00",
    slotMaxTime: "23:00:00",
    editable: true,
    selectable: true,
    events: [
      // sample events; replace with API integration when ready
      { id: '1', title: 'Math Class', start: new Date().toISOString().slice(0,11) + '09:00:00', end: new Date().toISOString().slice(0,11) + '10:00:00' },
      { id: '2', title: 'Study Session', start: new Date().toISOString().slice(0,11) + '13:00:00', end: new Date().toISOString().slice(0,11) + '14:30:00' }
    ],
    dayHeaderFormat: { weekday: 'short', month: 'short', day: 'numeric' }
  });
  calendar.render();

  // controls
  document.querySelectorAll('.control-btn[data-view]').forEach(btn=>{
    btn.addEventListener('click', ()=> calendar.changeView(btn.getAttribute('data-view')));
  });
  document.getElementById('today-btn')?.addEventListener('click', ()=> calendar.today());
  document.getElementById('prev-day')?.addEventListener('click', ()=> calendar.prev());
  document.getElementById('next-day')?.addEventListener('click', ()=> calendar.next());

  // Mini calendar placeholder (visual only)
  const mini = document.getElementById('mini-calendar');
  if (mini) mini.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;color:var(--muted)">Mini calendar visual</div>';

  // Editable logo support (temporary: replaces <img> src and stores data URL in localStorage)
  const logoFile = document.getElementById('logo-file');
  const editLogoBtn = document.getElementById('edit-logo-btn');
  const siteLogo = document.getElementById('site-logo');

  function loadStoredImages(){
    try {
      const logoData = localStorage.getItem('ip_logo');
      if (logoData && siteLogo) siteLogo.src = logoData;
    } catch(e){}
  }
  loadStoredImages();

  editLogoBtn?.addEventListener('click', ()=> logoFile.click());
  logoFile?.addEventListener('change', function(e){
    const f = this.files && this.files[0];
    if (!f) return;
    const reader = new FileReader();
    reader.onload = function(ev){
      const data = ev.target.result;
      if (siteLogo) siteLogo.src = data;
      try { localStorage.setItem('ip_logo', data); } catch(e){}
    };
    reader.readAsDataURL(f);
  });

})();