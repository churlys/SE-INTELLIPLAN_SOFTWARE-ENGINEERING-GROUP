// Clock Timer page: Focus + Short Break Pomodoro settings and timer.

// ===== Live Clock (matches other pages) =====
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

// ===== Settings storage =====
const STORAGE_FOCUS_MINUTES = "intelliplan:focusMinutes";
const STORAGE_SHORT_BREAK_MINUTES = "intelliplan:shortBreakMinutes";
const STORAGE_ALERT_SOUND = "intelliplan:alertSoundEnabled";
const STORAGE_POMODORO_STATE = "intelliplan:pomodoroState";

const DEFAULT_FOCUS_MINUTES = 25;
const DEFAULT_SHORT_BREAK_MINUTES = 5;

function clampInt(value, min, max) {
  const n = Number.parseInt(String(value), 10);
  if (!Number.isFinite(n)) return min;
  return Math.max(min, Math.min(max, n));
}

function getBool(key, fallback) {
  try {
    const raw = localStorage.getItem(key);
    if (raw == null) return fallback;
    return raw === "1" || raw === "true";
  } catch {
    return fallback;
  }
}

function setBool(key, val) {
  try {
    localStorage.setItem(key, val ? "1" : "0");
  } catch {
    // ignore
  }
}

function getInt(key, fallback, min, max) {
  try {
    const raw = localStorage.getItem(key);
    if (raw == null) return fallback;
    return clampInt(raw, min, max);
  } catch {
    return fallback;
  }
}

function setInt(key, val) {
  try {
    localStorage.setItem(key, String(val));
  } catch {
    // ignore
  }
}

// ===== Timer =====
let intervalId = null;

function ensureTicker() {
  const { state } = ensureStateUpToDate(loadState(), Date.now());
  if (state.running) {
    if (!intervalId) intervalId = setInterval(tickTimer, 500);
    if (startPauseBtn) {
      startPauseBtn.textContent = "⏸";
      startPauseBtn.setAttribute("aria-label", "Pause");
    }
  } else {
    if (intervalId) {
      clearInterval(intervalId);
      intervalId = null;
    }
    if (startPauseBtn) {
      startPauseBtn.textContent = "▶";
      startPauseBtn.setAttribute("aria-label", "Start");
    }
  }
}

const focusSelectEl = document.getElementById("focusMinutes");
const shortBreakSelectEl = document.getElementById("shortBreakMinutes");
const alertSoundEl = document.getElementById("alertSound");
const saveBtn = document.getElementById("btnSaveSettings");
const cancelBtn = document.getElementById("btnCancelSettings");

const modeEl = document.getElementById("timerMode");
const labelEl = document.getElementById("timerLabel");
const ringEl = document.getElementById("timerRing");
const startPauseBtn = document.getElementById("btnStartPause");
const resetBtn = document.getElementById("btnReset");

let audioCtx = null;
async function unlockAudio() {
  try {
    if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    if (audioCtx.state === "suspended") await audioCtx.resume();
  } catch {
    // ignore
  }
}

function currentDurationSeconds() {
  const s = loadState();
  return s.mode === "focus" ? s.focusSeconds : s.breakSeconds;
}

function modeLabel() {
  const s = loadState();
  return s.mode === "focus" ? "Focus" : "Short Break";
}

function saveState(state) {
  try {
    localStorage.setItem(STORAGE_POMODORO_STATE, JSON.stringify(state));
  } catch {
    // ignore
  }
}

