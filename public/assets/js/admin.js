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
})();
