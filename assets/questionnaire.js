/* North Coast Questionnaire — per-question navigation with chapter images. */
(function () {
  'use strict';

  var root = document.getElementById('ncq');
  if (!root || typeof NCQ === 'undefined') return;

  var t          = NCQ.i18n;
  var form       = root.querySelector('#ncq-form');
  var steps      = Array.prototype.slice.call(form.querySelectorAll('.ncq-step'));
  var shell      = root.querySelector('.ncq-shell');
  var btnPrev    = form.querySelector('[data-ncq-prev]');
  var btnNext    = form.querySelector('[data-ncq-next]');
  var btnSend    = form.querySelector('[data-ncq-submit]');
  var formErr    = form.querySelector('.ncq-form-error');
  var thanks     = root.querySelector('.ncq-thanks');
  var terminated = root.querySelector('.ncq-terminated');
  var progCount  = root.querySelector('.ncq-progress-count');
  var progTitle  = root.querySelector('.ncq-progress-title');
  var progBar    = root.querySelector('.ncq-progress-bar');
  var capNum     = root.querySelector('.ncq-visual-num');
  var capPlace   = root.querySelector('.ncq-visual-place');

  // Current position: step index and question index within that step.
  var curStep = 0;
  var curQ    = 0;

  // Phone field with country code (intl-tel-input).
  var phoneEl = form.querySelector('input[name="phone"]');
  var iti = phoneEl && window.intlTelInput ? window.intlTelInput(phoneEl, {
    initialCountry: 'eg',
    utilsScript: 'https://cdn.jsdelivr.net/npm/intl-tel-input@23/build/js/utils.js',
  }) : null;
  var NAT_COUNTRY = { ksa: 'sa', uk: 'gb', china: 'cn', russia: 'ru', canada: 'ca', kuwait: 'kw', uae: 'ae' };

  // ── Analytics (GTM dataLayer) ─────────────────────────────────────────
  function track(data) {
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push(data);
    console.log('[NCQ dataLayer]', data); // TODO: remove after testing
  }

  function $all(sel, ctx) { return Array.prototype.slice.call((ctx || form).querySelectorAll(sel)); }
  function pad(n) { return (n < 10 ? '0' : '') + n; }
  function qEl(id) { return form.querySelector('.ncq-q[data-q="' + id + '"]'); }

  // ── Reading answers ────────────────────────────────────────────────────
  function valueOf(id) {
    var el = qEl(id);
    if (!el) return null;
    var type = el.dataset.type;
    if (type === 'single') {
      var c = el.querySelector('input[name="' + id + '"]:checked');
      return c ? c.value : '';
    }
    if (type === 'multi') {
      return $all('input[name="' + id + '[]"]:checked', el).map(function (i) { return i.value; });
    }
    if (type === 'rank') return (el._rank || []).slice();
    if (type === 'scale') {
      var out = {};
      $all('.ncq-scale-row', el).forEach(function (row) {
        var c = row.querySelector('input:checked');
        if (c) out[row.dataset.item] = parseInt(c.value, 10);
      });
      return out;
    }
    var f = el.querySelector('.ncq-line');
    return f ? f.value.trim() : '';
  }

  function specifyText(id) {
    var i = form.querySelector('input[name="' + id + '_other"]');
    return i ? i.value.trim() : '';
  }

  function conditionMet(raw) {
    if (!raw) return true;
    var c = typeof raw === 'string' ? JSON.parse(raw) : raw;
    var v = valueOf(c.q);
    return Array.isArray(v) ? v.indexOf(c.v) !== -1 : v === c.v;
  }

  function activeSteps() {
    return steps.filter(function (s) { return conditionMet(s.dataset.condition); });
  }

  // Returns condition-visible questions (used for order, collection, and counting).
  // Nav-hidden questions (CSS class only) are still considered "visible" for data purposes.
  function visibleQuestions(step) {
    return $all('.ncq-q', step).filter(function (q) { return !q.hidden; });
  }

  // ── Option tiles: checked state + "specify" boxes ─────────────────────
  function syncOptions(q) {
    $all('.ncq-opt, .ncq-scale-opt', q).forEach(function (lbl) {
      var input = lbl.querySelector('input');
      lbl.classList.toggle('is-checked', !!(input && input.checked));
    });
    if (q.dataset.type === 'rank') return;
    $all('.ncq-specify', q).forEach(function (box) {
      var input = q.querySelector('.ncq-opts input[value="' + box.dataset.for + '"]');
      var show  = !!(input && input.checked);
      if (show && box.hidden) {
        box.hidden = false;
        var field = box.querySelector('input');
        if (field) setTimeout(function () { field.focus({ preventScroll: true }); }, 30);
      } else if (!show) {
        box.hidden = true;
      }
    });
  }

  // ── Questions whose options come from an earlier answer ───────────────
  function sourceChoices(sourceId) {
    var src = qEl(sourceId);
    if (!src) return [];
    return $all('.ncq-opts input:checked', src).map(function (input) {
      var label = input.closest('.ncq-opt').querySelector('.ncq-opt-text').textContent.trim();
      if (input.dataset.specify) {
        var txt = specifyText(sourceId);
        if (txt) label = txt;
      }
      return { key: input.value, label: label };
    });
  }

  function buildSubset(q) {
    var id      = q.dataset.q;
    var choices = sourceChoices(q.dataset.source);
    var sig     = JSON.stringify(choices);
    if (q._sig === sig) return;
    q._sig = sig;
    var wrap    = q.querySelector('.ncq-opts');
    var picked  = valueOf(id) || [];
    wrap.innerHTML = '';
    choices.forEach(function (c) {
      var lbl = document.createElement('label');
      lbl.className = 'ncq-opt';
      lbl.innerHTML = '<input type="checkbox"><span class="ncq-opt-mark" aria-hidden="true"></span><span class="ncq-opt-text"></span>';
      var input = lbl.querySelector('input');
      input.name    = id + '[]';
      input.value   = c.key;
      input.checked = picked.indexOf(c.key) !== -1;
      lbl.querySelector('.ncq-opt-text').textContent = c.label;
      wrap.appendChild(lbl);
    });
    q.querySelector('.ncq-q-empty').hidden = choices.length > 0;
    syncOptions(q);
  }

  function buildRank(q) {
    var choices = sourceChoices(q.dataset.source);
    var sig     = JSON.stringify(choices);
    if (q._sig === sig) return;
    q._sig    = sig;
    var keys   = choices.map(function (c) { return c.key; });
    var pool   = q.querySelector('.ncq-rank-pool');
    q._rank   = (q._rank || []).filter(function (k) { return keys.indexOf(k) !== -1; });
    q._labels = {};
    choices.forEach(function (c) { q._labels[c.key] = c.label; });

    pool.innerHTML = '';
    choices.forEach(function (c) {
      var b = document.createElement('button');
      b.type      = 'button';
      b.className = 'ncq-rank-chip';
      b.dataset.key = c.key;
      b.innerHTML = '<span class="ncq-rank-chip-n"></span><span></span>';
      b.lastChild.textContent = c.label;
      pool.appendChild(b);
    });

    var empty = choices.length === 0;
    q.querySelector('.ncq-q-empty').hidden      = !empty;
    q.querySelector('.ncq-rank-slots').hidden   = empty;
    q.querySelector('.ncq-rank-help').hidden    = empty;
    $all('.ncq-rank-slot', q).forEach(function (slot, i) {
      slot.hidden = i >= Math.max(choices.length, 1);
    });
    renderRank(q);
  }

  function initFixedRank(q) {
    q._rank   = [];
    q._labels = {};
    $all('.ncq-rank-chip', q).forEach(function (chip) {
      q._labels[chip.dataset.key] = chip.lastElementChild.textContent.trim();
    });
    renderRank(q);
  }

  function renderRank(q) {
    var need = Math.min(3, Object.keys(q._labels || {}).length);
    $all('.ncq-rank-slot', q).forEach(function (slot, i) {
      var key = q._rank[i];
      slot.classList.toggle('is-filled', !!key);
      slot.querySelector('strong').textContent = key ? q._labels[key] : '—';
    });
    $all('.ncq-rank-chip', q).forEach(function (chip) {
      var pos = q._rank.indexOf(chip.dataset.key);
      chip.classList.toggle('is-ranked', pos !== -1);
      chip.setAttribute('aria-pressed', pos !== -1 ? 'true' : 'false');
      chip.querySelector('.ncq-rank-chip-n').textContent = pos !== -1 ? pos + 1 : '';
      chip.disabled = pos === -1 && q._rank.length >= need;
    });
    $all('.ncq-specify', q).forEach(function (box) {
      var on = q._rank.indexOf(box.dataset.for) !== -1;
      if (on && box.hidden) {
        box.hidden = false;
        var field = box.querySelector('input');
        if (field) setTimeout(function () { field.focus({ preventScroll: true }); }, 30);
      } else if (!on) {
        box.hidden = true;
      }
    });
  }

  // ── Conditions ─────────────────────────────────────────────────────────
  // Refreshes condition-based question visibility (q.hidden).
  // Separately, nav-hiding (.ncq-q--nav-hidden) is managed by showQuestion / reapplyNavHiding.
  function refreshConditions() {
    $all('.ncq-q[data-condition]').forEach(function (q) {
      q.hidden = !conditionMet(q.dataset.condition);
    });
    $all('.ncq-q[data-source]').forEach(function (q) {
      if (q.dataset.type === 'rank') buildRank(q); else buildSubset(q);
    });
  }

  // Re-applies nav-hiding after a condition or answer change.
  // Clamps curQ if the visible question count changed.
  function reapplyNavHiding() {
    var step      = steps[curStep];
    var qs        = visibleQuestions(step);
    var allAtOnce = step.dataset.all === '1';

    if (allAtOnce) {
      curQ = qs.length - 1;
      qs.forEach(function (q) { q.classList.remove('ncq-q--nav-hidden'); });
    } else {
      curQ = Math.max(0, Math.min(curQ, qs.length - 1));
      qs.forEach(function (q, i) {
        q.classList.toggle('ncq-q--nav-hidden', i !== curQ);
      });
    }
    updateNav();
  }

  function refreshDependents() {
    refreshConditions();
    reapplyNavHiding();
  }

  // ── Validation ─────────────────────────────────────────────────────────
  function setError(q, msg) {
    q.classList.toggle('has-error', !!msg);
    var p = q.querySelector('.ncq-q-error');
    if (p) p.textContent = msg || '';
  }

  function validateQuestion(q) {
    var id   = q.dataset.q;
    var type = q.dataset.type;
    var req  = q.dataset.required === '1';
    var v    = valueOf(id);

    $all('.ncq-scale-row', q).forEach(function (r) { r.classList.remove('is-missing'); });
    $all('.ncq-specify', q).forEach(function (b) { b.classList.remove('has-error'); });

    if (type === 'single' && req && !v) return t.err_single;
    if (type === 'text'   && req && !v) return t.err_text;
    if (type === 'multi'  && req && v.length === 0 && q.querySelector('.ncq-opts input')) return t.err_multi;
    if (type === 'scale'  && req) {
      var missing = $all('.ncq-scale-row', q).filter(function (r) { return !r.querySelector('input:checked'); });
      missing.forEach(function (r) { r.classList.add('is-missing'); });
      if (missing.length) return t.err_scale;
    }
    if (type === 'rank' && req) {
      var need = Math.min(3, Object.keys(q._labels || {}).length);
      if (need > 0 && v.length < need) return t.err_rank;
    }
    if (type === 'input' && req && !v) {
      return id === 'phone' ? t.err_phone : id === 'email' ? t.err_email : t.err_name;
    }
    if (type === 'input' && q.querySelector('input[type="email"]') && v && !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v)) {
      return t.err_email;
    }
    if (id === 'phone' && v && iti && iti.isValidNumber() === false) return t.err_phone;
    var bad = $all('.ncq-specify:not([hidden])', q).filter(function (b) { return !b.querySelector('input').value.trim(); });
    if (bad.length) {
      bad.forEach(function (b) { b.classList.add('has-error'); });
      return t.err_specify;
    }
    return '';
  }

  // ── Layout helpers ─────────────────────────────────────────────────────
  function headerOffset() {
    var h = 0;
    $all('header, .elementor-location-header, #masthead, .site-header, #wpadminbar', document).forEach(function (el) {
      var cs = getComputedStyle(el);
      var r  = el.getBoundingClientRect();
      if ((cs.position === 'fixed' || cs.position === 'sticky') && r.top <= 60 && r.height > 0) {
        h = Math.max(h, r.bottom);
      }
    });
    return h;
  }

  function scrollToEl(el, extra) {
    var y = el.getBoundingClientRect().top + window.pageYOffset - headerOffset() - (extra || 0);
    window.scrollTo({ top: Math.max(0, y), behavior: 'smooth' });
  }

  function setStickyOffset() {
    root.style.setProperty('--ncq-sticky', (headerOffset() + 24) + 'px');
  }

  // ── Visual (sticky photo panel) ────────────────────────────────────────
  function setVisual(key, place, num) {
    $all('.ncq-visual-img', root).forEach(function (f) {
      f.classList.toggle('is-active', f.dataset.visual === key);
    });
    if (capPlace) capPlace.textContent = place;
    if (capNum)   capNum.textContent   = num;
  }

  // ── Progress & navigation buttons ──────────────────────────────────────
  function totalQuestions() {
    var n = 0;
    activeSteps().forEach(function (s) { n += visibleQuestions(s).length; });
    return n;
  }

  function absoluteQIndex() {
    var pos       = 0;
    var act       = activeSteps();
    var curStepEl = steps[curStep];
    for (var i = 0; i < act.length; i++) {
      if (act[i] === curStepEl) { pos += curQ; break; }
      pos += visibleQuestions(act[i]).length;
    }
    return pos;
  }

  function updateNav() {
    var act        = activeSteps();
    var step       = steps[curStep];
    var pos        = act.indexOf(step);
    var qs         = visibleQuestions(step);
    var isLastQ    = curQ >= qs.length - 1;
    var isLastStep = pos === act.length - 1;
    var isLast     = isLastQ && isLastStep;
    var isFirst    = pos <= 0 && curQ <= 0;

    btnPrev.hidden = isFirst;
    btnNext.hidden = isLast;
    btnSend.hidden = !isLast;

    progCount.innerHTML = t.chapter + ' <b>' + pad(pos + 1) + '</b> ' + t.of + ' ' + pad(act.length);
    progTitle.textContent = step.dataset.title;

    var total = totalQuestions();
    var absQ  = absoluteQIndex();
    var pct   = total > 0 ? Math.round(((absQ + 1) / (total + 1)) * 100) : 0;
    progBar.firstElementChild.style.width = pct + '%';
    progBar.setAttribute('aria-valuenow', pct);
  }

  // ── Show a specific question within a step ─────────────────────────────
  function showQuestion(stepIdx, qIdx, scroll) {
    var prevStepIdx = curStep;
    var step        = steps[stepIdx];

    if (stepIdx !== prevStepIdx) {
      // Un-nav-hide all questions in the step we're leaving (so counts work on return).
      $all('.ncq-q', steps[prevStepIdx]).forEach(function (q) {
        q.classList.remove('ncq-q--nav-hidden');
      });
      // Show the new step, hide all others.
      steps.forEach(function (s, i) { s.hidden = i !== stepIdx; });
      // Chapter entrance animation.
      step.classList.remove('is-entering');
      void step.offsetWidth;
      step.classList.add('is-entering');
    }

    curStep = stepIdx;

    // Recalculate condition-based visibility (only touches q.hidden).
    refreshConditions();

    var qs       = visibleQuestions(step);
    var allAtOnce = step.dataset.all === '1';

    if (allAtOnce) {
      // Show every question in the step simultaneously (contact page).
      curQ = qs.length - 1; // treat last Q as "current" for nav/progress logic
      qs.forEach(function (q) {
        q.classList.remove('ncq-q--nav-hidden');
        q.classList.remove('ncq-q--current');
      });
    } else {
      qIdx = Math.max(0, Math.min(qIdx, qs.length - 1));
      curQ = qIdx;

      // Nav-hide all condition-visible questions except the current one.
      qs.forEach(function (q, i) {
        var active = (i === qIdx);
        q.classList.toggle('ncq-q--nav-hidden', !active);
        if (active) {
          // Trigger entry animation by removing + re-adding the class.
          q.classList.remove('ncq-q--current');
          void q.offsetWidth;
          q.classList.add('ncq-q--current');
        }
      });
    }

    // Update the chapter-number label inside the legend.
    var act  = activeSteps();
    var pos  = act.indexOf(step);
    var sNum = step.querySelector('.ncq-step-n');
    if (sNum) sNum.textContent = pad(pos + 1);

    // Image only changes when the chapter changes.
    setVisual(step.dataset.step, step.dataset.place, pad(pos + 1));

    // Auto-set phone country flag when entering the contact step.
    // We read directly from the checked radio (works even when the nationality
    // fieldset is hidden) so the flag reflects the user's actual selection.
    if (iti && step.dataset.step === 'contact' && !phoneEl.value.trim()) {
      var natInput = form.querySelector('input[name="nationality"]:checked');
      var cc = natInput ? NAT_COUNTRY[natInput.value] : null;
      if (cc) iti.setCountry(cc);
    }

    formErr.textContent = '';
    if (scroll) scrollToEl(shell, 0);

    updateNav();
  }

  // ── Termination ────────────────────────────────────────────────────────
  function checkTerminate(q) {
    if (q.dataset.type !== 'single') return false;
    var checked = q.querySelector('input:checked');
    return !!(checked && checked.dataset.terminate === '1');
  }

  function terminate() {
    form.hidden = true;
    if (terminated) {
      terminated.hidden = false;
      progBar.firstElementChild.style.width = '100%';
      progBar.setAttribute('aria-valuenow', 100);
      progCount.textContent = '';
      progTitle.textContent = '';
      var sep = root.querySelector('.ncq-progress-sep');
      if (sep) sep.hidden = true;
      setVisual('terminated', terminated.dataset.place || '', '—');
      scrollToEl(shell, 0);
      terminated.focus({ preventScroll: true });
    }
  }

  // ── Navigation (next / back) ───────────────────────────────────────────
  function go(dir) {
    var step = steps[curStep];
    var qs   = visibleQuestions(step);
    var act  = activeSteps();
    var pos  = act.indexOf(step);

    if (dir > 0) {
      // Validate the current question before advancing.
      var q   = qs[curQ];
      var msg = validateQuestion(q);
      setError(q, msg);
      if (msg) {
        scrollToEl(q, 40);
        var focusable = q.querySelector('.ncq-specify.has-error input, input, button, textarea');
        if (focusable) setTimeout(function () { focusable.focus({ preventScroll: true }); }, 350);
        return;
      }

      // Check if this answer terminates the questionnaire.
      if (checkTerminate(q)) {
        terminate();
        return;
      }

      if (curQ < qs.length - 1) {
        // More questions remain in this chapter → show next question.
        showQuestion(curStep, curQ + 1, true);
      } else {
        // Last question of this chapter → advance to next chapter.
        var nextStep = act[pos + 1];
        if (nextStep) showQuestion(steps.indexOf(nextStep), 0, true);
      }
    } else {
      // Going backwards.
      if (curQ > 0) {
        showQuestion(curStep, curQ - 1, true);
      } else {
        var prevStep = act[pos - 1];
        if (prevStep) {
          var prevQs = visibleQuestions(prevStep);
          showQuestion(steps.indexOf(prevStep), prevQs.length - 1, true);
        }
      }
    }
  }

  // ── Collect + submit ───────────────────────────────────────────────────
  function collect() {
    var answers = {};
    activeSteps().forEach(function (step) {
      visibleQuestions(step).forEach(function (q) {
        var id = q.dataset.q;
        answers[id] = valueOf(id);
        if (id === 'phone' && iti && answers[id]) answers[id] = iti.getNumber() || answers[id];
        var sp = q.querySelector('.ncq-specify:not([hidden]) input');
        if (sp) answers[id + '_other'] = sp.value.trim();
      });
    });
    return answers;
  }

  function submit() {
    // Validate all questions in the current step (handles all-at-once contact page).
    var step      = steps[curStep];
    var qs        = visibleQuestions(step);
    var allAtOnce = step.dataset.all === '1';
    var toValidate = allAtOnce ? qs : [qs[curQ]];
    var firstErr  = null;

    toValidate.forEach(function (q) {
      var msg = validateQuestion(q);
      setError(q, msg);
      if (msg && !firstErr) firstErr = q;
    });
    if (firstErr) {
      scrollToEl(firstErr, 40);
      var focusable = firstErr.querySelector('.ncq-specify.has-error input, input, button, textarea');
      if (focusable) setTimeout(function () { focusable.focus({ preventScroll: true }); }, 350);
      return;
    }

    btnSend.classList.add('is-loading');
    btnSend.querySelector('.ncq-btn-label').textContent = t.sending;
    formErr.textContent = '';

    var body = new FormData();
    body.append('action',      'ncq_submit');
    body.append('nonce',       NCQ.nonce);
    body.append('lang',        NCQ.lang);
    body.append('answers',     JSON.stringify(collect()));
    body.append('ncq_website', form.querySelector('[name="ncq_website"]').value);

    fetch(NCQ.ajax_url, { method: 'POST', body: body, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res && res.success) { done(); }
        else { fail(res && typeof res.data === 'string' ? res.data : t.err_generic); }
      })
      .catch(function () { fail(t.err_network); });
  }

  function fail(msg) {
    btnSend.classList.remove('is-loading');
    btnSend.querySelector('.ncq-btn-label').textContent = t.submit;
    formErr.textContent = msg;
  }

  function done() {
    track({ event: 'questionnaire_submit', form_status: 'success' });
    form.hidden = true;
    thanks.hidden = false;
    progBar.firstElementChild.style.width = '100%';
    progBar.setAttribute('aria-valuenow', 100);
    progCount.textContent = '';
    progTitle.textContent = '';
    var sep = root.querySelector('.ncq-progress-sep');
    if (sep) sep.hidden = true;
    setVisual('thanks', thanks.dataset.place || '', '—');
    scrollToEl(shell, 0);
    thanks.focus({ preventScroll: true });
    downloadPdf();
  }

  function downloadPdf() {
    if (!NCQ.pdf_url) return;
    var a = document.createElement('a');
    a.href = NCQ.pdf_url;
    a.download = NCQ.pdf_name || '';
    a.rel = 'noopener';
    a.style.display = 'none';
    document.body.appendChild(a);
    a.click();
    setTimeout(function () { a.remove(); }, 1000);
  }

  // ── Events ─────────────────────────────────────────────────────────────
  form.addEventListener('change', function (e) {
    var q = e.target.closest('.ncq-q');
    if (!q) return;

    // Mutual exclusivity: "None" ↔ all sourced options (for questions like nc4).
    if (e.target.type === 'checkbox') {
      var noneInput = q.querySelector('input[data-none="1"]');
      if (noneInput) {
        if (e.target === noneInput && noneInput.checked) {
          // "None" just ticked → clear every other checkbox in this question.
          $all('input[name="' + q.dataset.q + '[]"]', q).forEach(function (inp) {
            if (inp !== noneInput) inp.checked = false;
          });
        } else if (e.target !== noneInput && e.target.checked) {
          // Any real option ticked → clear "None".
          noneInput.checked = false;
        }
      }
    }

    syncOptions(q);
    if (q.classList.contains('has-error')) setError(q, validateQuestion(q));
    // Refresh conditions then re-apply nav hiding (keeps one question visible).
    refreshDependents();
    // Mirror nationality → phone country flag as soon as the user picks a nationality.
    if (e.target.name === 'nationality' && iti && !phoneEl.value.trim()) {
      var cc = NAT_COUNTRY[e.target.value];
      if (cc) iti.setCountry(cc);
    }
  });

  form.addEventListener('input', function (e) {
    var q = e.target.closest('.ncq-q');
    if (!q) return;
    // Typing into an "Other" box may rename chips downstream.
    if (e.target.name && /_other$/.test(e.target.name)) refreshDependents();
    if (q.classList.contains('has-error')) setError(q, validateQuestion(q));
  });

  form.addEventListener('click', function (e) {
    var chip = e.target.closest('.ncq-rank-chip');
    if (!chip) return;
    var q   = chip.closest('.ncq-q');
    if (!q._rank || !q._labels) initFixedRank(q);
    var key = chip.dataset.key;
    var pos = q._rank.indexOf(key);
    if (pos !== -1) q._rank.splice(pos, 1);
    else if (q._rank.length < Math.min(3, Object.keys(q._labels).length)) q._rank.push(key);
    renderRank(q);
    if (q.classList.contains('has-error')) setError(q, validateQuestion(q));
  });

  var continueTracked = false;
  btnNext.addEventListener('click', function () {
    if (!continueTracked) {
      continueTracked = true;
      track({ event: 'questionnaire_subscribe', button_name: 'continue' });
    }
    go(1);
  });
  btnPrev.addEventListener('click', function () { go(-1); });
  form.addEventListener('submit', function (e) { e.preventDefault(); submit(); });

  // Enter in a single-line field moves forward instead of submitting early.
  form.addEventListener('keydown', function (e) {
    if (e.target.closest('.iti__dropdown-content')) return;
    if (e.key === 'Enter' && e.target.tagName === 'INPUT' && e.target.type !== 'checkbox' && e.target.type !== 'radio') {
      e.preventDefault();
      if (!btnNext.hidden) btnNext.click(); else submit();
    }
  });

  var begin = root.querySelector('.ncq-begin');
  if (begin) {
    begin.addEventListener('click', function (e) {
      e.preventDefault();
      track({ event: 'questionnaire_subscribe', button_name: 'begin_questionnaire' });
      scrollToEl(shell, 0);
    });
  }

  // Initialise fixed rank lists (rank questions with no source).
  $all('.ncq-q[data-type="rank"]:not([data-source])').forEach(initFixedRank);

  window.addEventListener('resize', setStickyOffset);
  setStickyOffset();
  $all('.ncq-q').forEach(syncOptions);

  // Start on step 0, question 0.
  showQuestion(0, 0, false);
})();