function loadState() {
  const focusSeconds = getInt(STORAGE_FOCUS_MINUTES, DEFAULT_FOCUS_MINUTES, 1, 180) * 60;
  const breakSeconds = getInt(STORAGE_SHORT_BREAK_MINUTES, DEFAULT_SHORT_BREAK_MINUTES, 1, 60) * 60;

  try {
    const raw = localStorage.getItem(STORAGE_POMODORO_STATE);
    if (!raw) {
      return {
        mode: "focus",
        running: false,
        remainingSeconds: focusSeconds,
        endAtMs: null,
        focusSeconds,
        breakSeconds,
      };
    }
    const parsed = JSON.parse(raw);
    const mode = parsed?.mode === "break" ? "break" : "focus";
    const running = !!parsed?.running;
    const remainingSeconds = clampInt(parsed?.remainingSeconds ?? focusSeconds, 0, 24 * 60 * 60);
    const endAtMs = Number.isFinite(parsed?.endAtMs) ? parsed.endAtMs : null;

    return {
      mode,
      running,
      remainingSeconds,
      endAtMs,
      focusSeconds,
      breakSeconds,
    };
  } catch {
    return {
      mode: "focus",
      running: false,
      remainingSeconds: focusSeconds,
      endAtMs: null,
      focusSeconds,
      breakSeconds,
    };
  }
}

function ensureStateUpToDate(state, nowMs) {
  let changed = false;

  if (state.running) {
    if (typeof state.endAtMs !== "number") {
      state.endAtMs = nowMs + state.remainingSeconds * 1000;
      changed = true;
    }
    state.remainingSeconds = Math.max(0, Math.ceil((state.endAtMs - nowMs) / 1000));
  }

  for (let i = 0; i < 3; i++) {
    if (state.running && state.remainingSeconds <= 0) {
      state.mode = state.mode === "focus" ? "break" : "focus";
      const duration = state.mode === "focus" ? state.focusSeconds : state.breakSeconds;
      state.remainingSeconds = duration;
      state.endAtMs = nowMs + duration * 1000;
      playBeep();
      changed = true;
      continue;
    }
    break;
  }

  return { state, changed };
}

function renderTimer(state) {
  if (modeEl) modeEl.textContent = state.mode === "focus" ? "Focus" : "Short Break";

  const m = Math.floor(state.remainingSeconds / 60).toString().padStart(2, "0");
  const s = Math.floor(state.remainingSeconds % 60).toString().padStart(2, "0");
  if (labelEl) labelEl.textContent = `${m}:${s}`;

  const durationSeconds = state.mode === "focus" ? state.focusSeconds : state.breakSeconds;
  const progress = durationSeconds > 0 ? (1 - state.remainingSeconds / durationSeconds) : 1;
  const percent = Math.max(0, Math.min(100, Math.round(progress * 100)));

  if (ringEl) {
    ringEl.style.background =
      `radial-gradient(closest-side, #fff 72%, transparent 73% 100%),` +
      `conic-gradient(var(--primary) 0% ${percent}%, var(--ring) ${percent}% 100%)`;
  }
}

function playBeep() {
  if (!getBool(STORAGE_ALERT_SOUND, false)) return;
  try {
    if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    // resume might be blocked if user never interacted; unlockAudio() is called on button clicks.
    audioCtx.resume?.().catch?.(() => {});

    const osc = audioCtx.createOscillator();
    const gain = audioCtx.createGain();
    osc.type = "sine";
    osc.frequency.value = 880;
    gain.gain.value = 0.06;
    osc.connect(gain);
    gain.connect(audioCtx.destination);
    osc.start();
    setTimeout(() => {
      osc.stop();
    }, 180);
  } catch {
    // ignore
  }
}

function tickTimer() {
  const now = Date.now();
  const { state, changed } = ensureStateUpToDate(loadState(), now);
  if (changed) saveState(state);
  renderTimer(state);
  ensureTicker();
}

function startTimer() {
  unlockAudio();
  const now = Date.now();
  const { state } = ensureStateUpToDate(loadState(), now);
  if (state.running) return;
  state.running = true;
  state.endAtMs = now + state.remainingSeconds * 1000;
  saveState(state);

  if (startPauseBtn) {
    startPauseBtn.textContent = "⏸";
    startPauseBtn.setAttribute("aria-label", "Pause");
  }
  ensureTicker();
}

function pauseTimer() {
  unlockAudio();
  const now = Date.now();
  const { state, changed } = ensureStateUpToDate(loadState(), now);
  state.running = false;
  state.endAtMs = null;
  if (changed) saveState(state);
  saveState(state);

  if (startPauseBtn) {
    startPauseBtn.textContent = "▶";
    startPauseBtn.setAttribute("aria-label", "Start");
  }
  if (intervalId) {
    clearInterval(intervalId);
    intervalId = null;
  }
  renderTimer(state);
  ensureTicker();
}

