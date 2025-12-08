<?php
// dashboard.php - updated to use a Figma-style modal for calendar event create/edit
// Requires lib/auth.php (require_auth(), current_user()) and lib/db.php (db()).
// Uses FullCalendar and the API endpoints at /api/calendar.php and /api/tasks.php
session_start();
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/auth.php';

require_auth();
$user = current_user();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Dashboard — IntelliPlan</title>

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">

  <!-- FullCalendar CSS (CDN) -->
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">

  <!-- site styles + dashboard-specific -->
  <link rel="stylesheet" href="styles.css">
  <link rel="stylesheet" href="styles-dashboard.css">
  <!-- small additional styles for modal and layout -->
  <link rel="stylesheet" href="assets/styles-dashboard-extra.css">
</head>
<body>

  <header class="site-header">
    <div class="container header-inner">
      <div class="logo"><a href="index.php"><img src="assets/logo.jpg" alt="logo" style="height:44px;border-radius:6px"></a></div>
      <nav class="nav"><!-- minimal nav --> </nav>
      <div class="actions">
        <span class="welcome">Hi, <?php echo htmlspecialchars($user['name']); ?></span>
        <a class="btn btn-ghost" href="logout.php">Log out</a>
      </div>
    </div>
  </header>

  <main style="padding-top:96px;">
    <div class="container">
      <div class="dashboard-header">
        <h1 style="margin:0;font-size:24px;">Dashboard</h1>
        <div style="color:var(--muted)">Overview of your tasks and calendar</div>
      </div>

      <div class="dashboard-main">
        <!-- Left column: calendar -->
        <div>
          <div class="card">
            <div id="calendar"></div>
          </div>

          <div style="height:18px;"></div>

          <div class="card">
            <h3 style="margin-top:0;">Quick add event</h3>
            <form id="quick-event-form">
              <label style="display:block;margin-bottom:8px;">Title<input id="qe-title" required class="input" style="width:100%;padding:8px;border-radius:6px;border:1px solid #eee"></label>
              <label style="display:block;margin-bottom:8px;">Start<input id="qe-start" type="datetime-local" class="input" style="width:100%;padding:8px;border-radius:6px;border:1px solid #eee"></label>
              <label style="display:block;margin-bottom:8px;">End<input id="qe-end" type="datetime-local" class="input" style="width:100%;padding:8px;border-radius:6px;border:1px solid #eee"></label>
              <button class="btn btn-primary" type="submit">Add event</button>
            </form>
          </div>
        </div>

        <!-- Right column: tasks list -->
        <div>
          <div class="card">
            <h3 style="margin-top:0;">Tasks</h3>

            <form id="task-add-form" style="display:flex; gap:8px; margin-bottom:12px;">
              <input id="task-title" placeholder="New task title" required style="flex:1;padding:8px;border-radius:6px;border:1px solid #eee">
              <input id="task-due" type="date" style="width:140px;padding:8px;border-radius:6px;border:1px solid #eee">
              <button class="btn btn-primary" type="submit">Add</button>
            </form>

            <ul id="task-list" style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:10px;"></ul>
          </div>

          <div style="height:18px;"></div>

          <div class="card">
            <h3 style="margin-top:0;">Notes (placeholder)</h3>
            <p style="color:var(--muted)">Notes functionality can be added the same way if you want persistence.</p>
          </div>
        </div>
      </div>
    </div>
  </main>

  <!-- Event modal (Figma-style) -->
  <div id="event-modal-overlay" class="modal-overlay" aria-hidden="true">
    <div id="event-modal" class="modal" role="dialog" aria-modal="true" aria-labelledby="event-modal-title">
      <div class="modal-header">
        <h2 id="event-modal-title">Event</h2>
        <button id="modal-close" class="modal-close" aria-label="Close">✕</button>
      </div>

      <form id="event-form" class="modal-body">
        <input type="hidden" name="id" id="ev-id">

        <label class="modal-field">
          <div class="modal-label">Title</div>
          <input id="ev-title" name="title" type="text" required>
        </label>

        <label class="modal-field">
          <div class="modal-label">Description</div>
          <textarea id="ev-desc" name="description" rows="3"></textarea>
        </label>

        <div class="modal-row">
          <label class="modal-field" style="flex:1;">
            <div class="modal-label">Start</div>
            <input id="ev-start" name="start" type="datetime-local" required>
          </label>
          <label class="modal-field" style="flex:1;">
            <div class="modal-label">End</div>
            <input id="ev-end" name="end" type="datetime-local">
          </label>
        </div>

        <label class="modal-field modal-inline">
          <input id="ev-allday" name="allDay" type="checkbox">
          <span class="modal-label">All day</span>
        </label>

        <div class="modal-actions">
          <button type="button" id="ev-delete" class="btn btn-outline" style="margin-right:8px;">Delete</button>
          <button type="button" id="ev-cancel" class="btn btn-ghost">Cancel</button>
          <button type="submit" class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>

  <!-- FullCalendar + Luxon (for timezone parsing) from CDN -->
  <script src="https://cdn.jsdelivr.net/npm/luxon@3.3.0/build/global/luxon.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

  <!-- dashboard client logic (modal-enabled) -->
  <script src="assets/dashboard.js"></script>
</body>
</html>