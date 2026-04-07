/**
 * Mahna Admin - Toast Notification System
 * Modern, animated, and theme-awre notifications
 */

class ToastNotificationSystem {
    constructor() {
        this.container = this.createContainer();
        this.icons = {
            success: 'bx-check',
            error: 'bx-x',
            warning: 'bx-info-circle',
            info: 'bx-bell'
        };
        this.defaultDuration = 4000;
    }

    createContainer() {
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }
        return container;
    }

    /**
     * Show a new toast notification
     * @param {string} type 'success', 'error', 'warning', 'info'
     * @param {string} title The bold title text
     * @param {string} message The detailed message
     * @param {number} duration Time in ms before auto-dismiss (0 for persistent)
     */
    show(type, title, message, duration = this.defaultDuration) {
        const toastId = 'toast-' + Math.random().toString(36).substr(2, 9);
        const iconClass = this.icons[type] || this.icons.info;
        
        const toastEl = document.createElement('div');
        toastEl.className = `custom-toast ${type}`;
        toastEl.id = toastId;
        
        toastEl.innerHTML = `
            <div class="toast-status-line"></div>
            <div class="toast-content-wrapper">
                <div class="toast-icon">
                    <i class='bx ${iconClass}'></i>
                </div>
                <div class="toast-body">
                    <div class="toast-title">${title}</div>
                    <div class="toast-message">${message}</div>
                </div>
            </div>
            <div class="toast-close" onclick="MahnaToast.hide('${toastId}')">
                <i class='bx bx-x'></i>
            </div>
            ${duration > 0 ? `
            <div class="toast-progress">
                <div class="toast-progress-bar"></div>
            </div>
            ` : ''}
        `;

        this.container.appendChild(toastEl);
        
        if (duration > 0) {
            const progressBar = toastEl.querySelector('.toast-progress-bar');
            
            // Setup animation
            let startTime = null;
            let remaining = duration;
            let animationFrame;
            let isPaused = false;
            
            const animate = (timestamp) => {
                if (!startTime) startTime = timestamp;
                
                if (!isPaused) {
                    const elapsed = timestamp - startTime;
                    const progress = 100 - ((elapsed / duration) * 100);
                    
                    if (progress <= 0) {
                        progressBar.style.width = '0%';
                        this.hide(toastId);
                        return;
                    }
                    
                    progressBar.style.width = `${progress}%`;
                } else {
                    startTime = timestamp - (duration - remaining);
                }
                
                animationFrame = requestAnimationFrame(animate);
            };
            
            animationFrame = requestAnimationFrame(animate);
            
            // Pause on hover
            toastEl.addEventListener('mouseenter', () => {
                isPaused = true;
                remaining = duration * (parseFloat(progressBar.style.width) / 100);
            });
            
            toastEl.addEventListener('mouseleave', () => {
                isPaused = false;
            });
        }
    }

    hide(toastId) {
        const toastEl = document.getElementById(toastId);
        if (toastEl) {
            toastEl.classList.add('hiding');
            setTimeout(() => {
                if (toastEl.parentNode) {
                    toastEl.parentNode.removeChild(toastEl);
                }
            }, 300); // Wait for slideOut animation to finish
        }
    }
}

// Initialize globally
window.MahnaToast = new ToastNotificationSystem();

// Shorthand helper functions
window.toast = {
    success: (title, message, duration) => MahnaToast.show('success', title, message, duration),
    error: (title, message, duration) => MahnaToast.show('error', title, message, duration),
    warning: (title, message, duration) => MahnaToast.show('warning', title, message, duration),
    info: (title, message, duration) => MahnaToast.show('info', title, message, duration)
};
