/* Project chat for the client portal and the admin panel. Messages are shown with textContent, never as HTML. */
(function () {
  'use strict';
  var chat = document.querySelector('[data-chat]');
  if (!chat || !window.fetch) return;
  var endpoint = chat.getAttribute('data-chat');
  var log = chat.querySelector('[data-chat-log]');
  var empty = chat.querySelector('[data-chat-empty]');
  var form = chat.querySelector('[data-chat-form]');
  var status = chat.querySelector('[data-chat-status]');
  var fileInput = chat.querySelector('[data-chat-file]');
  var fileLabel = chat.querySelector('[data-chat-filename]');
  var lastId = 0;
  var timer = null;
  var busy = false;
  var clip = '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 11.5l-8.3 8.3a5 5 0 0 1-7.1-7.1l8.6-8.6a3.3 3.3 0 0 1 4.7 4.7l-8.6 8.6a1.7 1.7 0 0 1-2.4-2.4l7.9-7.9"/></svg>';

  function when(iso) {
    var d = new Date(iso);
    if (isNaN(d)) return '';
    var today = new Date();
    var time = d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    return d.toDateString() === today.toDateString() ? time : d.toLocaleDateString([], { day: 'numeric', month: 'short' }) + ', ' + time;
  }

  function render(m) {
    var box = document.createElement('div');
    box.className = 'msg' + (m.mine ? ' is-mine' : '');
    var head = document.createElement('div');
    head.className = 'msg-head';
    var who = document.createElement('b');
    who.textContent = m.mine ? 'You' : m.from;
    var at = document.createElement('time');
    at.dateTime = m.at;
    at.textContent = when(m.at);
    head.appendChild(who);
    head.appendChild(at);
    box.appendChild(head);
    if (m.body) {
      var body = document.createElement('p');
      body.className = 'msg-body';
      body.textContent = m.body;
      box.appendChild(body);
    }
    if (m.file) {
      var a = document.createElement('a');
      a.className = 'msg-file';
      a.href = m.file.url;
      a.innerHTML = clip;
      a.appendChild(document.createTextNode(m.file.name + ' (' + m.file.size + ')'));
      box.appendChild(a);
    }
    return box;
  }

  function load() {
    if (busy) return;
    busy = true;
    var nearBottom = log.scrollHeight - log.scrollTop - log.clientHeight < 80;
    fetch(endpoint + '?after=' + lastId, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (data) {
        if (!data || !data.messages) return;
        data.messages.forEach(function (m) {
          if (m.id <= lastId) return;
          lastId = m.id;
          log.appendChild(render(m));
        });
        if (empty) empty.hidden = log.querySelectorAll('.msg').length > 0;
        if (data.messages.length && (nearBottom || lastId === data.messages[data.messages.length - 1].id)) log.scrollTop = log.scrollHeight;
      })
      .catch(function () {})
      .then(function () { busy = false; schedule(); });
  }

  function schedule() {
    clearTimeout(timer);
    timer = setTimeout(load, document.hidden ? 30000 : 6000);
  }

  document.addEventListener('visibilitychange', function () { if (!document.hidden) load(); });

  if (fileInput) {
    fileInput.addEventListener('change', function () {
      var f = fileInput.files && fileInput.files[0];
      fileLabel.textContent = f ? f.name : 'Attach a file';
      if (f && f.size > 10 * 1024 * 1024) {
        status.textContent = 'Files must be 10 MB or smaller.';
        fileInput.value = '';
        fileLabel.textContent = 'Attach a file';
      }
    });
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    var text = form.elements.body.value.trim();
    var hasFile = fileInput && fileInput.files && fileInput.files.length;
    if (!text && !hasFile) { status.textContent = 'Write a message or attach a file.'; return; }
    var btn = form.querySelector('button[type=submit]');
    btn.disabled = true;
    status.textContent = '';
    fetch(endpoint, { method: 'POST', body: new FormData(form), credentials: 'same-origin', headers: { Accept: 'application/json' } })
      .then(function (r) { return r.json().catch(function () { return { ok: false, message: 'Something went wrong. Please try again.' }; }); })
      .then(function (data) {
        if (data.ok) {
          form.elements.body.value = '';
          if (fileInput) { fileInput.value = ''; fileLabel.textContent = 'Attach a file'; }
          busy = false;
          load();
        } else {
          status.textContent = data.message || 'The message was not sent. Please try again.';
        }
      })
      .catch(function () { status.textContent = 'No connection. Please check your internet and try again.'; })
      .then(function () { btn.disabled = false; });
  });

  form.elements.body.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) form.requestSubmit ? form.requestSubmit() : form.dispatchEvent(new Event('submit'));
  });

  load();
})();
