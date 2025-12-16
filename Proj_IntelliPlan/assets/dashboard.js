// Live clock and Pomodoro timer driven entirely by JS (no fixed text in HTML).

// ===== Live Clock =====
function formatTime(date) {
  let hours = date.getHours();
  const minutes = date.getMinutes().toString().padStart(2, "0");
  const ampm = hours >= 12 ? "PM" : "AM";
  hours = hours % 12 || 12;
  return `${hours}:${minutes} ${ampm}`;
}
function formatDate(date) {
  const days = ["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"];
  const months = [
    "January","February","March","April","May","June",
    "July","August","September","October","November","December"
  ];
  const dname = days[date.getDay()];
  const mname = months[date.getMonth()];
  return `${dname}, ${mname} ${date.getDate()}`;
}
function startLiveClock() {
  const timeEl = document.getElementById("liveTime");
  const dateEl = document.getElementById("liveDate");
  const greetingEl = document.getElementById('timeGreeting');

  function greetingForHour(hour) {
    if (hour >= 18) return 'GOOD EVENING.';
    if (hour >= 12) return 'GOOD AFTERNOON.';
    return 'GOOD MORNING.';
  }

  function tick() {
    const now = new Date();
    if (timeEl) timeEl.textContent = formatTime(now);
    if (dateEl) dateEl.textContent = formatDate(now);
    if (greetingEl) greetingEl.textContent = greetingForHour(now.getHours());
  }
  tick();
  setInterval(tick, 1000);
}

// ===== Pomodoro Timer (synced with clocktimer.php) =====
const POMODORO_STATE_KEY = 'intelliplan:pomodoroState';
const POMODORO_SETTINGS = {
  focusMinutes: 'intelliplan:pomodoroFocusMinutes',
  breakMinutes: 'intelliplan:pomodoroShortBreakMinutes',
  alertSound: 'intelliplan:pomodoroAlertSound',
};

let pomodoroTicker = null;
let audioCtx = null;

function safeJsonParse(text, fallback) {
  try { return JSON.parse(text); } catch { return fallback; }
}

function getIntSetting(key, fallback) {
  const raw = localStorage.getItem(key);
  const n = parseInt(raw || '', 10);
  return Number.isFinite(n) && n > 0 ? n : fallback;
}

function getBoolSetting(key, fallback) {
  const raw = localStorage.getItem(key);
  if (raw === null) return fallback;
  return raw === '1' || raw === 'true' || raw === 'on' || raw === 'yes';
}

function loadPomodoroSettings() {
  const focusMinutes = getIntSetting(POMODORO_SETTINGS.focusMinutes, 25);
  const breakMinutes = getIntSetting(POMODORO_SETTINGS.breakMinutes, 5);
  const alertSound = getBoolSetting(POMODORO_SETTINGS.alertSound, true);
  return {
    focusSeconds: focusMinutes * 60,
    breakSeconds: breakMinutes * 60,
    alertSound,
  };
}

function loadPomodoroState() {
  const settings = loadPomodoroSettings();
  const stored = safeJsonParse(localStorage.getItem(POMODORO_STATE_KEY) || '', null);

  const base = {
    running: false,
    mode: 'focus',
    remainingSeconds: settings.focusSeconds,
    endAtMs: null,
    focusSeconds: settings.focusSeconds,
    breakSeconds: settings.breakSeconds,
  };

  if (!stored || typeof stored !== 'object') return base;

  const next = {
    ...base,
    ...stored,
    focusSeconds: settings.focusSeconds,
    breakSeconds: settings.breakSeconds,
  };

  next.running = !!next.running;
  next.mode = next.mode === 'break' ? 'break' : 'focus';
  next.remainingSeconds = Math.max(0, parseInt(next.remainingSeconds || 0, 10));
  next.endAtMs = next.endAtMs ? Number(next.endAtMs) : null;

  if (!next.running && (!Number.isFinite(next.remainingSeconds) || next.remainingSeconds <= 0)) {
    next.remainingSeconds = next.mode === 'focus' ? next.focusSeconds : next.breakSeconds;
  }

  return next;
}

function savePomodoroState(state) {
  localStorage.setItem(POMODORO_STATE_KEY, JSON.stringify(state));
}

function computedRemainingSeconds(state) {
  if (!state.running || !state.endAtMs) return state.remainingSeconds;
  const msLeft = state.endAtMs - Date.now();
  return Math.max(0, Math.ceil(msLeft / 1000));
}

function ensureAudioUnlocked() {
  if (!audioCtx) {
    const Ctx = window.AudioContext || window.webkitAudioContext;
    if (!Ctx) return;
    audioCtx = new Ctx();
  }
  if (audioCtx.state === 'suspended') {
    audioCtx.resume().catch(() => {});
  }
}

