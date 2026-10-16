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

  // Writing toolbar for long text fields: buttons, keyboard shortcuts and a live preview.
  var previewUrl = (document.querySelector('form[action$="/admin/logout"]') || {}).action;
  previewUrl = previewUrl ? previewUrl.replace(/\/admin\/logout$/, '/admin/preview') : null;
  Array.prototype.forEach.call(document.querySelectorAll('textarea[data-md]'), function (ta) {
    var form = ta.form;
    var token = form && form.querySelector('input[name=_csrf]');
    var bar = document.createElement('div');
    bar.className = 'md-toolbar';
    bar.setAttribute('role', 'toolbar');
    bar.setAttribute('aria-label', 'Formatting');
    var preview = document.createElement('div');
    preview.className = 'md-preview prose rich';
    preview.hidden = true;
    var tools = [
      ['B', 'Bold (Ctrl+B)', function () { wrap('**', '**', 'bold text'); }, 'md-b'],
      ['I', 'Italic (Ctrl+I)', function () { wrap('*', '*', 'italic text'); }, 'md-i'],
      ['Link', 'Add a link (Ctrl+K)', link],
      ['H2', 'Heading', function () { lines('## '); }],
      ['H3', 'Smaller heading', function () { lines('### '); }],
      ['• List', 'Bullet list (Ctrl+Shift+8)', function () { lines('- '); }],
      ['1. List', 'Numbered list (Ctrl+Shift+7)', function () { lines('', true); }],
      ['Quote', 'Quote', function () { lines('> '); }],
      ['Justify', 'Justify the selected paragraphs', function () { block('justify'); }],
      ['Center', 'Center the selected text', function () { block('center'); }],
      ['Line', 'Divider line', function () { insert('\n\n---\n\n'); }]
    ];
    tools.forEach(function (t) {
      var b = document.createElement('button');
      b.type = 'button';
      b.className = 'md-btn' + (t[3] ? ' ' + t[3] : '');
      b.textContent = t[0];
      b.title = t[1];
      b.setAttribute('aria-label', t[1]);
      b.addEventListener('click', function () { if (!preview.hidden) toggle(); t[2](); ta.focus(); });
      bar.appendChild(b);
    });
    var pv = document.createElement('button');
    pv.type = 'button';
    pv.className = 'md-btn md-preview-btn';
    pv.textContent = 'Preview';
    pv.setAttribute('aria-pressed', 'false');
    pv.addEventListener('click', toggle);
    if (previewUrl && token) bar.appendChild(pv);
    ta.parentNode.insertBefore(bar, ta);
    ta.parentNode.insertBefore(preview, ta.nextSibling);

    function sel() { return { s: ta.selectionStart, e: ta.selectionEnd, v: ta.value }; }
    function set(text, s, e) {
      ta.focus();
      ta.setRangeText(text, ta.selectionStart, ta.selectionEnd, 'end');
      if (s !== undefined) ta.setSelectionRange(s, e);
      ta.dispatchEvent(new Event('input', { bubbles: true }));
    }
    function wrap(a, b, placeholder) {
      var x = sel(), chosen = x.v.slice(x.s, x.e) || placeholder;
      set(a + chosen + b, x.s + a.length, x.s + a.length + chosen.length);
    }
    function insert(text) { set(text); }
    function link() {
      var x = sel(), label = x.v.slice(x.s, x.e) || 'link text';
      var url = window.prompt('Link address. Use https://... for other websites, or /pricing for a page on this site.', 'https://');
      if (!url || url === 'https://') return;
      set('[' + label + '](' + url.trim() + ')');
    }
    function lines(prefix, numbered) {
      var x = sel(), start = x.v.lastIndexOf('\n', x.s - 1) + 1, endIdx = x.v.indexOf('\n', x.e), end = endIdx === -1 ? x.v.length : endIdx;
      var block = x.v.slice(start, end).split('\n').map(function (l, i) {
        var clean = l.replace(/^(#{2,4}\s|[-*]\s|\d+[.)]\s|>\s?)/, '');
        return (numbered ? (i + 1) + '. ' : prefix) + (clean || (numbered || prefix === '- ' ? 'item' : 'text'));
      }).join('\n');
      ta.setSelectionRange(start, end);
      set(block, start, start + block.length);
    }
    function block(kind) {
      var x = sel(), chosen = x.v.slice(x.s, x.e) || 'Your text here';
      var before = x.s > 0 && x.v[x.s - 1] !== '\n' ? '\n\n' : '';
      set(before + ':::' + kind + '\n' + chosen + '\n:::\n');
    }
    function toggle() {
      var show = preview.hidden;
      if (show) {
        var body = new FormData();
        body.append('_csrf', token.value);
        body.append('text', ta.value);
        preview.textContent = 'Loading preview...';
        fetch(previewUrl, { method: 'POST', body: body, credentials: 'same-origin' })
          .then(function (r) { return r.json(); })
          .then(function (d) { preview.innerHTML = d && d.ok ? (d.html || '<p class="muted">Nothing to preview yet.</p>') : 'Preview is not available. Please reload the page.'; })
          .catch(function () { preview.textContent = 'Preview is not available. Please check your connection.'; });
        preview.style.minHeight = ta.offsetHeight + 'px';
      }
      preview.hidden = !show;
      ta.hidden = show;
      pv.textContent = show ? 'Back to editing' : 'Preview';
      pv.setAttribute('aria-pressed', String(show));
      Array.prototype.forEach.call(bar.querySelectorAll('.md-btn:not(.md-preview-btn)'), function (b) { b.disabled = show; });
    }
    ta.addEventListener('keydown', function (e) {
      if (!(e.ctrlKey || e.metaKey)) return;
      var k = e.key.toLowerCase();
      if (k === 'b' && !e.shiftKey) { e.preventDefault(); wrap('**', '**', 'bold text'); }
      else if (k === 'i' && !e.shiftKey) { e.preventDefault(); wrap('*', '*', 'italic text'); }
      else if (k === 'k') { e.preventDefault(); link(); }
      else if (e.shiftKey && (e.code === 'Digit8' || k === '*')) { e.preventDefault(); lines('- '); }
      else if (e.shiftKey && (e.code === 'Digit7' || k === '&')) { e.preventDefault(); lines('', true); }
    });
  });
})();
