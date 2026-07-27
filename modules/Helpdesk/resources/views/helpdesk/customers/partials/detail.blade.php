@php
    $colors = ['c1','c2','c3','c4','c5','c6','c7','c8'];
    $colorClass = $colors[$selected->id % 8];
    $initials = strtoupper(substr($selected->name ?? '?', 0, 2));
    $activeTab = request('dtab', 'general');
@endphp

<div class="bv-page-detail-scroll">

    {{-- Hero --}}
    <div class="bv-page-hero">
        <div class="bv-page-hero-cover"></div>
        @if($selected->avatar_url && !str_contains($selected->avatar_url, 'ui-avatars.com'))
            <img src="{{ $selected->avatar_url }}" alt="{{ $selected->name }}"
                 class="bv-page-hero-av bv-img-cover" width="60" height="60" loading="lazy">
        @else
            <div class="bv-page-hero-av {{ $colorClass }}">{{ $initials }}</div>
        @endif
        <div class="bv-page-hero-name">
            {{ $selected->name }}
            @if($selected->email_verified_at)
                <i class="fas fa-circle-check bv-text-success-12"></i>
            @endif
            @if($selected->banned_at)
                <span class="bv-tag urgent ms-1">Suspendido</span>
            @endif
        </div>
        <div class="bv-page-hero-sub">{{ $selected->email }}</div>
        <div class="bv-page-hero-actions">
            <a href="{{ route('manager.helpdesk.customers.show', $selected) }}" class="bv-page-hero-btn">
                <i class="fas fa-expand"></i> Ver completo
            </a>
            <a href="{{ route('manager.helpdesk.customers.edit', $selected) }}" class="bv-page-hero-btn">
                <i class="fas fa-pen"></i> Editar
            </a>
            @if($selected->banned_at)
                <form method="POST" action="{{ route('manager.helpdesk.customers.unban', $selected) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="bv-page-hero-btn bv-text-success">
                        <i class="fas fa-circle-check"></i> Reactivar
                    </button>
                </form>
            @else
                <form method="POST" action="{{ route('manager.helpdesk.customers.ban', $selected) }}" class="d-inline needs-confirm"
                      data-confirm-msg="¿Suspender a {{ addslashes($selected->name) }}?">
                    @csrf
                    <button type="submit" class="bv-page-hero-btn bv-text-warning">
                        <i class="fas fa-ban"></i> Suspender
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- Stats --}}
    <div class="bv-page-detail-stats bv-right-stats">
        <div class="bv-right-stat">
            <div class="val">{{ number_format($selected->total_conversations ?? 0) }}</div>
            <div class="lbl">Conversaciones</div>
        </div>
        <div class="bv-right-stat">
            <div class="val">{{ number_format($selected->total_page_visits ?? 0) }}</div>
            <div class="lbl">Visitas</div>
        </div>
        <div class="bv-right-stat">
            <div class="val">
                @if($selected->last_seen_at)
                    {{ $selected->last_seen_at->diffForHumans(null, true, true) }}
                @else
                    —
                @endif
            </div>
            <div class="lbl">Última visita</div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="bv-page-detail-tabs">
        <a href="{{ route('manager.helpdesk.customers.index', array_merge(request()->except('dtab'), ['selected' => $selected->id, 'dtab' => 'general'])) }}"
           class="bv-page-detail-tab {{ $activeTab === 'general' ? 'on' : '' }} text-decoration-none">
            General
        </a>
        <a href="{{ route('manager.helpdesk.customers.index', array_merge(request()->except('dtab'), ['selected' => $selected->id, 'dtab' => 'conversations'])) }}"
           class="bv-page-detail-tab {{ $activeTab === 'conversations' ? 'on' : '' }} text-decoration-none">
            Conversaciones
        </a>
        <a href="{{ route('manager.helpdesk.customers.index', array_merge(request()->except('dtab'), ['selected' => $selected->id, 'dtab' => 'emails'])) }}"
           class="bv-page-detail-tab {{ $activeTab === 'emails' ? 'on' : '' }} text-decoration-none">
            Emails
        </a>
        <a href="{{ route('manager.helpdesk.customers.index', array_merge(request()->except('dtab'), ['selected' => $selected->id, 'dtab' => 'notes'])) }}"
           class="bv-page-detail-tab {{ $activeTab === 'notes' ? 'on' : '' }} text-decoration-none">
            Notas internas
        </a>
    </div>

    {{-- Tab content --}}
    <div class="bv-page-detail-body">

        @if($activeTab === 'general')

            {{-- Contact info --}}
            <div class="bv-right-section">
                <div class="bv-right-section-head">
                    <span class="bv-right-section-title">Información de contacto</span>
                </div>
                @if($selected->phone)
                    <div class="bv-right-row">
                        <span class="bv-text-hint">Teléfono</span>
                        <span class="bv-text-12">{{ $selected->phone }}</span>
                    </div>
                @endif
                @if($selected->whatsapp_phone)
                    <div class="bv-right-row">
                        <span class="bv-text-hint">WhatsApp</span>
                        <span class="bv-text-12">{{ $selected->whatsapp_phone }}</span>
                    </div>
                @endif
                @if($selected->country || $selected->city)
                    <div class="bv-right-row">
                        <span class="bv-text-hint">Ubicación</span>
                        <span class="bv-text-12">
                            {{ collect([$selected->city, $selected->state, strtoupper($selected->country ?? '')])->filter()->implode(', ') }}
                        </span>
                    </div>
                @endif
                @if($selected->language)
                    <div class="bv-right-row">
                        <span class="bv-text-hint">Idioma</span>
                        <span class="bv-text-12">{{ strtoupper($selected->language) }}</span>
                    </div>
                @endif
                @if($selected->timezone)
                    <div class="bv-right-row">
                        <span class="bv-text-hint">Zona horaria</span>
                        <span class="bv-text-12">{{ $selected->timezone }}</span>
                    </div>
                @endif
                <div class="bv-right-row">
                    <span class="bv-text-hint">Registrado</span>
                    <span class="bv-text-12">{{ $selected->created_at->format('d M Y') }}</span>
                </div>
            </div>

            {{-- Social channels --}}
            @if($selected->facebook_psid || $selected->instagram_id)
                <div class="bv-right-section">
                    <div class="bv-right-section-head">
                        <span class="bv-right-section-title">Canales sociales</span>
                    </div>
                    @if($selected->facebook_psid)
                        <div class="bv-right-row">
                            <span class="bv-text-hint"><i class="fab fa-facebook"></i> Facebook</span>
                            <span class="bv-text-muted-11">Conectado</span>
                        </div>
                    @endif
                    @if($selected->instagram_id)
                        <div class="bv-right-row">
                            <span class="bv-text-hint"><i class="fab fa-instagram"></i> Instagram</span>
                            <span class="bv-text-muted-11">Conectado</span>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Custom attributes --}}
            @if(!empty($selected->custom_attributes))
                <div class="bv-right-section">
                    <div class="bv-right-section-head">
                        <span class="bv-right-section-title">Atributos personalizados</span>
                    </div>
                    @foreach($selected->custom_attributes as $key => $value)
                        @if($value)
                            <div class="bv-right-row">
                                <span class="bv-text-hint">{{ ucwords(str_replace('_', ' ', $key)) }}</span>
                                <span class="bv-text-12">{{ $value }}</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif

        @elseif($activeTab === 'conversations')

            <div id="conv-list-container">
                <div class="d-flex align-items-center justify-content-center py-4">
                    <span class="bv-text-muted-12">Cargando conversaciones…</span>
                </div>
            </div>

        @elseif($activeTab === 'emails')

            <div class="bv-right-section-head d-flex justify-content-between align-items-center px-3 pt-3 pb-1">
                <span class="bv-right-section-title">Emails recientes</span>
                @if(filled($selected->email))
                    <a href="{{ route('manager.helpdesk.customers.emails', $selected) }}"
                       class="bv-text-muted-11 text-decoration-none">
                        Ver todos <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                @endif
            </div>

            <div id="email-list-container">
                <div class="d-flex align-items-center justify-content-center py-4">
                    <span class="bv-text-muted-12">Cargando emails…</span>
                </div>
            </div>

        @elseif($activeTab === 'notes')

            @if($selected->internal_notes)
                <div class="bv-info-box">
                    {{ $selected->internal_notes }}
                </div>
            @else
                <div class="bv-page-empty bv-py-32">
                    <div class="icon bv-av-44"><i class="fas fa-note-sticky"></i></div>
                    <div class="title bv-text-13">Sin notas internas</div>
                    <div class="hint">Agrega notas privadas desde el perfil completo del contacto.</div>
                    <a href="{{ route('manager.helpdesk.customers.edit', $selected) }}" class="bv-page-hero-btn mt-2">
                        <i class="fas fa-pen"></i> Editar contacto
                    </a>
                </div>
            @endif

        @endif

    </div>
