@php
    $colors = ['c1','c2','c3','c4','c5','c6','c7','c8'];
@endphp

@forelse($customers as $customer)
    @php
        $colorClass = $colors[$customer->id % 8];
        $isSelected = request('selected') == $customer->id;
        $initials = strtoupper(substr($customer->name, 0, 1));
    @endphp
    <a href="{{ route('manager.helpdesk.customers.index', array_merge(request()->except('selected'), ['selected' => $customer->id])) }}"
       class="bv-conv text-decoration-none {{ $isSelected ? 'on' : '' }} {{ $customer->banned_at ? 'urgent' : '' }}">

        {{-- Avatar --}}
        @if($customer->avatar_url && !str_contains($customer->avatar_url, 'ui-avatars.com'))
            <img src="{{ $customer->avatar_url }}" alt="{{ $customer->name }}"
                 class="bv-av bv-img-cover" width="38" height="38" loading="lazy">
        @else
            <div class="bv-av {{ $colorClass }}">{{ $initials }}</div>
        @endif

        {{-- Body --}}
        <div class="body">
            <div class="row1">
                <span class="name">
                    {{ $customer->name }}
                    @if($customer->email_verified_at)
                        <i class="fas fa-circle-check bv-text-success-9"></i>
                    @endif
                </span>
                <span class="time">
                    @if($customer->last_seen_at)
                        {{ $customer->last_seen_at->diffForHumans(null, true, true) }}
                    @else
                        {{ $customer->created_at->diffForHumans(null, true, true) }}
                    @endif
                </span>
            </div>
            <div class="row2">
                <span class="preview">{{ $customer->email }}</span>
                @if($customer->total_conversations > 0)
                    <span class="bv-ucount">{{ $customer->total_conversations }}</span>
                @endif
            </div>
            <div class="row2 bv-mt-2">
                @if($customer->banned_at)
                    <span class="bv-tag urgent">Suspendido</span>
                @elseif($customer->phone)
                    <span class="preview bv-text-11">
                        <i class="fas fa-phone bv-text-10"></i> {{ $customer->phone }}
                    </span>
                @endif
                @if($customer->country)
                    <span class="bv-text-uppercase-meta">
                        {{ $customer->country }}
                    </span>
                @endif
            </div>
        </div>
    </a>
@empty
    <div class="bv-page-empty bv-py-40">
        <div class="icon"><i class="fas fa-users"></i></div>
        <div class="title">Sin contactos</div>
        <div class="hint">
            @if(request('search'))
                No se encontraron resultados para "{{ request('search') }}"
            @else
                Aún no hay contactos en esta categoría
            @endif
        </div>
    </div>
@endforelse
