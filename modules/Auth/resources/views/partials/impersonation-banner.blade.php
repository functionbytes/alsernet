@if (session()->has(\Modules\Auth\Services\ImpersonationService::SESSION_KEY))
    @php
        $impersonatorId = session()->get(\Modules\Auth\Services\ImpersonationService::SESSION_KEY);
        $impersonator = \App\Models\User::find($impersonatorId);
    @endphp
    <div class="alert alert-warning border-0 rounded-0 mb-0 text-center py-2">
        <i class="fas fa-user-secret me-2"></i>
        Estás impersonando a <strong>{{ auth()->user()->firstname }} {{ auth()->user()->lastname }}</strong>
        @if ($impersonator)
            como <strong>{{ $impersonator->firstname }} {{ $impersonator->lastname }}</strong>.
        @endif
        <form action="{{ route('auth.impersonation.stop') }}" method="POST" class="d-inline-block ms-2">
            @csrf
            <button type="submit" class="btn btn-sm btn-dark">
                <i class="fas fa-sign-out-alt me-1"></i> Salir
            </button>
        </form>
    </div>
@endif
