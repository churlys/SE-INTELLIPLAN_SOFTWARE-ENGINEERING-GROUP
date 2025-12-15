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
  const greetingEl = document.getElementById("greetingTitle");

  function greetingForHour(hours) {
    if (hours < 12) return "GOOD MORNING.";
    if (hours < 18) return "GOOD AFTERNOON.";
    return "GOOD EVENING.";
  }

  function updateGreeting(now) {
    if (!greetingEl) return;
    const next = greetingForHour(now.getHours());
    if (greetingEl.textContent !== next) greetingEl.textContent = next;
  }

  function tick() {
    const now = new Date();
    if (timeEl) timeEl.textContent = formatTime(now);
    if (dateEl) dateEl.textContent = formatDate(now);
    updateGreeting(now);
  }
  tick();
  setInterval(tick, 1000);
}

// ===== Pomodoro Timer =====
const DEFAULT_MINUTES = 25;
const MIN_MINUTES = 1;
const MAX_MINUTES = 180;
const STORAGE_KEY_MINUTES = "intelliplan:pomodoroMinutes";

function clampInt(value, min, max) {
  const n = Number.parseInt(String(value), 10);
  if (!Number.isFinite(n)) return min;
  return Math.max(min, Math.min(max, n));
}

function loadSavedMinutes() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY_MINUTES);
    if (raw == null) return DEFAULT_MINUTES;
    return clampInt(raw, MIN_MINUTES, MAX_MINUTES);
  } catch {
    return DEFAULT_MINUTES;
  }
}

function saveMinutes(minutes) {
  try {
    localStorage.setItem(STORAGE_KEY_MINUTES, String(minutes));
  } catch {
    // ignore storage failures
  }
}

let durationSeconds = loadSavedMinutes() * 60;
let remaining = durationSeconds;
let running = false;
let intervalId = null;

const labelEl = document.getElementById("timerLabel");
const ringEl = document.getElementById("timerRing");
const startPauseBtn = document.getElementById("btnStartPause");
const resetBtn = document.getElementById("btnReset");
const minutesInputEl = document.getElementById("timerMinutes");
const setTimerBtn = document.getElementById("btnSetTimer");

function renderTimer() {
  const m = Math.floor(remaining / 60).toString().padStart(2, "0");
  const s = Math.floor(remaining % 60).toString().padStart(2, "0");
  if (labelEl) labelEl.textContent = `${m}:${s}`;

  const progress = durationSeconds > 0 ? (1 - remaining / durationSeconds) : 1; // 0..1
  const percent = Math.max(0, Math.min(100, Math.round(progress * 100)));
  if (ringEl) {
    ringEl.style.background =
      `radial-gradient(closest-side, #fff 72%, transparent 73% 100%),` +
      `conic-gradient(var(--primary) 0% ${percent}%, var(--ring) ${percent}% 100%)`;
  }
}

function tickTimer() {
  if (!running) return;
  remaining -= 1;
  if (remaining <= 0) {
    remaining = 0;
    running = false;
    clearInterval(intervalId);
    intervalId = null;
  }
  renderTimer();
}

function startTimer() {
  if (running) return;
  running = true;
  startPauseBtn.textContent = "⏸";
  startPauseBtn.setAttribute("aria-label", "Pause");
  if (!intervalId) intervalId = setInterval(tickTimer, 1000);
}

function pauseTimer() {
  running = false;
  startPauseBtn.textContent = "▶";
  startPauseBtn.setAttribute("aria-label", "Start");
  if (intervalId) {
    clearInterval(intervalId);
    intervalId = null;
  }
}

function resetTimer() {
  remaining = durationSeconds;
  pauseTimer();
  renderTimer();
}

function applyMinutes(minutes) {
  const m = clampInt(minutes, MIN_MINUTES, MAX_MINUTES);
  durationSeconds = m * 60;
  remaining = durationSeconds;
  if (minutesInputEl) minutesInputEl.value = String(m);
  saveMinutes(m);
  pauseTimer();
  renderTimer();
}

// Wire up buttons
if (startPauseBtn) {
  startPauseBtn.addEventListener("click", () => {
    running ? pauseTimer() : startTimer();
  });
}
if (resetBtn) {
  resetBtn.addEventListener("click", resetTimer);
}
if (setTimerBtn) {
  setTimerBtn.addEventListener("click", () => {
    applyMinutes(minutesInputEl?.value ?? DEFAULT_MINUTES);
  });
}
if (minutesInputEl) {
  minutesInputEl.addEventListener("keydown", (e) => {
    if (e.key === "Enter") {
      e.preventDefault();
      applyMinutes(minutesInputEl.value);
    }
  });
}

// ===== Initialize =====
document.addEventListener("DOMContentLoaded", () => {
  startLiveClock();
  if (minutesInputEl) {
    minutesInputEl.value = String(durationSeconds / 60);
  }
  renderTimer();

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