@extends('layouts.full')

@section('title', 'Contactos · Helpdesk')

@push('css')
    <link rel="preconnect" href="https://fonts.googleapis.com"/>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="{{ asset('vendor/helpdesk/conversations-identity.css') }}"/>
    <link rel="stylesheet" href="{{ asset('vendor/helpdesk/conversations.css') }}"/>
@endpush

@section('content')
<div class="bv-page">

    {{-- Topbar --}}
    @include('helpdesk::managers.customers.partials.topbar')

    {{-- 2-column body --}}
    <div class="bv-page-body">

        {{-- LEFT: customer list --}}
        <div class="bv-page-list">

            {{-- Stats strip --}}
            <div class="bv-page-stats">
                <div class="bv-page-stat">
                    <div class="val">{{ number_format($stats['total']) }}</div>
                    <div class="lbl">Total</div>
                </div>
                <div class="bv-page-stat">
                    <div class="val">{{ number_format($stats['verified']) }}</div>
                    <div class="lbl">Verificados</div>
                </div>
                <div class="bv-page-stat">
                    <div class="val">{{ number_format($stats['new_this_month']) }}</div>
                    <div class="lbl">Este mes</div>
                </div>
                <div class="bv-page-stat">
                    <div class="val">{{ number_format($stats['total_conversations']) }}</div>
                    <div class="lbl">Chats</div>
                </div>
            </div>

            {{-- List head: tabs --}}
            <div class="bv-page-list-head">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="bv-page-list-title">Contactos</span>
                    <span class="bv-text-muted-11">{{ $customers->total() }} total</span>
                </div>
                <div class="bv-page-tabs">
                    @php
                        $currentTab = request('tab', 'all');
                        $tabRoute = fn($tab) => route('manager.helpdesk.customers.index', array_merge(request()->except(['tab','selected','dtab']), ['tab' => $tab]));
                    @endphp
                    <a href="{{ $tabRoute('all') }}" class="bv-page-tab {{ $currentTab === 'all' ? 'on' : '' }}">
                        Todos <span class="c">{{ $tabs['all'] }}</span>
                    </a>
                    <a href="{{ $tabRoute('verified') }}" class="bv-page-tab {{ $currentTab === 'verified' ? 'on' : '' }}">
                        Verificados <span class="c">{{ $tabs['verified'] }}</span>
                    </a>
                    <a href="{{ $tabRoute('active') }}" class="bv-page-tab {{ $currentTab === 'active' ? 'on' : '' }}">
                        Activos <span class="c">{{ $tabs['active'] }}</span>
                    </a>
                    <a href="{{ $tabRoute('banned') }}" class="bv-page-tab {{ $currentTab === 'banned' ? 'on' : '' }}">
                        Suspendidos <span class="c">{{ $tabs['banned'] }}</span>
                    </a>
                </div>
            </div>

            {{-- Scrollable list --}}
            <div class="bv-page-list-scroll">
                @include('helpdesk::managers.customers.partials.list')

                {{-- Pagination --}}
                @if($customers->hasPages())
                    <div class="bv-card-foot">
                        {{ $customers->appends(request()->input())->links() }}
                    </div>
                @endif
            </div>

        </div>

        {{-- RIGHT: detail panel --}}
        <div class="bv-page-detail">
            @if($selected)
                @include('helpdesk::managers.customers.partials.detail')
            @else
                @include('helpdesk::managers.customers.partials.detail-empty')
            @endif
        </div>

    </div>

</div>
@endsection

@push('scripts')
<script>
$(function () {
    @if(session('success'))
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
