/**
 * Premium Toast Notification System
 */

(function () {
    const ICONS = {
        success: '<span data-feather="check"></span>',
        error: '<span data-feather="alert-circle"></span>',
        warning: '<span data-feather="alert-triangle"></span>',
        info: '<span data-feather="info"></span>'
    };

    let toastContainer = null;

    function createContainer() {
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.className = 'premium-toast-container';
            document.body.appendChild(toastContainer);
        }
        return toastContainer;
    }

    function showToast(type, message, duration = 4000) {
        const container = createContainer();

        const toast = document.createElement('div');
        toast.className = `premium-toast toast-${type}`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');

        const iconHtml = ICONS[type] || ICONS.info;

        toast.innerHTML = `
            <div class="premium-toast-icon">
                ${iconHtml}
            </div>
            <div class="premium-toast-content fw-medium">
                ${message}
            </div>
            <button type="button" class="premium-toast-close" aria-label="Close">
                <span data-feather="x" style="width: 14px; height: 14px;"></span>
            </button>
            <div class="premium-toast-progress">
                <div class="premium-toast-progress-bar"></div>
            </div>
        `;

        container.appendChild(toast);

        // Replace feather icons if feather is available
        if (window.feather) {
            feather.replace();
        }

        // Animate in
        requestAnimationFrame(() => {
            toast.classList.add('toast-show');
        });

        // Progress bar animation
        const progressBar = toast.querySelector('.premium-toast-progress-bar');
        progressBar.style.transition = `transform ${duration}ms linear`;
        
        requestAnimationFrame(() => {
            progressBar.style.transform = 'scaleX(0)';
        });

        // Dismiss logic
        let dismissTimeout;
        const dismiss = () => {
            toast.classList.remove('toast-show');
            toast.classList.add('toast-hide');
            
            // Wait for slide-out transition to finish
            setTimeout(() => {
                toast.remove();
                if (container.children.length === 0) {
                    container.remove();
                    toastContainer = null;
                }
            }, 400); // 400ms matches css transition
        };

        const closeBtn = toast.querySelector('.premium-toast-close');
        closeBtn.addEventListener('click', () => {
            clearTimeout(dismissTimeout);
            dismiss();
        });

        // Auto dismiss
        dismissTimeout = setTimeout(dismiss, duration);

        // Pause on hover
        toast.addEventListener('mouseenter', () => {
            clearTimeout(dismissTimeout);
            const computedStyle = window.getComputedStyle(progressBar);
            const currentTransform = computedStyle.getPropertyValue('transform');
            progressBar.style.transition = 'none';
            progressBar.style.transform = currentTransform;
        });

        toast.addEventListener('mouseleave', () => {
            const currentScale = progressBar.getBoundingClientRect().width / toast.getBoundingClientRect().width;
            const remainingTime = duration * currentScale;
            
            progressBar.style.transition = `transform ${remainingTime}ms linear`;
            requestAnimationFrame(() => {
                progressBar.style.transform = 'scaleX(0)';
            });
            
            dismissTimeout = setTimeout(dismiss, remainingTime);
        });
    }

    // Expose API
    window.Toast = {
        success: (msg, duration) => showToast('success', msg, duration),
        error: (msg, duration) => showToast('error', msg, duration),
        warning: (msg, duration) => showToast('warning', msg, duration),
        info: (msg, duration) => showToast('info', msg, duration),
    };
})();