</div>

@if($activeTab === 'conversations')
@push('scripts')
<script>
$(function () {
    $.getJSON('{{ route("manager.helpdesk.customers.conversations", $selected->id) }}', function (res) {
        if (!res.success || !res.data.length) {
            $('#conv-list-container').html(
                '<div class="bv-page-empty bv-py-32">' +
                '<div class="icon bv-av-44"><i class="fas fa-comments"></i></div>' +
                '<div class="title bv-text-13">Sin conversaciones</div>' +
                '<div class="hint">Este contacto aún no ha iniciado conversaciones.</div>' +
                '</div>'
            );
            return;
        }
        var html = '';
        $.each(res.data, function (i, c) {
            var statusClass = c.status_open ? 'bv-status-open' : 'bv-status-closed';
            html += '<a href="{{ route("manager.helpdesk.conversations.show", "") }}/' + c.id + '" ' +
                'class="bv-conv text-decoration-none bv-d-flex">' +
                '<div class="bv-av c3 bv-text-11"><i class="fas fa-comment"></i></div>' +
                '<div class="body">' +
                '<div class="row1"><span class="name">' + (c.subject || '#' + c.id) + '</span><span class="time">' + c.time + '</span></div>' +
                '<div class="row2"><span class="preview">' + (c.preview || '—') + '</span>' +
                '<span class="bv-status-pill ' + statusClass + '">' + (c.status || '—') + '</span>' +
                '</div></div></a>';
        });
        $('#conv-list-container').html(html);
    }).fail(function () {
        $('#conv-list-container').html('<div class="bv-error-box">Error al cargar conversaciones.</div>');
    });
});
</script>
@endpush
@endif

