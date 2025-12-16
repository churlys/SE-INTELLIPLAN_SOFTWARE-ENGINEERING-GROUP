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

date_default_timezone_set('UTC');  
$now = new DateTime('now');
function hourLabel(int $hour): string {
  return date('g A', mktime($hour, 0));
}

// Detect current page
$currentPage = basename($_SERVER['PHP_SELF']);
$activitiesPages = ['tasks.php', 'exam.php', 'classes.php'];
$isActivitiesPage = in_array($currentPage, $activitiesPages, true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>IntelliPlan Dashboard</title>
  <link rel="stylesheet" href="assets/styles-dashboard.css" />
  
</head>
<body>
  <aside class="sidebar">
    <div class="brand">
      <div class="brand-logo">
        <img src="assets/logo.jpg" alt="IntelliPlan Logo" style="width:100%;height:100%;object-fit:contain;" onerror="this.textContent='🎓'">
      </div>
      <div class="brand-name">IntelliPlan</div>
    </div>

    <nav class="nav">
      <a class="nav-item <?php echo ($currentPage == 'dashboard.php') ? 'active' : ''; ?>" href="dashboard.php">
        <span class="nav-icon">🏠</span>
        <span class="nav-label">Dashboard</span>
      </a>
      <a class="nav-item <?php echo ($currentPage == 'calendar.php') ? 'active' : ''; ?>" href="calendar.php">
        <span class="nav-icon">🗓️</span>
        <span class="nav-label">Calendar</span>
      </a>
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
      <a class="nav-item" href="#" onclick="event.preventDefault(); document.getElementById('logoutForm').submit();">
        <span class="nav-icon">🚪</span>
        <span class="nav-label">Log Out</span>
      </a>
    </nav>
  </aside>

  <main class="main">
    <header class="topbar">
      <div class="date-time">
        <span class="time" id="liveTime">3:45 PM</span>
        <span class="date" id="liveDate">Wednesday, December 3</span>
      </div>
      <div class="top-actions">
        <button class="icon-btn" aria-label="Settings">⚙️</button>
        <button class="icon-btn" aria-label="Profile">👤</button>
        <div class="user-chip"><?php echo htmlspecialchars($user['email']); ?></div>
      </div>
    </header>

    <section class="content">
      <div class="grid">
        <!-- Focus Card and Stats -->
        <div class="focus-and-stats">
          <div class="focus-card">
            <div class="focus-card-inner">
              <div class="focus-info">
                <p class="muted"><span id="dueTodayCount">0</span> Tasks due today.</p>
                <h2 id="timeGreeting">GOOD AFTERNOON.</h2>
              </div>
              <div class="focus-timer">
                <div class="timer-panel" aria-label="Focus timer">
                  <div class="timer-ring" id="timerRing" aria-label="Pomodoro progress">
                    <div class="timer-circle">
                      <div class="timer-subtitle" id="timerMode">Focus</div>
                      <div class="timer-label" id="timerLabel">25:00</div>
                    </div>
                  </div>

                  <div class="timer-actions" aria-label="Timer controls">
                    <a class="circle-btn circle-btn-soft" href="clocktimer.php" aria-label="Timer settings">⚙️</a>
                    <button class="circle-btn circle-btn-primary" id="btnStartPause" aria-label="Start">▶</button>
                    <button class="circle-btn circle-btn-soft" id="btnReset" aria-label="Reset">↺</button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="stats-row">
            <div class="stat-card">
              <div class="stat-head">
                <span class="stat-icon">📌</span>
                <span class="stat-title">Pending Tasks</span>
              </div>
              <div class="stat-value" id="statPending">0</div>
              <div class="stat-sub">Last 7 days</div>
            </div>
            <div class="stat-card">
              <div class="stat-head">
                <span class="stat-icon">⚠️</span>
                <span class="stat-title">Overdue Tasks</span>
              </div>
              <div class="stat-value" id="statOverdue">0</div>
              <div class="stat-sub">Last 7 days</div>
            </div>
            <div class="stat-card">
              <div class="stat-head">
                <span class="stat-icon">✅</span>
                <span class="stat-title">Tasks Completed</span>
              </div>
              <div class="stat-value" id="statCompleted">0</div>
              <div class="stat-sub">Last 7 days</div>
            </div>
            <div class="stat-card">
              <div class="stat-head">
                <span class="stat-icon">🔥</span>
                <span class="stat-title">Your Streak</span>
              </div>
              <div class="stat-value" id="statStreak">0</div>
              <div class="stat-sub">Last 7 days</div>
            </div>
          </div>
        </div>

        <!-- Calendar -->
        <aside class="calendar-card">
          <div class="calendar-header">
            <span>Calendar</span>
            <div class="select-wrap">
              <button class="pill" type="button" aria-label="Calendar view">Day <span class="pill-caret">▾</span></button>
            </div>
          </div>

          <div class="calendar-week">
            <button class="weekday" type="button">
              <div class="wd-name">Mon</div>
              <div class="wd-num">1</div>
            </button>
            <button class="weekday" type="button">
              <div class="wd-name">Tue</div>
              <div class="wd-num">2</div>
            </button>
            <button class="weekday active" type="button">
              <div class="wd-name">Wed</div>
              <div class="wd-num">3</div>
            </button>
            <button class="weekday" type="button">
              <div class="wd-name">Thu</div>
              <div class="wd-num">4</div>
            </button>
            <button class="weekday" type="button">
              <div class="wd-name">Fri</div>
              <div class="wd-num">5</div>
            </button>
            <button class="weekday" type="button">
              <div class="wd-name">Sat</div>
              <div class="wd-num">6</div>
            </button>
            <button class="weekday" type="button">
              <div class="wd-name">Sun</div>
              <div class="wd-num">7</div>
            </button>
          </div>

          <div class="calendar-month" id="dashCalendarMonth" hidden>
            <!-- Month grid rendered via JS -->
          </div>

          <div class="calendar-dayline" aria-label="Selected day">
            <div class="daychip" aria-label="Selected date">
              <div class="daychip-name" id="dashSelectedDayName">MON</div>
              <div class="daychip-num" id="dashSelectedDayNum">15</div>
            </div>
            <div aria-hidden="true"></div>
          </div>

          <div class="calendar-hours" id="dashDaySchedule" aria-label="Day schedule"></div>
        </aside>
      </div>

      <!-- Bottom Row: Classes, Tasks, Exams -->
      <div class="bottom-row">
        <div class="panel">
          <div class="panel-head">
              <a href="classes.php" class="panel-title">Classes</a>
              <div class="panel-filters">
                <div class="select">Select Subject</div>
                <div class="select">Current</div>
              </div>
            </div>
          <div class="panel-body muted">No classes to display.</div>
        </div>

        <div class="panel">
          <div class="panel-head">
            <span>Tasks (<span id="dashTasksCount">0</span>)</span>
            <div class="panel-filters">
              <select class="select" id="dashTasksSubject" aria-label="Select Subject">
                <option value="">Select Subject</option>
              </select>
              <select class="select" id="dashTasksView" aria-label="Select View">
                <option value="current" selected>Current</option>
                <option value="past">Past</option>
                <option value="overdue">Overdue</option>
              </select>
            </div>
          </div>
          <div class="panel-body" id="dashboardTasksList">Loading tasks…</div>
        </div>

        <div class="panel">
          <div class="panel-head">
            <a href="exam.php" class="panel-title">Exams</a>
            <div class="panel-filters">
              <div class="select">Select Subject</div>
              <div class="select">Current</div>
            </div>
          </div>
          <div class="panel-body muted">No exams to display.</div>
        </div>
      </div>
    </section>
  </main>

  <form id="logoutForm" method="POST" action="logout.php" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
  </form>
  <script src="assets/dashboard.js"></script>
</body>
</html>