/**
 * Real-time Notifications Handler
 * Listens for notifications via Laravel Echo/Reverb WebSocket
 */

export function initializeNotifications() {
    // Only initialize if Echo is available
    if (!window.Echo) {
        console.warn('Laravel Echo not initialized');
        return;
    }

    // Get the current user ID from the page
    const userId = getUserId();
    if (!userId) {
        console.warn('User ID not found, notifications disabled');
        return;
    }

    console.log('Initializing real-time notifications for user:', userId);

    // Subscribe to private user channel for notifications
    window.Echo.private(`user.${userId}`)
        .notification((notification) => {
            handleNotification(notification);
        })
        .error((error) => {
            console.error('Notification channel error:', error);
        });

    // Listen for document stage advanced events
    window.Echo.channel('documents')
        .listen('DocumentStageAdvanced', (event) => {
            handleDocumentStageAdvanced(event);
        })
        .error((error) => {
            console.error('Document channel error:', error);
        });

    console.log('Real-time notifications initialized ✓');
}

/**
 * Extract user ID from the page
 * @returns {number|null}
 */
function getUserId() {
    // Try to get from meta tag
    const userIdMeta = document.querySelector('meta[name="user-id"]');
    if (userIdMeta) {
        return userIdMeta.getAttribute('content');
    }

    // Try to get from data attribute on body
    if (document.body.dataset.userId) {
        return document.body.dataset.userId;
    }

    // Try to get from window object (if set by Laravel)
    if (window.currentUserId) {
        return window.currentUserId;
    }

    // Try to get from localStorage (set after login)
    const storedUserId = localStorage.getItem('user_id');
    if (storedUserId) {
        return storedUserId;
    }

    return null;
}

/**
 * Handle incoming notifications
 * @param {Object} notification
 */
function handleNotification(notification) {
    console.log('📧 Notification received:', notification);

    // Show browser notification if supported
    if ('Notification' in window && Notification.permission === 'granted') {
        new Notification(notification.title, {
            body: notification.message,
            icon: getIconUrl(notification.icon),
            tag: `notification-${notification.document_id}`,
            requireInteraction: true,
        });
    }

    // Show in-app notification
    showInAppNotification(notification);

    // Trigger custom event for components to handle
    window.dispatchEvent(
        new CustomEvent('notification-received', {
            detail: notification,
        })
    );
}

/**
 * Handle document stage advancement events
 * @param {Object} event
 */
function handleDocumentStageAdvanced(event) {
    console.log('📄 Document stage advanced:', event);

    const notification = {
        title: 'Documento avanzó de etapa',
        message: event.message,
        document_id: event.document_id,
        current_stage: event.current_stage,
        total_stages: event.total_stages,
        icon: 'fas fa-check-circle',
    };

    handleNotification(notification);
}

/**
 * Show in-app notification
 * @param {Object} notification
 */
function showInAppNotification(notification) {
    // Create notification element
    const notifElement = document.createElement('div');
    notifElement.className =
        'fixed top-4 right-4 bg-white border-l-4 border-blue-500 shadow-lg rounded-lg p-4 max-w-sm z-50 animate-slideIn';
    notifElement.innerHTML = `
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <i class="${notification.icon || 'fas fa-bell'} text-blue-500 text-xl"></i>
            </div>
            <div class="ml-3 flex-1">
                <h3 class="text-sm font-medium text-gray-900">
                    ${notification.title}
                </h3>
                <div class="mt-1 text-sm text-gray-600">
                    ${notification.message}
                </div>
            </div>
            <button
                onclick="this.parentElement.parentElement.remove()"
                class="ml-2 text-gray-400 hover:text-gray-600"
            >
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;

    document.body.appendChild(notifElement);

    // Auto-remove after 5 seconds
    setTimeout(() => {
        notifElement.style.animation = 'slideOut 0.3s ease-out forwards';
        setTimeout(() => notifElement.remove(), 300);
    }, 5000);
}

/**
 * Request browser notification permission
 */
export function requestNotificationPermission() {
    if (!('Notification' in window)) {
        console.warn('Browser notifications not supported');
        return;
    }

    if (Notification.permission === 'granted') {
        console.log('Browser notifications already enabled');
        return;
    }

    if (Notification.permission !== 'denied') {
        Notification.requestPermission().then((permission) => {
            if (permission === 'granted') {
                console.log('Browser notifications enabled ✓');
            }
        });
    }
}

/**
 * Get icon URL from icon class
 * @param {string} iconClass
 * @returns {string}
 */
function getIconUrl(iconClass) {
    // For now, return a default icon URL
    // In production, you might want to use a Font Awesome CDN or bundled icons
    return 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"%3E%3Cpath d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"%3E%3C/path%3E%3C/svg%3E';
}

// Initialize on page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeNotifications);
} else {
    initializeNotifications();
}
