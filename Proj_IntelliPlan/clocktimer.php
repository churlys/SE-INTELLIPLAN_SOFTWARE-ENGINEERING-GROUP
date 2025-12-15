<?php
if (session_status() === PHP_SESSION_NONE) {
    $sessionDir = sys_get_temp_dir();
    if (!is_dir($sessionDir)) {
        @mkdir($sessionDir, 0700, true);
    }
    session_save_path($sessionDir);
    session_start();
}
if (file_exists(__DIR__ . '/lib/auth.php')) {
  require_once __DIR__ . '/lib/auth.php';
  if (function_exists('require_auth')) require_auth();
  $user = function_exists('current_user') ? current_user() : null;
} else {
  $user = ['name' => 'Demo User', 'email' => 'user@example.com'];
}

$currentPage = basename($_SERVER['PHP_SELF']);
$activitiesPages = ['tasks.php', 'exam.php', 'classes.php'];
$isActivitiesPage = in_array($currentPage, $activitiesPages, true);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Clock Timer — IntelliPlan</title>
  <link rel="stylesheet" href="assets/styles-dashboard.css">
  <link rel="stylesheet" href="assets/styles-clocktimer.css">
</head>
<body>
  <aside class="sidebar">
    <div class="brand">
      <div class="brand-logo"><img src="assets/logo.jpg" alt="Logo" style="width:100%;height:100%;object-fit:contain;"></div>
      <div class="brand-name">IntelliPlan</div>
    </div>
    <nav class="nav">
      <a class="nav-item <?php echo ($currentPage === 'dashboard.php') ? 'active' : ''; ?>" href="dashboard.php"><span class="nav-icon">🏠</span><span class="nav-label">Dashboard</span></a>
      <a class="nav-item <?php echo ($currentPage === 'calendar.php') ? 'active' : ''; ?>" href="calendar.php"><span class="nav-icon">🗓️</span><span class="nav-label">Calendar</span></a>
      <details class="nav-activities" <?php echo $isActivitiesPage ? 'open' : ''; ?>>
        <summary class="nav-item <?php echo $isActivitiesPage ? 'active' : ''; ?>" aria-label="Activities menu">
          <span class="nav-icon">🧩</span>
          <span class="nav-label">Activities</span>
          <span class="dropdown-arrow">▼</span>
        </summary>
        <div class="subnav">
          <a href="tasks.php" class="subnav-item <?php echo ($currentPage === 'tasks.php') ? 'active' : ''; ?>">📋 Tasks</a>
          <a href="classes.php" class="subnav-item <?php echo ($currentPage === 'classes.php') ? 'active' : ''; ?>">🎓 Classes</a>
          <a href="exam.php" class="subnav-item <?php echo ($currentPage === 'exam.php') ? 'active' : ''; ?>">📝 Exams</a>
        </div>
      </details>
      <div class="nav-separator"></div>
      <a class="nav-item" href="#" onclick="event.preventDefault(); document.getElementById('logoutForm').submit();"><span class="nav-icon">🚪</span><span class="nav-label">Log Out</span></a>
    </nav>
  </aside>

  <main class="main">
    <header class="topbar">
      <div class="date-time"><span class="time" id="liveTime"></span><span class="date" id="liveDate"></span></div>
      <div class="top-actions">
        <button class="icon-btn" aria-label="Settings">⚙️</button>
        <div class="user-chip"><?php echo htmlspecialchars($user['email']); ?></div>
      </div>
    </header>

    <section class="content">
      <div class="ct-shell">
        <h2 class="ct-title">Focus Time Timer</h2>

        <div class="ct-panel" id="pomodoroSettings">
          <div class="ct-grid">
            <div class="ct-field">
              <label class="ct-label" for="focusMinutes">Focus Time</label>
              <select class="ct-select" id="focusMinutes" aria-label="Focus Time">
                <option value="15">15 Minutes</option>
                <option value="20">20 Minutes</option>
                <option value="25" selected>25 Minutes</option>
                <option value="30">30 Minutes</option>
                <option value="45">45 Minutes</option>
                <option value="60">60 Minutes</option>
              </select>
            </div>

            <div class="ct-field">
              <label class="ct-label" for="shortBreakMinutes">Short Break</label>
              <select class="ct-select" id="shortBreakMinutes" aria-label="Short Break">
                <option value="3">3 Minutes</option>
                <option value="5" selected>5 Minutes</option>
                <option value="10">10 Minutes</option>
                <option value="15">15 Minutes</option>
              </select>
            </div>
          </div>

          <div class="ct-toggle-row">
            <div class="ct-toggle-label">Alert Sound</div>
            <label class="ct-switch">
              <input id="alertSound" type="checkbox" />
              <span class="ct-slider" aria-hidden="true"></span>
            </label>
          </div>

          <div class="ct-actions">
            <button type="button" class="ct-btn ct-btn-primary" id="btnSaveSettings">Save</button>
            <button type="button" class="ct-btn ct-btn-outline" id="btnCancelSettings">Cancel</button>
          </div>
        </div>

        <div class="ct-timer-wrap" aria-label="Timer">
          <div class="focus-timer">
            <div class="timer-panel" aria-label="Focus timer">
              <div class="timer-ring" id="timerRing" aria-label="Pomodoro progress">
                <div class="timer-circle">
                  <div class="timer-subtitle" id="timerMode">Focus</div>
                  <div class="timer-label" id="timerLabel">25:00</div>
                </div>
              </div>

              <div class="timer-actions" aria-label="Timer controls">
                
                <button class="circle-btn circle-btn-primary" id="btnStartPause" aria-label="Start">▶</button>
                <button class="circle-btn circle-btn-soft" id="btnReset" aria-label="Reset">↺</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <form id="logoutForm" method="POST" action="logout.php" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
  </form>

  <script src="assets/clocktimer.js"></script>
</body>
</html>