function beep() {
  ensureAudioUnlocked();
  if (!audioCtx || audioCtx.state !== 'running') return;
  const o = audioCtx.createOscillator();
  const g = audioCtx.createGain();
  o.type = 'sine';
  o.frequency.value = 880;
  g.gain.setValueAtTime(0.0001, audioCtx.currentTime);
  g.gain.exponentialRampToValueAtTime(0.12, audioCtx.currentTime + 0.01);
  g.gain.exponentialRampToValueAtTime(0.0001, audioCtx.currentTime + 0.25);
  o.connect(g);
  g.connect(audioCtx.destination);
  o.start();
  o.stop(audioCtx.currentTime + 0.26);
}

function advanceIfFinished(state) {
  const remaining = computedRemainingSeconds(state);
  if (!state.running || remaining > 0) return state;

  const { alertSound } = loadPomodoroSettings();
  if (alertSound) beep();

  const nextMode = state.mode === 'focus' ? 'break' : 'focus';
  const nextDuration = nextMode === 'focus' ? state.focusSeconds : state.breakSeconds;
  return {
    ...state,
    mode: nextMode,
    remainingSeconds: nextDuration,
    endAtMs: Date.now() + nextDuration * 1000,
    running: true,
  };
}

function renderPomodoro() {
  const ringEl = document.getElementById('timerRing');
  const labelEl = document.getElementById('timerLabel');
  const modeEl = document.getElementById('timerMode');
  const startPauseBtn = document.getElementById('btnStartPause');
  const resetBtn = document.getElementById('btnReset');
  if (!ringEl || !labelEl || !startPauseBtn || !resetBtn) return;

  let state = loadPomodoroState();
  state = advanceIfFinished(state);

  const remaining = computedRemainingSeconds(state);
  const total = state.mode === 'focus' ? state.focusSeconds : state.breakSeconds;
  const m = String(Math.floor(remaining / 60)).padStart(2, '0');
  const s = String(remaining % 60).padStart(2, '0');
  labelEl.textContent = `${m}:${s}`;
  if (modeEl) modeEl.textContent = state.mode === 'focus' ? 'Focus' : 'Short Break';

  const progress = total > 0 ? (1 - remaining / total) : 0;
  const percent = Math.max(0, Math.min(100, Math.round(progress * 100)));
  ringEl.style.background =
    `radial-gradient(closest-side, #fff 72%, transparent 73% 100%),` +
    `conic-gradient(var(--primary) 0% ${percent}%, var(--ring) ${percent}% 100%)`;

  startPauseBtn.textContent = state.running ? '⏸' : '▶';
  startPauseBtn.setAttribute('aria-label', state.running ? 'Pause' : 'Start');

  savePomodoroState({ ...state, remainingSeconds: remaining, endAtMs: state.running ? state.endAtMs : null });
}

function ensurePomodoroTicker() {
  const ringEl = document.getElementById('timerRing');
  const startPauseBtn = document.getElementById('btnStartPause');
  const resetBtn = document.getElementById('btnReset');
  if (!ringEl || !startPauseBtn || !resetBtn) return;

  const state = loadPomodoroState();
  const shouldRun = !!state.running;
  if (shouldRun && !pomodoroTicker) {
    pomodoroTicker = setInterval(renderPomodoro, 500);
  }
  if (!shouldRun && pomodoroTicker) {
    clearInterval(pomodoroTicker);
    pomodoroTicker = null;
  }
}

function initPomodoro() {
  const startPauseBtn = document.getElementById('btnStartPause');
  const resetBtn = document.getElementById('btnReset');
  const ringEl = document.getElementById('timerRing');
  if (!ringEl || !startPauseBtn || !resetBtn) return;

  const unlock = () => ensureAudioUnlocked();
  startPauseBtn.addEventListener('pointerdown', unlock, { passive: true });
  resetBtn.addEventListener('pointerdown', unlock, { passive: true });

  startPauseBtn.addEventListener('click', () => {
    let state = loadPomodoroState();
    const remaining = computedRemainingSeconds(state);

    if (state.running) {
      state = { ...state, running: false, remainingSeconds: remaining, endAtMs: null };
    } else {
      const dur = remaining > 0 ? remaining : (state.mode === 'focus' ? state.focusSeconds : state.breakSeconds);
      state = { ...state, running: true, remainingSeconds: dur, endAtMs: Date.now() + dur * 1000 };
    }

    savePomodoroState(state);
    renderPomodoro();
    ensurePomodoroTicker();
  });

  resetBtn.addEventListener('click', () => {
    const settings = loadPomodoroSettings();
    const state = {
      running: false,
      mode: 'focus',
      remainingSeconds: settings.focusSeconds,
      endAtMs: null,
      focusSeconds: settings.focusSeconds,
      breakSeconds: settings.breakSeconds,
    };
    savePomodoroState(state);
    renderPomodoro();
    ensurePomodoroTicker();
  });

  window.addEventListener('storage', (e) => {
    if (!e.key) return;
    if (e.key === POMODORO_STATE_KEY || Object.values(POMODORO_SETTINGS).includes(e.key)) {
      renderPomodoro();
      ensurePomodoroTicker();
    }
  });

  document.addEventListener('visibilitychange', () => {
    renderPomodoro();
    ensurePomodoroTicker();
  });
  window.addEventListener('focus', () => {
    renderPomodoro();
    ensurePomodoroTicker();
  });

  renderPomodoro();
  ensurePomodoroTicker();
}

