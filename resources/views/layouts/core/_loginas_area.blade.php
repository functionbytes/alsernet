@if (null !== Session::get('orig_admin_id'))
    <a href="{{ route('Admin\Admin2Controller@loginBack') }}" class="user-switch-area mc-modal-control bg-warning">
        <span class="material-symbols-rounded me-1">power_settings_new</span> {!! trans('messages.admin.loginas_area', [
            'name' => App\Models\User::findByUid(Session::get('orig_admin_id'))->admin->displayName()
        ]) !!}
    </a>
@endif
