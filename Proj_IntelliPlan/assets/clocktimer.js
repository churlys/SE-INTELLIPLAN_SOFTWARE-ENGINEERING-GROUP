// Clock + Pomodoro settings page logic (shared timer state with dashboard).

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

// ===== Shared Pomodoro State =====
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
    focusMinutes,
    breakMinutes,
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
      loadSettingsIntoForm();
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

// ===== Settings Form =====
function loadSettingsIntoForm() {
  const focusEl = document.getElementById('focusMinutes');
  const breakEl = document.getElementById('shortBreakMinutes');
  const alertEl = document.getElementById('alertSound');

  const settings = loadPomodoroSettings();
  if (focusEl) focusEl.value = String(settings.focusMinutes);
  if (breakEl) breakEl.value = String(settings.breakMinutes);
  if (alertEl) alertEl.checked = !!settings.alertSound;
}

function saveSettingsFromForm() {
  const focusEl = document.getElementById('focusMinutes');
  const breakEl = document.getElementById('shortBreakMinutes');
  const alertEl = document.getElementById('alertSound');

  const focusMinutes = parseInt(focusEl?.value || '25', 10) || 25;
  const breakMinutes = parseInt(breakEl?.value || '5', 10) || 5;
  const alertSound = !!alertEl?.checked;

  localStorage.setItem(POMODORO_SETTINGS.focusMinutes, String(focusMinutes));
  localStorage.setItem(POMODORO_SETTINGS.breakMinutes, String(breakMinutes));
  localStorage.setItem(POMODORO_SETTINGS.alertSound, alertSound ? '1' : '0');

  // Update state durations. If timer is not running, snap remaining to the current mode duration.
  let state = loadPomodoroState();
  const settings = loadPomodoroSettings();
  state = { ...state, focusSeconds: settings.focusSeconds, breakSeconds: settings.breakSeconds };
  if (!state.running) {
    state.remainingSeconds = state.mode === 'focus' ? state.focusSeconds : state.breakSeconds;
    state.endAtMs = null;
  }
  savePomodoroState(state);
}

document.addEventListener('DOMContentLoaded', () => {
  startLiveClock();
  loadSettingsIntoForm();
  initPomodoro();

  const btnSave = document.getElementById('btnSave');
  const btnCancel = document.getElementById('btnCancel');

  btnSave?.addEventListener('click', () => {
    saveSettingsFromForm();
    renderPomodoro();
    ensurePomodoroTicker();
  });

  btnCancel?.addEventListener('click', () => {
    loadSettingsIntoForm();
  });
});
