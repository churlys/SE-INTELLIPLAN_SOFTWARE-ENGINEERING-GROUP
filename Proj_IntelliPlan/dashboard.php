<?php
// dashboard_clone.php
// Clone of the IntelliPlan dashboard with clean placeholders.
// Goal: drop-in page where you only replace images and texts as needed.
// Integrates with your existing auth if lib/auth.php exists; otherwise shows demo user.

session_start();
if (file_exists(__DIR__ . '/lib/auth.php')) {
    require_once __DIR__ . '/lib/auth.php';
    if (function_exists('require_auth')) {
        require_auth();
    }
    $user = function_exists('current_user') ? current_user() : null;
} else {
    $user = ['name' => 'Demo User', 'email' => 'user@example.com'];
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Dashboard — IntelliPlan (Clone)</title>

  <!-- Replace these images with your assets when ready -->
  <!-- assets/logo-large.png: Sidebar brand -->
  <!-- assets/logo.png: Header brand -->
  <!-- assets/avatar.png: Header user avatar -->
  <!-- assets/gradient-hero.png: Hero gradient image -->
  <link rel="stylesheet" href="assets/styles-dashboard.css">
</head>
<body class="app-shell">

  <!-- Sidebar (left) -->
  <aside class="app-sidebar" aria-label="Primary navigation">
    <div class="logo">
      <a href="index.php"><img src="assets/logo-large.png" alt="IntelliPlan brand" onerror="this.style.display='none'"></a>
    </div>

    <nav class="nav" aria-label="Main">
      <a class="nav-item active" href="#" title="Dashboard">
        <span class="ico">🏠</span>
        <span class="nav-text">Dashboard</span>
      </a>
      <a class="nav-item" href="calendar.php" title="Calendar">
        <span class="ico">📅</span>
        <span class="nav-text">Calendar</span>
      </a>
      <a class="nav-item" href="#" title="Activities">
        <span class="ico">📚</span>
        <span class="nav-text">Activities</span>
      </a>
      <a class="nav-item" href="#" title="Tasks">
        <span class="ico">📝</span>
        <span class="nav-text">Tasks</span>
      </a>
      <a class="nav-item" href="#" title="Classes">
        <span class="ico">🏫</span>
        <span class="nav-text">Classes</span>
      </a>
      <a class="nav-item" href="#" title="Exam">
        <span class="ico">🧪</span>
        <span class="nav-text">Exam</span>
      </a>
    </nav>

    <div class="sidebar-fills" aria-hidden="true">
      <div class="fill"></div>
      <div class="fill"></div>
      <div class="fill"></div>
    </div>
  </aside>

  <!-- Main -->
  <main class="app-main">
    <div class="container-inner">
      <!-- Top header -->
      <header class="top-header" role="banner">
        <div class="brand-time">
          <img src="assets/logo.png" alt="IntelliPlan" class="brand-logo" onerror="this.style.display='none'">
          <div class="time-wrap">
            <div class="clock" id="clock">3:45 PM</div>
            <div class="clock-sub" id="date-sub">Wednesday, December 3</div>
          </div>
        </div>

        <div class="header-controls">
          <button class="icon" title="Settings" aria-label="Settings">⚙️</button>

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
      <!-- Main grid -->
      <div class="dashboard-wrap">
        <!-- Left column -->
        <section class="left-column">
          <!-- Hero block -->
          <div class="hero-block">
            <div id="hero-left" class="hero-left" style="background-image: url('assets/gradient.png');">
              <div class="kicker">0 task due today.</div>
              <h2 class="hero-title">GOOD AFTERNOON.</h2>
              <div class="hero-sub">Focus on your top tasks and schedule. Keep momentum going — you’ve got this.</div>
            </div>

            <!-- Stopwatch widget (right of hero on large screens) -->
            <aside class="hero-right" aria-label="Stopwatch">
              <div class="timer-card">
                <div class="timer-label">Stop Watch</div>
                <div class="timer-big" id="timer">25:00</div>
                <div class="timer-cta">
                  <button class="btn" aria-label="Start">▶</button>
                  <button class="btn-ghost" aria-label="Pause">⏸</button>
                </div>
              </div>
            </aside>
          </div>

          <!-- Stats -->
          <div class="stats-row">
            <div class="stat-card">
              <div class="stat-top"><span class="emoji">👀</span><span class="label">Pending Tasks</span></div>
              <div class="value">0</div>
              <div class="muted">Last 7 days</div>
            </div>
            <div class="stat-card">
              <div class="stat-top"><span class="emoji">⚠️</span><span class="label">Overdue Tasks</span></div>
              <div class="value">0</div>
              <div class="muted">Last 7 days</div>
            </div>
            <div class="stat-card">
              <div class="stat-top"><span class="emoji">👍</span><span class="label">Tasks Completed</span></div>
              <div class="value">0</div>
              <div class="muted">Last 7 days</div>
            </div>
            <div class="stat-card">
              <div class="stat-top"><span class="emoji">🔥</span><span class="label">Your Streak</span></div>
              <div class="value">0</div>
              <div class="muted">Last 7 days</div>
            </div>
          </div>

          <!-- Filters -->
          <div class="filters-row">
            <div class="filter-card">
              <div class="filter-title">Classes</div>
              <div class="filter-controls">
                <select aria-label="Select subject"><option>Select Subject</option></select>
                <select aria-label="Range"><option>Current</option></select>
              </div>
            </div>

            <div class="filter-card">
              <div class="filter-title">Tasks</div>
              <div class="filter-controls">
                <select><option>Select Subject</option></select>
                <select><option>Current</option></select>
              </div>
            </div>

            <div class="filter-card">
              <div class="filter-title">Exams</div>
              <div class="filter-controls">
                <select><option>Select Subject</option></select>
                <select><option>Current</option></select>
              </div>
            </div>
          </div>
        </section>

        <!-- Right column -->
        <aside class="right-column">
          <div class="small-calendar">
            <div class="small-cal-header">
              <strong>Calendar</strong>
              <select class="input"><option>Day</option><option>Week</option><option>Month</option></select>
            </div>

            <div class="day-chips">
              <div class="day-chip">Mon 1</div>
              <div class="day-chip">Tue 2</div>
              <div class="day-chip active">Wed 3</div>
              <div class="day-chip">Thu 4</div>
              <div class="day-chip">Fri 5</div>
              <div class="day-chip">Sat 6</div>
              <div class="day-chip">Sun 7</div>
            </div>

            <!-- Visual hour grid (placeholder) -->
            <div class="hour-grid">
              <div class="hour">1 AM</div>
              <div class="hour">2 AM</div>
              <div class="hour">3 AM</div>
              <div class="hour">4 AM</div>
              <div class="hour">5 AM</div>
              <div class="hour">6 AM</div>
              <div class="hour">7 AM</div>
            </div>
          </div>

          <div class="small-calendar">
            <strong>Quick actions</strong>
            <div class="muted" style="margin-top:8px">Create a new event, task or reminder.</div>
          </div>
        </aside>
      </div>
    </div>
  </main>

  <script src="assets/dashboard.js"></script>
</body>
</html>