// ===== Dashboard mini calendar (real-time week + today highlight) =====
let dashboardSelectedIso = null;
let dashboardCalendarView = 'day';

function parseTimeParts(timeStr) {
  if (!timeStr) return null;
  const parts = String(timeStr).split(':');
  const hh = parseInt(parts[0] || '', 10);
  const mm = parseInt(parts[1] || '0', 10);
  if (!Number.isFinite(hh) || !Number.isFinite(mm)) return null;
  return { hh, mm };
}

function formatHm12(hh, mm) {
  const ampm = hh >= 12 ? 'PM' : 'AM';
  const h12 = (hh % 12) || 12;
  const m2 = String(mm).padStart(2, '0');
  return `${h12}:${m2} ${ampm}`;
}

async function fetchJson(url) {
  const res = await fetch(url, { credentials: 'same-origin' });
  if (!res.ok) throw new Error('Network error ' + res.status);
  return await res.json();
}

// Week indicators (class dots)
let dashboardClassesCache = null;
let dashboardClassesCacheAt = 0;

async function getDashboardClassesCached() {
  const now = Date.now();
  if (dashboardClassesCache && (now - dashboardClassesCacheAt) < 30_000) return dashboardClassesCache;
  try {
    const data = await fetchJson('lib/api/classes.php?view=current');
    dashboardClassesCache = Array.isArray(data) ? data : [];
  } catch {
    dashboardClassesCache = [];
  }
  dashboardClassesCacheAt = now;
  return dashboardClassesCache;
}

function dayAbbrevForDate(date) {
  return ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'][date.getDay()];
}

function classMatchesIsoDate(cls, iso, dayAbbrev) {
  if (!cls) return false;
  const status = String(cls?.status || 'active').toLowerCase();
  if (status === 'archived') return false;

  const startsAt = cls?.starts_at ? new Date(cls.starts_at) : null;
  if (startsAt && !Number.isNaN(startsAt.getTime())) {
    return toIsoDate(startsAt) === iso;
  }

  const daysRaw = String(cls?.days || '').trim();
  if (!daysRaw || !dayAbbrev) return false;
  const days = daysRaw.split(',').map(s => s.trim()).filter(Boolean);
  return days.includes(dayAbbrev);
}

function ensureWeekdayDot(buttonEl) {
  if (!buttonEl) return null;
  let dot = buttonEl.querySelector('.wd-dot');
  if (!dot) {
    dot = document.createElement('span');
    dot.className = 'wd-dot';
    dot.setAttribute('aria-hidden', 'true');
    buttonEl.appendChild(dot);
  }
  return dot;
}

async function refreshDashboardWeekIndicators() {
  const weekEl = document.querySelector('.calendar-week');
  if (!weekEl) return;
  const dayButtons = Array.from(weekEl.querySelectorAll('button.weekday'));
  if (dayButtons.length === 0) return;

  const classes = await getDashboardClassesCached();
  dayButtons.forEach((btn) => {
    ensureWeekdayDot(btn);
    const iso = btn.dataset.date;
    if (!iso) {
      btn.classList.remove('has-items');
      return;
    }
    const d = new Date(iso + 'T00:00:00');
    const dayAbbrev = Number.isNaN(d.getTime()) ? null : dayAbbrevForDate(d);
    const hasClass = classes.some((c) => classMatchesIsoDate(c, iso, dayAbbrev));
    btn.classList.toggle('has-items', !!hasClass);
  });
}

// Day schedule (reminders)
let dashboardScheduleRequestId = 0;
const DASH_SCHEDULE_START_HOUR = 1;  // 1 AM
const DASH_SCHEDULE_END_HOUR = 23;   // 11 PM
const DASH_SCHEDULE_HOURS = DASH_SCHEDULE_END_HOUR - DASH_SCHEDULE_START_HOUR + 1;

function hourIndexForSchedule(hh) {
  if (!Number.isFinite(hh)) return null;
  if (hh < DASH_SCHEDULE_START_HOUR || hh > DASH_SCHEDULE_END_HOUR) return null;
  return hh - DASH_SCHEDULE_START_HOUR;
}

function scheduleRowTemplate(label) {
  const row = document.createElement('div');
  row.className = 'hour-row';
  row.innerHTML = `
    <div class="hour">${label}</div>
    <div class="hour-events"></div>
  `;
  return row;
}

