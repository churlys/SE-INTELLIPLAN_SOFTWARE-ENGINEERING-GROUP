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
    <div class="container-inner">
      <div class="calendar-shell">
        <div class="cal-header-row">
          <div>
            <div class="cal-title">Calendar</div>
            <div class="cal-range" id="calRange">This week</div>
          </div>
          <div class="cal-controls">
            <button class="cal-btn" id="prevBtn" aria-label="Previous">‹</button>
            <button class="cal-btn" id="todayBtn">Today</button>
            <button class="cal-btn" id="nextBtn" aria-label="Next">›</button>
            <div class="cal-mode">
              <button class="mode-btn active" data-mode="week" id="modeWeek">Week</button>
              <button class="mode-btn" data-mode="month" id="modeMonth">Month</button>
            </div>
          </div>
        </div>

        <div id="weekView" class="week-view"></div>
        <div id="monthView" class="month-view" hidden></div>
      </div>
    </div>
    </main>
    <form id="logoutForm" method="POST" action="logout.php" style="display:none;">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
    </form>
    <script src="assets/dashboard.js"></script>
    <script src="assets/calendar.js"></script>
    
  </body>
  </html>