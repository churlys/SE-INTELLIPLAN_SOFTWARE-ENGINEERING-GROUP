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
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Dashboard — IntelliPlan (Clone)</title>

  <link rel="stylesheet" href="assets/styles-dashboard.css">
</head>
<body class="app-shell">

  
  <aside class="app-sidebar" aria-label="Primary navigation">
   

    <nav class="nav" aria-label="Main">
      
      <a class="nav-item " href="dashboard.php" title="Dashboard">
   
        <span class="nav-text">Dashboard</span>
      </a>
      <a class="nav-item" href="calendar.php" title="Calendar">
      
        <span class="nav-text">Calendar</span>
      </a>
      <a class="nav-item" href="#" title="Activities">
      
        <span class="nav-text">Activities</span>
      </a>
    </nav>
    

    <div class="sidebar-fills" aria-hidden="true">
      <div class="fill"></div>
      <div class="fill"></div>
      <div class="fill"></div>
    </div>
  </aside>

  <main class="app-main">
    <div class="container-inner">
      <header class="top-header" role="banner">
        <div class="brand-time">
          <img src="assets/logo.jpg" alt="IntelliPlan" class="brand-logo" onerror="this.style.display='none'">
          <span class="logo-text">IntelliPlan</span>
          <div class="time-wrap">
            <div class="clock" id="clock">3:45 PM</div>
            <div class="clock-sub" id="date-sub">Wednesday, December 3</div>
          </div>
        </div>

        <div class="header-controls">
          <?php if (function_exists('csrf_token')): ?>
          <form action="logout.php" method="post" class="inline">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
            <button class="btn-ghost" type="submit">Log out</button>
          </form>
          <?php else: ?>
          <a class="btn-ghost" href="index.php">Home</a>
          <?php endif; ?>

          <div class="user-chip">
            <img src="assets/avatar.png" alt="avatar" class="avatar" onerror="this.style.display='none'">
            <span><?php echo htmlspecialchars($user['email'] ?? $user['name'] ?? 'User'); ?></span>
          </div>
        </div>
      </header>
    </div>

    <div class="container-inner">
      <div class="calendar-view">
            <div style="display:flex; align-items:center;">
              <div class="day-label"><?php echo strtoupper($now->format('D')); ?></div>
              <div class="date-badge"><?php echo $now->format('j'); ?></div>
            </div>

            <div class="calendar-grid">
              <?php for ($h = 1; $h <= 23; $h++): ?>
                <div class="hour-row">
                  <div class="hour-label"><?php echo hourLabel($h); ?></div>
                  <div class="hour-cell"></div>
                </div>
              <?php endfor; ?>
            </div>
      </div>
    </div>
    </main>
</body>
</html>