<?php
// dashboard.php - Dashboard page (Figma-styled UI + full calendar + tasks).
// Requires: lib/db.php and lib/auth.php (make sure those files exist and DB is configured).
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
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">

  <!-- Use your main styles + dashboard styles -->
  <link rel="stylesheet" href="assets/styles.css">
  <link rel="stylesheet" href="assets/styles-dashboard-extra.css">
  <style>
    /* small overrides for layout to match Figma-like proportions */
    body { font-family: "Inter", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial; background: #f6fbff; color: #071232; }
    .dashboard-header{ display:flex; align-items:center; justify-content:space-between; gap:12px; padding:18px 24px; }
    .dashboard-main{ display:grid; grid-template-columns: 700px 1fr; gap:28px; padding:18px 24px; align-items:start; max-width:1200px; margin:0 auto 80px; }
    .card { background:white; border-radius:12px; padding:18px; box-shadow:0 10px 30px rgba(8,30,65,0.04); }
    header.site-header { position:static; padding:18px 0; background:transparent; }
    .welcome { font-weight:700; margin-right:8px; }
    #calendar { width:100%; min-height:500px; }
    .container { max-width:1200px; margin:0 auto; padding:0 24px; }
  </style>
</head>
<body>

  <header class="site-header">
    <div class="container header-inner" style="align-items:center;">
      <div class="logo"><a href="index.php"><img src="assets/logo.jpg" alt="logo" style="height:44px;border-radius:6px"></a></div>
      <div style="flex:1"></div>
      <div class="actions" style="align-items:center">
        <span class="welcome">Hi, <?php echo htmlspecialchars($user['name']); ?></span>
        <a class="btn btn-ghost" href="logout.php">Log out</a>
      </div>
    </div>
  </header>

  <main style="padding-top:18px;">
    <div class="container">
      <div class="dashboard-header">
        <div>
          <h1 style="margin:0;font-size:28px;font-weight:800;">Dashboard</h1>
          <div style="color:var(--muted)">Overview of your tasks and calendar</div>
        </div>
      </div>

      <div class="dashboard-main">
        <!-- Left: Calendar and quick add -->
        <div>
          <div class="card">
            <div id="calendar"></div>
          </div>

          <div style="height:18px;"></div>

          <div class="card">
            <h3 style="margin-top:0;">Quick add event</h3>
            <form id="quick-event-form">
              <label style="display:block;margin-bottom:8px;">Title
                <input id="qe-title" required class="input" style="width:100%;padding:8px;border-radius:6px;border:1px solid #eee">
              </label>
              <label style="display:block;margin-bottom:8px;">Start
                <input id="qe-start" type="datetime-local" class="input" style="width:100%;padding:8px;border-radius:6px;border:1px solid #eee">
              </label>
              <label style="display:block;margin-bottom:8px;">End
                <input id="qe-end" type="datetime-local" class="input" style="width:100%;padding:8px;border-radius:6px;border:1px solid #eee">
              </label>
              <button class="btn btn-primary" type="submit">Add event</button>
            </form>
          </div>
        </div>

        <!-- Right: Tasks and notes -->
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

  <!-- Modal (Figma-style) -->
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

  <script src="https://cdn.jsdelivr.net/npm/luxon@3.3.0/build/global/luxon.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

  <!-- dashboard client script (use the asset you already have) -->
  <script src="assets/dashboard.js"></script>
</body>
</html>