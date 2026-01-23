<!DOCTYPE html>
<html lang="en">
<head>
    @include('manager.layouts.core._head')

    @include('manager.layouts.core._script_vars')

    @yield('head')
</head>
<body style="background:transparent;">
@include('admin.chat._builder');
</body>
</html>