function addScheduleItem(targetEl, type, text) {
  const div = document.createElement('div');
  div.className = `hour-event hour-event--${type}`;
  div.textContent = text;
  targetEl.appendChild(div);
}

async function fetchScheduleCalendarEvents(iso) {
  const start = `${iso} 00:00:00`;
  const end = `${iso} 23:59:59`;
  const url = `lib/api/calendar.php?start=${encodeURIComponent(start)}&end=${encodeURIComponent(end)}`;
  const data = await fetchJson(url);
  return Array.isArray(data) ? data : [];
}

async function fetchScheduleTasks() {
  const data = await fetchJson('lib/api/tasks.php');
  return Array.isArray(data) ? data : [];
}

async function fetchScheduleClasses() {
  const data = await fetchJson('lib/api/classes.php?view=current');
  return Array.isArray(data) ? data : [];
}

async function fetchScheduleExams(iso) {
  const url = `lib/api/exams.php?date=${encodeURIComponent(iso)}`;
  const data = await fetchJson(url);
  return Array.isArray(data) ? data : [];
}

async function renderDashboardDaySchedule() {
  const scheduleEl = document.getElementById('dashDaySchedule');
  if (!scheduleEl) return;
  if (dashboardCalendarView !== 'day') return;

  const iso = dashboardSelectedIso || toIsoDate(new Date());
  const requestId = ++dashboardScheduleRequestId;

  const hourRows = [];
  for (let hh = DASH_SCHEDULE_START_HOUR; hh <= DASH_SCHEDULE_END_HOUR; hh++) {
    const label = formatHm12(hh, 0).replace(':00', '');
    hourRows.push(scheduleRowTemplate(label));
  }
  scheduleEl.replaceChildren(...hourRows);

  const dayAbbrev = dayAbbrevForDate(new Date(iso + 'T00:00:00'));

  const results = await Promise.allSettled([
    fetchScheduleCalendarEvents(iso),
    fetchScheduleTasks(),
    fetchScheduleClasses(),
    fetchScheduleExams(iso),
  ]);
  if (requestId !== dashboardScheduleRequestId) return;

  const calEvents = results[0].status === 'fulfilled' ? results[0].value : [];
  const tasks = results[1].status === 'fulfilled' ? results[1].value : [];
  const classes = results[2].status === 'fulfilled' ? results[2].value : [];
  const exams = results[3].status === 'fulfilled' ? results[3].value : [];

  const buckets = Array.from({ length: DASH_SCHEDULE_HOURS }, () => []);

  calEvents.forEach((ev) => {
    const title = (ev?.title || '').trim();
    if (!title) return;
    if (ev?.allDay) {
      buckets[0].push({ sort: 0, type: 'event', text: `All day — ${title}` });
      return;
    }
    const startDt = ev?.start ? new Date(ev.start) : null;
    if (!startDt || Number.isNaN(startDt.getTime())) return;
    const hh = startDt.getHours();
    const mm = startDt.getMinutes();
    const idx = hourIndexForSchedule(hh);
    if (idx === null) return;
    buckets[idx].push({ sort: hh * 60 + mm, type: 'event', text: `${formatHm12(hh, mm)} — ${title}` });
  });

  tasks
    .filter((t) => (t?.due_date || '') === iso)
    .filter((t) => (String(t?.status || 'open').toLowerCase() !== 'done'))
    .forEach((t) => {
      const title = (t?.title || 'Untitled').trim();
      const dueParts = parseTimeParts(t?.due_time);
      if (!dueParts) {
        buckets[0].push({ sort: 1, type: 'task', text: `All day — ${title}` });
        return;
      }
      const idx = hourIndexForSchedule(dueParts.hh);
      if (idx === null) return;
      buckets[idx].push({
        sort: dueParts.hh * 60 + dueParts.mm,
        type: 'task',
        text: `${formatHm12(dueParts.hh, dueParts.mm)} — ${title}`,
      });
    });

  classes.forEach((c) => {
    const status = String(c?.status || 'active').toLowerCase();
    if (status === 'archived') return;

    const isoDate = iso;
    const d = new Date(isoDate + 'T00:00:00');
    const day = Number.isNaN(d.getTime()) ? null : dayAbbrev;
    if (!classMatchesIsoDate(c, isoDate, day)) return;

    const name = (c?.subject || c?.name || '').trim();
    if (!name) return;

    const startParts = parseTimeParts(c?.start_time);
    if (!startParts) {
      buckets[0].push({ sort: 2, type: 'class', text: `All day — ${name}` });
      return;
    }
    const idx = hourIndexForSchedule(startParts.hh);
    if (idx === null) return;

    let timeLabel = formatHm12(startParts.hh, startParts.mm);
    const endParts = parseTimeParts(c?.end_time);
    if (endParts) timeLabel = `${timeLabel}–${formatHm12(endParts.hh, endParts.mm)}`;

    buckets[idx].push({
      sort: startParts.hh * 60 + startParts.mm,
      type: 'class',
      text: `${timeLabel} — ${name}`,
    });
  });

  exams
    .filter((ex) => String(ex?.status || 'scheduled').toLowerCase() !== 'done')
    .forEach((ex) => {
      const title = (ex?.title || '').trim();
      if (!title) return;
      const timeParts = parseTimeParts(ex?.exam_time);
      if (!timeParts) {
        buckets[0].push({ sort: 3, type: 'exam', text: `All day — Exam: ${title}` });
        return;
      }
      const idx = hourIndexForSchedule(timeParts.hh);
      if (idx === null) return;
      buckets[idx].push({
        sort: timeParts.hh * 60 + timeParts.mm,
        type: 'exam',
        text: `${formatHm12(timeParts.hh, timeParts.mm)} — Exam: ${title}`,
      });
    });

  const totalItems = buckets.reduce((acc, b) => acc + b.length, 0);
  if (totalItems === 0) {
    const eventsEl = hourRows[0]?.querySelector('.hour-events');
    if (eventsEl) addScheduleItem(eventsEl, 'event', 'No reminders for this day.');
    return;
  }

  for (let i = 0; i < hourRows.length; i++) {
    const eventsEl = hourRows[i].querySelector('.hour-events');
    if (!eventsEl) continue;
    const items = buckets[i].sort((a, b) => a.sort - b.sort);
    items.forEach((it) => addScheduleItem(eventsEl, it.type, it.text));
  }
}

