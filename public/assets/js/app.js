/* AivraSol front end. No dependencies. Every feature also works without JavaScript. */
(function () {
  'use strict';

  var doc = document;
  var root = doc.documentElement;
  var $ = function (sel, ctx) { return (ctx || doc).querySelector(sel); };
  var $$ = function (sel, ctx) { return Array.prototype.slice.call((ctx || doc).querySelectorAll(sel)); };
  var desktop = window.matchMedia('(min-width: 1024px)');

  /* Toast */
  var toastEl = $('[data-toast]');
  var toastTimer;
  function toast(message, type) {
    if (!toastEl) return;
    toastEl.textContent = message;
    toastEl.className = 'toast is-visible' + (type ? ' is-' + type : '');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { toastEl.className = 'toast'; }, 5000);
  }

  /* Mobile navigation */
  var navToggle = $('[data-nav-toggle]');
  var nav = $('#main-nav');
  function setNav(open) {
    root.classList.toggle('nav-open', open);
    doc.body.classList.toggle('nav-open', open);
    if (navToggle) navToggle.setAttribute('aria-expanded', String(open));
    if (!open) closeDropdowns();
  }
  if (navToggle) {
    navToggle.addEventListener('click', function () { setNav(navToggle.getAttribute('aria-expanded') !== 'true'); });
  }
  if (nav) {
    nav.addEventListener('click', function (e) {
      if (e.target.closest('a')) setNav(false);
    });
  }
  desktop.addEventListener('change', function () { setNav(false); });

  /* Dropdowns: click to toggle (needed on touch and in the mobile menu); hover is handled by CSS on desktop */
  var dropdowns = $$('[data-dropdown]');
  function closeDropdowns(except) {
    dropdowns.forEach(function (btn) { if (btn !== except) btn.setAttribute('aria-expanded', 'false'); });
  }
  dropdowns.forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      var open = btn.getAttribute('aria-expanded') !== 'true';
      closeDropdowns(btn);
      btn.setAttribute('aria-expanded', String(open));
    });
  });
  doc.addEventListener('click', function (e) {
    if (desktop.matches && !e.target.closest('.nav-item')) closeDropdowns();
  });
  // Desktop: open on hover and wait a moment before closing, so the pointer can travel into the panel.
  $$('.nav-item').forEach(function (item) {
    if (!$('.mega', item)) return;
    var timer;
    item.addEventListener('mouseenter', function () {
      if (!desktop.matches) return;
      clearTimeout(timer);
      $$('.nav-item.is-open').forEach(function (o) { if (o !== item) o.classList.remove('is-open'); });
      item.classList.add('is-open');
    });
    item.addEventListener('mouseleave', function () {
      if (!desktop.matches) return;
      timer = setTimeout(function () { item.classList.remove('is-open'); }, 220);
    });
  });
  doc.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    var openBtn = dropdowns.filter(function (b) { return b.getAttribute('aria-expanded') === 'true'; })[0];
    if (openBtn) { closeDropdowns(); openBtn.focus(); return; }
    if (root.classList.contains('nav-open')) { setNav(false); if (navToggle) navToggle.focus(); }
  });

  /* Modals */
  function openModal(id) {
    var dlg = doc.getElementById(id);
    if (!dlg || typeof dlg.showModal !== 'function') return false;
    setNav(false);
    resetFormState($('form', dlg));
    dlg.showModal();
    var first = $('input:not([type=hidden]):not([tabindex="-1"]), textarea', dlg);
    if (first) first.focus();
    return true;
  }
  $$('[data-modal-open]').forEach(function (el) {
    el.addEventListener('click', function (e) {
      if (openModal(el.getAttribute('data-modal-open'))) e.preventDefault();
    });
  });
  $$('dialog.modal').forEach(function (dlg) {
    dlg.addEventListener('click', function (e) {
      if (e.target === dlg || e.target.closest('[data-modal-close]')) dlg.close();
    });
  });

  /* "Other" choices: show a text box to describe it */
  var OTHER = /^(other|something else)\b/i;
  function syncOther(select) {
    var box = select.closest('label, .cell, .field') || select.parentNode;
    var input = $('[data-other-for="' + select.name + '"]', box);
    if (!input) return;
    var show = !select.disabled && OTHER.test(select.value);
    input.hidden = !show;
    input.disabled = select.disabled;
    if (show) input.setAttribute('required', ''); else input.removeAttribute('required');
    return show;
  }
  $$('select[data-has-other]').forEach(function (select) {
    syncOther(select);
    select.addEventListener('change', function () {
      if (syncOther(select)) {
        var box = select.closest('label, .cell, .field') || select.parentNode;
        var input = $('[data-other-for="' + select.name + '"]', box);
        if (input) input.focus();
      }
    });
  });

  /* Pricing cards open the quote modal with the chosen options */
  $$('[data-quote-card]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      var q1 = form.elements.q1, q2 = form.elements.q2;
      var o1 = form.elements.q1_other, o2 = form.elements.q2_other;
      if (!q1.value || !q2.value) {
        e.preventDefault();
        (q1.value ? q2 : q1).focus();
        toast('Please choose both options to get your quote.', 'error');
        return;
      }
      var missing = [[q1, o1], [q2, o2]].filter(function (p) { return p[1] && OTHER.test(p[0].value) && !p[1].value.trim(); })[0];
      if (missing) {
        e.preventDefault();
        missing[1].focus();
        toast('Please tell us what you need in the box under "Other".', 'error');
        return;
      }
      var dlg = doc.getElementById('quote-modal');
      if (!dlg || typeof dlg.showModal !== 'function') return;
      e.preventDefault();
      var target = $('form', dlg);
      target.elements.plan_id.value = form.elements.plan.value;
      target.elements.q1.value = q1.value;
      target.elements.q2.value = q2.value;
      var t1 = o1 && OTHER.test(q1.value) ? o1.value.trim() : '';
      var t2 = o2 && OTHER.test(q2.value) ? o2.value.trim() : '';
      target.elements.q1_other.value = t1;
      target.elements.q2_other.value = t2;
      $('[data-quote-summary]', dlg).textContent = form.getAttribute('data-plan-title') + ': ' + (t1 ? q1.value + ' (' + t1 + ')' : q1.value) + ', ' + (t2 ? q2.value + ' (' + t2 + ')' : q2.value);
      openModal('quote-modal');
    });
  });

  /* Pricing page: show the questions for the selected plan */
  var planSelect = $('[data-plan-select]');
  if (planSelect) {
    planSelect.addEventListener('change', function () {
      $$('[data-plan-fields]').forEach(function (cell) {
        var on = cell.getAttribute('data-plan-fields') === planSelect.value;
        cell.hidden = !on;
        $$('select, input', cell).forEach(function (s) { s.disabled = !on; });
        $$('select[data-has-other]', cell).forEach(syncOther);
      });
    });
  }

  /* Forms sent with fetch, with a normal post as fallback */
  function resetFormState(form) {
    if (!form) return;
    $$('[aria-invalid]', form).forEach(function (el) { el.removeAttribute('aria-invalid'); });
    $$('.field-error, .form-status', form).forEach(function (el) { el.remove(); });
  }
  function showStatus(form, message, ok) {
    var p = doc.createElement('p');
    p.className = 'form-status ' + (ok ? 'is-success' : 'is-error');
    p.setAttribute('role', ok ? 'status' : 'alert');
    p.textContent = message;
    form.appendChild(p);
  }
  function showErrors(form, errors) {
    var first = null;
    Object.keys(errors || {}).forEach(function (name) {
      var field = form.elements[name];
      if (!field || !field.getAttribute) return;
      if (field.length && !field.tagName) field = field[0];
      field.setAttribute('aria-invalid', 'true');
      var msg = doc.createElement('span');
      msg.className = 'field-error';
      msg.id = 'err-' + Math.random().toString(36).slice(2);
      msg.textContent = errors[name];
      field.setAttribute('aria-describedby', msg.id);
      var holder = field.closest('.cell, .field, .check') || field.parentNode;
      holder.appendChild(msg);
      if (!first) first = field;
    });
    if (first) first.focus();
  }
  function clientCheck(form) {
    var errors = {};
    $$('[required]', form).forEach(function (el) {
      if (el.disabled) return;
      var empty = el.type === 'checkbox' ? !el.checked : el.type === 'file' ? !el.files.length : !el.value.trim();
      if (empty) errors[el.name] = el.type === 'checkbox' ? 'Please tick this box to continue.' : 'This field is required.';
      else if (el.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(el.value.trim())) errors[el.name] = 'Please enter a valid email address.';
      else if (el.minLength > 0 && el.value.trim().length < el.minLength) errors[el.name] = 'Please write at least ' + el.minLength + ' characters.';
    });
    var file = $('input[type=file]', form);
    if (file && file.files.length && file.files[0].size > 5 * 1024 * 1024) errors[file.name] = 'The file is too large. The limit is 5 MB.';
    return errors;
  }
  $$('[data-ajax-form]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (!window.fetch || !window.FormData) return;
      e.preventDefault();
      resetFormState(form);
      var errors = clientCheck(form);
      if (Object.keys(errors).length) { showErrors(form, errors); return; }
      var btn = $('[type=submit]', form);
      if (btn) { btn.setAttribute('aria-busy', 'true'); btn.disabled = true; }
      fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'fetch' },
        credentials: 'same-origin'
      }).then(function (res) {
        return res.json().catch(function () { return { ok: false, message: 'Something went wrong. Please try again.' }; });
      }).then(function (data) {
        if (data.ok) {
          form.reset();
          $$('select[data-has-other]', form).forEach(syncOther);
          showStatus(form, data.message, true);
          toast(data.message, 'success');
          var dlg = form.closest('dialog');
          if (dlg) setTimeout(function () { if (dlg.open) dlg.close(); }, 2600);
          trackLead(form.action.split('/').pop());
        } else {
          showErrors(form, data.errors);
          showStatus(form, data.message || 'Please check the form and try again.', false);
        }
      }).catch(function () {
        showStatus(form, 'We could not send your message. Please check your connection and try again.', false);
      }).then(function () {
        if (btn) { btn.removeAttribute('aria-busy'); btn.disabled = false; }
      });
    });
  });

  /* Service tabs */
  $$('[data-tabs]').forEach(function (wrap) {
    var tabs = $$('[role=tab]', wrap);
    function select(tab, focus) {
      tabs.forEach(function (t) {
        var on = t === tab;
        t.setAttribute('aria-selected', String(on));
        t.tabIndex = on ? 0 : -1;
        var panel = doc.getElementById(t.getAttribute('aria-controls'));
        if (panel) {
          panel.hidden = !on;
          if (on) { panel.classList.remove('fade-in'); void panel.offsetWidth; panel.classList.add('fade-in'); }
        }
      });
      if (focus) tab.focus();
      if (!desktop.matches) {
        var panelEl = doc.getElementById(tab.getAttribute('aria-controls'));
        if (panelEl && panelEl.getBoundingClientRect().top > window.innerHeight * .8) panelEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    }
    tabs.forEach(function (tab, i) {
      tab.addEventListener('click', function (e) { e.preventDefault(); select(tab); });
      tab.addEventListener('keydown', function (e) {
        var cols = window.matchMedia('(min-width: 768px)').matches ? 3 : 2;
        var map = { ArrowRight: 1, ArrowLeft: -1, ArrowDown: cols, ArrowUp: -cols };
        if (e.key === 'Home' || e.key === 'End') { e.preventDefault(); select(tabs[e.key === 'Home' ? 0 : tabs.length - 1], true); return; }
        if (!(e.key in map)) return;
        e.preventDefault();
        var next = (i + map[e.key] + tabs.length) % tabs.length;
        select(tabs[next], true);
      });
    });
  });

  /* Sliders */
  $$('[data-slider]').forEach(function (wrap) {
    var track = $('[data-track]', wrap);
    var prev = $('[data-slide=prev]', wrap);
    var next = $('[data-slide=next]', wrap);
    if (!track || !prev || !next) return;
    function step() {
      var card = track.firstElementChild;
      if (!card) return track.clientWidth;
      var gap = parseFloat(getComputedStyle(track).columnGap) || 0;
      return card.getBoundingClientRect().width + gap;
    }
    function update() {
      var max = track.scrollWidth - track.clientWidth - 2;
      prev.disabled = track.scrollLeft <= 2;
      next.disabled = track.scrollLeft >= max;
    }
    prev.addEventListener('click', function () { track.scrollBy({ left: -step(), behavior: 'smooth' }); });
    next.addEventListener('click', function () { track.scrollBy({ left: step(), behavior: 'smooth' }); });
    track.addEventListener('scroll', function () { window.requestAnimationFrame(update); }, { passive: true });
    track.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowRight') { e.preventDefault(); next.click(); }
      if (e.key === 'ArrowLeft') { e.preventDefault(); prev.click(); }
    });
    window.addEventListener('resize', update);
    update();
  });

  /* Portfolio filters */
  var filterBar = $('[data-filters]');
  if (filterBar) {
    filterBar.hidden = false;
    var cards = $$('[data-filter-grid] [data-category]');
    $$('[data-filter]', filterBar).forEach(function (btn) {
      btn.addEventListener('click', function () {
        var val = btn.getAttribute('data-filter');
        $$('[data-filter]', filterBar).forEach(function (b) { b.setAttribute('aria-pressed', String(b === btn)); });
        cards.forEach(function (c) { c.hidden = val !== '*' && c.getAttribute('data-category') !== val; });
      });
    });
  }

  /* Back to top */
  var topBtn = $('[data-to-top]');
  if (topBtn) {
    var onScroll = function () { topBtn.classList.toggle('is-visible', window.scrollY > 600); };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
    topBtn.addEventListener('click', function (e) {
      e.preventDefault();
      window.scrollTo({ top: 0, behavior: 'smooth' });
      var main = $('#main');
      if (main) main.focus({ preventScroll: true });
    });
  }

  /* Cookie consent. Analytics and marketing stay off until the visitor opts in. */
  var COOKIE = 'avs_consent';
  var consent = readConsent();
  var banner = $('[data-cookie]');
  function readConsent() {
    var m = doc.cookie.match(/(?:^|;\s*)avs_consent=([^;]+)/);
    if (!m) return null;
    var v = decodeURIComponent(m[1]);
    return { analytics: /a1/.test(v), marketing: /m1/.test(v) };
  }
  function writeConsent(c) {
    var v = 'v1.a' + (c.analytics ? 1 : 0) + '.m' + (c.marketing ? 1 : 0);
    var secure = location.protocol === 'https:' ? '; Secure' : '';
    doc.cookie = COOKIE + '=' + v + '; Max-Age=' + (60 * 60 * 24 * 182) + '; Path=/; SameSite=Lax' + secure;
    consent = c;
  }
  function clearCookies(pattern) {
    doc.cookie.split(';').forEach(function (part) {
      var name = part.split('=')[0].trim();
      if (pattern.test(name)) {
        var host = location.hostname.replace(/^www\./, '');
        ['', '; domain=' + host, '; domain=.' + host].forEach(function (d) {
          doc.cookie = name + '=; Max-Age=0; Path=/' + d;
        });
      }
    });
  }
  /* Google tag (Analytics and Ads) with Consent Mode, and the Meta Pixel. Nothing loads before consent. */
  var body = doc.body;
  var gaId = /^G-[A-Z0-9]+$/.test(body.getAttribute('data-ga') || '') ? body.getAttribute('data-ga') : '';
  var adsId = /^AW-[0-9]+$/.test(body.getAttribute('data-gads') || '') ? body.getAttribute('data-gads') : '';
  var adsLabel = body.getAttribute('data-gads-lead') || '';
  var pixelId = /^[0-9]+$/.test(body.getAttribute('data-pixel') || '') ? body.getAttribute('data-pixel') : '';
  var configured = {};
  function consentState(c) {
    var ad = c.marketing ? 'granted' : 'denied';
    return { analytics_storage: c.analytics ? 'granted' : 'denied', ad_storage: ad, ad_user_data: ad, ad_personalization: ad };
  }
  function loadGoogle(c) {
    if (!gaId && !adsId) return;
    var wantGa = gaId && c.analytics, wantAds = adsId && c.marketing;
    if (!window.gtag) {
      if (!wantGa && !wantAds) return;
      window.dataLayer = window.dataLayer || [];
      window.gtag = function () { window.dataLayer.push(arguments); };
      window.gtag('consent', 'default', consentState({ analytics: false, marketing: false }));
      window.gtag('js', new Date());
      var s = doc.createElement('script');
      s.async = true;
      s.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(gaId || adsId);
      doc.head.appendChild(s);
    }
    window.gtag('consent', 'update', consentState(c));
    if (wantGa && !configured.ga) { window.gtag('config', gaId, { anonymize_ip: true }); configured.ga = true; }
    if (wantAds && !configured.ads) { window.gtag('config', adsId); configured.ads = true; }
  }
  function loadPixel() {
    if (!pixelId || window.fbq) return;
    var n = window.fbq = function () { n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments); };
    if (!window._fbq) window._fbq = n;
    n.push = n; n.loaded = true; n.version = '2.0'; n.queue = [];
    var s = doc.createElement('script');
    s.async = true;
    s.src = 'https://connect.facebook.net/en_US/fbevents.js';
    doc.head.appendChild(s);
    window.fbq('init', pixelId);
    window.fbq('track', 'PageView');
  }
  function startTracking(c) {
    loadGoogle(c);
    if (c.marketing) loadPixel();
    else if (window.fbq) window.fbq('consent', 'revoke');
    if (!c.analytics) clearCookies(/^_ga|^_gid|^_gat/);
    if (!c.marketing) clearCookies(/^_fbp|^_fbc|^_gcl/);
  }
  /* Called after a form is sent: counts the lead in Analytics, Google Ads and Meta when allowed. */
  function trackLead(formName) {
    if (!consent) return;
    if (window.gtag && consent.analytics && gaId) window.gtag('event', 'generate_lead', { form: formName });
    if (window.gtag && consent.marketing && adsId && adsLabel) window.gtag('event', 'conversion', { send_to: adsId + '/' + adsLabel });
    if (window.fbq && consent.marketing && pixelId) window.fbq('track', 'Lead', { content_name: formName });
  }
  function syncSwitches() {
    if (!banner) return;
    $$('[data-consent]', banner).forEach(function (input) {
      input.checked = !!(consent && consent[input.getAttribute('data-consent')]);
    });
  }
  function apply(c) {
    writeConsent(c);
    startTracking(c);
    if (banner) banner.hidden = true;
    toast('Your cookie choices have been saved.', 'success');
  }
  if (banner) {
    if (!consent) banner.hidden = false;
    syncSwitches();
    $$('[data-consent-action]', banner).forEach(function (btn) {
      btn.addEventListener('click', function () {
        var act = btn.getAttribute('data-consent-action');
        if (act === 'accept') apply({ analytics: true, marketing: true });
        else if (act === 'reject') apply({ analytics: false, marketing: false });
        else apply({
          analytics: $('[data-consent=analytics]', banner).checked,
          marketing: $('[data-consent=marketing]', banner).checked
        });
      });
    });
  }
  function showOptions() {
    if (!banner) return;
    $('[data-cookie-opts]', banner).hidden = false;
    $('[data-consent-action=save]', banner).hidden = false;
    $('[data-cookie-customise]', banner).hidden = true;
  }
  var customise = banner && $('[data-cookie-customise]', banner);
  if (customise) customise.addEventListener('click', function () { showOptions(); $('[data-consent=analytics]', banner).focus(); });
  $$('[data-cookie-open]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (!banner) return;
      syncSwitches();
      showOptions();
      banner.hidden = false;
      var first = $('[data-consent]', banner);
      if (first) first.focus();
    });
  });
  if (consent) startTracking(consent);

  /* Scroll reveal and number count-up. Items already on screen at load are left alone, so nothing flashes. */
  var calm = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (!calm && 'IntersectionObserver' in window) {
    doc.documentElement.classList.add('fx');
    var SEL = '.section-head, .tile, .project-card, .post-card, .plan-card, .step, .review-card, .office-card, .faq-item, .stats-grid > div, .svc-tabs, .cta-inner, .case-body > *, .split > *, .post-layout > *';
    var reveal = function (el) {
      el.classList.add('is-in');
      el.addEventListener('transitionend', function done(e) {
        if (e.target !== el) return;
        el.removeAttribute('data-fx');
        el.classList.remove('is-in');
        el.style.transitionDelay = '';
        el.removeEventListener('transitionend', done);
      });
    };
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) { reveal(en.target); io.unobserve(en.target); }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
    $$(SEL).forEach(function (el) {
      if (el.closest('[data-fx]') || el.closest('.admin, .modal, .mega')) return;
      if (el.getBoundingClientRect().top < window.innerHeight * 0.9) return;
      var sibs = Array.prototype.filter.call(el.parentNode.children, function (c) { return c.matches(SEL); });
      el.style.transitionDelay = Math.min(sibs.indexOf(el), 5) * 90 + 'ms';
      el.setAttribute('data-fx', '');
      io.observe(el);
    });
    // Safety net: anything already scrolled past (for example after jumping to a link lower down) is shown too.
    var sweep = function () {
      $$('[data-fx]:not(.is-in)').forEach(function (el) {
        if (el.getBoundingClientRect().top < window.innerHeight) { reveal(el); io.unobserve(el); }
      });
    };
    var sweepTimer = null;
    window.addEventListener('scroll', function () {
      if (!sweepTimer) sweepTimer = setTimeout(function () { sweepTimer = null; sweep(); }, 150);
    }, { passive: true });
    var counters = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (!en.isIntersecting) return;
        counters.unobserve(en.target);
        var el = en.target, m = el.textContent.match(/^(\D*)(\d[\d,]*)(.*)$/);
        if (!m) return;
        var end = parseInt(m[2].replace(/,/g, ''), 10), start = null;
        if (!end || end > 100000) return;
        var step = function (t) {
          if (start === null) start = t;
          var p = Math.min(1, (t - start) / 1400), v = Math.round(end * (1 - Math.pow(1 - p, 3)));
          el.textContent = m[1] + v.toLocaleString('en') + m[3];
          if (p < 1) window.requestAnimationFrame(step);
        };
        window.requestAnimationFrame(step);
      });
    }, { threshold: 0.6 });
    $$('.stat-value').forEach(function (el) { counters.observe(el); });
  }
})();
