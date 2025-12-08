// assets/dashboard.js
// Client logic: FullCalendar setup + tasks list + persistence via /api/calendar.php and /api/tasks.php

(() => {
  // Basic helpers
  function jsonFetch(url, opts = {}) {
    opts.headers = opts.headers || {};
    if (opts.body && typeof opts.body === 'object') {
      opts.headers['Content-Type'] = 'application/json';
      opts.body = JSON.stringify(opts.body);
    }
    return fetch(url, opts).then(r => {
      if (!r.ok) return r.json().then(err => Promise.reject(err));
      return r.json();
    });
  }

  // Calendar init
  const calendarEl = document.getElementById('calendar');
  const Calendar = FullCalendar.Calendar;
  const calendar = new Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek,timeGridDay'
    },
    selectable: true,
    editable: true,
    navLinks: true,
    nowIndicator: true,
    select: function(info) {
      const title = prompt('Event title:');
      if (title) {
        const payload = { title, start: info.startStr, end: info.endStr, allDay: info.allDay };
        jsonFetch('/api/calendar.php', { method: 'POST', body: payload })
          .then(ev => {
            calendar.addEvent(ev);
          })
          .catch(err => alert(err.error || 'Could not create event'));
      }
      calendar.unselect();
    },
    eventClick: function(info) {
      const ev = info.event;
      const action = prompt('Edit title, or type DELETE to remove event', ev.title);
      if (action === null) return;
      if (action === 'DELETE') {
        jsonFetch('/api/calendar.php', { method: 'DELETE', body: { id: ev.id } })
          .then(() => ev.remove())
          .catch(err => alert(err.error || 'Could not delete'));
        return;
      }
      // update title
      jsonFetch('/api/calendar.php', { method: 'PUT', body: { id: ev.id, title: action, start: ev.startStr, end: ev.endStr, allDay: ev.allDay } })
        .then(updated => {
          ev.setProp('title', updated.title);
        })
        .catch(err => alert(err.error || 'Could not update'));
    },
    eventDrop: function(info) {
      const ev = info.event;
      jsonFetch('/api/calendar.php', { method: 'PUT', body: { id: ev.id, title: ev.title, start: ev.startStr, end: ev.endStr, allDay: ev.allDay } })
        .catch(err => { alert(err.error || 'Could not move event'); info.revert(); });
    },
    eventResize: function(info) {
      const ev = info.event;
      jsonFetch('/api/calendar.php', { method: 'PUT', body: { id: ev.id, title: ev.title, start: ev.startStr, end: ev.endStr, allDay: ev.allDay } })
        .catch(err => { alert(err.error || 'Could not resize event'); info.revert(); });
    },
    events: function(fetchInfo, successCallback, failureCallback) {
      const qs = new URLSearchParams({
        start: fetchInfo.startStr,
        end: fetchInfo.endStr
      });
      fetch('/api/calendar.php?' + qs.toString())
        .then(r => r.json())
        .then(data => successCallback(data))
        .catch(err => failureCallback(err));
    }
  });

  calendar.render();

  // Quick add event form
  const quickForm = document.getElementById('quick-event-form');
  quickForm?.addEventListener('submit', function(e) {
    e.preventDefault();
    const title = document.getElementById('qe-title').value.trim();
    const start = document.getElementById('qe-start').value;
    const end = document.getElementById('qe-end').value || null;
    if (!title || !start) return alert('Title and start are required');
    jsonFetch('/api/calendar.php', { method: 'POST', body: { title, start, end, allDay: false } })
      .then(ev => {
        calendar.addEvent(ev);
        quickForm.reset();
      })
      .catch(err => alert(err.error || 'Could not create event'));
  });

  // Tasks list
  const taskList = document.getElementById('task-list');
  const taskForm = document.getElementById('task-add-form');

  function loadTasks() {
    jsonFetch('/api/tasks.php')
      .then(tasks => {
        taskList.innerHTML = '';
        tasks.forEach(t => {
          const li = document.createElement('li');
          li.className = 'task-item';
          li.dataset.id = t.id;
          li.style.display = 'flex';
          li.style.justifyContent = 'space-between';
          li.style.alignItems = 'center';

          const left = document.createElement('div');
          left.style.display = 'flex';
          left.style.flexDirection = 'column';
          left.innerHTML = `<strong style="font-weight:700">${escapeHtml(t.title)}</strong><small style="color:#6b7a8a">${t.due_date ? (new Date(t.due_date)).toLocaleString() : ''}</small>`;

          const right = document.createElement('div');
          right.style.display = 'flex';
          right.style.gap = '8px';
          const doneBtn = document.createElement('button');
          doneBtn.className = 'btn';
          doneBtn.textContent = t.status === 'done' ? '✔' : '○';
          doneBtn.title = 'Toggle complete';
          doneBtn.addEventListener('click', () => {
            const newStatus = t.status === 'done' ? 'open' : 'done';
            jsonFetch('/api/tasks.php', { method: 'PUT', body: { id: t.id, title: t.title, details: t.details, due_date: t.due_date, status: newStatus } })
              .then(() => loadTasks())
              .catch(err => alert(err.error || 'Could not update task'));
          });
          const delBtn = document.createElement('button');
          delBtn.className = 'btn';
          delBtn.textContent = 'Delete';
          delBtn.addEventListener('click', () => {
            if (!confirm('Delete this task?')) return;
            jsonFetch('/api/tasks.php', { method: 'DELETE', body: { id: t.id } })
              .then(() => loadTasks())
              .catch(err => alert(err.error || 'Could not delete'));
          });
          right.appendChild(doneBtn);
          right.appendChild(delBtn);

          li.appendChild(left);
          li.appendChild(right);
          taskList.appendChild(li);
        });
      })
      .catch(err => console.error('load tasks error', err));
  }

  taskForm?.addEventListener('submit', function(e) {
    e.preventDefault();
    const title = document.getElementById('task-title').value.trim();
    const due = document.getElementById('task-due').value || null;
    if (!title) return;
    jsonFetch('/api/tasks.php', { method: 'POST', body: { title, due_date: due ? (new Date(due)).toISOString().slice(0,19).replace('T',' ') : null } })
      .then(() => {
        taskForm.reset();
        loadTasks();
      })
      .catch(err => alert(err.error || 'Could not add task'));
  });

  // escape helper
  function escapeHtml(s) {
    if (!s) return '';
    return s.replace(/[&<>"']/g, function(m) { return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]); });
  }

  // initial load
  loadTasks();

})();