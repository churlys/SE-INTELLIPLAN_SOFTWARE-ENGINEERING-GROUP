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
      <a class="nav-item <?php echo ($currentPage === 'dashboard.php') ? 'active' : ''; ?>" href="dashboard.php"><span class="nav-icon"><img src="assets/icon-dashboard.svg" alt="" aria-hidden="true" width="18" height="18"></span><span class="nav-label">Dashboard</span></a>
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
      <a class="nav-item" href="#" onclick="event.preventDefault(); document.getElementById('logoutForm').submit();">
        <span class="nav-icon"><img src="assets/logOUT.png" alt="" aria-hidden="true"></span>
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
            <span class="tasks-title-icon" aria-hidden="true">📝</span>
            <h2>Exams</h2>
          </div>
        </div>

        <div class="tasks-add">
          <form id="addExamForm" class="tasks-add-form" autocomplete="off">
            <div class="tasks-add-grid">
              <label class="tasks-field">
                <span class="tasks-label">Title</span>
                <input id="examTitle" type="text" placeholder="Enter exam title" required>
              </label>
              <label class="tasks-field">
                <span class="tasks-label">Subject</span>
                <input id="examSubject" type="text" placeholder="e.g. Math" list="examSubjectList">
                <datalist id="examSubjectList">
                  <option value="Math"></option>
                  <option value="English"></option>
                  <option value="Science"></option>
                  <option value="PE"></option>
                </datalist>
              </label>
              <label class="tasks-field">
                <span class="tasks-label">Exam Date</span>
                <input id="examDate" type="date" required>
              </label>
              <label class="tasks-field">
                <span class="tasks-label">Exam Time</span>
                <input id="examTime" type="time">
              </label>
              <label class="tasks-field">
                <span class="tasks-label">Location</span>
                <input id="examLocation" type="text" placeholder="Optional">
              </label>
              <label class="tasks-field tasks-field-full">
                <span class="tasks-label">Notes</span>
                <textarea id="examNotes" rows="3" placeholder="Optional notes"></textarea>
              </label>

              <label class="tasks-field tasks-field-full">
                <span class="tasks-label">Exam File (optional)</span>
                <input id="examFile" type="file" accept=".pdf,.doc,.docx,.txt,.png,.jpg,.jpeg">
              </label>
            </div>
            <div class="tasks-add-actions">
              <button type="submit" class="tasks-btn tasks-btn-primary">Save</button>
            </div>
            <div id="addExamError" class="tasks-error" hidden></div>
          </form>
        </div>

        <div class="panel">
          <div id="examsList" class="panel-body muted">Loading exams…</div>
        </div>
      </div>
    </section>
  </main>

  <form id="logoutForm" method="POST" action="logout.php" style="display:none;">
    <?php if (function_exists('csrf_token')): ?>
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
    <?php endif; ?>
  </form>

  <script src="assets/dashboard.js"></script>
  <script>
    (function(){
      const listEl = document.getElementById('examsList');
      const form = document.getElementById('addExamForm');
      if (!listEl || !form) return;

      const titleEl = document.getElementById('examTitle');
      const subjectEl = document.getElementById('examSubject');
      const dateEl = document.getElementById('examDate');
      const timeEl = document.getElementById('examTime');
      const locationEl = document.getElementById('examLocation');
      const notesEl = document.getElementById('examNotes');
      const errEl = document.getElementById('addExamError');

      function escapeHtml(s){
        return (s + '')
          .replace(/&/g,'&amp;')
          .replace(/</g,'&lt;')
          .replace(/>/g,'&gt;')
          .replace(/"/g,'&quot;')
          .replace(/'/g,'&#039;');
      }

      function formatTimeAmerican(timeStr){
        if (!timeStr) return '';
        const parts = String(timeStr).split(':');
        const h = parseInt(parts[0] || '0', 10);
        const m = parseInt(parts[1] || '0', 10);
        const ampm = h >= 12 ? 'PM' : 'AM';
        const displayHour = (h % 12) || 12;
        return `${displayHour}:${String(m).padStart(2,'0')} ${ampm}`;
      }

      async function fetchExams(){
        const res = await fetch('lib/api/exams.php', { credentials: 'same-origin' });
        if (!res.ok) throw new Error('Network error ' + res.status);
        const data = await res.json();
        return Array.isArray(data) ? data : [];
      }

      function render(exams){
        const sorted = (exams || []).slice().sort((a,b) => {
          const ad = String(a?.exam_date || '');
          const bd = String(b?.exam_date || '');
          if (ad && bd && ad !== bd) return ad.localeCompare(bd);
          const at = String(a?.exam_time || '');
          const bt = String(b?.exam_time || '');
          if (at && bt && at !== bt) return at.localeCompare(bt);
          return (b?.id || 0) - (a?.id || 0);
        });

        if (sorted.length === 0) {
          listEl.classList.add('muted');
          listEl.textContent = 'No exams to display.';
          return;
        }

        listEl.classList.remove('muted');
        listEl.innerHTML = '';
        sorted.slice(0, 25).forEach(x => {
          const row = document.createElement('div');
          row.className = 'dash-task-item';
          const meta = [];
          if (x?.subject) meta.push(String(x.subject));
          if (x?.exam_date) meta.push(String(x.exam_date));
          if (x?.exam_time) meta.push(formatTimeAmerican(String(x.exam_time)));
          if (x?.location) meta.push(String(x.location));

          row.innerHTML = `
            <div class="dash-task-title">${escapeHtml(x?.title || 'Untitled')}</div>
            ${meta.length ? `<div class="dash-task-meta">${escapeHtml(meta.join(' • '))}</div>` : ''}
          `;
          listEl.appendChild(row);
        });
      }

      async function refresh(){
        try {
          listEl.classList.add('muted');
          listEl.textContent = 'Loading exams…';
          const exams = await fetchExams();
          render(exams);
        } catch (e) {
          listEl.classList.add('muted');
          listEl.textContent = 'Failed to load exams.';
        }
      }

      form.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (errEl) { errEl.hidden = true; errEl.textContent = ''; }

        const payload = {
          title: (titleEl?.value || '').trim(),
          subject: (subjectEl?.value || '').trim(),
          exam_date: (dateEl?.value || '').trim(),
          exam_time: (timeEl?.value || '').trim(),
          location: (locationEl?.value || '').trim(),
          notes: (notesEl?.value || '').trim(),
        };

        const fileInput = document.getElementById('examFile');
        const selectedFile = (fileInput && fileInput.files && fileInput.files[0]) ? fileInput.files[0] : null;

        async function uploadExamFile(examId, file){
          const fd = new FormData();
          fd.append('exam_id', String(examId));
          fd.append('file', file);
          const res = await fetch('lib/api/exam_attachment.php', {
            method: 'POST',
            credentials: 'same-origin',
            body: fd,
          });
          const data = await res.json().catch(() => ({}));
          if (!res.ok) throw new Error(data?.error || ('Upload failed (' + res.status + ')'));
          return data;
        }

        try {
          const res = await fetch('lib/api/exams.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
          });
          const data = await res.json().catch(() => ({}));
          if (!res.ok) throw new Error(data?.error || ('Failed to save (' + res.status + ')'));

          if (selectedFile && data && data.id) {
            await uploadExamFile(data.id, selectedFile);
          }

          form.reset();
          await refresh();
        } catch (err) {
          if (errEl) {
            errEl.hidden = false;
            errEl.textContent = String(err?.message || err);
          }
        }
      });

      refresh();
    })();
  </script>
</body>
</html>
