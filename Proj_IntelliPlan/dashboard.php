<?php
session_start();
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
      <a class="nav-item <?php echo ($currentPage == 'activities.php') ? 'active' : ''; ?>" href="activities.php">
        <span class="nav-icon">🧩</span>
        <span class="nav-label">Activities</span>
      </a>
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
                <p class="muted">0 task due today.</p>
                <h2>GOOD AFTERNOON.</h2>
              </div>
              <div class="focus-timer">
                <div class="timer-ring" id="timerRing" aria-label="Pomodoro progress">
                  <div class="timer-circle">
                    <div class="timer-label" id="timerLabel">25:00</div>
                  </div>
                </div>
                <div class="timer-controls">
                  <button class="circle-btn" id="btnStartPause" aria-label="Start">▶</button>
                  <button class="circle-btn" id="btnReset" aria-label="Reset">↺</button>
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
              <button class="pill">Day</button>
            </div>
          </div>

          <div class="calendar-week">
            <div class="weekday">
              <div class="wd-name">Mon</div>
              <button class="wd-day">1</button>
            </div>
            <div class="weekday">
              <div class="wd-name">Tue</div>
              <button class="wd-day">2</button>
            </div>
            <div class="weekday active">
              <div class="wd-name">Wed</div>
              <button class="wd-day">3</button>
            </div>
            <div class="weekday">
              <div class="wd-name">Thu</div>
              <button class="wd-day">4</button>
            </div>
            <div class="weekday">
              <div class="wd-name">Fri</div>
              <button class="wd-day">5</button>
            </div>
            <div class="weekday">
              <div class="wd-name">Sat</div>
              <button class="wd-day">6</button>
            </div>
            <div class="weekday">
              <div class="wd-name">Sun</div>
              <button class="wd-day">7</button>
            </div>
          </div>

          <div class="calendar-hours">
            <div class="hour-row">
              <div class="hour">1 AM</div>
            </div>
            <div class="hour-row">
              <div class="hour">2 AM</div>
            </div>
            <div class="hour-row">
              <div class="hour">3 AM</div>
            </div>
            <div class="hour-row">
              <div class="hour">4 AM</div>
            </div>
            <div class="hour-row">
              <div class="hour">5 AM</div>
            </div>
          </div>
        </aside>
      </div>

      <!-- Bottom Row: Classes, Tasks, Exams -->
      <div class="bottom-row">
        <div class="panel">
          <div class="panel-head">
            <span>Classes</span>
            <div class="panel-filters">
              <div class="select">Select Subject</div>
              <div class="select">Current</div>
            </div>
          </div>
          <div class="panel-body muted">No classes to display.</div>
        </div>

        <div class="panel">
          <div class="panel-head">
            <span>Tasks</span>
            <div class="panel-filters">
              <div class="select">Select Subject</div>
              <div class="select">Current</div>
            </div>
          </div>
          <div class="panel-body muted">No tasks to display.</div>
        </div>

        <div class="panel">
          <div class="panel-head">
            <span>Exams</span>
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