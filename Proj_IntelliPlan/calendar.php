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
      <div class="nav-item dropdown-wrapper">
        <button class="nav-item dropdown-btn" aria-label="Activities menu" aria-expanded="false">
          <span class="nav-icon">🧩</span>
          <span class="nav-label">Activities</span>
          <span class="dropdown-arrow">▼</span>
        </button>
        <div class="dropdown-menu" hidden>
          <a href="tasks.php" class="dropdown-item">📋 Tasks</a>
          <a href="exam.php" class="dropdown-item">📝 Exams</a>
          <a href="classes.php" class="dropdown-item">🎓 Classes</a>
        </div>
      </div>
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
    <script>
      (function(){
        const dropdownBtns = document.querySelectorAll('.dropdown-btn');
        
        dropdownBtns.forEach(btn => {
          btn.addEventListener('click', function(e){
            e.preventDefault();
            const wrapper = this.closest('.dropdown-wrapper');
            const menu = wrapper.querySelector('.dropdown-menu');
            const isHidden = menu.hasAttribute('hidden');
            
            // Close all other dropdowns
            document.querySelectorAll('.dropdown-wrapper .dropdown-btn').forEach(otherBtn => {
              if (otherBtn !== btn) {
                otherBtn.classList.remove('active');
                otherBtn.setAttribute('aria-expanded', 'false');
                otherBtn.closest('.dropdown-wrapper').querySelector('.dropdown-menu').setAttribute('hidden', '');
              }
            });
            
            // Toggle current dropdown
            if (isHidden) {
              menu.removeAttribute('hidden');
              btn.classList.add('active');
              btn.setAttribute('aria-expanded', 'true');
            } else {
              menu.setAttribute('hidden', '');
              btn.classList.remove('active');
              btn.setAttribute('aria-expanded', 'false');
            }
          });
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e){
          if (!e.target.closest('.dropdown-wrapper')) {
            dropdownBtns.forEach(btn => {
              btn.classList.remove('active');
              btn.setAttribute('aria-expanded', 'false');
              btn.closest('.dropdown-wrapper').querySelector('.dropdown-menu').setAttribute('hidden', '');
            });
          }
        });
      })();
    </script>
    
  </body>
  </html>