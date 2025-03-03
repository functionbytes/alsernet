<li class="nav-item dropdown">
	<a middle-bar-control="element" href="javascript:;"
		class="nav-link d-flex align-items-center py-3 lvl-1 dropdown-toggle notifications-menu-item middle-bar-element"
		id="content-menu"
	>
		<i class="navbar-icon top-notification-icon">
			<svg class="NavIcon SidebarTopNavLinks-typeIcon BellNavIcon" viewBox="0 0 40 40"><path d="M7.5,32L7.5,32h-1c-1.5,0-2.8-0.8-3.4-2c-0.8-1.5-0.4-3.4,0.9-4.5c1.2-1,1.9-2.4,2-3.9v-6.1C6,8.1,12.3,2,20,2s14,6.1,14,13.5V22c0.2,1.4,0.9,2.6,2,3.5c1.3,1.1,1.7,2.9,0.9,4.5c-0.6,1.2-2,2-3.4,2h-0.9H7.5z M7.6,29h25.8c0.3,0,0.7-0.2,0.8-0.4c0.2-0.4,0-0.7-0.2-0.8l0,0c-1.6-1.4-2.7-3.3-3-5.5c0-0.1,0-0.1,0-0.2v-6.6C31,9.7,26.1,5,20,5S9,9.7,9,15.5v6.1v0.1c-0.2,2.4-1.3,4.5-3.1,6c-0.2,0.2-0.3,0.5-0.2,0.8C5.9,28.8,6.2,29,6.5,29H7.6L7.6,29z M24.7,34c-0.7,1.9-2.5,3.2-4.7,3.2s-4-1.3-4.7-3.2H24.7z"></path></svg>
		</i>
		@if (App\Models\Notification::count())
			<i class="top-notification-icon-dot" style="margin-left: 16px;
			margin-top: -10px;
			position: absolute;"></i>
		@endif
		<span class="leftbar-item">{{ trans('messages.notifications') }}</span>
	</a>
</li>

<script>
	var notificationBox;

	$(function() {
		$('.notifications-menu-item').on('click', function() {
			var sidebar = new Sidebar();
			if(!sidebar.showed()) {
				sidebar.load({
					url: '{{ route('manager.notifications') }}'
				});
			} else {
				sidebar.hide();
			}
		});
	});
</script>
