/**
 * gobuy Admin — Global Command Palette
 * ====================================
 * Pure vanilla JS, zero external dependencies.
 * Lazy-loaded on first Ctrl+K / trigger click, cached for session.
 *
 * Features:
 *  - Multi-factor fuzzy search with intelligent scoring
 *  - Keyboard navigation (↑ ↓ Enter Esc Tab Shift+Tab)
 *  - Recent & frequent page tracking (localStorage)
 *  - Permission-filtered index embedded by PHP (window.__gbPalette)
 *  - Breadcrumb paths per result
 *  - Match highlighting
 *  - ARIA-compliant (combobox role, activedescendant, live region)
 *  - Dark/light mode via existing CSS variables
 *  - Mobile full-screen overlay
 *  - Future-proof: static index today; pluggable async sources later
 *
 * @module CommandPalette
 */
(function () {
  'use strict';

  // ── Constants ─────────────────────────────────────────────────────────────
  var STORAGE_RECENT = 'gba_recent';
  var STORAGE_FREQ   = 'gba_freq';
  var MAX_RECENT     = 8;
  var MAX_RESULTS    = 60;
  var DEBOUNCE_MS    = 120;
  var SECTION_CAP    = 6; // max items per category in results

  // ── State ─────────────────────────────────────────────────────────────────
  var state = {
    open:       false,
    index:      [],   // the full search index
    results:    [],   // current flat list of visible items
    cursor:     -1,   // active item index in results
    query:      '',
    debTimer:   null,
    recent:     [],   // ids of recently visited pages
    freq:       {},   // id -> visit count
    initialized:false,
  };

  // ── DOM refs (set on first open) ──────────────────────────────────────────
  var dom = {
    backdrop: null,
    dialog:   null,
    input:    null,
    results:  null,
    clearBtn: null,
    liveReg:  null, // ARIA live region
  };

  // ── Initialise ─────────────────────────────────────────────────────────────
  function init() {
    if (state.initialized) { return; }
    state.initialized = true;

    // Load index embedded by PHP
    var raw = (window.__gbPalette || []);
    state.index = raw;

    // Load history
    try {
      state.recent = JSON.parse(localStorage.getItem(STORAGE_RECENT) || '[]');
      state.freq   = JSON.parse(localStorage.getItem(STORAGE_FREQ)   || '{}');
    } catch (_) {
      state.recent = [];
      state.freq   = {};
    }

    // Cache DOM refs
    dom.backdrop  = document.getElementById('gcp-backdrop');
    dom.dialog    = document.getElementById('gcp-dialog');
    dom.input     = document.getElementById('gcp-input');
    dom.results   = document.getElementById('gcp-results');
    dom.clearBtn  = document.getElementById('gcp-clear');
    dom.cancelBtn = document.getElementById('gcp-cancel');
    dom.liveReg   = document.getElementById('gcp-live');

    if (!dom.backdrop || !dom.input) { return; }

    // Input handler
    dom.input.addEventListener('input', onInput);

    // Clear button
    if (dom.clearBtn) {
      dom.clearBtn.addEventListener('click', function () {
        dom.input.value = '';
        dom.input.focus();
        clearBtn(false);
        render('');
      });
    }

    // Cancel button (mobile)
    if (dom.cancelBtn) {
      dom.cancelBtn.addEventListener('click', close);
    }

    // Backdrop click closes
    dom.backdrop.addEventListener('mousedown', function (e) {
      if (e.target === dom.backdrop) { close(); }
    });

    // Keyboard within dialog
    dom.backdrop.addEventListener('keydown', onKeydown);

    // Feather icon replacement inside palette results
    // (feather.replace() is called after each render if feather is loaded)
  }

  // ── Open / Close ──────────────────────────────────────────────────────────
  function open() {
    if (state.open) {
      dom.input && dom.input.focus();
      return;
    }

    init();
    if (!dom.backdrop) { return; }

    state.open   = true;
    state.cursor = -1;
    state.query  = '';

    dom.input.value = '';
    clearBtn(false);
    dom.backdrop.removeAttribute('aria-hidden');
    dom.backdrop.classList.add('gcp-open');
    document.body.style.overflow = 'hidden';

    // Focus the input on next frame so the animation starts first
    requestAnimationFrame(function () {
      dom.input.focus();
      render(''); // show recents / popular
    });

    // Trap focus to the dialog
    document.addEventListener('focusin', trapFocus, true);
  }

  function close() {
    if (!state.open) { return; }

    state.open = false;
    dom.backdrop.classList.remove('gcp-open');
    dom.backdrop.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';

    document.removeEventListener('focusin', trapFocus, true);

    // Return focus to the element that opened the palette
    var trigger = document.getElementById('gcp-trigger');
    if (trigger) { trigger.focus(); }
  }

  function toggle() {
    state.open ? close() : open();
  }

  // ── Focus trap ────────────────────────────────────────────────────────────
  function trapFocus(e) {
    if (!dom.dialog) { return; }
    if (!dom.dialog.contains(e.target)) {
      e.preventDefault();
      dom.input && dom.input.focus();
    }
  }

  // ── Input handler ─────────────────────────────────────────────────────────
  function onInput() {
    clearTimeout(state.debTimer);
    var q = dom.input.value;
    clearBtn(q.length > 0);
    state.debTimer = setTimeout(function () {
      render(q);
    }, DEBOUNCE_MS);
  }

  function clearBtn(visible) {
    if (!dom.clearBtn) { return; }
    dom.clearBtn.classList.toggle('gcp-visible', visible);
  }

  // ── Keyboard navigation ───────────────────────────────────────────────────
  function onKeydown(e) {
    switch (e.key) {
      case 'Escape':
        e.preventDefault();
        close();
        break;

      case 'ArrowDown':
        e.preventDefault();
        moveCursor(1);
        break;

      case 'ArrowUp':
        e.preventDefault();
        moveCursor(-1);
        break;

      case 'Tab':
        e.preventDefault();
        moveCursor(e.shiftKey ? -1 : 1);
        break;

      case 'Enter':
        e.preventDefault();
        if (state.cursor >= 0 && state.results[state.cursor]) {
          navigate(state.results[state.cursor]);
        } else if (state.results.length > 0) {
          navigate(state.results[0]);
        }
        break;

      default:
        // Any printable character focuses input
        if (e.key.length === 1 && !e.ctrlKey && !e.metaKey && !e.altKey) {
          dom.input.focus();
        }
        break;
    }
  }

  function moveCursor(delta) {
    var total = state.results.length;
    if (total === 0) { return; }

    var next = state.cursor + delta;
    if (next < 0)       { next = total - 1; }
    if (next >= total)  { next = 0; }

    setCursor(next);
  }

  function setCursor(index) {
    state.cursor = index;
    updateActiveItem();
    scrollActiveIntoView();
  }

  function updateActiveItem() {
    var items = dom.results.querySelectorAll('.gcp-item');
    items.forEach(function (el, i) {
      var active = (i === state.cursor);
      el.classList.toggle('gcp-active', active);
      el.setAttribute('aria-selected', active ? 'true' : 'false');
      if (active) {
        dom.input.setAttribute('aria-activedescendant', el.id);
      }
    });
    if (state.cursor < 0) {
      dom.input.removeAttribute('aria-activedescendant');
    }
  }

  function scrollActiveIntoView() {
    var items = dom.results.querySelectorAll('.gcp-item');
    var el = items[state.cursor];
    if (el) {
      el.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }
  }

  // ── Navigate to a result ──────────────────────────────────────────────────
  function navigate(item) {
    if (!item || !item.url) { return; }

    // Track recents
    trackVisit(item.id);

    // Close palette immediately for snappy feel
    close();

    // Standard Laravel page navigation
    window.location.href = item.url;
  }

  // ── History tracking ──────────────────────────────────────────────────────
  function trackVisit(id) {
    if (!id) { return; }

    // Recent — prepend, dedupe, cap
    state.recent = [id].concat(state.recent.filter(function (x) { return x !== id; }))
                       .slice(0, MAX_RECENT);

    // Frequency
    state.freq[id] = (state.freq[id] || 0) + 1;

    try {
      localStorage.setItem(STORAGE_RECENT, JSON.stringify(state.recent));
      localStorage.setItem(STORAGE_FREQ,   JSON.stringify(state.freq));
    } catch (_) { /* ignore quota errors */ }
  }

  // ── Search engine ─────────────────────────────────────────────────────────
  function search(query) {
    var q = normalize(query);

    if (q === '') {
      return buildEmptyQueryResults();
    }

    var scored = [];
    var index  = state.index;

    for (var i = 0; i < index.length; i++) {
      var item  = index[i];
      var score = scoreItem(item, q);
      if (score > 0) {
        scored.push({ item: item, score: score });
      }
    }

    // Sort by score desc
    scored.sort(function (a, b) { return b.score - a.score; });

    // Cap total
    return scored.slice(0, MAX_RESULTS).map(function (s) { return s.item; });
  }

  /**
   * Score a single index item against the normalised query.
   * Returns 0 if the item should be excluded entirely.
   */
  function scoreItem(item, q) {
    var score = 0;
    var label = normalize(item.label || '');

    // ── Title scoring ───────────────────────────────────────────────────────
    if (label === q) {
      score += 100;
    } else if (label.startsWith(q)) {
      score += 80;
    } else if (label.includes(q)) {
      score += 60;
    } else {
      // Word-level partial match
      var labelWords = label.split(/\s+/);
      var qWords     = q.split(/\s+/);
      var wordHits   = 0;
      for (var wi = 0; wi < qWords.length; wi++) {
        for (var wj = 0; wj < labelWords.length; wj++) {
          if (labelWords[wj].startsWith(qWords[wi])) { wordHits++; break; }
        }
      }
      if (wordHits > 0) {
        score += 40 * (wordHits / qWords.length);
      }
    }

    // ── Alias scoring ────────────────────────────────────────────────────────
    var aliases = item.aliases || [];
    for (var ai = 0; ai < aliases.length; ai++) {
      var alias = normalize(aliases[ai]);
      if (alias === q) { score += 55; break; }
      if (alias.startsWith(q) || alias.includes(q)) { score += 35; break; }
      if (q.startsWith(alias)) { score += 25; break; }
    }

    // ── Keyword scoring ──────────────────────────────────────────────────────
    var keywords = item.keywords || [];
    for (var ki = 0; ki < keywords.length; ki++) {
      var kw = normalize(keywords[ki]);
      if (kw === q) { score += 45; break; }
      if (kw.startsWith(q) || kw.includes(q)) { score += 25; break; }
      if (q.startsWith(kw)) { score += 15; break; }
    }

    // ── Subtitle scoring ────────────────────────────────────────────────────
    var subtitle = normalize(item.subtitle || '');
    if (subtitle.includes(q)) { score += 10; }

    // ── Category scoring ────────────────────────────────────────────────────
    var category = normalize(item.category || '');
    if (category.includes(q)) { score += 8; }

    // No match at all
    if (score === 0) {
      // Fuzzy character sequence match as last resort
      var fuzzyScore = fuzzy(q, label);
      if (fuzzyScore > 0) { score += fuzzyScore; }
      else { return 0; }
    }

    // ── Boosts ──────────────────────────────────────────────────────────────
    score += (item.priority || 50) * 0.3;

    // Recent boost
    var recentIdx = state.recent.indexOf(item.id);
    if (recentIdx !== -1) {
      score += Math.max(0, (MAX_RECENT - recentIdx)) * 2;
    }

    // Frequency boost (log scale)
    var freq = state.freq[item.id] || 0;
    if (freq > 0) {
      score += Math.min(15, Math.log2(freq + 1) * 5);
    }

    return score;
  }

  /**
   * Fuzzy character-sequence matching (like cmd+k feel in Linear).
   * Returns 0–20 based on how well characters match in order.
   */
  function fuzzy(query, target) {
    var qi = 0;
    var ti = 0;
    var score = 0;
    var consecutive = 0;
    var lastMatchIdx = -1;

    while (qi < query.length && ti < target.length) {
      if (query[qi] === target[ti]) {
        score++;
        if (lastMatchIdx === ti - 1) {
          consecutive++;
          score += consecutive;
        } else {
          consecutive = 0;
        }
        lastMatchIdx = ti;
        qi++;
      }
      ti++;
    }

    // All query characters must be found
    if (qi < query.length) { return 0; }

    // Normalise to 0–20
    return Math.min(20, score);
  }

  /**
   * When the query is empty, show recent + frequent pages.
   */
  function buildEmptyQueryResults() {
    var results = [];
    var seen    = {};

    // Recent first
    for (var ri = 0; ri < state.recent.length; ri++) {
      var id   = state.recent[ri];
      var item = findById(id);
      if (item && !seen[id]) {
        results.push(item);
        seen[id] = true;
        if (results.length >= 5) { break; }
      }
    }

    // Frequent (excluding already-shown recents)
    var freqPairs = Object.keys(state.freq).map(function (id) {
      return { id: id, count: state.freq[id] };
    });
    freqPairs.sort(function (a, b) { return b.count - a.count; });

    for (var fi = 0; fi < freqPairs.length && results.length < 8; fi++) {
      var fid   = freqPairs[fi].id;
      var fitem = findById(fid);
      if (fitem && !seen[fid]) {
        results.push(fitem);
        seen[fid] = true;
      }
    }

    // If still empty, show high-priority defaults
    if (results.length === 0) {
      var defaults = state.index.slice()
        .sort(function (a, b) { return (b.priority || 50) - (a.priority || 50); })
        .slice(0, 8);
      return defaults;
    }

    return results;
  }

  function findById(id) {
    for (var i = 0; i < state.index.length; i++) {
      if (state.index[i].id === id) { return state.index[i]; }
    }
    return null;
  }

  function normalize(str) {
    return (str || '').toLowerCase().trim();
  }

  // ── Render ────────────────────────────────────────────────────────────────
  function render(query) {
    state.query  = query;
    state.cursor = -1;

    var items = search(query);
    state.results = items;

    if (items.length === 0) {
      dom.results.innerHTML = buildEmptyState(query);
    } else {
      dom.results.innerHTML = buildResultsHTML(items, query);
    }

    // Replace feather icons in the new DOM
    if (window.feather) {
      window.feather.replace({ 'stroke-width': 2, width: 15, height: 15 });
    }

    // Update ARIA live region
    if (dom.liveReg) {
      dom.liveReg.textContent = items.length > 0
        ? items.length + ' results'
        : 'No results found';
    }
  }

  function buildResultsHTML(items, query) {
    var html     = '';
    var q        = normalize(query);
    var groups   = groupByCategory(items, query);
    var itemIdx  = 0;

    groups.forEach(function (group) {
      html += '<div class="gcp-section" role="presentation">' + escapeHtml(group.label) + '</div>';

      group.items.forEach(function (item) {
        html += buildItemHTML(item, q, itemIdx);
        itemIdx++;
      });
    });

    return html;
  }

  function groupByCategory(items, query) {
    var q = normalize(query);

    // When query is empty, keep one flat group with label based on context
    if (q === '') {
      var hasRecent = state.recent.length > 0;
      return [{
        label: hasRecent ? 'Recent & Frequent' : 'Popular',
        items: items,
      }];
    }

    // Group by category
    var map   = {};
    var order = [];

    items.forEach(function (item) {
      var cat = item.category || 'Other';
      if (!map[cat]) {
        map[cat]  = [];
        order.push(cat);
      }
      if (map[cat].length < SECTION_CAP) {
        map[cat].push(item);
      }
    });

    return order.map(function (cat) {
      return { label: cat, items: map[cat] };
    });
  }

  function buildItemHTML(item, query, itemIdx) {
    var icon     = item.icon || 'link';
    var label    = highlightMatch(escapeHtml(item.label || ''), query);
    var subtitle = item.subtitle
      ? '<div class="gcp-item-subtitle">' + escapeHtml(item.subtitle) + '</div>'
      : '';
    var breadcrumb = item.breadcrumb
      ? buildBreadcrumbHTML(item.breadcrumb)
      : '';

    var id = 'gcp-item-' + itemIdx;

    return (
      '<a class="gcp-item" href="' + escapeHtml(item.url) + '"' +
        ' id="' + id + '"' +
        ' role="option"' +
        ' aria-selected="false"' +
        ' data-id="' + escapeHtml(item.id) + '"' +
        ' tabindex="-1"' +
        ' onclick="return window.GobuyPalette._onItemClick(event, this)"' +
      '>' +
        '<span class="gcp-item-icon">' +
          '<span data-feather="' + escapeHtml(icon) + '"></span>' +
        '</span>' +
        '<span class="gcp-item-body">' +
          '<span class="gcp-item-label">' + label + '</span>' +
          subtitle +
        '</span>' +
        breadcrumb +
      '</a>'
    );
  }

  function buildBreadcrumbHTML(breadcrumb) {
    var parts = breadcrumb.split(' > ');
    if (parts.length <= 1) { return ''; }

    // Show only last two segments on desktop to keep it compact
    var display = parts.length > 3 ? parts.slice(-2) : parts;
    var html    = '<span class="gcp-item-breadcrumb" aria-label="Location: ' + escapeHtml(breadcrumb) + '">';

    display.forEach(function (part, i) {
      if (i > 0) {
        html += '<span class="gcp-item-breadcrumb-sep">›</span>';
      }
      html += '<span>' + escapeHtml(part) + '</span>';
    });

    html += '</span>';
    return html;
  }

  function buildEmptyState(query) {
    return (
      '<div class="gcp-empty">' +
        '<div class="gcp-empty-icon"><span data-feather="search"></span></div>' +
        '<div class="gcp-empty-title">No results for "' + escapeHtml(query) + '"</div>' +
        '<div class="gcp-empty-subtitle">Try a different keyword or check spelling</div>' +
      '</div>'
    );
  }

  // ── Highlight matching text ───────────────────────────────────────────────
  function highlightMatch(escapedLabel, query) {
    if (!query) { return escapedLabel; }

    // Work on the raw (unescaped) label for position finding, then escape
    var q    = query.toLowerCase();
    var raw  = escapedLabel; // already HTML-escaped
    var idx  = raw.toLowerCase().indexOf(q);

    if (idx === -1) { return escapedLabel; }

    return (
      raw.slice(0, idx) +
      '<mark class="gcp-highlight">' +
      raw.slice(idx, idx + q.length) +
      '</mark>' +
      raw.slice(idx + q.length)
    );
  }

  // ── Item click handler (exposed as global for inline onclick) ─────────────
  function onItemClick(e, el) {
    e.preventDefault();
    var id  = el.getAttribute('data-id');
    var url = el.getAttribute('href');

    if (id && url) {
      var item = findById(id) || { id: id, url: url };
      navigate(item);
    }
    return false;
  }

  // ── Utilities ─────────────────────────────────────────────────────────────
  function escapeHtml(str) {
    return (str || '')
      .replace(/&/g,  '&amp;')
      .replace(/</g,  '&lt;')
      .replace(/>/g,  '&gt;')
      .replace(/"/g,  '&quot;')
      .replace(/'/g,  '&#039;');
  }

  // ── Public API ────────────────────────────────────────────────────────────
  window.GobuyPalette = {
    open:        open,
    close:       close,
    toggle:      toggle,
    _onItemClick: onItemClick, // used by inline onclick on items
  };

  // If palette is somehow already in the DOM (non-lazy fallback), init now
  if (document.getElementById('gcp-backdrop')) {
    init();
  }
})();