@if($activeTab === 'emails')
@push('scripts')
<script>
$(function () {
    $.getJSON('{{ route("manager.helpdesk.customers.emails-data", $selected->id) }}', function (res) {
        if (!res.success || !res.data.length) {
            $('#email-list-container').html(
                '<div class="bv-page-empty bv-py-32">' +
                '<div class="icon bv-av-44"><i class="fas fa-envelope-open"></i></div>' +
                '<div class="title bv-text-13">Sin emails enviados</div>' +
                '<div class="hint">No se han enviado emails a este contacto.</div>' +
                '</div>'
            );
            return;
        }
        var statusColors = { sent: 'c5', failed: 'c1', queued: 'c4' };
        var html = '';
        $.each(res.data, function (i, m) {
            var avColor = statusColors[m.status] || 'c3';
            var icon = m.status === 'sent' ? 'fa-check' : (m.status === 'failed' ? 'fa-times' : 'fa-clock');
            html += '<a href="' + m.preview_url + '" target="_blank" ' +
                'class="bv-conv text-decoration-none bv-d-flex">' +
                '<div class="bv-av ' + avColor + ' bv-text-11"><i class="fas ' + icon + '"></i></div>' +
                '<div class="body">' +
                '<div class="row1"><span class="name">' + escHtml(m.subject) + '</span><span class="time">' + m.time + '</span></div>' +
                '<div class="row2"><span class="preview">' + (m.module || '—') + '</span>' +
                '<span class="bv-status-pill ' + (m.status === 'sent' ? 'bv-status-open' : 'bv-status-closed') + '">' + m.status_label + '</span>' +
                '</div></div></a>';
        });
        $('#email-list-container').html(html);
    }).fail(function () {
        $('#email-list-container').html('<div class="bv-error-box">Error al cargar emails.</div>');
    });

    function escHtml(str) {
        return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
});
</script>
@endpush
@endif
