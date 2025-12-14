// assets/calendar.js
// Lightweight week/month calendar renderer; no external deps.
(function(){
  // Optional clock placeholders if present
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

  // Simple week/month calendar renderer (no external deps)
  document.addEventListener('DOMContentLoaded', function() {
    const weekView = document.getElementById('weekView');
    const monthView = document.getElementById('monthView');
    const rangeLabel = document.getElementById('calRange');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const todayBtn = document.getElementById('todayBtn');
    const modeWeek = document.getElementById('modeWeek');
    const modeMonth = document.getElementById('modeMonth');

    if (!weekView || !monthView) return;

    const events = [
      { title: 'Project Kickoff', start: '2024-12-10T09:00:00', end: '2024-12-10T10:00:00' },
      { title: 'Design Review', start: '2024-12-11T13:30:00', end: '2024-12-11T14:30:00' },
      { title: 'Team Sync', start: '2024-12-12T11:00:00', end: '2024-12-12T11:30:00' },
      { title: 'Client Call', start: '2024-12-15T15:00:00', end: '2024-12-15T15:45:00' },
      { title: 'Sprint Planning', start: '2024-12-03T10:00:00', end: '2024-12-03T11:00:00' },
    ];

    let currentDate = new Date();
    let mode = 'week';

    const formatDate = (date) => date.toISOString().slice(0, 10);

    const startOfWeek = (date) => {
      const d = new Date(date);
      const day = d.getDay();
      const diff = (day === 0 ? -6 : 1) - day; // Monday start
      d.setDate(d.getDate() + diff);
      return d;
    };

    const endOfWeek = (start) => {
      const d = new Date(start);
      d.setDate(d.getDate() + 6);
      return d;
    };

    const renderWeek = () => {
      const weekStart = startOfWeek(currentDate);
      const weekEnd = endOfWeek(weekStart);
      const formatter = new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric' });
      rangeLabel.textContent = `${formatter.format(weekStart)} – ${formatter.format(weekEnd)}`;

      const weekdayFormatter = new Intl.DateTimeFormat('en-US', { weekday: 'short' });
      const todayStr = formatDate(new Date());

      // headers
      const days = [];
      for (let i = 0; i < 7; i++) {
        const day = new Date(weekStart);
        day.setDate(day.getDate() + i);
        const iso = formatDate(day);
        days.push({
          date: day,
          iso,
          label: weekdayFormatter.format(day),
          isToday: iso === todayStr,
        });
      }

      const dayHeaderHtml = `<div class="week-days">${days.map(d => `
        <div class="week-day ${d.isToday ? 'today' : ''}">
          <div class="label">${d.label}</div>
          <div class="date">${d.date.getDate()}</div>
        </div>
      `).join('')}</div>`;

      const dayEventsHtml = `<div class="week-events">${days.map(d => {
        const dayEvents = events.filter(ev => ev.start.startsWith(d.iso));
        const items = dayEvents.length ? dayEvents.map(ev => {
          const start = new Date(ev.start);
          const end = new Date(ev.end);
          const time = `${start.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })} – ${end.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
          return `<div class="event-chip"><strong>${ev.title}</strong><span>${time}</span></div>`;
        }).join('') : '<div class="event-chip" style="opacity:0.6;">No events</div>';
        return `<div class="day-column"><h4>${d.label}</h4>${items}</div>`;
      }).join('')}</div>`;

      weekView.innerHTML = dayHeaderHtml + dayEventsHtml;
    };

    const renderMonth = () => {
      const working = new Date(currentDate);
      working.setDate(1);
      const month = working.getMonth();
      const year = working.getFullYear();
      const firstDay = working.getDay();
      const offset = (firstDay === 0 ? 6 : firstDay - 1); // Monday start

      const daysInMonth = new Date(year, month + 1, 0).getDate();
      const todayStr = formatDate(new Date());
      const formatter = new Intl.DateTimeFormat('en-US', { month: 'long', year: 'numeric' });
      rangeLabel.textContent = formatter.format(working);

      const cells = [];
      // Leading blanks
      for (let i = 0; i < offset; i++) cells.push({ empty: true });
      for (let day = 1; day <= daysInMonth; day++) {
        const dateObj = new Date(year, month, day);
        const iso = formatDate(dateObj);
        const dayEvents = events.filter(ev => ev.start.startsWith(iso));
        cells.push({
          date: day,
          iso,
          isToday: iso === todayStr,
          events: dayEvents,
        });
      }

      monthView.innerHTML = `<div class="month-grid">${cells.map(cell => {
        if (cell.empty) return '<div class="month-cell" aria-hidden="true"></div>';
        const eventsHtml = cell.events.map(ev => `<div class="event-line"><span class="event-dot"></span><span>${ev.title}</span></div>`).join('');
        return `<div class="month-cell ${cell.isToday ? 'today' : ''}"><div class="date">${cell.date}</div>${eventsHtml}</div>`;
      }).join('')}</div>`;
    };

    const setMode = (nextMode) => {
      mode = nextMode;
      modeWeek.classList.toggle('active', mode === 'week');
      modeMonth.classList.toggle('active', mode === 'month');
      if (mode === 'week') {
        weekView.hidden = false;
        monthView.hidden = true;
        renderWeek();
      } else {
        weekView.hidden = true;
        monthView.hidden = false;
        renderMonth();
      }
    };

    const shift = (delta) => {
      if (mode === 'week') {
        currentDate.setDate(currentDate.getDate() + delta * 7);
        renderWeek();
      } else {
        currentDate.setMonth(currentDate.getMonth() + delta);
        renderMonth();
      }
    };

    // controls
    prevBtn?.addEventListener('click', () => shift(-1));
    nextBtn?.addEventListener('click', () => shift(1));
    todayBtn?.addEventListener('click', () => {
      currentDate = new Date();
      if (mode === 'week') renderWeek(); else renderMonth();
    });
    modeWeek?.addEventListener('click', () => setMode('week'));
    modeMonth?.addEventListener('click', () => setMode('month'));

    // initial render
    setMode('week');
  });
})();