<!DOCTYPE html>
<html lang="en">
<head>
	@include('layouts.core._head')

	@include('layouts.core._script_vars')

	@yield('head')

	@if (getThemeMode(Auth::user()->customer->theme_mode, request()->session()->get('customer-auto-theme-mode')) == 'dark')
		<meta name="theme-color" content="{{ getThemeColor(
			Auth::user()->customer->getColorScheme()) }}">
	@elseif (Auth::user()->customer->getMenuLayout() == 'left')
		<meta name="theme-color" content="#eff3f5">
	@endif

	<script>
		@if (Auth::user()->customer->theme_mode == 'auto')
			var ECHARTS_THEME = isDarkMode() ? 'dark' : null

			// auto detect dark-mode
			$(function() {
				autoDetechDarkMode('{{ route('AccountController@saveAutoThemeMode') }}');
			});
		@else
			var ECHARTS_THEME = '{{ Auth::user()->customer->theme_mode == 'dark' ? 'dark' : null }}';
		@endif
	</script>

    <!-- Theme -->
    <link rel="stylesheet" type="text/css" href="{{ asset('core/css/theme/'.Auth::user()->customer->getColorScheme().'.css') }}">
</head>
<body class="theme-{{ Auth::user()->customer->getColorScheme() }} {{ Auth::user()->customer->getMenuLayout() }}bar
	{{ Auth::user()->customer->getMenuLayout() }}bar-{{ request()->session()->get('customer-leftbar-state') }} state-{{ request()->session()->get('customer-leftbar-state') }}
	fullscreen-search-box
	mode-{{ getThemeMode(Auth::user()->customer->theme_mode, request()->session()->get('customer-auto-theme-mode'))  }}
">
	@if(config('app.cartpaye'))
		@include('layouts.core._menu_frontend_cartpaye')
    @elseif(config('app.store'))
		@include('layouts.core._menu_frontend_store')
	@elseif(config('app.brand'))
		@include('layouts.core._menu_frontend_brand')
	@elseif (!config('app.saas'))
		@include('layouts.core._menu_frontend_single')
	@else
		@include('layouts.core._menu_frontend_saas')
	@endif



	@include('layouts.core._middle_bar')

	<main class="container page-container px-3">
		@include('layouts.core._headbar_frontend')

        @if (config('app.store'))
            @include('layouts.core._topbar_frontend')
        @endif

		@yield('page_header')

		<!-- display flash message -->
		@include('layouts.core._errors')

		<!-- main inner content -->
		@yield('content')

		<!-- Footer -->
		@include('layouts.core._footer')
	</main>

	<!-- Admin area -->
	@include('layouts.core._admin_area')

	@if (!config('config.saas'))
		<!-- Admin area -->
		@include('layouts.core._loginas_area')
	@endif

	<!-- Notification -->
	@include('layouts.core._notify')
	@include('layouts.core._notify_frontend')

	<!-- display flash message -->
	@include('layouts.core._flash')

	<script>
		var wizardUserPopup;

		$(function() {
			// auto detect dark mode


			// Customer color scheme | menu layout wizard
			@if (false)
				$(function() {
					wizardUserPopup = new Popup({
						url: '{{ route('AccountController@wizardColorScheme') }}',
					});
					wizardUserPopup.load();
				});
			@endif

			@if (null !== Session::get('orig_admin_id') && Auth::user()->admin)
				notify({
					type: 'warning',
					message: `{!! trans('messages.current_login_as', ["name" =>Auth::user()->firsname]) !!}<br>{!! trans('messages.click_to_return_to_origin_user', ["link" => route("Admin\AdminController@loginBack")]) !!}`,
					timeout: false,
				});
			@endif

			@if (null !== Session::get('orig_admin_id') && Auth::user()->admin)
				notify({
					type: 'warning',
					message: `{!! trans('messages.site_is_offline') !!}`,
					timeout: false,
				});
			@endif
		})

	</script>

	{!! \App\Models\Setting::get('custom_script') !!}
</body>
</html>
