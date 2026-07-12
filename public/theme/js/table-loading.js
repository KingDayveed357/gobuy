/* =============================================================================
   gobuy admin — Table navigation loading
   -----------------------------------------------------------------------------
   Admin list pages are server-rendered: filtering, paginating and switching a
   status tab all trigger a full-page reload. During that request the old table
   just sits there (or blanks). This reveals a skeleton over any [data-admin-table]
   the moment such a reload starts, so the list never feels frozen — most visible
   on slow connections.

   Triggers are scoped to reload-in-place actions only (GET filter forms,
   pagination links, `.nav-links` tabs), so navigating away to an edit/detail
   page does NOT flash a skeleton.
   ============================================================================= */
(function () {
    'use strict';

    function reveal() {
        var tables = document.querySelectorAll('[data-admin-table]');
        for (var i = 0; i < tables.length; i++) {
            tables[i].classList.add('is-navigating');
            tables[i].setAttribute('aria-busy', 'true');
        }
    }

    function clear() {
        var tables = document.querySelectorAll('[data-admin-table]');
        for (var i = 0; i < tables.length; i++) {
            tables[i].classList.remove('is-navigating');
            tables[i].removeAttribute('aria-busy');
        }
    }

    // GET filter/search forms submit and reload the same list.
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (form && form.matches && form.matches('form') &&
            (form.getAttribute('method') || 'get').toLowerCase() === 'get') {
            reveal();
        }
    }, true);

    // Pagination links and status/filter tabs reload the list in place.
    document.addEventListener('click', function (e) {
        var link = e.target.closest('.pagination a, .nav-links a, [data-table-nav]');
        if (!link) { return; }
        if (link.getAttribute('aria-disabled') === 'true' ||
            link.classList.contains('disabled') ||
            link.classList.contains('active')) { return; }
        reveal();
    });

    // Restore from the back/forward cache lands on the old (skeleton-on) DOM —
    // clear it so the real rows show again.
    window.addEventListener('pageshow', function (e) {
        if (e.persisted) { clear(); }
    });
})();
