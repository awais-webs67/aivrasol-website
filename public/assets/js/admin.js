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

  // Visual editor for long text fields: what you see is what visitors see. The text is still saved as
  // Markdown in the hidden field, so the website renders it exactly as before. Without JavaScript the field stays a plain text box.
  var previewUrl = (document.querySelector('form[action$="/admin/logout"]') || {}).action;
  previewUrl = previewUrl ? previewUrl.replace(/\/admin\/logout$/, '/admin/preview') : null;
  var SVG = {
    undo: '<path d="M9 14L4 9l5-5"/><path d="M4 9h10.5a5.5 5.5 0 0 1 0 11H11"/>',
    redo: '<path d="M15 14l5-5-5-5"/><path d="M20 9H9.5a5.5 5.5 0 0 0 0 11H13"/>',
    ul: '<path d="M9 6h11M9 12h11M9 18h11"/><circle cx="4.5" cy="6" r="1.2"/><circle cx="4.5" cy="12" r="1.2"/><circle cx="4.5" cy="18" r="1.2"/>',
    ol: '<path d="M10 6h10M10 12h10M10 18h10M4 4.5h1.5V9M4 9h3M4 14.5h3l-3 4h3"/>',
    quote: '<path d="M5 17h4l1-4V7H4v6h3zM14 17h4l1-4V7h-6v6h3z"/>',
    link: '<path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/>',
    unlink: '<path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1M4 4l16 16"/>',
    left: '<path d="M4 6h16M4 10h10M4 14h16M4 18h10"/>',
    center: '<path d="M4 6h16M7 10h10M4 14h16M7 18h10"/>',
    justify: '<path d="M4 6h16M4 10h16M4 14h16M4 18h16"/>',
    hr: '<path d="M4 12h16"/><path d="M8 7h8M8 17h8" opacity=".4"/>',
    clear: '<path d="M6 5h12M12 5l-3 14M4 20l16-16"/>',
    code: '<path d="M8 8l-4 4 4 4M16 8l4 4-4 4"/>'
  };
  function svg(name) { return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + SVG[name] + '</svg>'; }
  var BLOCKS = /^(P|DIV|H1|H2|H3|H4|H5|H6|UL|OL|LI|BLOCKQUOTE|PRE|HR|TABLE|SECTION|ARTICLE)$/;
  var ALIGN = /\btext-(center|justify|right)\b/;

  // Parse HTML in a separate, inert document: nothing in it runs or loads.
  function parse(html) {
    html = html.replace(/<style[\s\S]*?<\/style>/gi, '').replace(/\sstyle\s*=\s*("[^"]*"|'[^']*'|[^\s>]+)/gi, '');
    return new DOMParser().parseFromString('<!doctype html><body>' + html, 'text/html').body;
  }
  function safeHref(u) { u = (u || '').trim(); return /^(https?:\/\/|mailto:|\/|#)/i.test(u) && !/[\s"'<>`]/.test(u) ? u : ''; }
  // Keep only the tags the website understands. Everything else is unwrapped to its text.
  function clean(root) {
    var keep = { P: 1, BR: 1, STRONG: 1, B: 1, EM: 1, I: 1, A: 1, UL: 1, OL: 1, LI: 1, H2: 1, H3: 1, H4: 1, BLOCKQUOTE: 1, HR: 1, CODE: 1, PRE: 1, IMG: 1, DIV: 1 };
    Array.prototype.slice.call(root.querySelectorAll('*')).forEach(function (el) {
      if (!el.parentNode) return;
      var tag = el.tagName;
      if (/^(SCRIPT|STYLE|META|LINK|TITLE|IFRAME|OBJECT|svg|SVG)$/.test(tag)) { el.remove(); return; }
      if (tag === 'H1') tag = 'H2';
      if (tag === 'H5' || tag === 'H6') tag = 'H4';
      if (!keep[tag]) {
        if (BLOCKS.test(el.tagName)) { var p = document.createElement('p'); while (el.firstChild) p.appendChild(el.firstChild); el.replaceWith(p); }
        else { while (el.firstChild) el.parentNode.insertBefore(el.firstChild, el); el.remove(); }
        return;
      }
      if (tag !== el.tagName) { var n = document.createElement(tag); while (el.firstChild) n.appendChild(el.firstChild); el.replaceWith(n); el = n; }
      var cls = el.tagName === 'DIV' && (el.className.match(ALIGN) || [])[0];
      Array.prototype.slice.call(el.attributes).forEach(function (at) {
        var ok = (el.tagName === 'A' && at.name === 'href' && safeHref(at.value)) || (el.tagName === 'IMG' && (at.name === 'alt' || (at.name === 'src' && /^(https:\/\/|\/)/.test(at.value))));
        if (!ok) el.removeAttribute(at.name);
      });
      if (el.tagName === 'IMG' && !el.getAttribute('src')) el.remove();
      if (cls) el.className = cls;
    });
    return root;
  }

  // HTML from the editor to the Markdown the website stores.
  function toMarkdown(root, base) {
    function href(u) {
      u = safeHref(u);
      if (base && base !== '/' && u.indexOf(base) === 0) u = '/' + u.slice(base.length);
      return u.replace(/[\s()]/g, encodeURIComponent);
    }
    function inline(node) {
      var out = '';
      Array.prototype.forEach.call(node.childNodes, function (c) {
        if (c.nodeType === 3) { out += c.nodeValue.replace(/[\s ]+/g, ' '); return; }
        if (c.nodeType !== 1) return;
        var t = c.tagName, inner;
        if (t === 'BR') { out += '\n'; return; }
        if (t === 'IMG') { var src = href(c.getAttribute('src')); if (src) out += '![' + (c.getAttribute('alt') || '') + '](' + src + ')'; return; }
        inner = inline(c);
        if (t === 'STRONG' || t === 'B' || t === 'EM' || t === 'I') {
          var mark = (t === 'STRONG' || t === 'B') ? '**' : '*';
          var m = inner.match(/^(\s*)([\s\S]*?)(\s*)$/);
          out += m[2] ? m[1] + mark + m[2] + mark + m[3] : inner;
        } else if (t === 'A') {
          var h = href(c.getAttribute('href'));
          out += h && inner.trim() ? '[' + inner.trim().replace(/[\[\]]/g, '') + '](' + h + ')' : inner;
        } else if (t === 'CODE') {
          out += '`' + c.textContent.replace(/`/g, '') + '`';
        } else if (BLOCKS.test(t)) {
          out += (out && !/\n$/.test(out) ? '\n' : '') + inner + '\n';
        } else {
          out += inner;
        }
      });
      return out;
    }
    function lineText(s) { return s.split('\n').map(function (l) { return l.trim(); }).filter(Boolean).join('\n'); }
    function blocks(node) {
      var out = [], loose = null;
      function flush() { if (loose) { var t = lineText(inline(loose)); if (t) out.push(t); loose = null; } }
      Array.prototype.forEach.call(node.childNodes, function (c) {
        var t = c.nodeType === 1 ? c.tagName : '';
        if (c.nodeType === 3 || (t && !BLOCKS.test(t))) {
          if (!loose) loose = document.createElement('p');
          loose.appendChild(c.cloneNode(true));
          return;
        }
        if (!t) return;
        flush();
        if (t === 'DIV' && ALIGN.test(c.className)) {
          var innerBlocks = blocks(c);
          if (innerBlocks) out.push(':::' + c.className.match(ALIGN)[1] + '\n' + innerBlocks + '\n:::');
        } else if (t === 'DIV' || t === 'SECTION' || t === 'ARTICLE') {
          var nested = c.querySelector('p, div, h2, h3, h4, ul, ol, blockquote, hr, pre');
          var x = nested ? blocks(c) : lineText(inline(c));
          if (x) out.push(x);
        } else if (/^H[1-6]$/.test(t)) {
          var lvl = Math.min(4, Math.max(2, +t[1]));
          var ht = inline(c).replace(/\s+/g, ' ').trim();
          if (ht) out.push(new Array(lvl + 1).join('#') + ' ' + ht);
        } else if (t === 'UL' || t === 'OL') {
          var n = 0, items = [];
          Array.prototype.forEach.call(c.children, function (li) {
            var it = inline(li).replace(/\s+/g, ' ').trim();
            if (it) items.push((t === 'OL' ? (++n) + '. ' : '- ') + it);
          });
          if (items.length) out.push(items.join('\n'));
        } else if (t === 'BLOCKQUOTE') {
          var q = inline(c).replace(/\s+/g, ' ').trim();
          if (q) out.push('> ' + q);
        } else if (t === 'HR') {
          out.push('---');
        } else if (t === 'PRE') {
          out.push('```\n' + c.textContent.replace(/\n$/, '') + '\n```');
        } else {
          var p = lineText(inline(c));
          if (p) out.push(p);
        }
      });
      flush();
      return out.join('\n\n');
    }
    return blocks(root);
  }

  // Caret position as plain character offsets, so undo can put it back.
  function caretGet(ed) {
    var s = window.getSelection();
    if (!s.rangeCount || !ed.contains(s.anchorNode)) return null;
    var r = s.getRangeAt(0), pre = document.createRange();
    pre.selectNodeContents(ed);
    pre.setEnd(r.startContainer, r.startOffset);
    var start = pre.toString().length;
    return [start, start + r.toString().length];
  }
  function caretSet(ed, pos) {
    if (!pos) return;
    var walker = document.createTreeWalker(ed, NodeFilter.SHOW_TEXT), node, seen = 0, range = document.createRange(), started = false;
    range.selectNodeContents(ed);
    range.collapse(false);
    while ((node = walker.nextNode())) {
      var len = node.nodeValue.length;
      if (!started && pos[0] <= seen + len) { range.setStart(node, pos[0] - seen); started = true; }
      if (started && pos[1] <= seen + len) { range.setEnd(node, pos[1] - seen); break; }
      seen += len;
    }
    var s = window.getSelection();
    s.removeAllRanges();
    s.addRange(range);
  }

  Array.prototype.forEach.call(document.querySelectorAll('textarea[data-md]'), function (ta) {
    var base = ta.getAttribute('data-base') || '/';
    var form = ta.form;
    var token = form && form.querySelector('input[name=_csrf]');
    var wrap = document.createElement('div');
    wrap.className = 'wys';
    var bar = document.createElement('div');
    bar.className = 'wys-bar';
    bar.setAttribute('role', 'toolbar');
    bar.setAttribute('aria-label', 'Formatting');
    var ed = document.createElement('div');
    ed.className = 'wys-area rich';
    ed.contentEditable = 'true';
    ed.setAttribute('role', 'textbox');
    ed.setAttribute('aria-multiline', 'true');
    var label = ta.id && document.querySelector('label[for="' + ta.id + '"]');
    if (label) { label.id = label.id || ta.id + '-label'; ed.setAttribute('aria-labelledby', label.id); label.addEventListener('click', function () { if (!ta.hidden) return; ed.focus(); }); }
    ed.setAttribute('data-placeholder', 'Start writing here...');
    ed.innerHTML = clean(parse(ta.getAttribute('data-html') || '')).innerHTML;
    ta.parentNode.insertBefore(wrap, ta);
    wrap.appendChild(bar);
    wrap.appendChild(ed);
    wrap.appendChild(ta);
    ta.hidden = true;
    ta.classList.add('wys-source');
    var lastMd = ta.value;

    function sync() {
      if (ta.hidden) {
        ta.value = toMarkdown(ed, base);
        lastMd = ta.value;
      }
      ed.classList.toggle('is-empty', ed.textContent.trim() === '' && !ed.querySelector('img, hr, li'));
    }

    // Undo and redo with our own history, so toolbar changes undo just like typing.
    var hist = [], at = -1, timer = null;
    function snap() {
      clearTimeout(timer); timer = null;
      var html = ed.innerHTML;
      if (at >= 0 && hist[at].html === html) { hist[at].caret = caretGet(ed) || hist[at].caret; return; }
      hist = hist.slice(0, at + 1);
      hist.push({ html: html, caret: caretGet(ed) });
      if (hist.length > 200) hist.shift();
      at = hist.length - 1;
      state();
    }
    function restore(i) {
      at = i;
      ed.innerHTML = hist[i].html;
      ed.focus();
      caretSet(ed, hist[i].caret);
      sync();
      state();
    }
    function undo() { if (timer) snap(); if (at > 0) restore(at - 1); }
    function redo() { if (at < hist.length - 1) restore(at + 1); }

    function exec(cmd, val) {
      if (!ta.hidden) return;
      ed.focus();
      snap();
      document.execCommand(cmd, false, val === undefined ? null : val);
      after();
    }
    function after() {
      var sel = window.getSelection(), keep = sel.rangeCount && ed.contains(sel.anchorNode) ? [sel.anchorNode, sel.anchorOffset, sel.focusNode, sel.focusOffset] : null, moved = false;
      // Browsers sometimes put a list or heading inside a paragraph. Lift it out so the structure stays simple.
      Array.prototype.forEach.call(ed.querySelectorAll('p'), function (p) {
        if (!p.querySelector('ul, ol, h2, h3, h4, blockquote, div, p, hr, pre')) return;
        var run = null;
        while (p.firstChild) {
          var c = p.firstChild;
          if (c.nodeType === 1 && BLOCKS.test(c.tagName)) { run = null; p.parentNode.insertBefore(c, p); }
          else if (c.nodeType === 3 && !c.nodeValue.trim() && !run) { c.remove(); }
          else { if (!run) { run = document.createElement('p'); p.parentNode.insertBefore(run, p); } run.appendChild(c); }
        }
        p.remove();
        moved = true;
      });
      if (moved && keep && ed.contains(keep[0]) && ed.contains(keep[2])) { try { sel.setBaseAndExtent(keep[0], keep[1], keep[2], keep[3]); } catch (e) { /* node gone */ } }
      Array.prototype.forEach.call(ed.querySelectorAll('[style]'), function (el) { el.removeAttribute('style'); });
      Array.prototype.forEach.call(ed.querySelectorAll('span, font'), function (el) {
        while (el.firstChild) el.parentNode.insertBefore(el.firstChild, el);
        el.remove();
      });
      sync();
      snap();
    }
    function topOf(node) {
      while (node && node.parentNode !== ed) node = node.parentNode;
      return node;
    }
    function align(kind) {
      if (!ta.hidden) return;
      ed.focus();
      var s = window.getSelection();
      if (!s.rangeCount || !ed.contains(s.anchorNode)) return;
      snap();
      var r = s.getRangeAt(0), a = topOf(r.startContainer), b = topOf(r.endContainer), pos = caretGet(ed);
      if (!a) {
        var p = document.createElement('p');
        p.appendChild(document.createElement('br'));
        ed.appendChild(p);
        a = b = p;
      }
      var boxed = a === b && a.nodeType === 1 && a.tagName === 'DIV' && ALIGN.test(a.className);
      if (boxed) {
        if (kind === 'left' || a.className.indexOf('text-' + kind) !== -1) {
          while (a.firstChild) ed.insertBefore(a.firstChild, a);
          a.remove();
        } else {
          a.className = 'text-' + kind;
        }
      } else if (kind !== 'left') {
        var box = document.createElement('div');
        box.className = 'text-' + kind;
        ed.insertBefore(box, a);
        var n = a, stop = b.nextSibling;
        while (n && n !== stop) {
          var next = n.nextSibling;
          if (n.nodeType === 1 && n.tagName === 'DIV' && ALIGN.test(n.className)) { while (n.firstChild) box.appendChild(n.firstChild); n.remove(); }
          else if (n.nodeType === 3 || !BLOCKS.test(n.tagName)) { var pp = document.createElement('p'); pp.appendChild(n); box.appendChild(pp); }
          else box.appendChild(n);
          n = next;
        }
      } else {
        Array.prototype.forEach.call(ed.querySelectorAll('div[class*="text-"]'), function (d) {
          if (!s.containsNode(d, true)) return;
          while (d.firstChild) d.parentNode.insertBefore(d.firstChild, d);
          d.remove();
        });
      }
      caretSet(ed, pos);
      after();
    }
    // Bullet and numbered lists, done by hand because browsers build them in odd ways.
    function list(tag) {
      if (!ta.hidden) return;
      ed.focus();
      var sel = window.getSelection();
      if (!sel.rangeCount || !ed.contains(sel.anchorNode)) return;
      snap();
      var keep = [sel.anchorNode, sel.anchorOffset, sel.focusNode, sel.focusOffset];
      var r = sel.getRangeAt(0);
      function blockOf(n) {
        while (n && n !== ed) {
          if (n.nodeType === 1 && (n.tagName === 'LI' || ((/^(P|H2|H3|H4|BLOCKQUOTE|DIV|UL|OL)$/.test(n.tagName)) && (n.parentNode === ed || ALIGN.test(n.parentNode.className || ''))))) {
            if (n.tagName === 'DIV' && ALIGN.test(n.className)) return null;
            return n;
          }
          n = n.parentNode;
        }
        return null;
      }
      var a = blockOf(r.startContainer), b = blockOf(r.endContainer);
      if (!a) {
        a = b = document.createElement('p');
        a.appendChild(document.createElement('br'));
        ed.appendChild(a);
        keep = [a, 0, a, 0];
      }
      if (a.tagName === 'LI') {
        var lst = a.parentNode;
        if (lst.tagName === tag) {
          Array.prototype.slice.call(lst.children).forEach(function (li) {
            var p = document.createElement('p');
            while (li.firstChild) p.appendChild(li.firstChild);
            lst.parentNode.insertBefore(p, lst);
          });
          lst.remove();
        } else {
          var swap = document.createElement(tag);
          while (lst.firstChild) swap.appendChild(lst.firstChild);
          lst.replaceWith(swap);
        }
      } else {
        if (b.tagName === 'LI') b = b.parentNode;
        if (b.parentNode !== a.parentNode) b = a;
        var box = document.createElement(tag);
        a.parentNode.insertBefore(box, a);
        var n = a, stop = b.nextSibling;
        while (n && n !== stop) {
          var next = n.nextSibling;
          if (n.nodeType === 1 && (n.tagName === 'UL' || n.tagName === 'OL')) { while (n.firstChild) box.appendChild(n.firstChild); n.remove(); }
          else if (n.nodeType === 1 || n.nodeValue.trim()) {
            var li = document.createElement('li');
            if (n.nodeType === 1) { while (n.firstChild) li.appendChild(n.firstChild); n.remove(); } else li.appendChild(n);
            if (!li.firstChild) li.appendChild(document.createElement('br'));
            box.appendChild(li);
          } else n.remove();
          n = next;
        }
      }
      try { if (ed.contains(keep[0]) && ed.contains(keep[2])) sel.setBaseAndExtent(keep[0], keep[1], keep[2], keep[3]); } catch (e) { /* node gone */ }
      after();
    }
    function block(tag) {
      var cur = current();
      exec('formatBlock', cur === tag && tag !== 'P' ? 'P' : tag);
    }
    function current() {
      var s = window.getSelection();
      if (!s.rangeCount || !ed.contains(s.anchorNode)) return '';
      var n = s.anchorNode.nodeType === 1 ? s.anchorNode : s.anchorNode.parentNode;
      while (n && n !== ed) {
        if (/^(H2|H3|H4|BLOCKQUOTE|P|LI)$/.test(n.tagName)) return n.tagName;
        n = n.parentNode;
      }
      return '';
    }
    // Put clean nodes at the caret without help from the browser, which would add inline styles.
    function insertNodes(box) {
      var sel = window.getSelection();
      if (!sel.rangeCount || !ed.contains(sel.anchorNode)) { ed.focus(); var rr = document.createRange(); rr.selectNodeContents(ed); rr.collapse(false); sel.removeAllRanges(); sel.addRange(rr); }
      snap();
      var r = sel.getRangeAt(0);
      r.deleteContents();
      var blocky = Array.prototype.some.call(box.childNodes, function (c) { return c.nodeType === 1 && BLOCKS.test(c.tagName); });
      if (blocky) {
        // Split the current paragraph at the caret and put the new blocks between the halves.
        var host = r.startContainer.nodeType === 1 ? r.startContainer : r.startContainer.parentNode;
        host = host.closest('p, h2, h3, h4, blockquote, li');
        if (host && ed.contains(host) && host.tagName !== 'LI') {
          var tail = document.createRange();
          tail.setStart(r.startContainer, r.startOffset);
          tail.setEndAfter(host.lastChild || host);
          var rest = tail.extractContents();
          var tailEl = host.cloneNode(false);
          tailEl.appendChild(rest);
          host.parentNode.insertBefore(tailEl, host.nextSibling);
          if (!tailEl.textContent.trim() && !tailEl.querySelector('img')) tailEl.remove();
          if (!host.textContent.trim() && !host.querySelector('img')) { host.parentNode.insertBefore(box.firstChild, host); host.remove(); }
          var ref = host.parentNode ? host.nextSibling : null;
          var last = null;
          while (box.firstChild) { last = box.firstChild; (ref ? ref.parentNode : ed).insertBefore(last, ref); }
          r = document.createRange();
          if (last) { r.selectNodeContents(last); r.collapse(false); sel.removeAllRanges(); sel.addRange(r); }
          after();
          return;
        }
      }
      var frag = document.createDocumentFragment(), end = null;
      while (box.firstChild) { end = box.firstChild; frag.appendChild(end); }
      r.insertNode(frag);
      if (end) { r = document.createRange(); r.setStartAfter(end); r.collapse(true); sel.removeAllRanges(); sel.addRange(r); }
      after();
    }
    function link() {
      if (!ta.hidden) return;
      var s = window.getSelection();
      var inside = ed.contains(s.anchorNode);
      var saved = inside && s.rangeCount ? s.getRangeAt(0).cloneRange() : null;
      var a = inside && s.anchorNode && (s.anchorNode.nodeType === 1 ? s.anchorNode : s.anchorNode.parentNode).closest('a');
      var url = window.prompt('Link address. Use https://... for another website, /pricing for a page on this site, or an email address.', a ? a.getAttribute('href') : 'https://');
      if (url === null) return;
      url = url.trim();
      if (url === '' || url === 'https://') { if (a) { if (saved) { s.removeAllRanges(); s.addRange(saved); } exec('unlink'); } return; }
      if (/^[^\s@\/]+@[^\s@\/]+\.[a-z]{2,}$/i.test(url)) url = 'mailto:' + url;
      else if (/^www\./i.test(url) || /^[a-z0-9-]+(\.[a-z0-9-]+)+(\/|$)/i.test(url)) url = 'https://' + url;
      if (!/^(https?:\/\/|mailto:|\/|#)/i.test(url) || /[\s"'<>`]/.test(url)) { window.alert('That address does not look right. Start it with https:// or /'); return; }
      ed.focus();
      if (saved) { s.removeAllRanges(); s.addRange(saved); }
      if (a) { snap(); a.setAttribute('href', url); after(); return; }
      if (s.isCollapsed) {
        var text = window.prompt('Text to show for the link', url.replace(/^mailto:/, ''));
        if (!text) return;
        var box = document.createElement('div'), na = document.createElement('a');
        na.href = url;
        na.setAttribute('href', url);
        na.textContent = text;
        box.appendChild(na);
        box.appendChild(document.createTextNode('\u00a0'));
        insertNodes(box);
      } else {
        exec('createLink', url);
      }
    }
    var codeBtn;
    function source() {
      if (ta.hidden) {
        sync();
        ta.hidden = false;
        ed.hidden = true;
        ta.style.minHeight = Math.max(ed.offsetHeight, 220) + 'px';
        ta.focus();
      } else {
        if (ta.value === lastMd) { ta.hidden = true; ed.hidden = false; ed.focus(); return finish(); }
        if (!previewUrl || !token) { window.alert('Please save the page to see the formatted text again.'); return; }
        var body = new FormData();
        body.append('_csrf', token.value);
        body.append('text', ta.value);
        codeBtn.disabled = true;
        fetch(previewUrl, { method: 'POST', body: body, credentials: 'same-origin' })
          .then(function (r) { return r.json(); })
          .then(function (d) {
            if (!d || !d.ok) throw new Error('no');
            snap();
            ed.innerHTML = clean(parse(d.html || '')).innerHTML;
            lastMd = ta.value;
            ta.hidden = true; ed.hidden = false;
            sync(); snap(); finish(); ed.focus();
          })
          .catch(function () { window.alert('Could not switch back. Please check your connection, or save the page.'); })
          .then(function () { codeBtn.disabled = false; });
      }
      function finish() {
        codeBtn.setAttribute('aria-pressed', String(!ta.hidden));
        Array.prototype.forEach.call(bar.querySelectorAll('button:not(.wys-code)'), function (b) { b.disabled = !ta.hidden; });
        if (ta.hidden) state();
      }
      finish();
    }

    var tools = [
      ['undo', 'Undo (Ctrl+Z)', undo, svg('undo')],
      ['redo', 'Redo (Ctrl+Y)', redo, svg('redo')],
      '|',
      ['bold', 'Bold (Ctrl+B)', function () { exec('bold'); }, '<b>B</b>'],
      ['italic', 'Italic (Ctrl+I)', function () { exec('italic'); }, '<i>I</i>'],
      ['link', 'Add or change a link (Ctrl+K)', link, svg('link')],
      ['unlink', 'Remove link', function () { exec('unlink'); }, svg('unlink')],
      '|',
      ['P', 'Normal text', function () { block('P'); }, 'Text'],
      ['H2', 'Heading', function () { block('H2'); }, 'H2'],
      ['H3', 'Small heading', function () { block('H3'); }, 'H3'],
      '|',
      ['ul', 'Bullet list (Ctrl+Shift+8)', function () { list('UL'); }, svg('ul')],
      ['ol', 'Numbered list (Ctrl+Shift+7)', function () { list('OL'); }, svg('ol')],
      ['BLOCKQUOTE', 'Quote', function () { block('BLOCKQUOTE'); }, svg('quote')],
      '|',
      ['left', 'Align left', function () { align('left'); }, svg('left')],
      ['center', 'Center', function () { align('center'); }, svg('center')],
      ['justify', 'Justify', function () { align('justify'); }, svg('justify')],
      ['hr', 'Divider line', function () { exec('insertHorizontalRule'); }, svg('hr')],
      ['clear', 'Clear bold, italic and links from the selection', function () { exec('removeFormat'); exec('unlink'); }, svg('clear')],
      ['code', 'Show the Markdown code', source, svg('code') + '<span>Code</span>']
    ];
    var btns = {};
    tools.forEach(function (t) {
      if (t === '|') { var sep = document.createElement('span'); sep.className = 'wys-sep'; bar.appendChild(sep); return; }
      var b = document.createElement('button');
      b.type = 'button';
      b.className = 'wys-btn wys-' + t[0].toLowerCase();
      b.title = t[1];
      b.setAttribute('aria-label', t[1]);
      b.innerHTML = t[3];
      b.addEventListener('mousedown', function (e) { e.preventDefault(); });
      b.addEventListener('click', function () { t[2](); });
      bar.appendChild(b);
      btns[t[0]] = b;
      if (t[0] === 'code') codeBtn = b;
    });
    btns.code.setAttribute('aria-pressed', 'false');

    function state() {
      btns.undo.disabled = at <= 0 && !timer;
      btns.redo.disabled = at >= hist.length - 1;
      if (!ta.hidden) return;
      var inside = ed.contains(window.getSelection().anchorNode);
      var blk = inside ? current() : '';
      var an = inside ? window.getSelection().anchorNode : null;
      var li = an && (an.nodeType === 1 ? an : an.parentNode).closest('li');
      var listTag = li && ed.contains(li) ? li.parentNode.tagName : '';
      [['bold', inside && document.queryCommandState('bold')], ['italic', inside && document.queryCommandState('italic')],
       ['ul', listTag === 'UL'], ['ol', listTag === 'OL'],
       ['H2', blk === 'H2'], ['H3', blk === 'H3'], ['BLOCKQUOTE', blk === 'BLOCKQUOTE']].forEach(function (x) {
        btns[x[0]].classList.toggle('is-on', !!x[1]);
        btns[x[0]].setAttribute('aria-pressed', String(!!x[1]));
      });
      var box = inside ? topOf(window.getSelection().anchorNode) : null;
      var al = box && box.nodeType === 1 && box.tagName === 'DIV' ? (box.className.match(ALIGN) || [])[1] : null;
      ['center', 'justify'].forEach(function (k) { btns[k].classList.toggle('is-on', al === k); });
    }

    try { document.execCommand('defaultParagraphSeparator', false, 'p'); } catch (e) { /* older browsers */ }
    ed.addEventListener('input', function () {
      sync();
      clearTimeout(timer);
      timer = setTimeout(snap, 400);
      btns.undo.disabled = false;
    });
    ed.addEventListener('beforeinput', function (e) {
      if (e.inputType === 'historyUndo') { e.preventDefault(); undo(); }
      else if (e.inputType === 'historyRedo') { e.preventDefault(); redo(); }
    });
    ed.addEventListener('keydown', function (e) {
      if (!(e.ctrlKey || e.metaKey) || e.altKey) return;
      var k = e.key.toLowerCase();
      if (k === 'z' && !e.shiftKey) { e.preventDefault(); undo(); }
      else if (k === 'y' || (k === 'z' && e.shiftKey)) { e.preventDefault(); redo(); }
      else if (k === 'b' && !e.shiftKey) { e.preventDefault(); exec('bold'); }
      else if (k === 'i' && !e.shiftKey) { e.preventDefault(); exec('italic'); }
      else if (k === 'u') { e.preventDefault(); }
      else if (k === 'k') { e.preventDefault(); link(); }
      else if (e.shiftKey && (e.code === 'Digit8' || k === '*')) { e.preventDefault(); list('UL'); }
      else if (e.shiftKey && (e.code === 'Digit7' || k === '&')) { e.preventDefault(); list('OL'); }
    });
    ed.addEventListener('paste', function (e) {
      var cd = e.clipboardData;
      if (!cd) return;
      e.preventDefault();
      var html = cd.getData('text/html'), box;
      if (html) {
        box = clean(parse(html.replace(/<!--[\s\S]*?-->/g, '')));
      } else {
        box = document.createElement('div');
        var parts = (cd.getData('text/plain') || '').split(/\r?\n\s*\r?\n/);
        parts.forEach(function (part) {
          var holder = parts.length > 1 ? document.createElement('p') : box;
          part.split(/\r?\n/).forEach(function (line, i) {
            if (i) holder.appendChild(document.createElement('br'));
            holder.appendChild(document.createTextNode(line));
          });
          if (holder !== box) box.appendChild(holder);
        });
      }
      insertNodes(box);
    });
    ed.addEventListener('drop', function (e) { if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) e.preventDefault(); });
    ed.addEventListener('click', function (e) { if (e.target.closest('a')) e.preventDefault(); });
    document.addEventListener('selectionchange', function () { if (document.activeElement === ed) state(); });
    if (form) form.addEventListener('submit', sync);
    sync();
    snap();
    state();
  });

  // Repeating rows (experience, education...): add and remove rows without reloading.
  Array.prototype.forEach.call(document.querySelectorAll('[data-repeat]'), function (box) {
    var rows = box.querySelector('[data-rows]');
    var add = box.querySelector('[data-add-row]');
    var max = parseInt(box.getAttribute('data-max'), 10) || 10;
    var key = box.getAttribute('data-repeat');
    var blank = rows.lastElementChild.cloneNode(true);
    var next = rows.children.length;
    function refresh() {
      var n = rows.children.length;
      add.hidden = n >= max;
      Array.prototype.forEach.call(rows.querySelectorAll('[data-remove-row]'), function (b) { b.hidden = n <= 1; });
    }
    function wire(row) {
      row.querySelector('[data-remove-row]').addEventListener('click', function () {
        row.remove();
        refresh();
      });
    }
    Array.prototype.forEach.call(rows.children, wire);
    add.addEventListener('click', function () {
      var row = blank.cloneNode(true);
      var i = next++;
      Array.prototype.forEach.call(row.querySelectorAll('[name]'), function (el) {
        el.name = el.name.replace(new RegExp('^' + key + '\\[\\d+\\]'), key + '[' + i + ']');
        el.value = '';
      });
      var legend = row.querySelector('legend');
      if (legend) legend.textContent = legend.textContent.replace(/\d+$/, String(rows.children.length + 1));
      rows.appendChild(row);
      wire(row);
      refresh();
      var first = row.querySelector('input, textarea');
      if (first) first.focus();
    });
    refresh();
  });

  // "Select all" box in lists such as People.
  Array.prototype.forEach.call(document.querySelectorAll('[data-check-all]'), function (all) {
    var table = all.closest('table');
    all.addEventListener('change', function () {
      Array.prototype.forEach.call(table.querySelectorAll('tbody input[type=checkbox]'), function (c) { c.checked = all.checked; });
    });
  });
})();
