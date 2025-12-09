<?php
// calendar.php
// Calendar page that mirrors the Figma layout.
// - Requires lib/auth.php for require_auth()/current_user()
// - Includes an editable logo + editable background (local replacement via file input + localStorage for temporary testing)
// - Uses FullCalendar for the main calendar area (day/week/month). Place assets in assets/
//
// Usage:
// 1. Add lib/db.php and lib/auth.php (already in your project)
// 2. Place assets/styles-calendar.css and assets/calendar.js in assets/
// 3. Start server: php -S localhost:8000
// 4. Visit /calendar.php (must be logged in)
session_start();
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/db.php';
require_auth();
$user = current_user();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Calendar — IntelliPlan</title>

  <link rel="stylesheet" href="assets/styles.css">
  <link rel="stylesheet" href="assets/styles-calendar.css">

  <!-- FullCalendar (for the real calendar area) -->
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
</head>
<body class="app-shell">

  <!-- SIDEBAR -->
  <aside class="app-sidebar" aria-label="Primary navigation">
    <div class="logo">
      <!-- editable logo (click edit to replace) -->
      <img id="site-logo" src="assets/logo.png" alt="IntelliPlan Logo" onerror="this.style.display='none'">
    </div>

    <nav role="navigation" aria-label="Main" class="nav">
      <div class="nav-item"><a href="dashboard.php" title="Dashboard" style="text-decoration:none">🏠</a></div>
      <div class="nav-item active"><a href="calendar.php" title="Calendar" style="text-decoration:none">📅</a></div>
      <div class="nav-item"><a href="#" title="Activities" style="text-decoration:none">📚</a></div>
    </nav>

    <div style="flex:1"></div>

    <div style="width:100%;display:flex;flex-direction:column;gap:10px;align-items:center">
      <div style="width:64%;height:44px;background:#f2f6fb;border-radius:8px"></div>
      <div style="width:64%;height:44px;background:#f2f6fb;border-radius:8px"></div>
    </div>

    <!-- small image edit controls -->
    <div style="margin-top:18px;">
      <label class="image-edit">
        <button id="edit-logo-btn" class="edit-btn">Edit logo</button>
        <input id="logo-file" type="file" accept="image/*">
      </label>
    </div>
  </aside>

  <!-- MAIN -->
  <main class="app-main">
    <div class="top-header">
      <div class="brand-time">
        <div class="logo-sm"><img src="assets/logo.jpg" alt="logo" style="width:100%;height:100%;object-fit:contain" onerror="this.style.display='none'"></div>
        <div>
          <div class="title">IntelliPlan</div>
          <div class="current-time" id="clock">3:45 PM</div>
          <div class="current-sub" id="date-sub">Wednesday, December 3</div>
        </div>
      </div>

      <div class="header-controls">
        <button class="btn-ghost" title="Settings">⚙️</button>
        <div class="user-chip">
          <img src="assets/avatar.png" alt="avatar" style="width:28px;height:28px;border-radius:6px;object-fit:cover" onerror="this.style.display='none'">
          <span><?php echo htmlspecialchars($user['email'] ?? $user['name'] ?? 'User'); ?></span>
        </div>
      </div>
    </div>

    <div class="dashboard-wrap">
      <!-- LEFT: Calendar white panel -->
      <section class="calendar-panel">
        <div class="calendar-controls">
          <div class="left">
            <button class="control-btn" id="prev-day">◀</button>
            <div style="width:220px;text-align:center;padding:6px 8px;border-radius:8px;background:rgba(255,255,255,0.85)">05 Dec, Friday, 2025</div>
            <button class="control-btn" id="next-day">▶</button>
          </div>

          <div class="view-toggle">
            <button class="control-btn" data-view="timeGridDay">Day</button>
            <button class="control-btn" data-view="timeGridWeek">Week</button>
            <button class="control-btn" data-view="dayGridMonth">Month</button>
            <button class="control-btn" id="today-btn">Today</button>
          </div>
        </div>

        <!-- main calendar (FullCalendar) -->
        <div id="main-calendar"></div>
      </section>

      <!-- RIGHT: small calendar and widgets -->
      <aside class="right-column">
        <div class="small-card">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
            <strong>Calendar</strong>
            <select class="input" id="small-range"><option>Day</option><option>Week</option></select>
          </div>

          <div class="day-chips">
            <div class="day-chip">Mon 1</div>
            <div class="day-chip">Tue 2</div>
            <div class="day-chip" style="background:linear-gradient(180deg,#dfeaff,#f0e9ff)">Wed 3</div>
            <div class="day-chip">Thu 4</div>
            <div class="day-chip">Fri 5</div>
          </div>

          <div id="mini-calendar" style="height:260px;border-radius:8px;background:linear-gradient(180deg,#fff,#fbfdff);border:1px solid var(--soft-border)"></div>
        </div>

        <div class="small-card">
          <strong>Quick actions</strong>
          <div style="margin-top:8px;color:var(--muted)">Create a new event, task or reminder.</div>
        </div>
      </aside>
    </div>
  </main>

  <!-- scripts -->
  <script src="https://cdn.jsdelivr.net/npm/luxon@3.3.0/build/global/luxon.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
  <script src="assets/calendar.js"></script>
</body>
</html>