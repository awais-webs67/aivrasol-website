/* AivraSol admin helpers */
(function () {
  'use strict';
  var btn = document.querySelector('[data-admin-menu]');
  var side = document.getElementById('admin-side');
  var backdrop;
  function set(open) {
    side.classList.toggle('is-open', open);
    btn.setAttribute('aria-expanded', String(open));
    if (open) {
      backdrop = document.createElement('div');
      backdrop.className = 'admin-backdrop';
      backdrop.addEventListener('click', function () { set(false); });
      document.body.appendChild(backdrop);
    } else if (backdrop) {
      backdrop.remove();
      backdrop = null;
    }
  }
  if (btn && side) {
    btn.addEventListener('click', function () { set(!side.classList.contains('is-open')); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && side.classList.contains('is-open')) set(false); });
  }
  Array.prototype.forEach.call(document.querySelectorAll('form[data-confirm]'), function (form) {
    form.addEventListener('submit', function (e) {
      if (!window.confirm(form.getAttribute('data-confirm'))) e.preventDefault();
    });
  });
  // On an open, sending campaign page, ask the server to send the next email every minute (works without cron).
  var tick = document.querySelector('[data-campaign-tick]');
  var token = document.querySelector('input[name=_csrf]');
  if (tick && token && window.fetch) {
    setInterval(function () {
      if (document.hidden) return;
      var body = new FormData();
      body.append('_csrf', token.value);
      fetch(tick.getAttribute('data-campaign-tick'), { method: 'POST', body: body, credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (d) { if (d && d.report) tick.textContent = 'Background send: ' + d.report; })
        .catch(function () {});
    }, 60000);
  }
})();