function startOfWeekMonday(date) {
  const d = new Date(date);
  d.setHours(0, 0, 0, 0);
  const day = (d.getDay() + 6) % 7; // Mon=0 ... Sun=6
  d.setDate(d.getDate() - day);
  return d;
}

function toIsoDate(date) {
  const yyyy = date.getFullYear();
  const mm = String(date.getMonth() + 1).padStart(2, '0');
  const dd = String(date.getDate()).padStart(2, '0');
  return `${yyyy}-${mm}-${dd}`;
}

function renderDashboardWeekCalendar() {
  const weekEl = document.querySelector('.calendar-week');
  if (!weekEl) return;

  const days = Array.from(weekEl.querySelectorAll('button.weekday'));
  if (days.length === 0) return;

  const now = new Date();
  const todayIso = toIsoDate(now);
  if (!dashboardSelectedIso) dashboardSelectedIso = todayIso;

  const base = new Date(dashboardSelectedIso + 'T00:00:00');
  const baseDate = Number.isNaN(base.getTime()) ? now : base;
  const weekStart = startOfWeekMonday(baseDate);

  for (let i = 0; i < days.length; i++) {
    const cell = days[i];
    const nameEl = cell.querySelector('.wd-name');
    const numEl = cell.querySelector('.wd-num');
    if (!nameEl || !numEl) continue;

    const d = new Date(weekStart);
    d.setDate(weekStart.getDate() + i);
    const iso = toIsoDate(d);

    const short = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'][d.getDay()];
    nameEl.textContent = short;
    numEl.textContent = String(d.getDate());

    cell.dataset.date = iso;
    cell.setAttribute('aria-label', iso);

    if (iso === dashboardSelectedIso) cell.classList.add('active');
    else cell.classList.remove('active');
  }

  renderDashboardSelectedDayChip();
  refreshDashboardWeekIndicators();
}

function renderDashboardSelectedDayChip() {
  const nameEl = document.getElementById('dashSelectedDayName');
  const numEl = document.getElementById('dashSelectedDayNum');
  if (!nameEl || !numEl || !dashboardSelectedIso) return;

  const d = new Date(dashboardSelectedIso + 'T00:00:00');
  if (Number.isNaN(d.getTime())) return;

  const dayName = ['SUN','MON','TUE','WED','THU','FRI','SAT'][d.getDay()];
  nameEl.textContent = dayName;
  numEl.textContent = String(d.getDate());
}

function setDashboardSelectedDate(iso) {
  if (!iso) return;
  dashboardSelectedIso = iso;
  renderDashboardCalendar?.();
  renderDashboardWeekCalendar();
  renderDashboardDaySchedule();
}

function scheduleDashboardCalendarRefresh() {
  const now = new Date();
  const nextMidnight = new Date(now);
  nextMidnight.setHours(24, 0, 0, 0);
  const ms = Math.max(250, nextMidnight.getTime() - now.getTime() + 25);
  setTimeout(() => {
    const prevToday = toIsoDate(new Date(Date.now() - 1000));
    const newToday = toIsoDate(new Date());
    if (dashboardSelectedIso === prevToday) dashboardSelectedIso = newToday;
    renderDashboardCalendar?.();
    renderDashboardWeekCalendar();
    renderDashboardDaySchedule();
    scheduleDashboardCalendarRefresh();
  }, ms);
}

