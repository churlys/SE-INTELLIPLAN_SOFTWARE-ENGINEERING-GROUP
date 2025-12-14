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

// ===== Pomodoro Timer =====
const DEFAULT_SECONDS = 25 * 60; // 25 minutes
let remaining = DEFAULT_SECONDS;
let running = false;
let intervalId = null;

const labelEl = document.getElementById("timerLabel");
const ringEl = document.getElementById("timerRing");
const startPauseBtn = document.getElementById("btnStartPause");
const resetBtn = document.getElementById("btnReset");

function renderTimer() {
  const m = Math.floor(remaining / 60).toString().padStart(2, "0");
  const s = Math.floor(remaining % 60).toString().padStart(2, "0");
  if (labelEl) labelEl.textContent = `${m}:${s}`;

  const progress = 1 - remaining / DEFAULT_SECONDS; // 0..1
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
    // Optional: notify user
    // alert("Pomodoro finished!");
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
  remaining = DEFAULT_SECONDS;
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

// ===== Initialize =====
document.addEventListener("DOMContentLoaded", () => {
  startLiveClock();
  renderTimer();
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