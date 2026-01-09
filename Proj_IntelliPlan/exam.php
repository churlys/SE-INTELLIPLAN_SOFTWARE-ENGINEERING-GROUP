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
          <button type="button" class="tasks-add-btn" id="openAddExam">Add Exam</button>
        </div>

        <div class="tasks-tabs" role="tablist" aria-label="Exam filter">
          <button type="button" class="tasks-tab active" data-view="current" role="tab" aria-selected="true">Current</button>
          <button type="button" class="tasks-tab" data-view="past" role="tab" aria-selected="false">Completed</button>
          <button type="button" class="tasks-tab" data-view="overdue" role="tab" aria-selected="false">Overdue</button>
        </div>

        <div id="addExamPanel" class="tasks-add" hidden>
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
              <button type="button" class="tasks-btn" id="cancelAddExam">Cancel</button>
              <button type="submit" class="tasks-btn tasks-btn-primary">Save</button>
            </div>
            <div id="addExamError" class="tasks-error" hidden></div>
          </form>
        </div>

        <div class="tasks-section-label" id="examsSectionLabel">Upcoming (0)</div>
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
      const sectionLabelEl = document.getElementById('examsSectionLabel');
      const form = document.getElementById('addExamForm');
      if (!listEl || !form) return;

      const openAddExamBtn = document.getElementById('openAddExam');
      const addExamPanel = document.getElementById('addExamPanel');
      const cancelAddExamBtn = document.getElementById('cancelAddExam');

      const titleEl = document.getElementById('examTitle');
      const subjectEl = document.getElementById('examSubject');
      const dateEl = document.getElementById('examDate');
      const timeEl = document.getElementById('examTime');
      const locationEl = document.getElementById('examLocation');
      const notesEl = document.getElementById('examNotes');
      const errEl = document.getElementById('addExamError');

      // Add exam panel
      openAddExamBtn?.addEventListener('click', () => {
        if (!addExamPanel) return;
        addExamPanel.hidden = !addExamPanel.hidden;
        if (errEl) { errEl.hidden = true; errEl.textContent = ''; }
        if (!addExamPanel.hidden) titleEl?.focus();
      });
      cancelAddExamBtn?.addEventListener('click', () => {
        if (!addExamPanel) return;
        addExamPanel.hidden = true;
        if (errEl) { errEl.hidden = true; errEl.textContent = ''; }
        form.reset();
      });

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

      function isoToday(){
        const d = new Date();
        const yyyy = d.getFullYear();
        const mm = String(d.getMonth()+1).padStart(2,'0');
        const dd = String(d.getDate()).padStart(2,'0');
        return `${yyyy}-${mm}-${dd}`;
      }

      async function fetchExams(){
        const res = await fetch('lib/api/exams.php', { credentials: 'same-origin' });
        if (!res.ok) {
          const bodyText = await res.text().catch(() => '');
          let msg = 'Request failed (' + res.status + ')';
          if (bodyText) {
            try {
              const j = JSON.parse(bodyText);
              msg = j?.error || j?.detail || msg;
            } catch {
              msg = bodyText;
            }
          }
          throw new Error(msg);
        }
        const data = await res.json();
        return Array.isArray(data) ? data : [];
      }

      async function updateExam(payload){
        const res = await fetch('lib/api/exams.php', {
          method: 'PUT',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) throw new Error(data?.error || ('Request failed (' + res.status + ')'));
        return data;
      }

      async function deleteExam(id){
        const res = await fetch('lib/api/exams.php', {
          method: 'DELETE',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id }),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) throw new Error(data?.error || ('Request failed (' + res.status + ')'));
        return data;
      }

      let allExams = [];
      let currentView = 'current';

      function filteredExams(){
        const today = isoToday();
        const now = new Date();
        const nowTime = `${String(now.getHours()).padStart(2,'0')}:${String(now.getMinutes()).padStart(2,'0')}:${String(now.getSeconds()).padStart(2,'0')}`;
        return (allExams || []).filter(x => {
          const status = String(x?.status || 'scheduled').toLowerCase();
          const d = String(x?.exam_date || '').trim();
          const examTimeRaw = String(x?.exam_time || '').trim();
          const examTime = examTimeRaw ? (examTimeRaw.length === 5 ? (examTimeRaw + ':00') : examTimeRaw) : '';
          const isOverdueByTime = !!d && d === today && !!examTime && examTime < nowTime;

          if (currentView === 'past') return status === 'done';
          if (currentView === 'overdue') return status !== 'done' && ((!!d && d < today) || isOverdueByTime);
          // current
          return status !== 'done' && (!d || d > today || (d === today && (!examTime || !isOverdueByTime)));
        });
      }

      function render(){
        const exams = filteredExams();
        const sorted = (exams || []).slice().sort((a,b) => {
          const ad = String(a?.exam_date || '');
          const bd = String(b?.exam_date || '');
          if (ad && bd && ad !== bd) return ad.localeCompare(bd);
          const at = String(a?.exam_time || '');
          const bt = String(b?.exam_time || '');
          if (at && bt && at !== bt) return at.localeCompare(bt);
          return (b?.id || 0) - (a?.id || 0);
        });

        if (sectionLabelEl) {
          if (currentView === 'past') {
            sectionLabelEl.textContent = `Completed (${sorted.length}) — Auto-deletes after 24 hours`;
          } else if (currentView === 'overdue') {
            sectionLabelEl.textContent = `Overdue (${sorted.length}) — Auto-deletes after 24 hours`;
          } else {
            sectionLabelEl.textContent = `Upcoming (${sorted.length})`;
          }
        }

        if (sorted.length === 0) {
          listEl.classList.add('muted');
          listEl.textContent = 'No exams to display.';
          return;
        }

        listEl.classList.remove('muted');
        listEl.innerHTML = '';
        sorted.slice(0, 25).forEach(x => {
          const card = document.createElement('div');
          card.className = 'task-card';

          const left = document.createElement('div');
          left.className = 'task-left';

          const isDone = String(x?.status || 'scheduled').toLowerCase() === 'done';
          const check = document.createElement('button');
          check.type = 'button';
          check.className = 'task-check' + (isDone ? ' done' : '');
          check.setAttribute('aria-label', isDone ? 'Mark exam as not done' : 'Mark exam as done');
          check.addEventListener('click', async () => {
            try {
              const nextStatus = isDone ? 'scheduled' : 'done';
              await updateExam({
                id: x.id,
                title: x.title,
                subject: x.subject ?? null,
                exam_date: x.exam_date,
                exam_time: x.exam_time ?? null,
                location: x.location ?? null,
                notes: x.notes ?? null,
                status: nextStatus,
              });
              await refresh();
            } catch (e) {
              alert('Failed to update: ' + (e?.message || e));
            }
          });

          const main = document.createElement('div');
          main.className = 'task-main';
          const title = document.createElement('div');
          title.className = 'task-title';
          title.textContent = x?.title || 'Untitled';

          const meta = document.createElement('div');
          meta.className = 'task-meta';
          const parts = [];
          if (x?.subject) parts.push(String(x.subject));
          if (x?.exam_date) parts.push(String(x.exam_date));
          if (x?.exam_time) parts.push(formatTimeAmerican(String(x.exam_time)));
          if (x?.location) parts.push(String(x.location));
          meta.textContent = parts.join(' • ');

          main.appendChild(title);
          if (parts.length) main.appendChild(meta);

          left.appendChild(check);
          left.appendChild(main);

          const right = document.createElement('div');
          right.className = 'task-right';
          const showDelete = (currentView === 'overdue' || currentView === 'past');
          if (showDelete) {
            const del = document.createElement('button');
            del.type = 'button';
            del.className = 'task-delete';
            del.setAttribute('aria-label', 'Delete exam');
            del.title = 'Delete exam';
            del.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path></svg>';
            del.addEventListener('click', async () => {
              const msg = currentView === 'overdue'
                ? 'Delete this overdue exam? This cannot be undone.'
                : 'Delete this completed exam? This cannot be undone.';
              if (!confirm(msg)) return;
              try {
                await deleteExam(x.id);
                await refresh();
              } catch (e) {
                alert('Failed to delete: ' + (e?.message || e));
              }
            });
            right.appendChild(del);
          }

          card.appendChild(left);
          card.appendChild(right);
          listEl.appendChild(card);
        });
      }

      async function refresh(){
        try {
          listEl.classList.add('muted');
          listEl.textContent = 'Loading exams…';
          const exams = await fetchExams();
          allExams = Array.isArray(exams) ? exams : [];
          render();
        } catch (e) {
          listEl.classList.add('muted');
          listEl.textContent = 'Failed to load exams.';
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
          if (addExamPanel) addExamPanel.hidden = true;
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
