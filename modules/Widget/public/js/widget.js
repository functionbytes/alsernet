class WidgetManager {
    constructor() {
        this.routes = window.BWidget?.routes || {};
    }

    init() {
        const sortableGroups = [
            {
                name: 'wrap-widgets',
                pull: 'clone',
                put: false
            }
        ];

        // Add all sidebar items to sortable groups
        $('.sidebar-item').each(function() {
            sortableGroups.push({
                name: 'wrap-widgets',
                pull: true,
                put: true
            });
        });

        const saveWidgets = ($sidebar) => {
            if ($sidebar.length > 0) {
                const items = [];

                $sidebar.find('li[data-id]').each(function(index, element) {
                    items.push($(element).find('form').serialize());
                });

                const sidebarId = $sidebar.data('id');

                $.ajax({
                    url: this.routes.save_widgets_sidebar,
                    type: 'POST',
                    data: {
                        items: items,
                        sidebar_id: sidebarId,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        $sidebar.find('ul').html(response.data);
                        if (window.Botble) {
                            window.Botble.showSuccess(response.message);
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function(xhr) {
                        const message = xhr.responseJSON?.message || 'An error occurred';
                        if (window.Botble) {
                            window.Botble.showError(message);
                        } else {
                            alert(message);
                        }
                    },
                    complete: function() {
                        $sidebar.find('.widget-save i').remove();
                    }
                });
            }
        };

        // Initialize Sortable for each widget area
        sortableGroups.forEach((group, index) => {
            const element = document.getElementById('wrap-widget-' + (index + 1));

            if (element) {
                Sortable.create(element, {
                    sort: index !== 0,
                    group: group,
                    delay: 0,
                    disabled: false,
                    animation: 150,
                    handle: '.card-header',
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    dataIdAttr: 'data-id',
                    forceFallback: false,
                    fallbackClass: 'sortable-fallback',
                    scroll: true,
                    scrollSensitivity: 30,
                    scrollSpeed: 10,
                    onUpdate: (evt) => {
                        if (evt.from !== evt.to) {
                            saveWidgets($(evt.from).closest('.sidebar-item'));
                        }
                        saveWidgets($(evt.item).closest('.sidebar-item'));
                    },
                    onAdd: (evt) => {
                        if (evt.from !== evt.to) {
                            saveWidgets($(evt.from).closest('.sidebar-item'));
                        }
                        saveWidgets($(evt.item).closest('.sidebar-item'));
                    }
                });
            }
        });

        // Delete widget
        $('#wrap-widgets').on('click', '.widget-control-delete', (e) => {
            e.preventDefault();

            const $button = $(e.currentTarget);
            const $widget = $button.closest('li');
            const $sidebar = $button.closest('.sidebar-item');

            if (!confirm('Are you sure you want to delete this widget?')) {
                return;
            }

            $.ajax({
                url: this.routes.delete,
                type: 'DELETE',
                data: {
                    widget_id: $widget.data('id'),
                    position: $widget.data('position'),
                    sidebar_id: $sidebar.data('id'),
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    $sidebar.find('ul').html(response.data);
                    if (window.Botble) {
                        window.Botble.showSuccess(response.message);
                    } else {
                        alert(response.message);
                    }
                },
                error: function(xhr) {
                    const message = xhr.responseJSON?.message || 'An error occurred';
                    if (window.Botble) {
                        window.Botble.showError(message);
                    } else {
                        alert(message);
                    }
                }
            });
        });

        // Toggle widget content
        $('#wrap-widgets').on('click', '.widget-item .card-header', function(e) {
            const $header = $(e.currentTarget);
            const $widget = $header.closest('.widget-item');
            const $content = $widget.find('.widget-content');

            $content.slideToggle(300);

            const $icon = $header.find('.ti');
            if ($icon.hasClass('ti-chevron-up')) {
                setTimeout(() => {
                    $header.closest('.card').toggleClass('card-no-border-bottom-radius');
                }, 300);
            } else {
                $header.closest('.card').toggleClass('card-no-border-bottom-radius');
            }

            $icon.toggleClass('ti-chevron-down').toggleClass('ti-chevron-up');
        });

        // Toggle sidebar
        $('#wrap-widgets').on('click', '.sidebar-item .card-header .button-sidebar', function(e) {
            const $button = $(e.currentTarget);
            const $card = $button.closest('.card');
            const $body = $card.find('.card-body');

            $body.slideToggle(300);
            $button.find('.ti').toggleClass('ti-chevron-down').toggleClass('ti-chevron-up');
        });

        // Save widget
        $('#wrap-widgets').on('click', '.widget-save', (e) => {
            e.preventDefault();
            const $button = $(e.currentTarget);
            saveWidgets($button.closest('.sidebar-item'));
        });
    }
}

$(function() {
    new WidgetManager().init();
});
