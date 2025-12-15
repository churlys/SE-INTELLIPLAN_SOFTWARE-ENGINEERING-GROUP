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
        <div class="tasks-shell">
          <div class="tasks-head-row">
            <div class="tasks-title">
              <span class="tasks-title-icon" aria-hidden="true">📋</span>
              <h2>Tasks</h2>
            </div>
            <button type="button" class="tasks-add-btn" id="openAddTask">Add Task</button>
          </div>

          <div class="tasks-tabs" role="tablist" aria-label="Task filter">
            <button type="button" class="tasks-tab active" data-view="current" role="tab" aria-selected="true">Current</button>
            <button type="button" class="tasks-tab" data-view="past" role="tab" aria-selected="false">Past</button>
            <button type="button" class="tasks-tab" data-view="overdue" role="tab" aria-selected="false">Overdue</button>
          </div>

          <div class="tasks-filters">
            <label class="tasks-select" aria-label="Select Subject">
              <select id="subjectFilter">
                <option value="">Select Subject</option>
              </select>
              <span class="tasks-select-arrow" aria-hidden="true">▾</span>
            </label>
          </div>

          <div id="addTaskPanel" class="tasks-add" hidden>
            <form id="addTaskForm" class="tasks-add-form" autocomplete="off">
              <div class="tasks-add-grid">
                <label class="tasks-field">
                  <span class="tasks-label">Title</span>
                  <input id="taskTitle" type="text" placeholder="Enter task title" required>
                </label>
                <label class="tasks-field">
                  <span class="tasks-label">Subject</span>
                  <input id="taskSubject" type="text" placeholder="e.g. Math">
                </label>
                <label class="tasks-field">
                  <span class="tasks-label">Due Date</span>
                  <input id="taskDue" type="date">
                </label>
                <label class="tasks-field tasks-field-full">
                  <span class="tasks-label">Details</span>
                  <textarea id="taskDetails" rows="3" placeholder="Optional details"></textarea>
                </label>
              </div>
              <div class="tasks-add-actions">
                <button type="button" class="tasks-btn" id="cancelAddTask">Cancel</button>
                <button type="submit" class="tasks-btn tasks-btn-primary">Save</button>
              </div>
              <div id="addTaskError" class="tasks-error" hidden></div>
            </form>
          </div>

          <div class="tasks-section-label" id="tasksSectionLabel">This month (0)</div>
          <div id="tasksList" class="tasks-list"></div>
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

    const tasksListEl = document.getElementById('tasksList');
    const tasksSectionLabelEl = document.getElementById('tasksSectionLabel');
    const subjectFilterEl = document.getElementById('subjectFilter');
    const openAddTaskBtn = document.getElementById('openAddTask');
    const addTaskPanel = document.getElementById('addTaskPanel');
    const cancelAddTaskBtn = document.getElementById('cancelAddTask');
    const addTaskForm = document.getElementById('addTaskForm');
    const addTaskError = document.getElementById('addTaskError');

    let allTasks = [];
    let currentView = 'current';

    function escapeHtml(s){
      return (s+'')
        .replace(/&/g,'&amp;')
        .replace(/</g,'&lt;')
        .replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;')
        .replace(/'/g,'&#039;');
    }

    function isoToday(){
      const d = new Date();
      const yyyy = d.getFullYear();
      const mm = String(d.getMonth()+1).padStart(2,'0');
      const dd = String(d.getDate()).padStart(2,'0');
      return `${yyyy}-${mm}-${dd}`;
    }

    function isThisMonth(isoDate){
      if (!isoDate) return false;
      const d = new Date(isoDate + 'T00:00:00');
      const now = new Date();
      return d.getFullYear() === now.getFullYear() && d.getMonth() === now.getMonth();
    }

    function filteredTasks(){
      const subject = (subjectFilterEl?.value || '').trim();
      const today = isoToday();

      return allTasks.filter(t => {
        if (subject && (t.subject || '') !== subject) return false;

        const status = (t.status || 'open').toLowerCase();
        const due = t.due_date || '';

        if (currentView === 'past') {
          return status === 'done';
        }
        if (currentView === 'overdue') {
          return status !== 'done' && !!due && due < today;
        }
        // current
        return status !== 'done' && (!due || due >= today);
      });
    }

    function upsertSubjectOptions(tasks){
      if (!subjectFilterEl) return;
      const subjects = Array.from(new Set(tasks.map(t => (t.subject || '').trim()).filter(Boolean))).sort((a,b)=>a.localeCompare(b));
      const current = subjectFilterEl.value;
      subjectFilterEl.innerHTML = '<option value="">Select Subject</option>' + subjects.map(s => `<option value="${escapeHtml(s)}">${escapeHtml(s)}</option>`).join('');
      if (subjects.includes(current)) subjectFilterEl.value = current;
    }

    function render(){
      const tasks = filteredTasks();
      const thisMonthCount = tasks.filter(t => isThisMonth(t.due_date)).length;
      tasksSectionLabelEl.textContent = `This month (${thisMonthCount})`;

      if (!tasksListEl) return;
      tasksListEl.innerHTML = '';

      if (tasks.length === 0) {
        const empty = document.createElement('div');
        empty.className = 'tasks-empty muted';
        empty.textContent = 'No tasks to display.';
        tasksListEl.appendChild(empty);
        return;
      }

      tasks.forEach(t => {
        const card = document.createElement('div');
        card.className = 'task-card';

        const left = document.createElement('div');
        left.className = 'task-left';

        const isDone = (t.status || 'open').toLowerCase() === 'done';
        const check = document.createElement('button');
        check.type = 'button';
        check.className = 'task-check' + (isDone ? ' done' : '');
        check.setAttribute('aria-label', isDone ? 'Mark as not done' : 'Mark as done');
        check.addEventListener('click', async () => {
          try {
            const nextStatus = isDone ? 'open' : 'done';
            await updateTask({
              id: t.id,
              title: t.title,
              details: t.details ?? null,
              subject: t.subject ?? null,
              due_date: t.due_date ?? null,
              status: nextStatus,
            });
            await refreshTasks();
          } catch (e) {
            // no-op; fetch error shown in list on next render
          }
        });

        const main = document.createElement('div');
        main.className = 'task-main';
        const title = document.createElement('div');
        title.className = 'task-title';
        title.textContent = t.title || 'Untitled';
        const meta = document.createElement('div');
        meta.className = 'task-meta';
        const parts = [];
        if (t.subject) parts.push(t.subject);
        if (t.due_date) parts.push(t.due_date);
        meta.textContent = parts.join(' • ');
        main.appendChild(title);
        if (parts.length) main.appendChild(meta);

        left.appendChild(check);
        left.appendChild(main);

        card.appendChild(left);
        tasksListEl.appendChild(card);
      });
    }

    async function refreshTasks(){
      try {
        const res = await fetch('lib/api/tasks.php', { credentials: 'same-origin' });
        if (!res.ok) throw new Error('Network error ' + res.status);
        const tasks = await res.json();
        allTasks = Array.isArray(tasks) ? tasks : [];
        upsertSubjectOptions(allTasks);
        render();
      } catch (e) {
        tasksListEl.innerHTML = '';
        const err = document.createElement('div');
        err.className = 'tasks-empty';
        err.textContent = 'Failed to load tasks: ' + e.message;
        tasksListEl.appendChild(err);
      }
    }

    async function createTask(payload){
      const res = await fetch('lib/api/tasks.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok) throw new Error(data.error || ('Request failed ' + res.status));
      return data;
    }

    async function updateTask(payload){
      const res = await fetch('lib/api/tasks.php', {
        method: 'PUT',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok) throw new Error(data.error || ('Request failed ' + res.status));
      return data;
    }

    // Tabs
    document.querySelectorAll('.tasks-tab').forEach(btn => {
      btn.addEventListener('click', () => {
        currentView = btn.getAttribute('data-view') || 'current';
        document.querySelectorAll('.tasks-tab').forEach(b => {
          const active = b === btn;
          b.classList.toggle('active', active);
          b.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        render();
      });
    });

    subjectFilterEl?.addEventListener('change', render);

    // Add task panel
    openAddTaskBtn?.addEventListener('click', () => {
      addTaskPanel.hidden = !addTaskPanel.hidden;
      addTaskError.hidden = true;
      if (!addTaskPanel.hidden) document.getElementById('taskTitle')?.focus();
    });
    cancelAddTaskBtn?.addEventListener('click', () => {
      addTaskPanel.hidden = true;
      addTaskError.hidden = true;
    });

    addTaskForm?.addEventListener('submit', async (e) => {
      e.preventDefault();
      addTaskError.hidden = true;

      const title = document.getElementById('taskTitle')?.value?.trim() || '';
      const subject = document.getElementById('taskSubject')?.value?.trim() || '';
      const due = document.getElementById('taskDue')?.value || null;
      const details = document.getElementById('taskDetails')?.value?.trim() || '';

      if (!title) {
        addTaskError.textContent = 'Title is required.';
        addTaskError.hidden = false;
        return;
      }

      try {
        await createTask({
          title,
          subject: subject || null,
          due_date: due || null,
          details: details || null,
        });
        addTaskForm.reset();
        addTaskPanel.hidden = true;
        await refreshTasks();
      } catch (err) {
        addTaskError.textContent = err.message || 'Failed to save task.';
        addTaskError.hidden = false;
      }
    });

    refreshTasks();
  </script>
</body>
</html>
