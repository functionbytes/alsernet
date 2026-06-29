<!DOCTYPE html>
<html lang="en">
<head>
    @include('manager.layouts.core._head')

    @include('manager.layouts.core._script_vars')

    @yield('head')
</head>
<body style="background:transparent;">
@include('helpdesks.chat._builder');
</body>
</html>



