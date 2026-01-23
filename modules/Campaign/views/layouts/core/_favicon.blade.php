@if (Setting::get('site_favicon'))
    <link rel="shortcut icon" type="image/png" href="{{ route('SettingController@file', Setting::get('site_favicon')) }}"/>
@else
    @include('layouts.core._favicon_default')
@endif
