/* gobuy admin — in-page helpers only.
   The shell (collapse, mobile, fixed nav) is handled by phoenix.js. */
(function () {
  'use strict';

  // Inline category creation from the product form (fetch -> append <option>).
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.js-create-category');
    if (!btn) { return; }

    var nameEl = document.getElementById('newCategoryName');
    var parentEl = document.getElementById('newCategoryParent');
    var errorEl = document.getElementById('addCategoryError');
    var select = document.querySelector(btn.getAttribute('data-target-select'));
    var token = document.querySelector('meta[name="csrf-token"]');

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
          var prefix = category.parent_id ? '↳ ' : '';
          var opt = new Option(prefix + category.name, category.id, true, true);
          select.add(opt);
          select.value = category.id;
        }
        if (nameEl) { nameEl.value = ''; }
        if (parentEl) { parentEl.value = ''; }
        if (errorEl) { errorEl.classList.add('d-none'); }
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

  // Inline brand creation from the product form (fetch -> append <option>).
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.js-create-brand');
    if (!btn) { return; }

    var nameEl = document.getElementById('newBrandName');
    var errorEl = document.getElementById('addBrandError');
    var select = document.querySelector(btn.getAttribute('data-target-select'));
    var token = document.querySelector('meta[name="csrf-token"]');

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
        if (nameEl) { nameEl.value = ''; }
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