function renderDashboardMonthCalendar() {
  const monthEl = document.getElementById('dashCalendarMonth');
  if (!monthEl) return;

  const base = dashboardSelectedIso ? new Date(dashboardSelectedIso + 'T00:00:00') : new Date();
  const year = base.getFullYear();
  const month = base.getMonth();

  const firstOfMonth = new Date(year, month, 1);
  const gridStart = startOfWeekMonday(firstOfMonth);

  const daysShort = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
  const currentMonth = month;

  const frag = document.createDocumentFragment();

  const header = document.createElement('div');
  header.className = 'calendar-month-header';
  daysShort.forEach(n => {
    const h = document.createElement('div');
    h.className = 'month-header-cell';
    h.textContent = n;
    header.appendChild(h);
  });
  frag.appendChild(header);

  const grid = document.createElement('div');
  grid.className = 'calendar-month-grid';
  for (let i = 0; i < 42; i++) {
    const d = new Date(gridStart);
    d.setDate(gridStart.getDate() + i);
    const iso = toIsoDate(d);

    const btn = document.createElement('button');
    btn.className = 'weekday monthday';
    btn.type = 'button';
    btn.dataset.date = iso;
    btn.setAttribute('aria-label', iso);
    btn.textContent = String(d.getDate());

    if (d.getMonth() !== currentMonth) btn.classList.add('muted');
    if (iso === dashboardSelectedIso) btn.classList.add('active');

    grid.appendChild(btn);
  }
  frag.appendChild(grid);

  monthEl.innerHTML = '';
  monthEl.appendChild(frag);

  renderDashboardSelectedDayChip();
}

function renderDashboardCalendar() {
  const weekEl = document.querySelector('.calendar-week');
  const monthEl = document.getElementById('dashCalendarMonth');
  const daylineEl = document.querySelector('.calendar-dayline');
  const hoursEl = document.querySelector('.calendar-hours');

  if (dashboardCalendarView === 'month') {
    if (weekEl) weekEl.hidden = true;
    if (monthEl) monthEl.hidden = false;
    if (daylineEl) daylineEl.hidden = false;
    if (hoursEl) hoursEl.hidden = true;
    renderDashboardMonthCalendar();
  } else {
    if (weekEl) weekEl.hidden = false;
    if (monthEl) monthEl.hidden = true;
    if (daylineEl) daylineEl.hidden = false;
    if (hoursEl) hoursEl.hidden = (dashboardCalendarView !== 'day');
    renderDashboardWeekCalendar();
  }
}

function initDashboardCalendar() {
  if (!document.querySelector('.calendar-week')) return;

  renderDashboardCalendar?.();
  renderDashboardWeekCalendar();
  renderDashboardDaySchedule();
  scheduleDashboardCalendarRefresh();

  document.querySelector('.calendar-week')?.addEventListener('click', (e) => {
    const btn = e.target.closest('button.weekday');
    if (!btn) return;
    const iso = btn.dataset.date;
    if (iso) setDashboardSelectedDate(iso);
  });

  const todayBtn = document.getElementById('dashTodayBtn');
  if (todayBtn) {
    todayBtn.addEventListener('click', () => {
      const todayIso = toIsoDate(new Date());
      setDashboardSelectedDate(todayIso);
    });
  }

  const viewSel = document.getElementById('dashCalendarView');
  if (viewSel) {
    dashboardCalendarView = (viewSel.value || 'day').toLowerCase();
    viewSel.addEventListener('change', () => {
      dashboardCalendarView = (viewSel.value || 'day').toLowerCase();
      renderDashboardCalendar();
      renderDashboardDaySchedule();
    });
  }

  document.getElementById('dashCalendarMonth')?.addEventListener('click', (e) => {
    const btn = e.target.closest('button.monthday');
    if (!btn) return;
    const iso = btn.dataset.date;
    if (iso) setDashboardSelectedDate(iso);
  });

  document.addEventListener('visibilitychange', () => {
    if (!document.hidden) {
      renderDashboardWeekCalendar();
      renderDashboardDaySchedule();
    }
  });
  window.addEventListener('focus', () => {
    renderDashboardWeekCalendar();
    renderDashboardDaySchedule();
  });
}

