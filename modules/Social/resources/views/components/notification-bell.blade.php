<div class="dropdown" id="notification-dropdown">
    <button class="btn btn-light position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" id="notification-bell">
        <i class="fas fa-bell fs-5"></i>
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notification-badge" style="display: none;">
            <span id="notification-count">0</span>
        </span>
    </button>

    <div class="dropdown-menu dropdown-menu-end shadow-lg" style="width: 350px; max-height: 500px; overflow-y: auto;">
        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
            <h6 class="mb-0">Notificaciones</h6>
            <button type="button" class="btn btn-sm btn-link text-muted" onclick="markAllAsRead()">
                Marcar todas como leídas
            </button>
        </div>

        <div id="notifications-list">
            <div class="text-center py-4">
                <div class="spinner-border spinner-border-sm text-muted" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
            </div>
        </div>

        <div class="border-top text-center py-2">
            <a href="{{ route('admin.social.notifications.all') }}" class="text-decoration-none small">
                Ver todas las notificaciones
            </a>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load initial notifications
    loadNotifications();
    updateNotificationCount();

    // Refresh every 30 seconds (fallback for when WebSocket is not connected)
    setInterval(updateNotificationCount, 30000);

    // Setup Laravel Echo for real-time notifications
    @if(config('broadcasting.default') !== 'null')
    if (typeof Echo !== 'undefined') {
        Echo.channel('account.{{ auth()->user()->account_id }}')
            .listen('.mention.received', (e) => {
                showToast('Nueva Mención', `${e.author_name}: ${e.content.substring(0, 50)}...`, 'info');
                playNotificationSound();
                updateNotificationCount();
                loadNotifications();
            })
            .listen('.post.submitted', (e) => {
                showToast('Post para Revisión', `${e.creator_name} envió un post para aprobación`, 'warning');
                playNotificationSound();
                updateNotificationCount();
                loadNotifications();
            });
    }
    @endif
});

function loadNotifications() {
    fetch('{{ route("admin.social.notifications.index") }}')
        .then(response => response.json())
        .then(data => {
            renderNotifications(data.notifications);
        })
        .catch(error => {
            console.error('Error loading notifications:', error);
        });
}

function updateNotificationCount() {
    fetch('{{ route("admin.social.notifications.count") }}')
        .then(response => response.json())
        .then(data => {
            const badge = document.getElementById('notification-badge');
            const count = document.getElementById('notification-count');

            if (data.count > 0) {
                badge.style.display = 'block';
                count.textContent = data.count > 99 ? '99+' : data.count;
            } else {
                badge.style.display = 'none';
            }
        });
}

function renderNotifications(notifications) {
    const container = document.getElementById('notifications-list');

    if (notifications.length === 0) {
        container.innerHTML = `
            <div class="text-center py-4 text-muted">
                <i class="fas fa-bell-slash fs-1 mb-2 d-block"></i>
                <p class="small mb-0">No hay notificaciones nuevas</p>
            </div>
        `;
        return;
    }

    container.innerHTML = notifications.map(notification => {
        const icon = getNotificationIcon(notification.type);
        const color = getNotificationColor(notification.type);

        return `
            <div class="dropdown-item py-3 border-bottom notification-item" data-id="${notification.id}">
                <div class="d-flex">
                    <div class="flex-shrink-0">
                        <div class="avatar avatar-sm rounded-circle bg-light-${color} text-${color}">
                            <i class="ti ${icon}"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <p class="mb-1 small">${getNotificationMessage(notification)}</p>
                        <small class="text-muted">${notification.created_at}</small>
                    </div>
                    <div class="flex-shrink-0 ms-2">
                        <button type="button" class="btn btn-sm btn-link text-muted p-0" onclick="markAsRead('${notification.id}')" title="Marcar como leída">
                            <i class="fas fa-check"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

function getNotificationIcon(type) {
    const icons = {
        'PostSubmittedForReviewNotification': 'ti-file-text',
        'PostApprovedNotification': 'ti-check-circle',
        'PostRejectedNotification': 'ti-x-circle',
        'NewMentionReceived': 'ti-message-circle',
    };
    return icons[type] || 'ti-bell';
}

function getNotificationColor(type) {
    const colors = {
        'PostSubmittedForReviewNotification': 'warning',
        'PostApprovedNotification': 'success',
        'PostRejectedNotification': 'danger',
        'NewMentionReceived': 'info',
    };
    return colors[type] || 'secondary';
}

function getNotificationMessage(notification) {
    const data = notification.data;

    switch(notification.type) {
        case 'PostSubmittedForReviewNotification':
            return `<strong>${data.creator_name}</strong> envió un post para revisión`;
        case 'PostApprovedNotification':
            return `Tu post fue aprobado por <strong>${data.reviewer_name}</strong>`;
        case 'PostRejectedNotification':
            return `Tu post fue rechazado por <strong>${data.reviewer_name}</strong>`;
        default:
            return 'Nueva notificación';
    }
}

function markAsRead(id) {
    fetch('{{ route('admin.social.notifications.mark-as-read', ':id') }}'.replace(':id', id), {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove notification from list
            document.querySelector(`[data-id="${id}"]`).remove();
            updateNotificationCount();

            // Check if list is empty
            const list = document.getElementById('notifications-list');
            if (list.children.length === 0) {
                loadNotifications();
            }
        }
    });
}

function markAllAsRead() {
    fetch('{{ route("admin.social.notifications.mark-all-read") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadNotifications();
            updateNotificationCount();
        }
    });
}

function showToast(title, message, type = 'info') {
    // Check if toast container exists, if not create it
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '9999';
        document.body.appendChild(toastContainer);
    }

    const toastId = 'toast-' + Date.now();
    const bgClass = {
        'info': 'bg-info',
        'success': 'bg-success',
        'warning': 'bg-warning',
        'danger': 'bg-danger',
    }[type] || 'bg-info';

    const toastHTML = `
        <div id="${toastId}" class="toast align-items-center text-white ${bgClass} border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <strong>${title}</strong><br>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;

    toastContainer.insertAdjacentHTML('beforeend', toastHTML);

    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, {
        autohide: true,
        delay: 5000
    });

    toast.show();

    // Remove toast element after it's hidden
    toastElement.addEventListener('hidden.bs.toast', function () {
        toastElement.remove();
    });
}

function playNotificationSound() {
    // Optional: Play a subtle notification sound
    // const audio = new Audio('/sounds/notification.mp3');
    // audio.play().catch(e => console.log('Could not play notification sound'));
}
</script>

<style>
.notification-item {
    cursor: pointer;
    transition: background-color 0.2s;
}

.notification-item:hover {
    background-color: #f8f9fa;
}

#notification-bell {
    position: relative;
}

.toast-container {
    z-index: 9999;
}
</style>
@endpush
