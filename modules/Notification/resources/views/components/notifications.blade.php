<style>
    /* Notification badge dot (unread indicator) */
    div#notification-badge {
        width: 14px !important;
        height: 14px !important;
        background-color: #90bb13 !important;
        border-radius: 50% !important;
        position: absolute !important;
        top: -4px !important;
        right: 4px !important;
        border: 2px solid white !important;
        box-sizing: border-box !important;
        z-index: 10 !important;
        display: none !important;
        flex: none !important;
        flex-shrink: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    /* Show badge when it has pulse animation */
    div#notification-badge.badge-pulse {
        display: block !important;
        animation: pulse-badge 2s infinite !important;
    }

    /* Pulse animation for unread badge */
    @keyframes pulse-badge {
        0% {
            box-shadow: 0 0 0 0 rgba(144, 187, 19, 0.9), inset 0 0 0 0 rgba(255, 255, 255, 0.5);
        }
        50% {
            box-shadow: 0 0 0 10px rgba(144, 187, 19, 0), inset 0 0 0 2px rgba(255, 255, 255, 0.3);
        }
        100% {
            box-shadow: 0 0 0 0 rgba(144, 187, 19, 0), inset 0 0 0 0 rgba(255, 255, 255, 0);
        }
    }
</style>


<!-- Notifications Bell Dropdown -->
<li class="nav-item nav-icon-hover-bg rounded-circle dropdown"
    id="notifications-dropdown"
    data-api-index-route="{{ route('api.notifications.index') }}"
    data-api-read-route="{{ url('/api/notifications/{id}/read') }}"
    data-mark-all-read-route="{{ route('api.notifications.mark-all-read') }}"
    data-refresh-interval="3000"
    data-limit="4">

    <a class="nav-link position-relative" href="javascript:void(0)" id="drop2" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="fa-duotone fa-bell fs-6"></i>
        <div id="notification-badge"></div>
    </a>

    <div class="dropdown-menu content-dd dropdown-menu-end dropdown-menu-animate-up" aria-labelledby="drop2" style="min-width: 360px; max-width: 400px;">
        <!-- Header -->
        <div class="d-flex align-items-center justify-content-between py-3 px-4 border-bottom">
            <h5 class="mb-0 fs-5 fw-semibold">Notificaciones</h5>
            <span id="unread-count-text" class="badge bg-primary px-2 py-1" style="display: none;">0 nuevas</span>
        </div>

        <!-- Loading State -->
        <div id="notifications-loading" class="text-center py-5">
            <div class="spinner-border spinner-border-sm text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="text-muted mt-2 mb-0 small">Cargando notificaciones...</p>
        </div>

        <!-- Notifications List -->
        <div id="notifications-list" class="message-body" data-simplebar style="display: none;">
            <!-- Notifications will be inserted here via AJAX -->
        </div>

        <!-- Empty State -->
        <div id="notifications-empty" class="text-center py-5" style="display: none;">
            <div class="mb-3">
                <i class="fas fa-bell-slash fs-6 text-muted opacity-50"></i>
            </div>
            <h6 class="fw-semibold mb-1">Sin notificaciones</h6>
            <p class="text-muted mb-0 small">No tienes notificaciones nuevas</p>
        </div>

        <!-- Footer -->
        <div class="border-top p-3">
            <a href="{{ route('notifications.index') }}" class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2">
                <div class="text-start">
                    <div class="fw-semibold">Ver todas las notificaciones</div>
                </div>
            </a>
        </div>
    </div>
</li>

@push('scripts')
    <script src="{{ asset('modules/Notification/js/notifications.js') }}?v={{ md5_file(base_path('modules/Notification/public/js/notifications.js')) }}"></script>
@endpush