function resetTimer() {
  unlockAudio();
  if (intervalId) {
    clearInterval(intervalId);
    intervalId = null;
  }

  const focusSeconds = getInt(STORAGE_FOCUS_MINUTES, DEFAULT_FOCUS_MINUTES, 1, 180) * 60;
  const breakSeconds = getInt(STORAGE_SHORT_BREAK_MINUTES, DEFAULT_SHORT_BREAK_MINUTES, 1, 60) * 60;
  const state = {
    mode: "focus",
    running: false,
    remainingSeconds: focusSeconds,
    endAtMs: null,
    focusSeconds,
    breakSeconds,
  };
  saveState(state);

  if (startPauseBtn) {
    startPauseBtn.textContent = "▶";
    startPauseBtn.setAttribute("aria-label", "Start");
  }
  renderTimer(state);
  ensureTicker();
}

function loadSettingsIntoUI() {
  const focusMin = getInt(STORAGE_FOCUS_MINUTES, DEFAULT_FOCUS_MINUTES, 1, 180);
  const breakMin = getInt(STORAGE_SHORT_BREAK_MINUTES, DEFAULT_SHORT_BREAK_MINUTES, 1, 60);

  if (focusSelectEl) focusSelectEl.value = String(focusMin);
  if (shortBreakSelectEl) shortBreakSelectEl.value = String(breakMin);
  if (alertSoundEl) alertSoundEl.checked = getBool(STORAGE_ALERT_SOUND, false);

  // Keep the running timer synced; just refresh UI.
}

function saveSettingsFromUI() {
  const focusMin = clampInt(focusSelectEl?.value ?? DEFAULT_FOCUS_MINUTES, 1, 180);
  const breakMin = clampInt(shortBreakSelectEl?.value ?? DEFAULT_SHORT_BREAK_MINUTES, 1, 60);

  setInt(STORAGE_FOCUS_MINUTES, focusMin);
  setInt(STORAGE_SHORT_BREAK_MINUTES, breakMin);
  setBool(STORAGE_ALERT_SOUND, !!alertSoundEl?.checked);

  // Update shared state durations; if not running, reset to apply immediately.
  const now = Date.now();
  const { state } = ensureStateUpToDate(loadState(), now);
  state.focusSeconds = focusMin * 60;
  state.breakSeconds = breakMin * 60;
  if (!state.running) {
    state.mode = "focus";
    state.remainingSeconds = state.focusSeconds;
    state.endAtMs = null;
  }
  saveState(state);
  tickTimer();
}

// ===== Wire up =====
if (startPauseBtn) {
  startPauseBtn.addEventListener("click", () => {
    unlockAudio();
    const { state } = ensureStateUpToDate(loadState(), Date.now());
    state.running ? pauseTimer() : startTimer();
  });
}
if (resetBtn) resetBtn.addEventListener("click", resetTimer);
if (saveBtn) saveBtn.addEventListener("click", saveSettingsFromUI);
if (cancelBtn) cancelBtn.addEventListener("click", () => {
  loadSettingsIntoUI();
  resetTimer();
});

document.addEventListener("DOMContentLoaded", () => {
  startLiveClock();
  loadSettingsIntoUI();
  tickTimer();
  ensureTicker();

  window.addEventListener("storage", (e) => {
    if (!e.key) return;
    if (
      e.key === STORAGE_POMODORO_STATE ||
      e.key === STORAGE_FOCUS_MINUTES ||
      e.key === STORAGE_SHORT_BREAK_MINUTES ||
      e.key === STORAGE_ALERT_SOUND
    ) {
      tickTimer();
      ensureTicker();
    }
  });

  window.addEventListener("focus", () => {
    tickTimer();
    ensureTicker();
  });

  document.addEventListener("visibilitychange", () => {
    if (!document.hidden) {
      tickTimer();
      ensureTicker();
    }
  });
});
