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

    let events = [];
    let lastFetchKey = '';
    let lastTodayIso = '';
    let refreshTimerId = null;
    let midnightTimerId = null;

    let currentDate = new Date();
    let mode = 'week';

    const pad2 = (n) => String(n).padStart(2, '0');
    const formatDate = (date) => `${date.getFullYear()}-${pad2(date.getMonth() + 1)}-${pad2(date.getDate())}`;

    const toIsoDateTimeLocal = (date, endOfDay) => {
      const d = new Date(date);
      if (endOfDay) d.setHours(23, 59, 59, 999);
      else d.setHours(0, 0, 0, 0);
      return `${d.getFullYear()}-${pad2(d.getMonth() + 1)}-${pad2(d.getDate())} ${pad2(d.getHours())}:${pad2(d.getMinutes())}:${pad2(d.getSeconds())}`;
    };

    const startOfWeek = (date) => {
      const d = new Date(date);
      const day = d.getDay();
      const diff = (day === 0 ? -6 : 1) - day; // Monday start
      d.setDate(d.getDate() + diff);
      d.setHours(0, 0, 0, 0);
      return d;
    };

    const endOfWeek = (start) => {
      const d = new Date(start);
      d.setDate(d.getDate() + 6);
      d.setHours(23, 59, 59, 999);
      return d;
    };

    async function fetchEvents(rangeStart, rangeEnd){
      const qs = new URLSearchParams({
        start: toIsoDateTimeLocal(rangeStart, false),
        end: toIsoDateTimeLocal(rangeEnd, true),
      });
      const res = await fetch(`lib/api/calendar.php?${qs.toString()}`, { credentials: 'same-origin' });
      if (!res.ok) throw new Error('Calendar API error ' + res.status);
      const data = await res.json();
      return Array.isArray(data) ? data : [];
    }

    async function fetchTasks(rangeStart, rangeEnd){
      const res = await fetch('lib/api/tasks.php', { credentials: 'same-origin' });
      if (!res.ok) throw new Error('Tasks API error ' + res.status);
      const data = await res.json();
      const tasks = Array.isArray(data) ? data : [];

      const startIso = formatDate(rangeStart);
      const endIso = formatDate(rangeEnd);

      return tasks.filter(t => {
        const due = (t?.due_date || '').trim();
        if (!due) return false;
        const status = (t?.status || 'open').toLowerCase();
        if (status === 'done') return false;
        return due >= startIso && due <= endIso;
      });
    }

    function normalizeEvents(raw){
      return raw.map(e => {
        const start = e?.start ? new Date(e.start) : null;
        const end = e?.end ? new Date(e.end) : null;
        return {
          id: e?.id,
          title: e?.title || 'Untitled',
          start,
          end,
          allDay: !!(e?.allDay || e?.all_day),
          description: e?.description || '',
          source: 'calendar',
        };
      }).filter(e => e.start instanceof Date && !isNaN(e.start));
    }

    function normalizeTaskEvents(tasks){
      return tasks.map(t => {
        const due = (t?.due_date || '').trim();
        const start = due ? new Date(due + 'T00:00:00') : null;
        return {
          id: `task:${t?.id ?? ''}`,
          title: t?.title || 'Task',
          start,
          end: null,
          allDay: true,
          description: '',
          source: 'task',
        };
      }).filter(e => e.start instanceof Date && !isNaN(e.start));
    }

    async function ensureEventsLoaded(rangeStart, rangeEnd){
      const key = `${formatDate(rangeStart)}..${formatDate(rangeEnd)}`;
      if (key === lastFetchKey) return;
      lastFetchKey = key;
      try {
        const [raw, tasks] = await Promise.all([
          fetchEvents(rangeStart, rangeEnd),
          fetchTasks(rangeStart, rangeEnd),
        ]);
        events = [...normalizeEvents(raw), ...normalizeTaskEvents(tasks)];
      } catch (err) {
        // Fail soft: render empty calendar if API fails.
        events = [];
      }
    }

    function eventsForIsoDay(iso){
      return events
        .filter(ev => formatDate(ev.start) === iso)
        .sort((a, b) => a.start - b.start);
    }

    function formatTimeRange(ev){
      if (ev.source === 'task') return 'Due';
      if (ev.allDay) return 'All day';
      const start = ev.start;
      const end = ev.end;
      const fmt = { hour: '2-digit', minute: '2-digit' };
      const s = start.toLocaleTimeString([], fmt);
      if (!end || isNaN(end)) return s;
      const e = end.toLocaleTimeString([], fmt);
      return `${s} – ${e}`;
    }

    const renderWeek = async () => {
      const weekStart = startOfWeek(currentDate);
      const weekEnd = endOfWeek(weekStart);
      const formatter = new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric' });
      rangeLabel.textContent = `${formatter.format(weekStart)} – ${formatter.format(weekEnd)}`;

      // Render quickly, then fill events after load.
      weekView.innerHTML = '<div class="week-days"></div><div class="week-events"></div>';
      await ensureEventsLoaded(weekStart, weekEnd);

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
        const dayEvents = eventsForIsoDay(d.iso);
        const items = dayEvents.length ? dayEvents.map(ev => {
          const time = formatTimeRange(ev);
          return `<div class="event-chip"><strong>${escapeHtml(ev.title)}</strong><span>${escapeHtml(time)}</span></div>`;
        }).join('') : '<div class="event-chip" style="opacity:0.6;">No events</div>';
        return `<div class="day-column"><h4>${d.label}</h4>${items}</div>`;
      }).join('')}</div>`;

      weekView.innerHTML = dayHeaderHtml + dayEventsHtml;
    };

    const renderMonth = async () => {
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

      const monthStart = new Date(year, month, 1);
      monthStart.setHours(0, 0, 0, 0);
      const monthEnd = new Date(year, month, daysInMonth);
      monthEnd.setHours(23, 59, 59, 999);

      monthView.innerHTML = '<div class="month-grid"></div>';
      await ensureEventsLoaded(monthStart, monthEnd);

      const cells = [];
      // Leading blanks
      for (let i = 0; i < offset; i++) cells.push({ empty: true });
      for (let day = 1; day <= daysInMonth; day++) {
        const dateObj = new Date(year, month, day);
        const iso = formatDate(dateObj);
        const dayEvents = eventsForIsoDay(iso);
        cells.push({
          date: day,
          iso,
          isToday: iso === todayStr,
          events: dayEvents,
        });
      }

      monthView.innerHTML = `<div class="month-grid">${cells.map(cell => {
        if (cell.empty) return '<div class="month-cell" aria-hidden="true"></div>';
        const eventsHtml = cell.events.map(ev => `<div class="event-line"><span class="event-dot"></span><span>${escapeHtml(ev.title)}</span></div>`).join('');
        return `<div class="month-cell ${cell.isToday ? 'today' : ''}"><div class="date">${cell.date}</div>${eventsHtml}</div>`;
      }).join('')}</div>`;
    };

    function escapeHtml(s){
      return (s + '')
        .replace(/&/g,'&amp;')
        .replace(/</g,'&lt;')
        .replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;')
        .replace(/'/g,'&#039;');
    }

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

    function rerenderIfDayChanged() {
      const nowIso = formatDate(new Date());
      if (nowIso !== lastTodayIso) {
        lastTodayIso = nowIso;
        lastFetchKey = '';
      }
      if (mode === 'week') renderWeek(); else renderMonth();
    }

    function scheduleMidnightRefresh(){
      if (midnightTimerId) clearTimeout(midnightTimerId);
      const now = new Date();
      const next = new Date(now);
      next.setHours(24, 0, 0, 0);
      const ms = Math.max(250, next.getTime() - now.getTime() + 25);
      midnightTimerId = setTimeout(() => {
        rerenderIfDayChanged();
        scheduleMidnightRefresh();
      }, ms);
    }

    // Keep "today" highlight and task/event list fresh in real time.
    lastTodayIso = formatDate(new Date());
    scheduleMidnightRefresh();
    document.addEventListener('visibilitychange', () => {
      if (!document.hidden) rerenderIfDayChanged();
    });
    window.addEventListener('focus', rerenderIfDayChanged);

    // Periodic refresh so newly added tasks/events appear without reload.
    refreshTimerId = setInterval(() => {
      lastFetchKey = '';
      rerenderIfDayChanged();
    }, 60 * 1000);
  });
})();