// ===== Initialize =====
document.addEventListener("DOMContentLoaded", () => {
  startLiveClock();
  initPomodoro();
  initDashboardCalendar();

  // ===== Dashboard classes panel =====
  (async function initDashboardClasses(){
    const listEl = document.getElementById('dashboardClassesList');
    if (!listEl) return;

    function escapeHtml(s){
      return (s + '')
        .replace(/&/g,'&amp;')
        .replace(/</g,'&lt;')
        .replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;')
        .replace(/'/g,'&#039;');
    }

    function formatTimeAmerican(timeStr){
      if (!timeStr) return '';
      const parts = String(timeStr).split(':');
      const h = parseInt(parts[0] || '0', 10);
      const m = parseInt(parts[1] || '0', 10);
      const ampm = h >= 12 ? 'PM' : 'AM';
      const displayHour = (h % 12) || 12;
      return `${displayHour}:${String(m).padStart(2,'0')} ${ampm}`;
    }

    async function fetchClasses(){
      const data = await fetchJson('lib/api/classes.php?view=current');
      return Array.isArray(data) ? data : [];
    }

    function render(classes){
      const current = (classes || [])
        .filter(c => String(c?.status || 'active').toLowerCase() !== 'archived')
        .sort((a, b) => {
          const as = a?.start_time || '';
          const bs = b?.start_time || '';
          if (as && bs && as !== bs) return as.localeCompare(bs);
          if (as && !bs) return -1;
          if (!as && bs) return 1;
          return String(a?.subject || a?.name || '').localeCompare(String(b?.subject || b?.name || ''));
        })
        .slice(0, 5);

      if (current.length === 0) {
        listEl.classList.add('muted');
        listEl.textContent = 'No classes to display.';
        return;
      }

      listEl.classList.remove('muted');
      listEl.innerHTML = '';
      current.forEach(c => {
        const row = document.createElement('div');
        row.className = 'dash-task-item';
        const title = (c?.subject || c?.name || 'Untitled').trim();
        const meta = [];
        if (c?.days) meta.push(String(c.days).split(',').map(s => s.trim()).filter(Boolean).join(', '));
        if (c?.start_time && c?.end_time) {
          meta.push(`${formatTimeAmerican(c.start_time)} - ${formatTimeAmerican(c.end_time)}`);
        } else if (c?.time) {
          meta.push(String(c.time));
        }
        if (c?.professor) meta.push(String(c.professor));

        row.innerHTML = `
          <div class="dash-task-title">${escapeHtml(title)}</div>
          ${meta.length ? `<div class="dash-task-meta">${escapeHtml(meta.join(' • '))}</div>` : ''}
        `;
        listEl.appendChild(row);
      });
    }

    try {
      const classes = await fetchClasses();
      render(classes);
    } catch (e) {
      listEl.classList.add('muted');
      listEl.textContent = 'Failed to load classes.';
    }
  })();

  // ===== Dashboard task widgets (stats + list) =====
  (async function initDashboardTasks(){
    const statPendingEl = document.getElementById('statPending');
    const statOverdueEl = document.getElementById('statOverdue');
    const statCompletedEl = document.getElementById('statCompleted');
    const dueTodayCountEl = document.getElementById('dueTodayCount');
    const dashTasksCountEl = document.getElementById('dashTasksCount');
    const dashboardTasksListEl = document.getElementById('dashboardTasksList');
    const dashTasksSubjectEl = document.getElementById('dashTasksSubject');
    const dashTasksViewEl = document.getElementById('dashTasksView');

    // Only run on pages that actually have dashboard task widgets.
    if (!statPendingEl && !dashboardTasksListEl && !dueTodayCountEl && !dashTasksCountEl) return;

    function isoToday(){
      const d = new Date();
      const yyyy = d.getFullYear();
      const mm = String(d.getMonth() + 1).padStart(2, '0');
      const dd = String(d.getDate()).padStart(2, '0');
      return `${yyyy}-${mm}-${dd}`;
    }

    function isDone(t){
      return (t?.status || 'open').toLowerCase() === 'done';
    }

    function isOverdue(t, today){
      const due = t?.due_date || '';
      if (!due) return false;
      return !isDone(t) && due < today;
    }

    function isPending(t, today){
      const due = t?.due_date || '';
      if (isDone(t)) return false;
      return !due || due >= today;
    }

    function isDueToday(t, today){
      const due = t?.due_date || '';
      return !isDone(t) && !!due && due === today;
    }

    async function fetchTasks(){
      const res = await fetch('lib/api/tasks.php', { credentials: 'same-origin' });
      if (!res.ok) throw new Error('Network error ' + res.status);
      const data = await res.json();
      return Array.isArray(data) ? data : [];
    }

    function buildSubjectOptions(tasks){
      if (!dashTasksSubjectEl) return;
      const selected = dashTasksSubjectEl.value;
      const subjects = Array.from(new Set(tasks.map(t => (t?.subject || '').trim()).filter(Boolean)))
        .sort((a, b) => a.localeCompare(b));

      dashTasksSubjectEl.innerHTML = '<option value="">Select Subject</option>' +
        subjects.map(s => `<option value="${escapeHtml(s)}">${escapeHtml(s)}</option>`).join('');

      if (selected && subjects.includes(selected)) {
        dashTasksSubjectEl.value = selected;
      }
    }

    function filterForPanel(tasks){
      const today = isoToday();
      const subject = (dashTasksSubjectEl?.value || '').trim();
      const view = (dashTasksViewEl?.value || 'current').toLowerCase();

      return tasks.filter(t => {
        if (subject && (t?.subject || '') !== subject) return false;

        if (view === 'past') return isDone(t);
        if (view === 'overdue') return isOverdue(t, today);
        // current
        return isPending(t, today);
      });
    }

    function renderTaskList(tasks){
      if (!dashboardTasksListEl) return;
      const filtered = filterForPanel(tasks);
      if (dashTasksCountEl) dashTasksCountEl.textContent = String(filtered.length);

      const current = filtered
        .sort((a, b) => {
          const ad = a?.due_date || '';
          const bd = b?.due_date || '';
          if (!ad && bd) return 1;
          if (ad && !bd) return -1;
          if (ad && bd && ad !== bd) return ad.localeCompare(bd);
          return (b?.id || 0) - (a?.id || 0);
        })
        .slice(0, 5);

      if (current.length === 0) {
        dashboardTasksListEl.classList.add('muted');
        dashboardTasksListEl.textContent = 'No tasks to display.';
        return;
      }

      dashboardTasksListEl.classList.remove('muted');
      dashboardTasksListEl.innerHTML = '';
      current.forEach(t => {
        const row = document.createElement('div');
        row.className = 'dash-task-item';
        const meta = [];
        if (t.subject) meta.push(t.subject);
        if (t.due_date) meta.push(t.due_date);
        row.innerHTML = `
          <div class="dash-task-title">${escapeHtml(t.title || 'Untitled')}</div>
          ${meta.length ? `<div class="dash-task-meta">${escapeHtml(meta.join(' • '))}</div>` : ''}
        `;
        dashboardTasksListEl.appendChild(row);
      });
    }

    function escapeHtml(s){
      return (s + '')
        .replace(/&/g,'&amp;')
        .replace(/</g,'&lt;')
        .replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;')
        .replace(/'/g,'&#039;');
    }

    try {
      const tasks = await fetchTasks();
      const today = isoToday();

      const pendingCount = tasks.filter(t => isPending(t, today)).length;
      const overdueCount = tasks.filter(t => isOverdue(t, today)).length;
      const completedCount = tasks.filter(t => isDone(t)).length;
      const dueTodayCount = tasks.filter(t => isDueToday(t, today)).length;

      if (statPendingEl) statPendingEl.textContent = String(pendingCount);
      if (statOverdueEl) statOverdueEl.textContent = String(overdueCount);
      if (statCompletedEl) statCompletedEl.textContent = String(completedCount);

      if (dueTodayCountEl) {
        dueTodayCountEl.textContent = String(dueTodayCount);
        // Fix plural grammar by tweaking the trailing text node when present.
        const p = dueTodayCountEl.parentElement;
        if (p && p.childNodes && p.childNodes.length) {
          const last = p.childNodes[p.childNodes.length - 1];
          if (last && last.nodeType === Node.TEXT_NODE) {
            last.textContent = ` ${dueTodayCount === 1 ? 'task' : 'tasks'} due today.`;
          }
        }
      }

      buildSubjectOptions(tasks);
      renderTaskList(tasks);

      dashTasksSubjectEl?.addEventListener('change', () => renderTaskList(tasks));
      dashTasksViewEl?.addEventListener('change', () => renderTaskList(tasks));
    } catch (e) {
      if (dashboardTasksListEl) {
        dashboardTasksListEl.classList.add('muted');
        dashboardTasksListEl.textContent = 'Failed to load tasks.';
      }

      if (dashTasksCountEl) dashTasksCountEl.textContent = '0';
    }
  })();
});

// ===== Dropdown click-to-toggle behavior (no hover) =====
(function () {
  function closeAllDropdowns() {
    document.querySelectorAll('.dropdown-wrapper.open').forEach(wrapper => {
      wrapper.classList.remove('open');
      const btn = wrapper.querySelector('.dropdown-btn');
      const menu = wrapper.querySelector('.dropdown-menu');
      if (btn) {
        btn.classList.remove('active');
        btn.setAttribute('aria-expanded', 'false');
      }
      if (menu) menu.hidden = true;
    });
  }

  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.dropdown-btn');
    if (btn) {
      const wrapper = btn.closest('.dropdown-wrapper');
      if (!wrapper) return;
      const menu = wrapper.querySelector('.dropdown-menu');
      const isOpen = wrapper.classList.contains('open');
      // close others
      closeAllDropdowns();
      if (!isOpen) {
        wrapper.classList.add('open');
        btn.classList.add('active');
        btn.setAttribute('aria-expanded', 'true');
        if (menu) menu.hidden = false;
      }
      e.preventDefault();
      return;
    }

    // Click outside — close all
    if (!e.target.closest('.dropdown-wrapper')) {
      closeAllDropdowns();
    }
  });

  // Close on Escape
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeAllDropdowns();
  });
})();