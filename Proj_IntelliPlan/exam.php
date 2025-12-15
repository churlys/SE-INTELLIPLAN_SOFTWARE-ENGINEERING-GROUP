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
  <title>Exams — IntelliPlan</title>
  <link rel="stylesheet" href="assets/styles-dashboard.css">
</head>
<body>
  <aside class="sidebar">
    <div class="brand">
      <div class="brand-logo"><img src="assets/logo.jpg" alt="Logo" style="width:100%;height:100%;object-fit:contain;"></div>
      <div class="brand-name">IntelliPlan</div>
    </div>
    <nav class="nav">
      <a class="nav-item" href="dashboard.php"><span class="nav-icon">🏠</span><span class="nav-label">Dashboard</span></a>
      <a class="nav-item" href="calendar.php"><span class="nav-icon">🗓️</span><span class="nav-label">Calendar</span></a>
      <a class="nav-item active" href="exam.php"><span class="nav-icon">📝</span><span class="nav-label">Exams</span></a>
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
      <h2>Exams</h2>
      <div class="panel">
        <div id="examsList" class="panel-body muted">No exams to display.</div>
      </div>
    </section>
  </main>

  <form id="logoutForm" method="POST" action="logout.php" style="display:none;">
    <?php if (function_exists('csrf_token')): ?>
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
    <?php endif; ?>
  </form>

  <script>
    // Load current date/time
    document.getElementById('liveTime').textContent = new Date().toLocaleTimeString([], {hour: 'numeric', minute: '2-digit'});
    document.getElementById('liveDate').textContent = new Date().toLocaleDateString(undefined, {weekday: 'long', month: 'long', day: 'numeric'});

    // Dropdown toggle functionality
    (function(){
      const dropdownBtns = document.querySelectorAll('.dropdown-btn');
      dropdownBtns.forEach(btn => {
        btn.addEventListener('click', function(e){
          e.preventDefault();
          const wrapper = this.closest('.dropdown-wrapper');
          const menu = wrapper.querySelector('.dropdown-menu');
          const isHidden = menu.hasAttribute('hidden');
          document.querySelectorAll('.dropdown-wrapper .dropdown-btn').forEach(otherBtn => {
            if (otherBtn !== btn) {
              otherBtn.classList.remove('active');
              otherBtn.setAttribute('aria-expanded', 'false');
              otherBtn.closest('.dropdown-wrapper').querySelector('.dropdown-menu').setAttribute('hidden', '');
            }
          });
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
