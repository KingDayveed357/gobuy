/* gobuy admin — in-page helpers only.
   The shell (collapse, mobile, fixed nav) is handled by phoenix.js. */
(function () {
  'use strict';

  /* ============================================================
     COLLAPSED SIDEBAR — JS Portal Flyout System
     ============================================================
     Architecture:
     - A single <div class="gb-navbar-flyout-portal"> is appended to <body>
       once, reused for every flyout.
     - Per-item flyout content is built/cloned on mouseenter and discarded on
       close (replaceChildren()), keeping DOM footprint minimal.
     - A 120 ms debounce timer prevents flicker when the pointer briefly leaves
       one element before entering the portal or another item.
     - The CSS pseudo-element bridge (::after on .nav-item-wrapper) fills the
       gap between the sidebar and the portal panel so mouseout never fires
       while the user traverses that gap.
     - For group items  : clones the .parent > .nav list into a styled panel.
     - For single links : renders a compact tooltip-style label panel.
  ============================================================ */

  var flyoutState = {
    portal: null,
    activeTrigger: null,
    hideTimer: null,
    isOpen: false,
  };

  /* ---- Utilities ---- */

  function isCollapsedSidebar() {
    return document.documentElement.classList.contains('navbar-vertical-collapsed');
  }

  function cancelFlyoutHide() {
    if (flyoutState.hideTimer) {
      window.clearTimeout(flyoutState.hideTimer);
      flyoutState.hideTimer = null;
    }
  }

  function hideFlyout() {
    cancelFlyoutHide();
    flyoutState.isOpen = false;

    if (flyoutState.portal) {
      flyoutState.portal.classList.remove('is-open');
      /*
       * Delay DOM cleanup until after the CSS opacity transition (120 ms)
       * so the panel doesn't instantly vanish — matches the transition
       * duration set on .gb-navbar-flyout-portal in admin.css.
       */
      var portalRef = flyoutState.portal;
      window.setTimeout(function () {
        if (!flyoutState.isOpen) {
          portalRef.replaceChildren();
        }
      }, 130);
    }

    flyoutState.activeTrigger = null;
  }

  function scheduleFlyoutHide() {
    cancelFlyoutHide();
    flyoutState.hideTimer = window.setTimeout(hideFlyout, 120);
  }

  /* ---- Portal management ---- */

  function ensureFlyoutPortal() {
    if (flyoutState.portal) {
      return flyoutState.portal;
    }

    var portal = document.createElement('div');
    portal.className = 'gb-navbar-flyout-portal';

    /* Keep flyout alive while the pointer is over the panel itself */
    portal.addEventListener('mouseenter', cancelFlyoutHide);
    portal.addEventListener('mouseleave', scheduleFlyoutHide);

    document.body.appendChild(portal);
    flyoutState.portal = portal;
    return portal;
  }

  /* ---- Positioning ---- */

  function positionFlyout(trigger, panel) {
    var triggerRect = trigger.getBoundingClientRect();
    var sidebar = trigger.closest('.navbar-vertical');
    var sidebarRect = sidebar ? sidebar.getBoundingClientRect() : null;
    var portal = ensureFlyoutPortal();

    /*
     * Measure panel dimensions while hidden so layout isn't affected.
     * We temporarily show with visibility:hidden to get offsetWidth/Height.
     */
    portal.style.visibility = 'hidden';
    portal.style.display = 'block';
    portal.style.top = '0px';
    portal.style.left = '0px';

    var panelWidth  = panel.offsetWidth  || 220;
    var panelHeight = panel.offsetHeight || 0;
    var vpH = window.innerHeight;
    var vpW = window.innerWidth;

    /* Vertical: align top of panel with top of trigger row (offset -4px) */
    var desiredTop = triggerRect.top - 4;
    var top = Math.max(16, Math.min(desiredTop, vpH - panelHeight - 16));

    /*
     * Horizontal: place panel 16px to the right of the sidebar's right edge.
     * Fallback to trigger's right edge + 16 if no sidebar ref.
     */
    var sidebarRight = sidebarRect ? sidebarRect.right : triggerRect.right;
    var left = sidebarRight + 16;
    left = Math.min(left, vpW - panelWidth - 16);
    if (left < 16) { left = 16; }

    portal.style.top  = top  + 'px';
    portal.style.left = left + 'px';
    portal.style.visibility = 'visible';
  }

  /* ---- Panel builders ---- */

  /**
   * Build a flyout panel for a GROUP nav item (has sub-links).
   * Clones the existing .parent > ul from the DOM, strips BS collapse
   * attributes, and wraps it in our styled container.
   *
   * @param {Element} wrapper  - The .nav-item-wrapper element
   * @returns {Element}        - The .gb-navbar-flyout-menu element
   */
  function buildGroupPanel(wrapper) {
    var sourceList = wrapper.querySelector('.parent-wrapper.label-1 > .parent');
    if (!sourceList) { return null; }

    /* Clone the <ul> but strip Bootstrap collapse behaviour */
    var clonedList = sourceList.cloneNode(true);
    clonedList.removeAttribute('id');
    clonedList.removeAttribute('data-bs-parent');
    clonedList.classList.remove('collapse');
    clonedList.classList.add('show');

    /* Apply our flyout panel classes */
    clonedList.classList.add('gb-navbar-flyout-menu', 'scrollbar');

    /* Show the section title (it's d-none in the DOM; we display it in the flyout) */
    var titleEl = clonedList.querySelector('.collapsed-nav-item-title');
    if (titleEl) {
      titleEl.classList.remove('d-none');
      titleEl.style.display = '';
    }

    return clonedList;
  }

  /**
   * Build a flyout panel for a SINGLE LINK nav item (no sub-links).
   * Renders a compact, premium tooltip-style card showing only the route title.
   *
   * @param {Element} trigger - The .nav-link.label-1 anchor element
   * @returns {Element}       - The .gb-navbar-flyout-menu element
   */
  function buildTooltipPanel(trigger) {
    var labelEl  = trigger.querySelector('.nav-link-text');
    var labelTxt = labelEl ? labelEl.textContent.trim() : trigger.textContent.trim();

    var panel = document.createElement('div');
    panel.className = 'gb-navbar-flyout-menu gb-flyout--tooltip';

    var label = document.createElement('span');
    label.className = 'gb-flyout-tooltip-label';
    label.textContent = labelTxt;

    panel.appendChild(label);
    return panel;
  }

  /* ---- Core show logic ---- */

  function showFlyout(trigger) {
    if (!isCollapsedSidebar()) { return; }

    var wrapper = trigger.closest('.nav-item-wrapper');
    if (!wrapper) { return; }

    /* If this trigger is already showing, just cancel any pending hide */
    if (flyoutState.activeTrigger === trigger && flyoutState.isOpen) {
      cancelFlyoutHide();
      return;
    }

    cancelFlyoutHide();

    var portal = ensureFlyoutPortal();
    var isGroup = wrapper.querySelector('.parent-wrapper.label-1') !== null;
    var panel;

    if (isGroup) {
      panel = buildGroupPanel(wrapper);
      if (!panel) { return; } /* no sub-items, bail silently */
    } else {
      panel = buildTooltipPanel(trigger);
    }

    /* Swap portal content atomically */
    portal.replaceChildren(panel);
    flyoutState.activeTrigger = trigger;
    flyoutState.isOpen = true;

    /* Position before making visible to avoid a frame of wrong placement */
    positionFlyout(trigger, panel);
    portal.classList.add('is-open');
  }

  /* ---- Event binding ---- */

  function bindCollapsedFlyouts() {
    /*
     * Bind to ALL .nav-item-wrapper elements (both group and link types).
     * The trigger used for showFlyout() is the .label-1.nav-link inside it.
     */
    var wrappers = document.querySelectorAll(
      '.navbar-vertical .nav-item-wrapper'
    );

    if (!wrappers.length) { return; }

    wrappers.forEach(function (wrapper) {
      /* Idempotent — skip if already bound */
      if (wrapper.dataset.gbFlyoutBound === '1') { return; }
      wrapper.dataset.gbFlyoutBound = '1';

      var trigger = wrapper.querySelector('.nav-link.label-1');
      if (!trigger) { return; }

      wrapper.addEventListener('mouseenter', function () {
        showFlyout(trigger);
      });

      wrapper.addEventListener('mouseleave', function () {
        scheduleFlyoutHide();
      });

      /* Keyboard accessibility */
      trigger.addEventListener('focus', function () {
        showFlyout(trigger);
      });

      trigger.addEventListener('blur', function () {
        scheduleFlyoutHide();
      });
    });
  }

  function initCollapsedFlyouts() {
    bindCollapsedFlyouts();

    /* Close on outside click */
    document.addEventListener('click', function (e) {
      if (!flyoutState.portal || !flyoutState.isOpen) { return; }
      if (e.target.closest('.gb-navbar-flyout-portal')) { return; }
      if (!e.target.closest('.navbar-vertical')) { hideFlyout(); }
    });

    /* Close when the sidebar collapse toggle is clicked */
    document.addEventListener('click', function (e) {
      if (e.target.closest('.navbar-vertical-toggle')) { hideFlyout(); }
    });

    /* Reposition on window resize */
    window.addEventListener('resize', function () {
      if (!flyoutState.portal || !flyoutState.activeTrigger || !flyoutState.isOpen) { return; }
      var panel = flyoutState.portal.firstElementChild;
      if (panel) { positionFlyout(flyoutState.activeTrigger, panel); }
    });

    /* Reposition on scroll (captures scroll inside the sidebar content too) */
    window.addEventListener('scroll', function () {
      if (!flyoutState.portal || !flyoutState.activeTrigger || !flyoutState.isOpen) { return; }
      var panel = flyoutState.portal.firstElementChild;
      if (panel) { positionFlyout(flyoutState.activeTrigger, panel); }
    }, true);
  }

  /* ---- Boot ---- */

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCollapsedFlyouts, { once: true });
  } else {
    initCollapsedFlyouts();
  }

  /* ============================================================
     Inline category creation from the product form
     (fetch -> append <option>)
  ============================================================ */
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.js-create-category');
    if (!btn) { return; }

    var nameEl   = document.getElementById('newCategoryName');
    var parentEl = document.getElementById('newCategoryParent');
    var errorEl  = document.getElementById('addCategoryError');
    var select   = document.querySelector(btn.getAttribute('data-target-select'));
    var token    = document.querySelector('meta[name="csrf-token"]');

    if (!nameEl || !nameEl.value.trim()) {
      if (errorEl) { errorEl.textContent = 'Please enter a category name.'; errorEl.classList.remove('d-none'); }
      return;
    }

    btn.classList.add('is-loading');
    fetch(btn.getAttribute('data-store-url'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': token ? token.getAttribute('content') : '',
      },
      body: JSON.stringify({
        name: nameEl.value.trim(),
        parent_id: parentEl && parentEl.value ? parentEl.value : null,
        is_active: 1,
      }),
    })
      .then(function (res) { return res.ok ? res.json() : res.json().then(function (j) { throw j; }); })
      .then(function (category) {
        if (select) {
          var prefix = category.parent_id ? '\u21b3 ' : '';
          var opt = new Option(prefix + category.name, category.id, true, true);
          select.add(opt);
          select.value = category.id;
        }
        if (nameEl)   { nameEl.value   = ''; }
        if (parentEl) { parentEl.value = ''; }
        if (errorEl)  { errorEl.classList.add('d-none'); }
        var modalEl = document.getElementById('addCategoryModal');
        var modal = window.bootstrap && modalEl ? window.bootstrap.Modal.getInstance(modalEl) : null;
        if (modal) { modal.hide(); }
      })
      .catch(function (err) {
        var msg = (err && err.errors && Object.values(err.errors)[0][0]) || 'Could not create category.';
        if (errorEl) { errorEl.textContent = msg; errorEl.classList.remove('d-none'); }
      })
      .finally(function () { btn.classList.remove('is-loading'); });
  });

  /* ============================================================
     Inline brand creation from the product form
     (fetch -> append <option>)
  ============================================================ */
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.js-create-brand');
    if (!btn) { return; }

    var nameEl  = document.getElementById('newBrandName');
    var errorEl = document.getElementById('addBrandError');
    var select  = document.querySelector(btn.getAttribute('data-target-select'));
    var token   = document.querySelector('meta[name="csrf-token"]');

    if (!nameEl || !nameEl.value.trim()) {
      if (errorEl) { errorEl.textContent = 'Please enter a brand name.'; errorEl.classList.remove('d-none'); }
      return;
    }

    btn.classList.add('is-loading');
    fetch(btn.getAttribute('data-store-url'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': token ? token.getAttribute('content') : '',
      },
      body: JSON.stringify({
        name: nameEl.value.trim(),
        is_active: 1,
      }),
    })
      .then(function (res) { return res.ok ? res.json() : res.json().then(function (j) { throw j; }); })
      .then(function (brand) {
        if (select) {
          var opt = new Option(brand.name, brand.id, true, true);
          select.add(opt);
          select.value = brand.id;
        }
        if (nameEl)  { nameEl.value  = ''; }
        if (errorEl) { errorEl.classList.add('d-none'); }
        var modalEl = document.getElementById('addBrandModal');
        var modal = window.bootstrap && modalEl ? window.bootstrap.Modal.getInstance(modalEl) : null;
        if (modal) { modal.hide(); }
      })
      .catch(function (err) {
        var msg = (err && err.errors && Object.values(err.errors)[0][0]) || 'Could not create brand.';
        if (errorEl) { errorEl.textContent = msg; errorEl.classList.remove('d-none'); }
      })
      .finally(function () { btn.classList.remove('is-loading'); });
  });
})();
