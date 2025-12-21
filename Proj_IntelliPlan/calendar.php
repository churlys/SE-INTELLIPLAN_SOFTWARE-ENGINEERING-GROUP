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
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Calendar — IntelliPlan</title>

  <link rel="stylesheet" href="assets/styles-dashboard.css">
  <link rel="stylesheet" href="assets/calendarstyles.css">
 
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
      <a class="nav-item <?php echo ($currentPage === 'dashboard.php') ? 'active' : ''; ?>" href="dashboard.php">
        <span class="nav-icon"><img src="assets/icon-dashboard.svg" alt="" aria-hidden="true" width="18" height="18"></span>
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
        <span class="nav-icon"><img src="assets/logOUT.png" alt="" aria-hidden="true"></span>
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
      <div class="calendar-shell" aria-label="Calendar">
        <div class="cal-header-row">
          <div>
            <div class="cal-title">Calendar</div>
            <div class="cal-range" id="calRange">&nbsp;</div>
          </div>

          <div class="cal-controls" aria-label="Calendar controls">
            <button type="button" class="cal-btn" id="prevBtn" aria-label="Previous">←</button>
            <button type="button" class="cal-btn" id="todayBtn">Today</button>
            <button type="button" class="cal-btn" id="nextBtn" aria-label="Next">→</button>

            <div class="cal-mode" role="tablist" aria-label="Calendar mode">
              <button type="button" class="mode-btn active" id="modeWeek" role="tab" aria-selected="true">Week</button>
              <button type="button" class="mode-btn" id="modeMonth" role="tab" aria-selected="false">Month</button>
            </div>
          </div>
        </div>

        <div id="weekView" class="week-view"></div>
        <div id="monthView" class="month-view" hidden></div>
      </div>
    </section>
  </main>

  <form id="logoutForm" method="POST" action="logout.php" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
  </form>
  <script src="assets/dashboard.js"></script>
  <script src="assets/calendar.js"></script>
  
 </body>
 </html>