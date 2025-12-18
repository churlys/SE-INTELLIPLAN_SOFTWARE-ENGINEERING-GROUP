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
  <title>Classes — IntelliPlan</title>
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
      <a class="nav-item" href="#" onclick="event.preventDefault(); document.getElementById('logoutForm').submit();">
        <span class="nav-icon">🚪</span>
        <span class="nav-label">Log Out</span>
      </a>
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
            <span class="tasks-title-icon" aria-hidden="true">🎓</span>
            <h2>Classes</h2>
          </div>
          <button type="button" class="tasks-add-btn" id="openAddClass">Add Class</button>
        </div>

        <div class="tasks-tabs" role="tablist" aria-label="Class filter">
          <button type="button" class="tasks-tab active" data-view="current" role="tab" aria-selected="true">Current</button>
          <button type="button" class="tasks-tab" data-view="past" role="tab" aria-selected="false">Past</button>
        </div>

        <div class="tasks-filters">
          <label class="tasks-select" aria-label="Select Subject">
            <select id="subjectFilter">
              <option value="">Select Subject</option>
              <option value="Math">Math</option>
              <option value="English">English</option>
              <option value="Science">Science</option>
              <option value="PE">PE</option>
            </select>
            <span class="tasks-select-arrow" aria-hidden="true">▾</span>
          </label>
        </div>

        <div class="tasks-section-label" id="tasksSectionLabel">Classes (0)</div>
        <div id="classList" class="tasks-list"></div>

        <div id="addClassPanel" class="tasks-add" hidden>
          <form id="addClassForm" class="tasks-add-form" autocomplete="off">
            <div class="tasks-add-grid">
              <label class="tasks-field">
                <span class="tasks-label">Subject</span>
                <input id="className" type="text" placeholder="e.g. Math" list="classSubjectList">
                <datalist id="classSubjectList">
                  <option value="Math"></option>
                  <option value="English"></option>
                  <option value="Science"></option>
                  <option value="PE"></option>
                </datalist>
              </label>
                <label class="tasks-field">
                  <span class="tasks-label">Time</span>
                  <div class="tasks-time-range">
                    <input id="classStartTime" type="time" step="60" aria-label="Start time">
                    <span class="time-sep">—</span>
                    <input id="classEndTime" type="time" step="60" aria-label="End time">
                  </div>
                  <div class="tasks-note">Set start and end time for the class (time of day).</div>
                </label>
                <label class="tasks-field">
                  <span class="tasks-label">Days</span>
                  <div class="tasks-days" aria-label="Select days">
                    <label><input type="checkbox" name="classDays" value="Mon"> Mon</label>
                    <label><input type="checkbox" name="classDays" value="Tue"> Tue</label>
                    <label><input type="checkbox" name="classDays" value="Wed"> Wed</label>
                    <label><input type="checkbox" name="classDays" value="Thu"> Thu</label>
                    <label><input type="checkbox" name="classDays" value="Fri"> Fri</label>
                    <label><input type="checkbox" name="classDays" value="Sat"> Sat</label>
                    <label><input type="checkbox" name="classDays" value="Sun"> Sun</label>
                  </div>
                </label>
                <label class="tasks-field">
                  <span class="tasks-label">Professor</span>
                  <input id="classProfessor" type="text" placeholder="e.g. Mr. Gonzales">
                </label>
            </div>
            <div class="tasks-add-actions">
              <button type="button" class="tasks-btn" id="cancelAddClass">Cancel</button>
              <button type="submit" class="tasks-btn tasks-btn-primary">Save</button>
            </div>
            <div id="addClassError" class="tasks-error" hidden></div>
          </form>
        </div>
      </div>
    </section>
  </main>

  <form id="logoutForm" method="POST" action="logout.php" style="display:none;">
    <?php if (function_exists('csrf_token')): ?>
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
    <?php endif; ?>
  </form>

  <script>
    document.getElementById('liveTime').textContent = new Date().toLocaleTimeString([], {hour: 'numeric', minute: '2-digit'});
    document.getElementById('liveDate').textContent = new Date().toLocaleDateString(undefined, {weekday: 'long', month: 'long', day: 'numeric'});

    const classesListEl = document.getElementById('classList');
    const classesSectionLabelEl = document.getElementById('tasksSectionLabel');
    const subjectFilterEl = document.getElementById('subjectFilter');

    let allClasses = [], currentView = 'current';

    function showNotification(message, duration = 3000){
      const notification = document.createElement('div');
      notification.className = 'notification';
      notification.style.cssText = `
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        background: #4CAF50;
        color: white;
        padding: 16px 24px;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        z-index: 9999;
        max-width: 90%;
        text-align: center;
        font-size: 14px;
        animation: slideDown 0.3s ease-out;
      `;
      notification.textContent = message;
      document.body.appendChild(notification);
      
      setTimeout(() => {
        notification.style.animation = 'slideUp 0.3s ease-out';
        setTimeout(() => notification.remove(), 300);
      }, duration);
    }

    const style = document.createElement('style');
    style.textContent = `
      @keyframes slideDown {
        from { transform: translateX(-50%) translateY(-100%); opacity: 0; }
        to { transform: translateX(-50%) translateY(0); opacity: 1; }
      }
      @keyframes slideUp {
        from { transform: translateX(-50%) translateY(0); opacity: 1; }
        to { transform: translateX(-50%) translateY(-100%); opacity: 0; }
      }
    `;
    document.head.appendChild(style);

    function escapeHtml(s){
      return (s+'')
        .replace(/&/g,'&amp;')
        .replace(/</g,'&lt;')
        .replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;')
        .replace(/'/g,'&#039;');
    }

    function formatTimeAmerican(timeStr){
      if (!timeStr) return '';
      const [hours, minutes] = timeStr.split(':');
      const h = parseInt(hours, 10);
      const m = parseInt(minutes, 10);
      const ampm = h >= 12 ? 'PM' : 'AM';
      const displayHour = h % 12 || 12;
      return `${displayHour}:${String(m).padStart(2, '0')} ${ampm}`;
    }

    function filteredClasses(){
      const subject = (subjectFilterEl?.value || '').trim();
      return allClasses.filter(c => {
        if (subject && String(c.subject || '').trim().toLowerCase() !== subject.toLowerCase()) return false;
        const status = (c.status || 'active').toLowerCase();
        if (currentView === 'past') {
          return status === 'archived';
        }
        // current
        return status !== 'archived';
      });
    }

    function upsertSubjectOptions(classes){
      if (!subjectFilterEl) return;
      const builtinSubjects = ['Math', 'English', 'Science', 'PE'];
      const byKey = new Map();
      builtinSubjects.forEach(s => byKey.set(s.toLowerCase(), s));
      (classes || []).forEach(c => {
        const trimmed = String(c?.subject || '').trim();
        if (!trimmed) return;
        const key = trimmed.toLowerCase();
        if (!byKey.has(key)) byKey.set(key, trimmed);
      });

      const builtinKeys = new Set(builtinSubjects.map(s => s.toLowerCase()));
      const builtins = builtinSubjects.map(s => byKey.get(s.toLowerCase()) || s);
      const custom = Array.from(byKey.entries())
        .filter(([key]) => !builtinKeys.has(key))
        .map(([, value]) => value)
        .sort((a, b) => a.localeCompare(b));

      const subjects = [...builtins, ...custom];
      const current = subjectFilterEl.value;
      subjectFilterEl.innerHTML = '<option value="">Select Subject</option>' +
        subjects.map(s => `<option value="${escapeHtml(s)}">${escapeHtml(s)}</option>`).join('');
      if (current && subjects.some(s => s.toLowerCase() === current.toLowerCase())) {
        subjectFilterEl.value = subjects.find(s => s.toLowerCase() === current.toLowerCase());
      }
    }

    function addSubjectToDropdown(subject){
      if (!subjectFilterEl || !subject) return;
      const trimmed = subject.trim();
      if (!trimmed) return;
      const option = Array.from(subjectFilterEl.options).find(o => o.value === trimmed);
      if (!option) {
        const newOption = document.createElement('option');
        newOption.value = trimmed;
        newOption.textContent = trimmed;
        subjectFilterEl.appendChild(newOption);
        // Sort options
        const options = Array.from(subjectFilterEl.options).slice(1);
        options.sort((a, b) => a.textContent.localeCompare(b.textContent));
        options.forEach(opt => subjectFilterEl.appendChild(opt));
      }
    }

    function render(){
      const classes = filteredClasses();
      classesSectionLabelEl.textContent = `Classes (${classes.length})`;

      if (!classesListEl) return;
      classesListEl.innerHTML = '';

      if (classes.length === 0) {
        const empty = document.createElement('div');
        empty.className = 'tasks-empty muted';
        empty.textContent = 'No classes to display.';
        classesListEl.appendChild(empty);
        return;
      }

      classes.forEach(c => {
        const card = document.createElement('div');
        card.className = 'task-card';

        const left = document.createElement('div');
        left.className = 'task-left';

        const main = document.createElement('div');
        main.className = 'task-main';
        const title = document.createElement('div');
        title.className = 'task-title';
        title.textContent = c.name || 'Untitled Class';
        const meta = document.createElement('div');
        meta.className = 'task-meta';
        const parts = [];
        // Show days if present
        if (c.days) parts.push(c.days);
        // Prefer structured start/end time if available
        if (c.start_time && c.end_time) {
          parts.push(`${formatTimeAmerican(c.start_time)} - ${formatTimeAmerican(c.end_time)}`);
        } else if (c.starts_at) {
          try {
            const dt = new Date(c.starts_at);
            parts.push(dt.toLocaleString(undefined, { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }));
          } catch (e) {
            if (c.time) parts.push(c.time);
          }
        } else if (c.time) {
          parts.push(c.time);
        }
        if (c.professor) parts.push(c.professor);
        meta.textContent = parts.join(' • ');
        main.appendChild(title);
        if (parts.length) main.appendChild(meta);

        left.appendChild(main);
        card.appendChild(left);

        const right = document.createElement('div');
        right.className = 'task-right';
        right.style.display = 'flex';
        right.style.gap = '8px';
        right.style.alignItems = 'center';
        
        const archiveBtn = document.createElement('button');
        archiveBtn.className = 'task-action-btn';
        const isArchived = (c.status || 'active').toLowerCase() === 'archived';
        archiveBtn.innerHTML = isArchived ? '↻' : '✓';
        archiveBtn.setAttribute('aria-label', isArchived ? 'Mark as current' : 'Mark as past');
        archiveBtn.setAttribute('title', isArchived ? 'Mark as current' : 'Mark as past');
        archiveBtn.style.cssText = `
          background: ${isArchived ? '#2196F3' : '#4CAF50'};
          color: white;
          border: none;
          border-radius: 6px;
          width: 36px;
          height: 36px;
          cursor: pointer;
          font-size: 16px;
          display: flex;
          align-items: center;
          justify-content: center;
          transition: all 0.2s ease;
          box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        `;
        archiveBtn.onmouseover = () => {
          archiveBtn.style.transform = 'scale(1.1)';
          archiveBtn.style.boxShadow = '0 4px 8px rgba(0,0,0,0.2)';
        };
        archiveBtn.onmouseout = () => {
          archiveBtn.style.transform = 'scale(1)';
          archiveBtn.style.boxShadow = '0 2px 4px rgba(0,0,0,0.1)';
        };
        archiveBtn.addEventListener('click', async () => {
          try {
            const newStatus = isArchived ? 'active' : 'archived';
            await fetch('lib/api/classes.php', {
              method: 'PUT',
              credentials: 'same-origin',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ 
                id: c.id,
                name: c.name,
                subject: c.subject,
                start_time: c.start_time,
                end_time: c.end_time,
                days: c.days,
                professor: c.professor,
                status: newStatus
              }),
            });
            if (newStatus === 'archived') {
              showNotification('This class is now completed and will be put in the past.');
            }
            await refreshClasses();
          } catch (err) {
            alert('Failed to update class: ' + err.message);
          }
        });
        right.appendChild(archiveBtn);
        
        const deleteBtn = document.createElement('button');
        deleteBtn.className = 'task-delete-btn';
        deleteBtn.innerHTML = '✕';
        deleteBtn.setAttribute('aria-label', 'Delete class');
        deleteBtn.style.cssText = `
          background: #F44336;
          color: white;
          border: none;
          border-radius: 6px;
          width: 36px;
          height: 36px;
          cursor: pointer;
          font-size: 18px;
          font-weight: bold;
          display: flex;
          align-items: center;
          justify-content: center;
          transition: all 0.2s ease;
          box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        `;
        deleteBtn.onmouseover = () => {
          deleteBtn.style.transform = 'scale(1.1)';
          deleteBtn.style.boxShadow = '0 4px 8px rgba(0,0,0,0.2)';
        };
        deleteBtn.onmouseout = () => {
          deleteBtn.style.transform = 'scale(1)';
          deleteBtn.style.boxShadow = '0 2px 4px rgba(0,0,0,0.1)';
        };
        deleteBtn.addEventListener('click', async () => {
          if (!confirm('Delete this class?')) return;
          try {
            await fetch('lib/api/classes.php', {
              method: 'DELETE',
              credentials: 'same-origin',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ id: c.id }),
            });
            await refreshClasses();
          } catch (err) {
            alert('Failed to delete class: ' + err.message);
          }
        });
        right.appendChild(deleteBtn);
        card.appendChild(right);

        classesListEl.appendChild(card);
      });
    }

    async function refreshClasses(){
      try {
        const res = await fetch('lib/api/classes.php', { credentials: 'same-origin' });
        if (!res.ok) throw new Error('Network error ' + res.status);
        const classes = await res.json();
        allClasses = Array.isArray(classes) ? classes : [];
        upsertSubjectOptions(allClasses);
        render();
      } catch (e) {
        classesListEl.innerHTML = '';
        const err = document.createElement('div');
        err.className = 'tasks-empty';
        err.textContent = 'Failed to load classes: ' + e.message;
        classesListEl.appendChild(err);
      }
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

    // Add class panel handlers
    const openAddClassBtn = document.getElementById('openAddClass');
    const addClassPanel = document.getElementById('addClassPanel');
    const cancelAddClassBtn = document.getElementById('cancelAddClass');
    const addClassForm = document.getElementById('addClassForm');
    const addClassError = document.getElementById('addClassError');

    openAddClassBtn?.addEventListener('click', () => {
      addClassPanel.hidden = !addClassPanel.hidden;
      addClassError.hidden = true;
      if (!addClassPanel.hidden) document.getElementById('className')?.focus();
    });
    
    cancelAddClassBtn?.addEventListener('click', () => {
      addClassPanel.hidden = true;
      addClassError.hidden = true;
    });

    async function createClass(payload){
      const res = await fetch('lib/api/classes.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok) throw new Error(data.error || ('Request failed ' + res.status));
      return data;
    }

    addClassForm?.addEventListener('submit', async (e) => {
      e.preventDefault();
      addClassError.hidden = true;
      const name = document.getElementById('className')?.value?.trim() || '';
      const subject = document.getElementById('className')?.value?.trim() || '';
      const startTime = document.getElementById('classStartTime')?.value?.trim() || '';
      const endTime = document.getElementById('classEndTime')?.value?.trim() || '';
      const professor = document.getElementById('classProfessor')?.value?.trim() || '';
      const daysNodes = Array.from(document.querySelectorAll('input[name="classDays"]:checked'));
      const days = daysNodes.map(n => n.value).join(',');
      
      // Validate required fields
      if (!subject) {
        addClassError.textContent = 'Subject is required.';
        addClassError.hidden = false;
        return;
      }
      if (!startTime) {
        addClassError.textContent = 'Start time is required.';
        addClassError.hidden = false;
        return;
      }
      if (!endTime) {
        addClassError.textContent = 'End time is required.';
        addClassError.hidden = false;
        return;
      }
      if (daysNodes.length === 0) {
        addClassError.textContent = 'At least one day must be selected.';
        addClassError.hidden = false;
        return;
      }
      
      try {
        await createClass({ name: subject, subject: subject, start_time: startTime, end_time: endTime, days: days, professor: professor });
        addSubjectToDropdown(subject);
        addClassForm.reset();
        addClassPanel.hidden = true;
        await refreshClasses();
      } catch (err) {
        addClassError.textContent = err.message || 'Failed to save class.';
        addClassError.hidden = false;
      }
    });

    refreshClasses();

    // Dropdown handled by shared assets/dashboard.js
  </script>
  <script src="assets/dashboard.js"></script>
</body>
</html>
