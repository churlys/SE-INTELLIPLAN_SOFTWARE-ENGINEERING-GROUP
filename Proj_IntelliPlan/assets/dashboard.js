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
  function tick() {
    const now = new Date();
    if (timeEl) timeEl.textContent = formatTime(now);
    if (dateEl) dateEl.textContent = formatDate(now);
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
  const weekStart = startOfWeekMonday(now);

  if (!dashboardSelectedIso) dashboardSelectedIso = todayIso;

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
  renderDashboardWeekCalendar();
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
    renderDashboardWeekCalendar();
    scheduleDashboardCalendarRefresh();
  }, ms);
}

function initDashboardCalendar() {
  if (!document.querySelector('.calendar-week')) return;

  renderDashboardWeekCalendar();
  scheduleDashboardCalendarRefresh();

  document.querySelector('.calendar-week')?.addEventListener('click', (e) => {
    const btn = e.target.closest('button.weekday');
    if (!btn) return;
    const iso = btn.dataset.date;
    if (iso) setDashboardSelectedDate(iso);
  });

  document.addEventListener('visibilitychange', () => {
    if (!document.hidden) renderDashboardWeekCalendar();
  });
  window.addEventListener('focus', renderDashboardWeekCalendar);
}

// ===== Initialize =====
document.addEventListener("DOMContentLoaded", () => {
  startLiveClock();
  initPomodoro();
  initDashboardCalendar();

  // ===== Dashboard task widgets (stats + list) =====
  (async function initDashboardTasks(){
    const statPendingEl = document.getElementById('statPending');
    const statOverdueEl = document.getElementById('statOverdue');
    const statCompletedEl = document.getElementById('statCompleted');
    const dueTodayCountEl = document.getElementById('dueTodayCount');
    const dashboardTasksListEl = document.getElementById('dashboardTasksList');
    const dashTasksSubjectEl = document.getElementById('dashTasksSubject');
    const dashTasksViewEl = document.getElementById('dashTasksView');

    // Only run on pages that actually have dashboard task widgets.
    if (!statPendingEl && !dashboardTasksListEl && !dueTodayCountEl) return;

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
      const current = filterForPanel(tasks)
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