{{--
    Document module · Modal Gestionar documento individual.

    Uso:
        @include('helpdeskdocument::modals.doc-manage', [
            'id'        => 'docManage_xyz',
            'document'  => $document,        // Model | array
            'customer'  => $customer,        // Model | array (para el form de cliente)
            'order'     => $order,           // (opcional)
            'history'   => $history,         // array de eventos
            'actions'   => $actions,         // array de acciones de envio
        ])

    $document debe exponer:
        - id, name, status ('approved' | 'pending' | 'rejected')
        - status_label
        - notes (opcional)

    $history es un array de:
        [ 'ts' => '17/05 21:32', 'actor' => 'cliente', 'description' => 'Documento subido' ]

    $actions es un array de:
        [ 'key' => 'reminder', 'label' => 'Recordatorio', 'description' => 'Reenviar solicitud pendiente' ]
--}}
@php
    $id           = $id ?? 'docManage';
    $document     = $document ?? null;
    $customer     = $customer ?? null;
    $order        = $order ?? null;
    $history      = $history ?? [];
    $actions      = $actions ?? config('document.manage_actions', [
        ['key' => 'initial',     'label' => 'Solicitud inicial',       'description' => 'Solicitar documentos al cliente'],
        ['key' => 'reminder',    'label' => 'Recordatorio',            'description' => 'Reenviar solicitud pendiente'],
        ['key' => 'confirmed',   'label' => 'Confirmación de carga',   'description' => 'Avisar recepción correcta'],
        ['key' => 'approved',    'label' => 'Notificación aprobación', 'description' => 'Documento aprobado'],
    ]);

    $orderRef     = is_array($order)    ? ($order['reference'] ?? null)   : ($order?->reference ?? null);
    $docName      = is_array($document) ? ($document['name'] ?? '—')      : ($document?->name ?? '—');
    $docStatus    = is_array($document) ? ($document['status'] ?? 'pending')         : ($document?->status ?? 'pending');
    $docStatusLb  = is_array($document) ? ($document['status_label'] ?? 'Pendiente') : ($document?->status_label ?? 'Pendiente');

    $cFirst       = is_array($customer) ? ($customer['firstname'] ?? '') : ($customer?->firstname ?? '');
    $cLast        = is_array($customer) ? ($customer['lastname'] ?? '')  : ($customer?->lastname ?? '');
    $cDoc         = is_array($customer) ? ($customer['identifier'] ?? '') : ($customer?->identifier ?? '');
    $cEmail       = is_array($customer) ? ($customer['email'] ?? '')     : ($customer?->email ?? '');
@endphp

@include('helpdeskdocument::modals._assets')

<div class="docs-backdrop" data-docs-modal-backdrop-for="{{ $id }}"></div>
<div id="{{ $id }}" class="docs-modal docs-modal-md" role="dialog" aria-modal="true" aria-labelledby="{{ $id }}-title">
    <div class="docs-head">
        <div class="docs-head-icon"><i class="fa-regular fa-folder"></i></div>
        <div class="docs-head-text">
            <div class="docs-head-label">Dashboard · Gestionar documento</div>
            <div class="docs-head-title" id="{{ $id }}-title">
                @if($orderRef) Orden #{{ $orderRef }} · @endif {{ $docName }}
            </div>
        </div>
        <button type="button" class="docs-close" aria-label="Cerrar">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <div class="docs-body">
        {{-- Validacion etapa --}}
        <div class="docs-section">
            <div class="h">
                <span class="t">Validación etapa</span>
                <span class="s">Progreso de validación del documento</span>
            </div>
            <div class="docs-validation">
                <span class="dot {{ $docStatus }}"></span>
                Etapa actual: <b>{{ $docStatusLb }}</b>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary flex-fill" data-docs-approve>
                    <i class="fa-solid fa-check me-1"></i> Aprobar
                </button>
                <button type="button" class="btn btn-outline-secondary flex-fill" data-docs-reject>
                    <i class="fa-solid fa-xmark me-1"></i> Rechazar
                </button>
            </div>
        </div>

        {{-- Acciones de envio --}}
        <div class="docs-section">
            <div class="h">
                <span class="t">Acciones de envío</span>
                <span class="s">Comunicaciones automáticas</span>
            </div>
            @foreach($actions as $action)
                <div class="docs-action-row">
                    <div class="body">
                        <div class="nm">{{ $action['label'] }}</div>
                        <div class="ds">{{ $action['description'] }}</div>
                    </div>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-docs-action="{{ $action['key'] }}">
                        Enviar
                    </button>
                </div>
            @endforeach
        </div>

        {{-- Informacion del cliente --}}
        <div class="docs-section">
            <div class="h"><span class="t">Información del cliente</span></div>
            <div class="docs-frow">
                <div class="docs-field">
                    <label class="flabel">Nombre</label>
                    <input class="finput" name="firstname" value="{{ $cFirst }}">
                </div>
                <div class="docs-field">
                    <label class="flabel">Apellidos</label>
                    <input class="finput" name="lastname" value="{{ $cLast }}">
                </div>
            </div>
            <div class="docs-frow">
                <div class="docs-field">
                    <label class="flabel">DNI/NIE/CIF</label>
                    <input class="finput" name="identifier" value="{{ $cDoc }}">
                </div>
                <div class="docs-field">
                    <label class="flabel">Correo</label>
                    <input class="finput" name="email" type="email" value="{{ $cEmail }}">
                </div>
            </div>
        </div>

        {{-- Notas internas --}}
        <div class="docs-section">
            <div class="h"><span class="t">Notas internas</span></div>
            <div class="docs-field">
                <textarea class="ftextarea" rows="2" placeholder="Añadir comentario sobre el documento..."></textarea>
            </div>
            <button type="button" class="btn btn-outline-secondary btn-sm align-self-start" data-docs-add-note>
                <i class="fa-solid fa-plus me-1"></i> Agregar nota
            </button>
        </div>

        {{-- Historial --}}
        <div class="docs-section">
            <div class="h"><span class="t">Historial de acciones</span></div>
            @if(count($history))
                <div class="docs-history">
                    @foreach($history as $event)
                        <div>
                            <span class="ts">{{ $event['ts'] ?? '' }}</span>
                            · {{ $event['description'] ?? '' }}
                            @if(! empty($event['actor']))
                                por <b>{{ $event['actor'] }}</b>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="docs-vd-empty">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    Sin historial registrado
                </div>
            @endif
        </div>
    </div>

    <div class="docs-foot">
        <button type="button" class="btn btn-primary" data-docs-save>
            Guardar cambios
        </button>
        <button type="button" class="btn btn-outline-secondary" data-docs-history-full>
            Ver historial completo
        </button>
        <button type="button" class="btn btn-outline-secondary" data-docs-modal-close>
            Cerrar
        </button>
    </div>
</div>
