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
  <title>Tasks — IntelliPlan</title>
  <link rel="stylesheet" href="assets/styles-dashboard.css">
</head>
<body>
  <aside class="sidebar">
    <div class="brand">
      <div class="brand-logo"><img src="assets/logo.jpg" alt="Logo" style="width:100%;height:100%;object-fit:contain;"></div>
      <div class="brand-name">IntelliPlan</div>
    </div>
    <nav class="nav">
      <a class="nav-item <?php echo ($currentPage === 'dashboard.php') ? 'active' : ''; ?>" href="dashboard.php"><span class="nav-icon">🏠</span><span class="nav-label">Dashboard</span></a>
      <a class="nav-item <?php echo ($currentPage === 'calendar.php') ? 'active' : ''; ?>" href="calendar.php"><span class="nav-icon">🗓️</span><span class="nav-label">Calendar</span></a>
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
      <h2>Tasks</h2>
      <div class="panel">
        <div id="tasksList" class="panel-body muted">Loading tasks…</div>
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

    // Fetch tasks from API
    async function loadTasks(){
      try{
        const res = await fetch('lib/api/tasks.php', { credentials: 'same-origin' });
        if (!res.ok) throw new Error('Network error ' + res.status);
        const tasks = await res.json();
        const el = document.getElementById('tasksList');
        if (!Array.isArray(tasks) || tasks.length === 0){
          el.classList.add('muted');
          el.textContent = 'No tasks to display.';
          return;
        }
        el.classList.remove('muted');
        el.innerHTML = '';
        tasks.forEach(t => {
          const item = document.createElement('div');
          item.style.padding = '10px 0';
          item.style.borderBottom = '1px solid var(--border)';
          item.innerHTML = `<div style="font-weight:700">${escapeHtml(t.title)}</div><div style="color:var(--muted);font-size:13px">${t.due_date || ''}</div>`;
          el.appendChild(item);
        });
      } catch (e){
        document.getElementById('tasksList').textContent = 'Failed to load tasks: ' + e.message;
      }
    }
    function escapeHtml(s){ return (s+'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
    loadTasks();
  </script>
</body>
</html>
