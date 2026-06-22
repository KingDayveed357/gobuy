/**
 * GoBuy Mega Menu
 * Premium desktop hover menu with diagonal-movement prevention and category switching.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        initDesktopMenu();
        initMobileSearch();
    });

    /* ============================================================
       Desktop Mega Menu
       ============================================================ */
    function initDesktopMenu() {
        const trigger = document.getElementById('desktopMegaMenuTrigger');
        const panel   = document.getElementById('desktopMegaMenu');
        if (!trigger || !panel) { return; }

        let hideTimer   = null;
        let switchTimer = null;

        /* ---- Show / Hide with 150ms delay ---- */
        const showMenu = () => {
            clearTimeout(hideTimer);
            panel.style.display = 'block';
            trigger.setAttribute('aria-expanded', 'true');
        };

        const hideMenu = () => {
            hideTimer = setTimeout(() => {
                panel.style.display = 'none';
                trigger.setAttribute('aria-expanded', 'false');
            }, 180);
        };

        trigger.addEventListener('mouseenter', showMenu);
        trigger.addEventListener('mouseleave', hideMenu);
        panel.addEventListener('mouseenter',   showMenu);
        panel.addEventListener('mouseleave',   hideMenu);

        /* Keyboard: Escape closes */
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape' && panel.style.display === 'block') {
                panel.style.display = 'none';
                trigger.setAttribute('aria-expanded', 'false');
                trigger.focus();
            }
        });

        /* Click outside closes */
        document.addEventListener('click', e => {
            if (!panel.contains(e.target) && !trigger.contains(e.target)) {
                panel.style.display = 'none';
                trigger.setAttribute('aria-expanded', 'false');
            }
        });

        /* ---- Category switching (Amazon diagonal prevention) ---- */
        const rootLinks = panel.querySelectorAll('.mega-menu-root-link');
        const contents  = panel.querySelectorAll('.mega-menu-content');

        function activateCategory(link) {
            const targetId = link.getAttribute('data-target');
            if (!targetId) { return; }

            rootLinks.forEach(l => l.classList.remove('active'));
            contents.forEach(c  => { c.style.display = 'none'; });

            link.classList.add('active');
            const target = document.getElementById(targetId);
            if (target) { target.style.display = 'block'; }

            // Reset search
            const search = panel.querySelector('.mega-menu-search');
            if (search && search.value) {
                search.value = '';
                filterSubcategories('', targetId);
            }
        }

        rootLinks.forEach(link => {
            link.addEventListener('mouseenter', () => {
                clearTimeout(switchTimer);
                switchTimer = setTimeout(() => activateCategory(link), 120);
            });

            /* Also allow click navigation (doesn't prevent href) */
            link.addEventListener('focus', () => activateCategory(link));
        });

        /* ---- Subcategory search ---- */
        const searchInput = panel.querySelector('.mega-menu-search');
        const emptyState  = panel.querySelector('.mega-menu-empty-state');

        if (searchInput) {
            searchInput.addEventListener('input', e => {
                const activeLink = panel.querySelector('.mega-menu-root-link.active');
                const activeId   = activeLink ? activeLink.getAttribute('data-target') : null;
                filterSubcategories(e.target.value.toLowerCase().trim(), activeId);
            });
        }

        function filterSubcategories(query, activeId) {
            if (!activeId) { return; }
            const activeContent = document.getElementById(activeId);
            if (!activeContent) { return; }

            const items = activeContent.querySelectorAll('.subcategory-item');

            if (!query) {
                items.forEach(i => { i.style.display = ''; });
                if (emptyState) { emptyState.style.display = 'none'; }
                return;
            }

            let found = false;
            items.forEach(item => {
                const name = (item.dataset.name || '').toLowerCase();
                const show = name.includes(query);
                item.style.display = show ? '' : 'none';
                if (show) { found = true; }
            });

            if (emptyState) {
                emptyState.style.display = found ? 'none' : 'block';
            }
        }
    }

    /* ============================================================
       Mobile Offcanvas Search
       ============================================================ */
    function initMobileSearch() {
        const searchInput      = document.querySelector('.mega-menu-mobile-search');
        const mobileCategories = document.querySelectorAll('#mobileMegaMenu .mobile-category-item');

        if (!searchInput) { return; }

        searchInput.addEventListener('input', e => {
            const query = e.target.value.toLowerCase().trim();

            if (!query) {
                mobileCategories.forEach(c => { c.style.display = ''; });
                /* Re-collapse all open accordions when search is cleared */
                document.querySelectorAll('#accordionMegaMenu .accordion-collapse.show').forEach(col => {
                    col.classList.remove('show');
                    const btn = col.closest('.accordion-item')?.querySelector('.accordion-button');
                    if (btn) { btn.classList.add('collapsed'); }
                });
                return;
            }

            mobileCategories.forEach(category => {
                const catName = (category.dataset.name || '').toLowerCase();
                const subs    = category.querySelectorAll('.mobile-subcategory-item');
                let hasMatch  = catName.includes(query);

                subs.forEach(sub => {
                    const subName = (sub.dataset.name || '').toLowerCase();
                    const show    = subName.includes(query) || catName.includes(query);
                    sub.style.display = show ? '' : 'none';
                    if (show) { hasMatch = true; }
                });

                category.style.display = hasMatch ? '' : 'none';

                /* Auto-expand accordion if a sub matches */
                if (hasMatch) {
                    const collapse = category.querySelector('.accordion-collapse');
                    const btn      = category.querySelector('.accordion-button');
                    if (collapse && !collapse.classList.contains('show')) {
                        collapse.classList.add('show');
                        if (btn) { btn.classList.remove('collapsed'); }
                    }
                }
            });
        });
    }
})();
