/**
 * GoBuy Wizard Engine
 * Custom stepper logic for the gb-wizard system.
 * Drives gb-step / gb-step-pane components independently of Phoenix tab logic.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-gb-wizard]').forEach(initWizard);
    });

    function initWizard(wizard) {
        const wizardId  = wizard.id || 'wizard';
        const stepsEl   = wizard.querySelectorAll('[data-gb-step]');
        const panes     = wizard.querySelectorAll('.gb-step-pane[data-gb-step]');
        const btnPrev   = wizard.querySelector('[data-gb-prev]');
        const btnNext   = wizard.querySelector('[data-gb-next]');
        const sessionKey = `gb_wizard_${wizardId}`;

        // Build ordered step map: step number → { navEl, paneEl }
        const steps = [];
        stepsEl.forEach(el => {
            const num  = parseInt(el.dataset.gbStep, 10);
            const id   = el.dataset.gbTarget;
            const pane = wizard.querySelector(`#${id}`);
            if (pane) {
                steps[num - 1] = { nav: el, pane };
            }
        });

        let currentStep = restoreStep();

        // ---- Init ----
        activateStep(currentStep, false);
        bindStepClicks();
        bindNavButtons();
        bindInlineValidation();
        bindStatePersistence();

        // ---- Step Activation ----
        function activateStep(index, animate = true) {
            if (index < 0 || index >= steps.length) { return; }

            steps.forEach((s, i) => {
                // Nav classes
                s.nav.classList.remove('is-active', 'is-done');
                if (i < index)       { s.nav.classList.add('is-done'); }
                else if (i === index) { s.nav.classList.add('is-active'); }

                // Pane visibility
                s.pane.classList.remove('is-active');
                if (animate) { s.pane.style.animation = 'none'; }
            });

            const active = steps[index];
            if (animate) {
                // Force reflow to re-trigger animation
                requestAnimationFrame(() => {
                    active.pane.style.animation = '';
                    active.pane.classList.add('is-active');
                });
            } else {
                active.pane.classList.add('is-active');
            }

            // Update focus for a11y
            active.nav.focus({ preventScroll: true });

            currentStep = index;
            saveStep(currentStep);
            updateNavButtons();
        }

        // ---- Nav button state ----
        function updateNavButtons() {
            if (!btnPrev || !btnNext) { return; }
            btnPrev.style.display = currentStep > 0 ? '' : 'none';
            btnNext.style.display = currentStep < steps.length - 1 ? '' : 'none';
        }

        // ---- Step click navigation ----
        function bindStepClicks() {
            steps.forEach((s, i) => {
                s.nav.addEventListener('click', () => activateStep(i));
                s.nav.addEventListener('keydown', e => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        activateStep(i);
                    }
                });
            });
        }

        // ---- Prev / Next buttons ----
        function bindNavButtons() {
            if (btnPrev) {
                btnPrev.addEventListener('click', () => {
                    activateStep(currentStep - 1);
                });
            }
            if (btnNext) {
                btnNext.addEventListener('click', () => {
                    if (validateCurrentStep()) {
                        activateStep(currentStep + 1);
                    }
                });
            }
        }

        // ---- Inline validation ----
        function bindInlineValidation() {
            wizard.querySelectorAll('.wizard-step-form').forEach(form => {
                form.querySelectorAll('input, select, textarea').forEach(input => {
                    input.addEventListener('blur', () => {
                        touchInput(input, form);
                    });
                    input.addEventListener('input', () => {
                        if (input.dataset.touched) { touchInput(input, form); }
                    });
                });
            });
        }

        function touchInput(input, form) {
            input.dataset.touched = 'true';
            applyValidity(input, form);
        }

        function applyValidity(input, form) {
            input.classList.remove('is-valid', 'is-invalid');

            // Password match special handling
            if (input.dataset.wizardConfirmPassword) {
                const pw = form.querySelector('[data-wizard-password]');
                if (pw && input.value !== pw.value) {
                    input.setCustomValidity('Passwords do not match');
                } else {
                    input.setCustomValidity('');
                }
            }

            if (input.value === '' && !input.required) { return; }
            input.classList.add(input.checkValidity() ? 'is-valid' : 'is-invalid');
        }

        function validateCurrentStep() {
            const pane = steps[currentStep].pane;
            const form = pane.querySelector('.wizard-step-form');
            if (!form) { return true; }

            let valid = true;
            form.querySelectorAll('input, select, textarea').forEach(input => {
                applyValidity(input, form);
                if (!input.checkValidity()) { valid = false; }
            });

            if (!valid) { form.classList.add('was-validated'); }
            return valid;
        }

        // ---- State persistence (sessionStorage) ----
        function saveStep(index) {
            try { sessionStorage.setItem(sessionKey, String(index)); } catch (_) {}
        }

        function restoreStep() {
            try {
                const saved = sessionStorage.getItem(sessionKey);
                if (saved !== null) {
                    const idx = parseInt(saved, 10);
                    if (!isNaN(idx) && idx >= 0 && idx < steps.length) { return idx; }
                }
            } catch (_) {}
            return 0;
        }

        // ---- Form field value persistence ----
        function bindStatePersistence() {
            const fieldKey = `${sessionKey}_fields`;

            // Restore field values
            try {
                const saved = JSON.parse(sessionStorage.getItem(fieldKey) || '{}');
                wizard.querySelectorAll('input:not([type=password]):not([type=hidden]), select, textarea').forEach(input => {
                    const name = input.name || input.id;
                    if (name && saved[name] !== undefined) {
                        if (input.type === 'checkbox' || input.type === 'radio') {
                            input.checked = saved[name];
                        } else {
                            input.value = saved[name];
                        }
                    }
                });
            } catch (_) {}

            // Save on change
            wizard.addEventListener('input', e => {
                const target = e.target;
                const name = target.name || target.id;
                if (!name || target.type === 'password') { return; }

                try {
                    const saved = JSON.parse(sessionStorage.getItem(fieldKey) || '{}');
                    saved[name] = (target.type === 'checkbox' || target.type === 'radio') ? target.checked : target.value;
                    sessionStorage.setItem(fieldKey, JSON.stringify(saved));
                } catch (_) {}
            });

            // Clear on form submit
            wizard.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', () => {
                    try {
                        sessionStorage.removeItem(sessionKey);
                        sessionStorage.removeItem(fieldKey);
                    } catch (_) {}
                });
            });
        }
    }
})();
