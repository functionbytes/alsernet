/**
 * Notification Module
 * Manages notification bell dropdown and unread notification handling
 * Routes are passed via data attributes on the notifications component
 */

(function() {
    'use strict';

    // Configuration object - initialized from data attributes
    let config = {
        apiIndexRoute: null,
        apiReadRoute: null,
        markAllReadRoute: null,
        refreshInterval: 60000, // 60 seconds
        limit: 4,
    };

    // State management
    let state = {
        unreadCount: 0,
        refreshTimer: null,
        notificationPermission: 'default', // 'granted', 'denied', or 'default'
        shownNotifications: new Set(), // Track which notifications have been shown as desktop notifications
        retryCount: 0,
        maxRetries: 3,
        baseRetryDelay: 2000,
    };

    /**
     * Initialize the notification system
     * Call this after the DOM is ready
     */
    window.NotificationManager = {
        init: function(options = {}) {
            // Merge options with defaults
            Object.assign(config, options);

            // Validate that routes are provided
            if (!config.apiIndexRoute || !config.apiReadRoute) {
                console.warn('NotificationManager: Routes not properly configured');
                return;
            }

            // Request browser notification permission
            requestNotificationPermission();

            // Load initial notifications
            loadNotifications();

            // Set up auto-refresh
            state.refreshTimer = setInterval(function() {
                console.log('⏰ Auto-refresh triggered (every 60s)');
                loadNotifications();
            }, config.refreshInterval);
            console.log('✅ Auto-refresh timer started. Interval: ' + config.refreshInterval + 'ms');

            // Set up event listeners
            setupEventListeners();
        },

        /**
         * Manually refresh notifications
         */
        refresh: function() {
            loadNotifications();
        },

        /**
         * Stop the notification system
         */
        destroy: function() {
            if (state.refreshTimer) {
                clearInterval(state.refreshTimer);
                state.refreshTimer = null;
            }
        },

        /**
         * Get current unread count
         */
        getUnreadCount: function() {
            return state.unreadCount;
        },
    };

    /**
     * Request browser notification permission
     */
    function requestNotificationPermission() {
        // Check if browser supports notifications
        if (!('Notification' in window)) {
            console.warn('⚠️  This browser does not support desktop notifications');
            return;
        }

        // Check current permission status
        state.notificationPermission = Notification.permission;
        console.log('🔔 Current notification permission status:', state.notificationPermission);

        // If already denied, don't ask again
        if (state.notificationPermission === 'denied') {
            console.warn('⚠️  Notification permission was previously denied');
            return;
        }

        // If already granted, don't ask again
        if (state.notificationPermission === 'granted') {
            console.log('✅ Notification permission already granted - desktop notifications enabled');
            return;
        }

        // Request permission (for 'default' state)
        if (state.notificationPermission === 'default') {
            console.log('🔔 Requesting notification permission from user...');
            Notification.requestPermission().then(function(permission) {
                state.notificationPermission = permission;
                if (permission === 'granted') {
                    console.log('✅ Notification permission granted');
                } else {
                    console.warn('⚠️  Notification permission denied');
                }
            });
        }
    }

    /**
     * Play notification sound
     */
    function playNotificationSound() {
        try {
            // Create a simple beep sound using Web Audio API
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();

            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);

            oscillator.frequency.value = 800; // Frequency in Hz
            oscillator.type = 'sine';

            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);

            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.5);

            console.log('🔊 Notification sound played');
        } catch (error) {
            console.log('Could not play notification sound:', error);
        }
    }

    /**
     * Show a desktop notification
     */
    function showDesktopNotification(notification) {
        // Check if we have permission
        if (state.notificationPermission !== 'granted') {
            console.log('📵 Cannot show desktop notification - permission not granted. Current permission:', state.notificationPermission);
            return;
        }

        // Check if browser supports notifications
        if (!('Notification' in window)) {
            console.warn('⚠️  This browser does not support desktop notifications');
            return;
        }

        try {
            const title = notification.title || 'Nueva notificación';
            const message = notification.message || '';

            console.log('📲 Attempting to show desktop notification:', {
                title: title,
                message: message.substring(0, 50) + '...',
                notificationId: notification.id,
            });

            const options = {
                body: message,
                icon: '/favicon.ico',
                badge: '/favicon.ico',
                tag: 'notification-' + notification.id,
                requireInteraction: true, // Require user interaction to dismiss
                timestamp: notification.created_at_full ? new Date(notification.created_at_full).getTime() : Date.now(),
                silent: false, // Enable sound/vibration
            };

            // Create and show the OS notification
            const desktopNotification = new Notification(title, options);

            // Click handler - navigate to the action URL
            desktopNotification.onclick = function(event) {
                event.preventDefault();
                window.focus();
                if (notification.action_url) {
                    window.location.href = notification.action_url;
                }
                desktopNotification.close();
            };

            // Close handler
            desktopNotification.onclose = function() {
                console.log('🔔 Desktop notification closed');
            };

            console.log('🔔 Desktop notification shown successfully:', title);
        } catch (error) {
            console.error('❌ Error showing desktop notification:', error);
        }
    }

    /**
     * Load notifications from API
     */
    function loadNotifications() {
        // Build URL with query parameters
        const url = new URL(config.apiIndexRoute, window.location.origin);
        url.searchParams.append('limit', config.limit);
        url.searchParams.append('unread', 'true');

        console.log('📡 Loading notifications from API:', url.toString());

        $.ajax({
            url: url.toString(),
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            },
            xhrFields: {
                withCredentials: true,
            },
            success: function(response) {
                console.log('✅ API Response received:', response);
                state.retryCount = 0;
                state.unreadCount = response.unread_count;
                console.log('📊 Unread count: ' + state.unreadCount);

                updateBadge(state.unreadCount);
                renderNotifications(response.notifications);

                // Hide loading state
                $('#notifications-loading').hide();

                // Update unread count text
                updateUnreadCountText(state.unreadCount);
                updateRemainingText(state.unreadCount);
            },
            error: function(xhr, status, error) {
                console.error('❌ Error loading notifications:', xhr.status, xhr.statusText);
                console.error('Response:', xhr.responseText);

                if (xhr.status === 0 || xhr.status >= 500) {
                    if (state.retryCount < state.maxRetries) {
                        var delay = state.baseRetryDelay * Math.pow(2, state.retryCount);
                        state.retryCount++;
                        console.log('🔄 Retrying in ' + delay + 'ms (attempt ' + state.retryCount + '/' + state.maxRetries + ')');
                        setTimeout(loadNotifications, delay);
                        return;
                    }
                }

                state.retryCount = 0;
                handleLoadError();
            },
        });
    }

    /**
     * Mark a notification as read
     */
    function markAsRead(notificationId, callback) {
        // Replace {id} placeholder with actual ID
        const url = config.apiReadRoute.replace('{id}', notificationId);

        $.ajax({
            url: url,
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            },
            xhrFields: {
                withCredentials: true,
            },
            success: function(response) {
                state.unreadCount = response.unread_count;
                updateBadge(state.unreadCount);
                updateUnreadCountText(state.unreadCount);

                // Reload notifications to show the next unread one
                loadNotifications();

                if (callback) callback();
            },
            error: function(xhr) {
                console.error('Error marking notification as read:', xhr);
                if (xhr.status === 401) {
                    console.warn('User not authenticated');
                }
            },
        });
    }

    /**
     * Update the notification badge (visual indicator only, no number)
     */
    function updateBadge(count) {
        const $badge = $('#notification-badge');
        const $notificationIcon = $('#notifications-dropdown button[data-bs-toggle="dropdown"]');

        console.log('🔔 updateBadge() called with count:', count);
        console.log('Badge element found:', $badge.length > 0 ? 'YES' : 'NO');
        console.log('Icon element found:', $notificationIcon.length > 0 ? 'YES' : 'NO');

        if (count > 0) {
            // Use CSS class to show badge with animation
            $badge.addClass('badge-pulse');
            $notificationIcon.addClass('has-unread-notifications');
            console.log('✅ Badge SHOWN with pulse animation. Count: ' + count);
        } else {
            // Remove class to hide badge
            $badge.removeClass('badge-pulse');
            $notificationIcon.removeClass('has-unread-notifications');
            console.log('❌ Badge HIDDEN. No unread notifications.');
        }
    }

    /**
     * Update the unread count chip in the panel header
     */
    function updateUnreadCountText(count) {
        var $chip = $('#unread-count-text');
        if (count > 0) {
            $chip.text(count).show();
        } else {
            $chip.hide();
        }
    }

    /**
     * Update the "additional notifications" overflow text
     */
    function updateRemainingText(count) {
        var $extra = $('#notif-extra');
        if (count > config.limit) {
            var remaining = count - config.limit;
            $extra.text('+' + remaining + ' notificaciones adicionales').show();
        } else {
            $extra.hide();
        }
    }

    /**
     * Render notifications in the dropdown
     */
    function renderNotifications(notifications) {
        var $list = $('#notifications-list');
        var $empty = $('#notifications-empty');

        if (notifications.length === 0) {
            $list.hide();
            $empty.show();
            return;
        }

        $list.empty().show();
        $empty.hide();

        notifications.forEach(function(notification) {
            var isUnread = !notification.is_read;
            var iconClass = notification.icon || 'fas fa-bell';
            var unreadBadge = isUnread ? '<span class="badge-new">Nuevo</span>' : '';
            var unreadClass = isUnread ? ' unread' : '';

            var html = '<button class="notif-item' + unreadClass + '"'
                + ' data-notification-id="' + notification.id + '"'
                + ' data-action-url="' + (notification.action_url || '#') + '"'
                + ' type="button">'
                + '<div class="ico"><i class="' + iconClass + '"></i></div>'
                + '<div class="body">'
                + '<div class="head"><span class="title">' + notification.title + '</span>' + unreadBadge + '</div>'
                + '<div class="desc">' + notification.message + '</div>'
                + '<div class="meta"><i class="far fa-clock"></i> ' + notification.created_at + '</div>'
                + '</div>'
                + '</button>';

            $list.append(html);

            if (isUnread && !state.shownNotifications.has(notification.id)) {
                showDesktopNotification(notification);
                playNotificationSound();
                state.shownNotifications.add(notification.id);
            }
        });
    }

    /**
     * Handle load error state
     */
    function handleLoadError() {
        $('#notifications-loading').html(
            '<div class="text-center py-5">' +
            '<i class="fas fa-exclamation-triangle text-warning fs-1 mb-3"></i>' +
            '<p class="text-muted mb-0">Error al cargar notificaciones</p>' +
            '</div>'
        );
    }

    /**
     * Set up event listeners for notification interactions and user dock
     */
    function setupEventListeners() {
        // Click on notification item
        $(document).off('click', '.notif-item').on('click', '.notif-item', function(e) {
            e.preventDefault();
            var $item = $(this);
            var notificationId = $item.data('notification-id');
            var actionUrl = $item.data('action-url');

            markAsRead(notificationId, function() {
                if (actionUrl && actionUrl !== '#') {
                    window.location.href = actionUrl;
                }
            });
        });

        // Close button for notification panel
        $(document).off('click', '#btn-notif-close').on('click', '#btn-notif-close', function() {
            var $toggle = $('#notifications-dropdown [data-bs-toggle="dropdown"]');
            if ($toggle.length) {
                var instance = bootstrap.Dropdown.getInstance($toggle[0]);
                if (instance) { instance.hide(); }
            }
        });

        // Mark all read
        $(document).off('click', '#btn-mark-all-read').on('click', '#btn-mark-all-read', function() {
            var markAllUrl = config.markAllReadRoute;
            if (!markAllUrl) { return; }

            $.ajax({
                url: markAllUrl,
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                },
                success: function() {
                    state.unreadCount = 0;
                    updateBadge(0);
                    updateUnreadCountText(0);
                    loadNotifications();
                },
            });
        });

        // User dock toggle
        $(document).off('click', '#user-dock-trigger').on('click', '#user-dock-trigger', function(e) {
            e.stopPropagation();
            $('#user-dock').toggleClass('open');
        });

        // Close dock when clicking outside
        $(document).off('click.dock').on('click.dock', function(e) {
            if (!$(e.target).closest('#user-dock-wrap').length) {
                $('#user-dock').removeClass('open');
            }
        });
    }

    /**
     * Auto-initialize when document is ready
     * This will be called from the Blade template with proper route parameters
     */
    $(document).ready(function() {
        // Get configuration from data attributes on the notifications component
        const $notificationsComponent = $('#notifications-dropdown');
        if ($notificationsComponent.length) {
            const options = {
                apiIndexRoute: $notificationsComponent.data('api-index-route'),
                apiReadRoute: $notificationsComponent.data('api-read-route'),
                markAllReadRoute: $notificationsComponent.data('mark-all-read-route'),
                refreshInterval: $notificationsComponent.data('refresh-interval') || 60000,
                limit: $notificationsComponent.data('limit') || 4,
            };

            window.NotificationManager.init(options);

            // Listen for real-time notifications via Laravel Echo/Reverb
            if (window.Echo) {
                const userId = $('meta[name="user-id"]').attr('content');
                if (userId) {
                    // Private channel: NewNotificationEvent broadcasts on user.{id}
                    window.Echo.private(`user.${userId}`)
                        .listen('.notification.new', (data) => {
                            showDesktopNotification({
                                title: data.title,
                                message: data.message,
                                action_url: data.action_url,
                            });
                            playNotificationSound();
                            window.NotificationManager.refresh();
                        })
                        .error((error) => {
                            // Private channel auth failed — fall back to public channel polling
                            console.warn('Private channel unavailable, using public channel fallback');
                            window.Echo.channel(`public-notifications.${userId}`)
                                .notification(() => {
                                    window.NotificationManager.refresh();
                                });
                        });
                }
            }
        }
    });
})();
