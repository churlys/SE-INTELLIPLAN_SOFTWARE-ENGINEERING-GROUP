<?php
// dashboard.php - updated to accept a one-off gradient upload directly from this page
// Security notes:
// - This is convenient for testing but treat it as temporary. Remove the upload handling
//   after you upload your gradient to assets/gradient-hero.png and verify the page.
// - The script requires lib/auth.php so only logged-in users can upload.
//
// What it does:
// - On POST with name="gradient_upload" and a valid CSRF token it validates the file
//   (MIME, size) and saves it to assets/gradient-hero.png, then redirects back.
// - Shows a small upload button in the right column (same spot as Upload preview).
// - Displays success / error flash messages.

session_start();
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/auth.php';
require_auth();
$user = current_user();

// Flash messages
$flash = ['success' => '', 'error' => ''];

// Handle one-off upload (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gradient_upload'])) {
    // CSRF validation
    $token = $_POST['csrf_token'] ?? '';
    if (!function_exists('verify_csrf_token') || !verify_csrf_token($token)) {
        $flash['error'] = 'Invalid request (CSRF).';
    } elseif (empty($_FILES['gradient_file']) || $_FILES['gradient_file']['error'] !== UPLOAD_ERR_OK) {
        $flash['error'] = 'No file uploaded or upload error.';
    } else {
        $f = $_FILES['gradient_file'];
        // Basic validation
        $maxBytes = 3 * 1024 * 1024; // 3 MB
        if ($f['size'] > $maxBytes) {
            $flash['error'] = 'File too large. Max 3 MB.';
        } else {
            // Validate MIME safely
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $f['tmp_name']);
            finfo_close($finfo);
            $allowed = ['image/png','image/jpeg','image/webp'];
            if (!in_array($mime, $allowed, true)) {
                $flash['error'] = 'Unsupported file type. Use PNG/JPEG/WebP.';
            } else {
                // Ensure assets directory exists
                $dstDir = __DIR__ . '/assets';
                if (!is_dir($dstDir)) mkdir($dstDir, 0755, true);
                $dst = $dstDir . '/gradient-hero.png'; // always write this filename (overwrites)
                if (!move_uploaded_file($f['tmp_name'], $dst)) {
                    $flash['error'] = 'Failed to save uploaded file.';
                } else {
                    // Tighten file permissions
                    @chmod($dst, 0644);
                    $flash['success'] = 'Gradient uploaded successfully.';
                    // Redirect to avoid resubmission and to show the new background
                    header('Location: ' . $_SERVER['REQUEST_URI']);
                    exit;
                }
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Dashboard — IntelliPlan</title>

  <link rel="stylesheet" href="assets/styles-dashboard.css">
</head>
<body class="app-shell">

  <!-- Sidebar -->
  <aside class="app-sidebar" aria-label="Primary navigation">
    <div class="logo"><a href="index.php"><img src="assets/logo-large.png" alt="logo" style="width:56px;height:56px;object-fit:contain" onerror="this.style.display='none'"></a></div>
    <nav class="nav" aria-label="Main">
      <a class="nav-item active" href="#"><span aria-hidden="true">🏠</span></a>
      <a class="nav-item" href="calendar.php"><span aria-hidden="true">📅</span></a>
      <a class="nav-item" href="#"><span aria-hidden="true">📚</span></a>
    </nav>

    <div style="flex:1"></div>

    <div style="width:100%;display:flex;flex-direction:column;gap:10px;align-items:center">
      <div style="width:72%;height:46px;background:#f2f6fb;border-radius:8px"></div>
      <div style="width:72%;height:46px;background:#f2f6fb;border-radius:8px"></div>
      <div style="width:72%;height:46px;background:#f2f6fb;border-radius:8px"></div>
    </div>
  </aside>

  <!-- Main -->
  <main class="app-main">
    <!-- header -->
    <div class="top-header">
      <div style="display:flex;align-items:center;gap:12px">
        <div class="brand">
          <div class="logo-sm"><img src="assets/logo.jpg" alt="logo" style="width:56px;height:56px;object-fit:contain" onerror="this.style.display='none'"></div>
          <div>
            <div class="title">IntelliPlan</div>
            <div class="clock" id="clock">3:45 PM</div>
            <div class="clock-sub" id="date-sub">Wednesday, December 3</div>
          </div>
        </div>
      </div>

      <div class="header-controls">
        <div class="icon" title="Settings">⚙️</div>

        <form action="logout.php" method="post" style="display:inline">
          <?php if (function_exists('csrf_token')): ?>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
          <?php endif; ?>
          <button class="btn-ghost" type="submit">Log out</button>
        </form>

        <div class="user-chip">
          <img src="assets/avatar.png" alt="avatar" style="width:28px;height:28px;border-radius:6px;object-fit:cover" onerror="this.style.display='none'">
          <span><?php echo htmlspecialchars($user['email'] ?? $user['name'] ?? 'User'); ?></span>
        </div>
      </div>
    </div>

    <!-- flash messages -->
    <div style="max-width:1260px;margin:6px auto 0;padding:0 12px;">
      <?php if ($flash['success']): ?>
        <div style="background:#e6ffef;border:1px solid #c7f0d9;color:#0b6b3a;padding:10px;border-radius:8px;margin-bottom:10px;"><?php echo htmlspecialchars($flash['success']); ?></div>
      <?php endif; ?>
      <?php if ($flash['error']): ?>
        <div style="background:#fff0f0;border:1px solid #ffd6d6;color:#7c1212;padding:10px;border-radius:8px;margin-bottom:10px;"><?php echo htmlspecialchars($flash['error']); ?></div>
      <?php endif; ?>
    </div>

    <!-- dashboard grid -->
    <div class="dashboard-wrap">
      <!-- left -->
      <div class="left-column">
        <div class="hero-block">
          <div id="hero-left" class="hero-left" style="background-image: url('assets/gradient.png');">
            <div class="title-kicker">0 task due today.</div>
            <div class="hero-title">GOOD AFTERNOON.</div>
            <div class="hero-sub">Focus on your top tasks and schedule. Keep momentum going — you’ve got this.</div>
          </div>

          <div class="hero-right">
            <div class="timer-card">
              <div class="timer-label">Step Watch</div>
              <div class="timer-big" id="timer">25:00</div>
              <div class="timer-cta">
                <button class="btn">▶</button>
                <button class="btn-ghost">⏸</button>
              </div>
            </div>
          </div>
        </div>

        <div class="stats-row" aria-hidden="false">
          <div class="stat-card">
            <div class="label">Pending Tasks</div>
            <div class="value">0</div>
            <div style="color:var(--muted);font-size:12px">Last 7 days</div>
          </div>
          <div class="stat-card">
            <div class="label">Overdue Tasks</div>
            <div class="value">0</div>
            <div style="color:var(--muted);font-size:12px">Last 7 days</div>
          </div>
          <div class="stat-card">
            <div class="label">Tasks Completed</div>
            <div class="value">0</div>
            <div style="color:var(--muted);font-size:12px">Last 7 days</div>
          </div>
          <div class="stat-card">
            <div class="label">Your Streak</div>
            <div class="value">0</div>
            <div style="color:var(--muted);font-size:12px">Last 7 days</div>
          </div>
        </div>

        <div class="filters-row">
          <div class="filter-card">
            <label style="font-weight:700;color:var(--muted)">Classes</label>
            <select style="margin-left:auto"><option>All</option></select>
          </div>
          <div class="filter-card">
            <label style="font-weight:700;color:var(--muted)">Tasks</label>
            <select style="margin-left:auto"><option>All</option></select>
          </div>
          <div class="filter-card">
            <label style="font-weight:700;color:var(--muted)">Exams</label>
            <select style="margin-left:auto"><option>Upcoming</option></select>
          </div>
        </div>
      </div>

      <!-- right -->
      <aside class="right-column">
        <div class="small-calendar">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
            <strong>Calendar</strong>
            <select class="input"><option>Day</option><option>Week</option><option>Month</option></select>
          </div>

          <div class="chips">
            <div class="chip">Mon 1</div>
            <div class="chip">Tue 2</div>
            <div class="chip" style="background:linear-gradient(180deg,#dfeaff,#f0e9ff)">Wed 3</div>
            <div class="chip">Thu 4</div>
            <div class="chip">Fri 5</div>
          </div>

          <div class="hour-grid" aria-hidden="true">
            <div style="opacity:0.4;color:var(--muted);font-size:13px;padding-left:8px">1 AM</div>
            <div style="opacity:0.4;color:var(--muted);font-size:13px;padding-left:8px">2 AM</div>
            <div style="opacity:0.4;color:var(--muted);font-size:13px;padding-left:8px">3 AM</div>
            <div style="opacity:0.4;color:var(--muted);font-size:13px;padding-left:8px">4 AM</div>
            <div style="opacity:0.4;color:var(--muted);font-size:13px;padding-left:8px">5 AM</div>
            <div style="opacity:0.4;color:var(--muted);font-size:13px;padding-left:8px">6 AM</div>
            <div style="opacity:0.4;color:var(--muted);font-size:13px;padding-left:8px">7 AM</div>
          </div>
        </div>

        <div class="small-calendar">
          <strong>Quick actions</strong>
          <div style="color:var(--muted);margin-top:8px">Create a new event, task or reminder.</div>

          <!-- Upload gradient form (writes to assets/gradient-hero.png) -->
          <form method="post" enctype="multipart/form-data" style="margin-top:12px">
            <?php if (function_exists('csrf_token')): ?>
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
            <?php endif; ?>
            <label style="display:flex;gap:8px;align-items:center">
              <input type="file" name="gradient_file" accept="image/*" required>
              <button class="btn-ghost" name="gradient_upload" type="submit">Upload gradient</button>
            </label>
            <div style="margin-top:6px;color:var(--muted);font-size:12px">Upload PNG/JPEG/WebP (max 3 MB). Remove this form after uploading.</div>
          </form>

        </div>
      </aside>
    </div>
  </main>

  <script>
    // dashboard-exact.js minimal embed: clock only (kept inline to avoid extra file)
    function updateClock(){
      const el = document.getElementById('clock');
      const sub = document.getElementById('date-sub');
      const now = new Date();
      if (el) el.textContent = now.toLocaleTimeString([], {hour:'numeric', minute:'2-digit'});
      if (sub) sub.textContent = now.toLocaleDateString([], {weekday:'long', month:'long', day:'numeric'});
    }
    updateClock();
    setInterval(updateClock, 1000);
  </script>
</body>
</html>