@extends('layouts.theme')

@section('title', 'Chat IA — ' . $product->name)

@push('css')
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
    <style>
        .chat-shell { display: flex; flex-direction: column; height: calc(100vh - 180px); min-height: 560px; }
        .chat-layout { display: grid; gap: 1rem; grid-template-columns: 260px 1fr 300px; height: 100%; }
        @media (max-width: 1199px) {
            .chat-layout { grid-template-columns: 1fr; height: auto; }
            .chat-side { max-height: none; }
            .chat-shell { height: auto; }
        }
        .chat-side { display: flex; flex-direction: column; overflow-y: auto; max-height: 100%; }
        .chat-main { display: flex; flex-direction: column; min-height: 520px; }
        .chat-messages { flex: 1 1 auto; overflow-y: auto; background: #f5f6f8; padding: 1rem; }
        .chat-bubble { max-width: 90%; padding: 0.75rem 1rem; border-radius: 0.75rem; margin-bottom: 0.75rem; position: relative; }
        .chat-bubble.user { background: #90bb13; color: #fff; margin-left: auto; max-width: 75%; }
        .chat-bubble.assistant { background: #fff; border: 1px solid #e5e7eb; color: #000; }
        .chat-bubble .md { line-height: 1.55; font-size: 0.93rem; word-break: break-word; overflow-wrap: anywhere; }
        .chat-bubble .md p { margin-bottom: 0.5rem; }
        .chat-bubble .md p:last-child { margin-bottom: 0; }
        .chat-bubble .md ul, .chat-bubble .md ol { margin-bottom: 0.5rem; padding-left: 1.25rem; }
        .chat-bubble .md h1, .chat-bubble .md h2, .chat-bubble .md h3, .chat-bubble .md h4 { font-weight: 600; margin-top: 0.75rem; margin-bottom: 0.35rem; font-size: 1rem; }
        .chat-bubble .md code { background: rgba(0,0,0,0.06); padding: 0.1rem 0.35rem; border-radius: 0.25rem; font-size: 0.85em; }
        .chat-bubble .md pre { background: #000; color: #fff; padding: 0.75rem; border-radius: 0.5rem; overflow-x: auto; }
        .chat-bubble .md pre code { background: transparent; color: inherit; padding: 0; }
        .chat-bubble .md a { color: #0c7a64; text-decoration: underline; word-break: break-all; }
        .chat-bubble.user .md a { color: #fff; font-weight: 600; }
        .chat-bubble .md blockquote { border-left: 3px solid #90bb13; padding-left: 0.75rem; color: #5a6a85; }
        .chat-bubble .md table { border-collapse: collapse; font-size: 0.85em; margin-bottom: 0.5rem; }
        .chat-bubble .md table th, .chat-bubble .md table td { border: 1px solid #e5e7eb; padding: 0.25rem 0.5rem; }
        .msg-meta { font-size: 0.75rem; color: #5a6a85; margin-top: 0.35rem; display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap; }
        .msg-actions { display: none; gap: 0.25rem; margin-top: 0.35rem; }
        .chat-bubble.assistant:hover .msg-actions { display: flex; }
        .product-chip { display: inline-flex; align-items: center; gap: 0.35rem; background: #f0f0f0; padding: 0.2rem 0.5rem; border-radius: 0.35rem; font-size: 0.82rem; color: #5a6a85; }
        .product-chip strong { color: #000; }

        /* === Chat header redesign === */
        .chat-header-card { border: 1px solid #e5e7eb; background: linear-gradient(135deg, #fff 0%, #fafbfa 100%); }
        .product-icon-box { width: 44px; height: 44px; border-radius: 10px; background: linear-gradient(135deg, #90bb13 0%, #7aa310 100%); color: #fff; display: inline-flex; align-items: center; justify-content: center; font-size: 1.1rem; box-shadow: 0 2px 6px rgba(144, 187, 19, 0.25); }
        .min-w-0 { min-width: 0; }
        .chip { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.2rem 0.6rem; border-radius: 12px; font-size: 0.76rem; font-weight: 500; border: 1px solid transparent; }
        .chip i { font-size: 0.7rem; }.chip-supplier {
                                          background: #f5f6f8;
                                          color: #000000;
                                          border-color: #f1f1f1;
                                      }
        .chip-category { background: rgba(144, 187, 19, 0.1); color: #6d8c0c; border-color: rgba(144, 187, 19, 0.2); }
        .chip-saved { background: rgba(19, 222, 185, 0.12); color: #0c7a64; border-color: rgba(19, 222, 185, 0.25); }

        .chat-stats-group { background: #f5f6f8; border-radius: 10px; padding: 0.25rem; gap: 0; overflow: hidden; border: 1px solid #e5e7eb; }
        .chat-stats-group .stat-cell { display: flex; flex-direction: column; align-items: flex-start; padding: 0.35rem 0.85rem; border-right: 1px solid #e5e7eb; line-height: 1.1; min-width: 0; }
        .chat-stats-group .stat-cell:last-child { border-right: none; }
        .stat-label { font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.4px; color: #5a6a85; font-weight: 600; }
        .stat-value { font-size: 0.9rem; font-weight: 700; color: #2a3547; font-variant-numeric: tabular-nums; }
        .stat-model { font-size: 0.78rem; font-family: 'SFMono-Regular', Consolas, monospace; max-width: 160px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .invisible-hidden { visibility: hidden; opacity: 0; width: 0; padding: 0; border: 0; overflow: hidden; }
        @media (max-width: 991px) {
            .chat-stats-group { width: 100%; order: 3; }
        }
        @media (max-width: 575px) {
            .chat-stats-group .stat-cell { padding: 0.25rem 0.5rem; }
            .stat-value { font-size: 0.8rem; }
            .product-icon-box { width: 36px; height: 36px; font-size: 0.95rem; }
        }
        .prompt-card { cursor: pointer; transition: all 0.15s; border: 1px solid #e5e7eb; background: #fff; border-radius: 0.5rem; padding: 0.75rem; text-align: left; width: 100%; }
        .prompt-card:hover { border-color: #90bb13; background: #f9fbf3; transform: translateY(-1px); }
        .prompt-card .label { font-weight: 600; font-size: 0.9rem; color: #000; }
        .prompt-card .scope { font-size: 0.72rem; color: #5a6a85; margin-top: 0.1rem; display: inline-block; }
        .chat-history-item { padding: 0.5rem 0.75rem; border-radius: 0.35rem; cursor: pointer; display: block; color: #000; text-decoration: none; border: 1px solid transparent; }
        .chat-history-item:hover { background: #f0f0f0; color: #000; }
        .chat-history-item.active { background: rgba(144, 187, 19, 0.12); border-color: #90bb13; color: #000; }
        .chat-history-item .title { font-size: 0.85rem; font-weight: 500; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .chat-history-item .meta { font-size: 0.7rem; color: #5a6a85; margin-top: 0.15rem; }
        .sources-block { border-top: 1px solid #e5e7eb; margin-top: 0.5rem; padding-top: 0.5rem; font-size: 0.82rem; }
        .sources-block a { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 100%; }
        .model-price-badge { font-size: 0.68rem; color: #5a6a85; margin-left: 0.35rem; }
        .model-select-wrap { position: relative; }
        .cost-pill { background: rgba(19, 222, 185, 0.12); color: #0c7a64; padding: 0.2rem 0.5rem; border-radius: 0.35rem; font-size: 0.75rem; font-weight: 600; }
        .sticky-composer { border-top: 1px solid #e5e7eb; background: #fff; padding: 0.75rem 1rem; }
        .icon-btn { border: 1px solid #e5e7eb; background: #fff; width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center; border-radius: 0.35rem; cursor: pointer; color: #5a6a85; font-size: 0.8rem; }
        .icon-btn:hover { background: #f0f0f0; color: #000; }
        .icon-btn.saved { background: rgba(19, 222, 185, 0.15); color: #0c7a64; border-color: #13deb9; }
        .edit-area { border: 1px dashed #90bb13; background: #f9fbf3; border-radius: 0.5rem; padding: 0.5rem; }
        .edit-area textarea { width: 100%; border: 1px solid #e5e7eb; border-radius: 0.35rem; padding: 0.5rem; font-family: inherit; font-size: 0.9rem; min-height: 140px; }
        #composer textarea { resize: none; overflow-y: hidden; min-height: 40px; max-height: 400px; }
        .token-badge { background: rgba(0,0,0,0.05); color: #5a6a85; padding: 0.1rem 0.4rem; border-radius: 0.25rem; font-size: 0.7rem; }
        .typing-dots span { display: inline-block; width: 6px; height: 6px; background: #90bb13; border-radius: 50%; margin: 0 2px; animation: pulse 1.4s infinite ease-in-out; }
        .typing-dots span:nth-child(2) { animation-delay: 0.2s; }
        .typing-dots span:nth-child(3) { animation-delay: 0.4s; }
        @keyframes pulse { 0%, 80%, 100% { opacity: 0.3; transform: scale(0.8); } 40% { opacity: 1; transform: scale(1); } }
        .view-toggle { display: inline-flex; background: #f0f0f0; border-radius: 0.35rem; padding: 2px; }
        .view-toggle button { border: none; background: transparent; font-size: 0.7rem; padding: 0.15rem 0.5rem; border-radius: 0.25rem; color: #5a6a85; cursor: pointer; }
        .view-toggle button.active { background: #fff; color: #000; box-shadow: 0 1px 2px rgba(0,0,0,0.1); }
        .md-source-view { background: #000; color: #f9fbf3; padding: 0.75rem; border-radius: 0.5rem; font-family: 'SFMono-Regular', Consolas, monospace; font-size: 0.8rem; white-space: pre-wrap; word-break: break-word; max-height: 400px; overflow-y: auto; }
        .md-source-view .tag { color: #90bb13; }
        .md-source-view .attr { color: #49beff; }
        .char-counter { font-size: 0.7rem; color: #5a6a85; }
        .char-counter.warning { color: #b37a00; }
        .quick-template { display: inline-flex; align-items: center; gap: 0.25rem; background: #fff; border: 1px solid #e5e7eb; border-radius: 1rem; padding: 0.25rem 0.65rem; font-size: 0.78rem; color: #000; cursor: pointer; transition: all 0.15s; }
        .quick-template:hover { border-color: #90bb13; background: #f9fbf3; }
        .quick-template i { color: #90bb13; }
        .shop-preview { font-family: system-ui, -apple-system, sans-serif; line-height: 1.6; color: #000; }
        .shop-preview h1, .shop-preview h2, .shop-preview h3 { font-weight: 700; margin-top: 1.25rem; margin-bottom: 0.5rem; }
        .shop-preview h1 { font-size: 1.5rem; }
        .shop-preview h2 { font-size: 1.25rem; }
        .shop-preview h3 { font-size: 1.1rem; }
        .shop-preview table { width: 100%; border-collapse: collapse; margin: 0.75rem 0; }
        .shop-preview th, .shop-preview td { padding: 0.5rem 0.75rem; border-bottom: 1px solid #e5e7eb; text-align: left; }
        .shop-preview th { background: #f5f6f8; font-weight: 600; }
        .shop-preview ul li, .shop-preview ol li { margin-bottom: 0.25rem; }
        .shop-preview a { color: #0c7a64; }
        .shop-preview blockquote { border-left: 3px solid #90bb13; padding-left: 0.75rem; color: #5a6a85; margin: 0.75rem 0; }
        #quillSaveEditor .ql-editor { min-height: 260px; font-size: 0.95rem; }
    </style>
@endpush

@section('page_header')
    @include('core::components.card', ['title' => 'Chat IA'])
@endsection

@section('content')

    <div class="widget-content">
        @if($existingContent && ! $forceChat)
            @php
                $isPublished = $existingContent->status === 'published';
                $statusLabel = $isPublished ? 'Publicado' : 'Validado';
                $statusColor = $isPublished ? 'primary' : 'success';
            @endphp
            <div class="d-flex align-items-center justify-content-center" >

                    <div class="card w-100">
                        <div class="card-body text-center p-4">
                            <div class="d-flex align-items-center justify-content-center mx-auto mb-3"
                                 style="width:64px;height:64px;border-radius:50%;background:rgba(144,187,19,0.12);border:2px solid #90bb13;">
                                <i class="fas fa-check-double" style="font-size:1.5rem;color:#7aa310;"></i>
                            </div>
                            <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }} mb-3 px-3 py-2" style="font-size:.78rem;border-radius:12px;">
                                {{ $statusLabel }}
                            </span>
                            <h5 class="fw-bold mb-1">Este producto ya tiene contenido generado</h5>
                            <p class="text-muted mb-1">
                                <strong>{{ $existingContent->generated_name ?? $product->name }}</strong>
                            </p>
                            <p class="text-muted small mb-0">
                                Proveedor: <strong>{{ $product->supplier?->label ?? '—' }}</strong>
                                &nbsp;·&nbsp; Código: <strong>{{ $product->code ?? '—' }}</strong>
                            </p>
                        </div>
                        <div class="card-footer bg-transparent border-top px-4 py-3">
                            <p class="text-muted small text-center mb-3">
                                {{ $isPublished ? 'Publicado' : 'Validado' }} el {{ $existingContent->updated_at->format('d/m/Y \a \l\a\s H:i') }}.
                                Puedes revisarlo o generar una variante alternativa.
                            </p>
                            <div class="d-flex flex-column gap-2">
                                <a href="{{ route('settings.suppliers.content.show', $existingContent->uid) }}"
                                   class="btn btn-primary fw-semibold text-white w-100">
                                    Ver contenido
                                </a>
                                <a href="{{ route('settings.suppliers.products.chat', ['uid' => $product->uid]) }}?force=1"
                                   class="btn btn-secondary fw-semibold text-white w-100">
                                    Nuevo chat
                                </a>
                            </div>
                        </div>
                    </div>
            </div>
        @endif

        @if(! $existingContent || $forceChat)
        {{-- Product header with hierarchy --}}
        <div class="card mb-3 chat-header-card">
            <div class="card-body p-3">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    {{-- LEFT: Product identity --}}
                    <div class="d-flex align-items-center gap-3 flex-grow-1 min-w-0">
                        <div class="product-icon-box flex-shrink-0">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="min-w-0 flex-grow-1">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <h5 class="mb-0 fw-bold text-truncate" style="max-width: 320px;" title="{{ $product->name }}">
                                    {{ $product->name }}
                                </h5>
                                <span class="badge bg-light text-dark border">{{ $product->code }}</span>
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-wrap mt-1">
                                @if($product->supplier)
                                    <span class="chip chip-supplier" title="Proveedor">
                                        <i class="fas fa-truck"></i> {{ $product->supplier->label }}
                                    </span>
                                @endif
                                @if($product->category)
                                    <span class="chip chip-category" title="Categoría">
                                        <i class="fas fa-tag"></i> {{ $product->category->name }}
                                    </span>
                                @endif
                                @if($currentChat?->saved_content_id)
                                    <span class="chip chip-saved" title="Contenido guardado">
                                        <i class="fas fa-check-circle"></i> Guardado
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- CENTER: Stats (only when chat has data) --}}
                    <div class="chat-stats-group d-flex align-items-stretch {{ $currentChat ? '' : 'invisible-hidden' }}" id="chatStatsGroup">
                        <div class="stat-cell">
                            <span class="stat-label">Coste</span>
                            <span class="stat-value" id="totalCost">${{ number_format($currentChat->total_cost ?? 0, 5) }}</span>
                        </div>
                        <div class="stat-cell">
                            <span class="stat-label">Tokens</span>
                            <span class="stat-value" id="totalTokens">{{ number_format($currentChat->total_tokens ?? 0) }}</span>
                        </div>
                        @if($currentChat)
                            <div class="stat-cell">
                                <span class="stat-label">Modelo</span>
                                <span class="stat-value stat-model">{{ $currentChat->model }}</span>
                            </div>
                        @endif
                    </div>
                    {{-- RIGHT: Actions --}}
                    <div class="d-flex align-items-center gap-1">
                        @if($currentChat)
                            <div class="dropdown">
                                <button class="btn  btn-info" data-bs-toggle="dropdown" type="button" title="Exportar conversación" aria-expanded="false">
                                    <i class="fas fa-download"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li class="dropdown-header small">Exportar conversación</li>
                                    <li><a class="dropdown-item" href="{{ route('settings.suppliers.products.chat.export', ['uid' => $product->uid, 'chatUid' => $currentChat->uid]) }}?format=md">Markdown <small class="text-muted ms-1">.md</small></a></li>
                                    <li><a class="dropdown-item" href="{{ route('settings.suppliers.products.chat.export', ['uid' => $product->uid, 'chatUid' => $currentChat->uid]) }}?format=html">HTML <small class="text-muted ms-1">.html</small></a></li>
                                    <li><a class="dropdown-item" href="{{ route('settings.suppliers.products.chat.export', ['uid' => $product->uid, 'chatUid' => $currentChat->uid]) }}?format=json">JSON <small class="text-muted ms-1">.json</small></a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <button class="dropdown-item" type="button" id="btnForkChat">
                                            <i class="fas fa-code-branch me-2"></i>Fork conversación
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        @endif
                        <button type="button" class="btn  btn-info" id="btnShortcuts" title="Atajos de teclado (Cmd+/)">
                            <i class="fas fa-keyboard"></i>
                        </button>
                        <a href="{{ $existingContent ? route('settings.suppliers.content.show', $existingContent->uid) : route('settings.suppliers.products.index') }}" class="btn  btn-info">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="chat-shell">
            <div class="chat-layout">
                {{-- LEFT: Chat history --}}
                <aside class="card chat-side">
                    <div class="card-header py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-semibold">Conversaciones</h6>
                        <button class="btn btn-sm btn-primary" id="btnNewChat" type="button" title="Nueva conversación (Cmd+N)">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    <div class="p-2 border-bottom">
                        <input type="search" id="historySearch" class="form-control" placeholder="Buscar en historial..." value="{{ request('history_search') }}">
                    </div>
                    <div class="card-body p-2" style="flex:1 1 auto; overflow-y: auto;">
                        @if($recentChats->isEmpty())
                            <p class="text-muted small text-center py-3 mb-0">Sin historial todavía</p>
                        @else
                            @foreach($recentChats as $chat)
                                <a href="{{ route('settings.suppliers.products.chat', ['uid' => $product->uid]) }}?chat={{ $chat->uid }}"
                                   class="chat-history-item mb-1 {{ $currentChat && $currentChat->uid === $chat->uid ? 'active' : '' }}"
                                   data-chat-uid="{{ $chat->uid }}">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1" style="min-width:0;">
                                            <div class="title">{{ $chat->title ?? 'Conversación sin título' }}</div>
                                            <div class="meta">
                                                {{ $chat->created_at->diffForHumans() }}
                                                · {{ $chat->model }}
                                                @if($chat->saved_content_id)
                                                    · <i class="fas fa-check-circle text-success" title="Guardado"></i>
                                                @endif
                                            </div>
                                        </div>
                                        <button class="btn btn-sm p-0 ms-1 text-muted delete-chat" data-chat-uid="{{ $chat->uid }}" title="Eliminar" type="button">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </a>
                            @endforeach
                        @endif
                    </div>
                </aside>

                {{-- CENTER: Chat conversation --}}
                <main class="card chat-main">
                    <div class="chat-messages" id="messagesArea">
                        @if($messages->isEmpty())
                            {{-- Empty state with prompt cards + quick templates --}}
                            <div id="emptyState">
                                <div class="text-center py-4">
                                    <i class="fas fa-robot fa-3x d-block mx-auto mb-3" style="color:#90bb13;opacity:.5;"></i>
                                    <h5 class="fw-semibold mb-2">¿Cómo empezamos?</h5>
                                    <p class="text-muted small mb-4">Elige un prompt guardado o usa una plantilla rápida</p>
                                </div>

                                @if($prompts->isNotEmpty())
                                    <div class="px-3 mb-3">
                                        <small class="text-muted fw-semibold d-block mb-2">Prompts guardados</small>
                                        <div class="row g-2">
                                            @foreach($prompts->take(4) as $prompt)
                                                <div class="col-md-6">
                                                    <button type="button" class="prompt-card w-100 prompt-quick"
                                                            data-prompt-uid="{{ $prompt->uid }}">
                                                        <div class="label">{{ $prompt->label }}</div>
                                                        @if($prompt->scope)
                                                            <span class="scope">{{ $prompt->scope }}</span>
                                                        @endif
                                                    </button>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <div class="px-3">
                                    <small class="text-muted fw-semibold d-block mb-2">Plantillas rápidas (HTML enriquecido)</small>
                                    <div class="d-flex flex-wrap gap-2">
                                        <button type="button" class="quick-template quick-tpl" data-tpl="seo-description">
                                            <i class="fas fa-search"></i> Descripción SEO
                                        </button>
                                        <button type="button" class="quick-template quick-tpl" data-tpl="tech-sheet">
                                            <i class="fas fa-list-alt"></i> Ficha técnica HTML
                                        </button>
                                        <button type="button" class="quick-template quick-tpl" data-tpl="faq">
                                            <i class="fas fa-question-circle"></i> Preguntas frecuentes
                                        </button>
                                        <button type="button" class="quick-template quick-tpl" data-tpl="features-grid">
                                            <i class="fas fa-th"></i> Grid de características
                                        </button>
                                        <button type="button" class="quick-template quick-tpl" data-tpl="pros-cons">
                                            <i class="fas fa-balance-scale"></i> Pros y contras
                                        </button>
                                        <button type="button" class="quick-template quick-tpl" data-tpl="comparison">
                                            <i class="fas fa-table"></i> Tabla comparativa
                                        </button>
                                        <button type="button" class="quick-template quick-tpl" data-tpl="warning-box">
                                            <i class="fas fa-exclamation-triangle"></i> Ficha con avisos
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @else
                            @foreach($messages as $msg)
                                <div class="d-flex {{ $msg->role === 'user' ? 'justify-content-end' : 'justify-content-start' }}">
                                    <div class="chat-bubble {{ $msg->role === 'user' ? 'user' : 'assistant' }}" data-message-id="{{ $msg->id }}">
                                        <div class="md">{!! $msg->role === 'user' ? e($msg->content) : '' !!}</div>
                                        @if($msg->role === 'assistant')
                                            <script type="application/json" class="md-source-data">{!! json_encode($msg->content, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) !!}</script>
                                            @if(! empty($msg->sources))
                                                <script type="application/json" class="md-sources-raw">{!! json_encode($msg->sources, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) !!}</script>
                                            @endif
                                            @if(! empty($msg->sources))
                                                <div class="sources-block">
                                                    <strong class="text-muted small"><i class="fas fa-globe me-1 text-primary"></i>Fuentes web</strong>
                                                    <ul class="mb-0 mt-1 ps-3 small">
                                                        @foreach($msg->sources as $src)
                                                            <li><a href="{{ $src['url'] ?? '#' }}" target="_blank" rel="noopener noreferrer">{{ $src['title'] ?? $src['url'] ?? 'Fuente' }}</a></li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endif
                                            <div class="msg-actions">
                                                <div class="view-toggle" role="group">
                                                    <button class="vt-render active" data-view="render" title="Vista renderizada">Vista</button>
                                                    <button class="vt-source" data-view="source" title="Código fuente">Código</button>
                                                </div>
                                                <button class="icon-btn btn-preview-shop" title="Ver como en tienda"><i class="fas fa-eye"></i></button>
                                                <button class="icon-btn btn-copy" title="Copiar texto"><i class="fas fa-copy"></i></button>
                                                <button class="icon-btn btn-copy-html" title="Copiar HTML"><i class="fas fa-code"></i></button>
                                                <button class="icon-btn btn-edit" title="Editar"><i class="fas fa-pen"></i></button>
                                                <button class="icon-btn btn-regenerate" title="Regenerar"><i class="fas fa-redo"></i></button>
                                                <button class="icon-btn btn-save {{ $currentChat && $currentChat->saved_content_id ? 'saved' : '' }}"
                                                        title="{{ $currentChat && $currentChat->saved_content_id ? 'Ya guardado' : 'Guardar como contenido' }}">
                                                    <i class="fas {{ $currentChat && $currentChat->saved_content_id ? 'fa-check' : 'fa-save' }}"></i>
                                                </button>
                                            </div>
                                            <div class="msg-meta">
                                                @if($msg->model)<span>{{ $msg->model }}</span>@endif
                                                @if($msg->cost > 0)<span>· ${{ number_format($msg->cost, 6) }}</span>@endif
                                                @if($msg->total_tokens > 0)<span>· {{ number_format($msg->total_tokens) }} tokens</span>@endif
                                                @if($msg->latency_ms)<span>· {{ number_format($msg->latency_ms) }}ms</span>@endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>

                    {{-- Composer --}}
                    <div class="sticky-composer" id="composer">
                        <div class="input-group">
                            <textarea id="messageInput" class="form-control" rows="1"
                                      placeholder="Escribe un mensaje o ajuste... (Enter = enviar, Shift+Enter = nueva línea)"></textarea>
                            <button class="btn btn-primary" id="btnSend" type="button" title="Enviar">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                            <button class="btn btn-outline-danger d-none" id="btnCancel" type="button" title="Cancelar petición">
                                <i class="fas fa-stop"></i>
                            </button>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-1 gap-2 flex-wrap">
                            <small class="text-muted" id="statusDisplay">
                                <span id="modelDisplay">{{ $currentChat->model ?? 'gpt-4o-mini' }}</span>
                                <span id="webSearchIndicator" class="ms-2" style="display:{{ ($currentChat->web_search_enabled ?? false) ? 'inline' : 'none' }};">
                                    <i class="fas fa-globe text-primary"></i> Búsqueda web
                                </span>
                            </small>
                            <small class="char-counter" id="charCounter" style="visibility:hidden"></small>
                        </div>
                    </div>
                </main>

                {{-- RIGHT: Configuration --}}
                <aside class="card chat-side">
                    <div class="card-header py-2 px-3 border-bottom">
                        <h6 class="mb-0 fw-semibold">Configuración</h6>
                    </div>
                    <div class="card-body p-3" style="overflow-y: auto;">
                        {{-- Prompt selector --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Prompt</label>
                            <select id="promptSelector" class="form-select select2">
                                <option value="">Sin plantilla (chat libre)</option>
                                @foreach($prompts as $prompt)
                                    <option value="{{ $prompt->uid }}"
                                            data-web-search="{{ $prompt->enable_web_search ? '1' : '0' }}"
                                            data-ai-model="{{ $prompt->ai_model ?? '' }}"
                                            {{ $currentChat && $currentChat->prompt_uid === $prompt->uid ? 'selected' : '' }}>
                                        {{ $prompt->label }}
                                        @if($prompt->scope) — {{ $prompt->scope }} @endif
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted d-block mt-1">Define las instrucciones iniciales</small>
                        </div>

                        {{-- Model selector --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Modelo</label>
                            <select id="modelSelector" class="form-select select2">
                                @foreach($models as $key => $cfg)
                                    <option value="{{ $key }}"
                                            data-cost-in="{{ $cfg['cost_per_1k_in'] }}"
                                            data-cost-out="{{ $cfg['cost_per_1k_out'] }}"
                                            data-provider="{{ $cfg['provider'] }}"
                                            {{ ($currentChat->model ?? 'gpt-4o-mini') === $key ? 'selected' : '' }}>
                                        {{ $cfg['label'] }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="mt-1" id="modelPriceInfo">
                                <small class="text-muted" id="modelPriceLabel"></small>
                            </div>
                        </div>

                        {{-- Búsqueda web siempre activa — campo oculto --}}
                        <input type="hidden" id="webSearchToggle" value="1">

                        {{-- Execute prompt --}}
                        <button class="btn btn-primary  w-100 mb-2" id="btnSendPrompt" type="button">
                            Ejecutar prompt
                        </button>
                        <button class="btn btn-outline-secondary  w-100 mb-3" id="btnReset" type="button">
                            Vaciar chat actual
                        </button>

                        {{-- Tips --}}
                        <div class="border-top pt-3 mt-2">
                            <h6 class="fw-semibold small mb-2">Cómo usarlo</h6>
                            <ul class="small text-muted ps-3 mb-0">
                                <li class="mb-1">Ejecuta un prompt para empezar con contexto del producto</li>
                                <li class="mb-1">Activa búsqueda web para info actualizada</li>
                                <li class="mb-1">Edita la respuesta antes de guardar si quieres retocar</li>
                                <li class="mb-0">Usa los botones de copiar / regenerar sobre cada respuesta</li>
                            </ul>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
        @endif
    </div>

    {{-- Save confirmation modal with Quill editor --}}
    <div class="modal fade" id="saveModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl  modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar y guardar contenido</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info small mb-3 py-2">
                        <i class="fas fa-info-circle me-1"></i>
                        Se guardará como <strong>borrador pendiente de validación</strong> en
                        <code>/panel/setting/suppliers/content</code>. Puedes formatearlo con el editor antes de guardar.
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-12 col-sm-8">
                            <label class="form-label fw-semibold mb-1" for="saveProductName">Nombre del producto</label>
                            <input type="text" class="form-control" id="saveProductName"
                                   placeholder="Nombre del producto..."
                                   value="{{ $product->name }}">
                        </div>
                        <div class="col-12 col-sm-4">
                            <label class="form-label fw-semibold mb-1" for="saveProductBrand">Marca</label>
                            <input type="text" class="form-control" id="saveProductBrand"
                                   placeholder="Marca..."
                                   value="">
                        </div>
                    </div>

                    <ul class="nav nav-tabs nav-tabs-sm small" role="tablist">
                        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabEditor" type="button">Editor</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabHtml" type="button">HTML</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabShopPreview" type="button">Vista</button></li>
                    </ul>
                    <div class="tab-content border border-top-0 rounded-bottom p-2">
                        <div class="tab-pane fade show active" id="tabEditor">
                            <div id="quillSaveEditor"></div>
                        </div>
                        <div class="tab-pane fade" id="tabHtml">
                            <textarea id="htmlSaveSource" class="form-control md-source-view" rows="14"></textarea>
                            <small class="text-muted d-block mt-1">Edita el HTML directamente. Los cambios aquí se sincronizan con el editor al cerrar esta pestaña.</small>
                        </div>
                        <div class="tab-pane fade" id="tabShopPreview">
                            <div class="shop-preview p-3 bg-white" id="shopPreviewArea" style="max-height: 400px; overflow-y: auto;"></div>
                            <small class="text-muted d-block mt-1">Así se verá en la ficha de producto.</small>
                        </div>
                    </div>

                    <div class="mt-2 small text-muted d-flex justify-content-between">
                        <span>Conversación: <code id="saveChatRef"></code></span>
                        <span id="saveCharCount"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary w-100 mb-1" id="btnConfirmSave">Guardar contenido</button>
                    <button type="button" class="btn btn-secondary w-100 " data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Save success modal --}}
    <div class="modal fade" id="saveSuccessModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered" style="max-width:420px">
            <div class="modal-content">
                <div class="modal-body text-center py-4 px-4">
                    <div class="mb-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success-subtle text-success" style="width:56px;height:56px;font-size:1.5rem">
                            <i class="fas fa-check"></i>
                        </span>
                    </div>
                    <h5 class="fw-bold mb-1">Contenido guardado</h5>
                    <p class="text-muted small mb-4">El contenido se guardó como borrador pendiente de validación.</p>
                    <div class="d-flex flex-column gap-2">
                        <a id="btnGoToContent" href="#" class="btn btn-primary w-100">
                            Ver contenido generado
                        </a>
                        <button type="button" class="btn btn-outline-secondary w-100" data-bs-dismiss="modal">
                            Seguir en el chat
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Edit message modal (same Quill editor, updates only the bubble) --}}
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar mensaje</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info small mb-3 py-2">
                        <i class="fas fa-info-circle me-1"></i>
                        Los cambios aquí son <strong>locales</strong> al mensaje del chat. No se guardan como contenido hasta pulsar "Guardar".
                    </div>

                    <ul class="nav nav-tabs nav-tabs-sm small" role="tablist">
                        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabEditEditor" type="button">Editor</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabEditHtml" type="button">HTML</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabEditPreview" type="button">Vista</button></li>
                    </ul>
                    <div class="tab-content border border-top-0 rounded-bottom p-2">
                        <div class="tab-pane fade show active" id="tabEditEditor">
                            <div id="quillEditEditor"></div>
                        </div>
                        <div class="tab-pane fade" id="tabEditHtml">
                            <textarea id="htmlEditSource" class="form-control md-source-view" rows="14"></textarea>
                        </div>
                        <div class="tab-pane fade" id="tabEditPreview">
                            <div class="shop-preview p-3 bg-white" id="editPreviewArea" style="max-height: 400px; overflow-y: auto;"></div>
                        </div>
                    </div>
                    <div class="mt-2 small text-muted text-end">
                        <span id="editCharCount"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary w-100 mb-1" id="btnConfirmEdit">Aplicar cambios</button>
                    <button type="button" class="btn btn-secondary w-100 " data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Keyboard shortcuts modal --}}
    <div class="modal fade" id="shortcutsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Atajos de teclado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-sm align-middle mb-0">
                        <tbody>
                        <tr><td>Enviar mensaje</td><td class="text-end"><kbd>Enter</kbd></td></tr>
                        <tr><td>Nueva línea en el mensaje</td><td class="text-end"><kbd>Shift</kbd>+<kbd>Enter</kbd></td></tr>
                        <tr><td>Nueva conversación</td><td class="text-end"><kbd>Cmd</kbd>/<kbd>Ctrl</kbd>+<kbd>N</kbd></td></tr>
                        <tr><td>Ejecutar prompt seleccionado</td><td class="text-end"><kbd>Cmd</kbd>/<kbd>Ctrl</kbd>+<kbd>Enter</kbd></td></tr>
                        <tr><td>Focus en búsqueda de historial</td><td class="text-end"><kbd>Cmd</kbd>/<kbd>Ctrl</kbd>+<kbd>K</kbd></td></tr>
                        <tr><td>Mostrar esta ayuda</td><td class="text-end"><kbd>Cmd</kbd>/<kbd>Ctrl</kbd>+<kbd>/</kbd></td></tr>
                        <tr><td>Cerrar modal/preview</td><td class="text-end"><kbd>Esc</kbd></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Shop preview modal (inline, per message) --}}
    <div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"> Preview: cómo se verá en la tienda</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="shop-preview" id="previewContent"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/marked@11.1.1/marked.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/dompurify@3.0.8/dist/purify.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
    <script>
        $(function () {
            const productId = {{ $product->id }};
            const csrf      = $('meta[name="csrf-token"]').attr('content');
            const PROMPT_VARS      = @json($promptVariables ?? []);
            const PROMPT_TEMPLATES = @json($prompts->pluck('prompt_template', 'uid'));

            function renderTemplate(template) {
                if (!template) return '';
                return template.replace(/\{\{\s*([a-z0-9_]+)\s*\}\}/gi, function (_, key) {
                    const val = PROMPT_VARS[key];
                    return (val !== undefined && val !== null && val !== '') ? val : 'N/A';
                });
            }
            const urls = {
                show:       '{{ route("settings.suppliers.products.chat", ["uid" => $product->uid]) }}',
                send:       '{{ route("settings.suppliers.products.chat.send", ["uid" => $product->uid]) }}',
                regenerate: '{{ route("settings.suppliers.products.chat.regenerate", ["uid" => $product->uid]) }}',
                reset:      '{{ route("settings.suppliers.products.chat.reset", ["uid" => $product->uid]) }}',
                save:       '{{ route("settings.suppliers.products.chat.save", ["uid" => $product->uid]) }}',
                destroy:    '{{ route("settings.suppliers.products.chat.destroy", ["uid" => $product->uid, "chatUid" => "__UID__"]) }}',
                fork:       '{{ route("settings.suppliers.products.chat.fork", ["uid" => $product->uid, "chatUid" => "__UID__"]) }}',
            };

            let chatUid          = @json($currentChat?->uid);
            let isWaiting        = false;
            let activeXhr        = null;
            let regenerateBackup = null;
            const saveModal = new bootstrap.Modal(document.getElementById('saveModal'));
            const saveSuccessModal = new bootstrap.Modal(document.getElementById('saveSuccessModal'));
            const previewModal = new bootstrap.Modal(document.getElementById('previewModal'));
            let quillEditor = null;
            const MESSAGE_SOURCES = {};     // id => original content (for toggle view)
            const MESSAGE_SOURCES_URLS = {}; // id => sources array [{url, title}, ...]
            let _pendingSaveSources = [];

            marked.setOptions({ breaks: true, gfm: true });

            // DOMPurify config: allow semantic HTML + Bootstrap classes + safe styles
            const PURIFY_CFG = {
                ALLOWED_TAGS: ['p', 'strong', 'em', 'u', 's', 'br', 'hr',
                    'ul', 'ol', 'li',
                    'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
                    'a', 'img',
                    'blockquote', 'code', 'pre',
                    'table', 'thead', 'tbody', 'tfoot', 'tr', 'td', 'th',
                    'div', 'span', 'section', 'article',
                    'small', 'mark', 'abbr', 'sub', 'sup'],
                ALLOWED_ATTR: ['href', 'target', 'rel', 'title', 'src', 'alt', 'class', 'style', 'id', 'colspan', 'rowspan'],
                ALLOWED_URI_REGEXP: /^(?:(?:https?|mailto):|[^a-z]|[a-z+.\-]+(?:[^a-z+.\-:]|$))/i,
                FORBID_TAGS: ['script', 'iframe', 'object', 'embed', 'form', 'input', 'textarea', 'select', 'button', 'svg'],
                FORBID_ATTR: ['onerror', 'onload', 'onclick', 'onmouseover', 'onfocus', 'onblur', 'onchange', 'onsubmit'],
                ALLOW_DATA_ATTR: false,
            };

            DOMPurify.addHook('uponSanitizeAttribute', function (node, data) {
                if (data.attrName === 'style') {
                    // Allow only safe CSS properties
                    const safe = data.attrValue.replace(/[^;]+/g, function (rule) {
                        const prop = (rule.split(':')[0] || '').trim().toLowerCase();
                        const allowed = ['color', 'background-color', 'background', 'font-weight', 'font-style',
                            'text-align', 'text-decoration', 'margin', 'margin-top', 'margin-bottom',
                            'padding', 'border', 'border-left', 'border-radius', 'width', 'max-width',
                            'display', 'font-size', 'line-height', 'float', 'clear'];
                        return allowed.includes(prop) ? rule : '';
                    });
                    data.attrValue = safe;
                }
            });

            function renderContent(text) {
                if (!text) return '';
                // If content looks like HTML (has tags), render as HTML sanitized
                const trimmed = text.trim();
                const looksLikeHtml = /<\/?(p|div|span|h[1-6]|ul|ol|li|strong|em|table|section|article|blockquote|hr)\b/i.test(trimmed);
                if (looksLikeHtml) {
                    return DOMPurify.sanitize(trimmed, PURIFY_CFG);
                }
                return DOMPurify.sanitize(marked.parse(text), PURIFY_CFG);
            }

            function renderAsSource(text) {
                return '<div class="md-source-view">' + escapeHtml(text || '') + '</div>';
            }

            // Render already-embedded assistant messages
            $('.chat-bubble.assistant .md-source-data').each(function () {
                const raw = JSON.parse($(this).text());
                const id = $(this).closest('.chat-bubble').data('message-id');
                MESSAGE_SOURCES[id] = raw;
                $(this).siblings('.md').html(renderContent(raw));
                $(this).remove();
            });
            // Extract sources stored for server-rendered messages
            $('.chat-bubble.assistant .md-sources-raw').each(function () {
                const id = $(this).closest('.chat-bubble').data('message-id');
                try { MESSAGE_SOURCES_URLS[id] = JSON.parse($(this).text()); } catch(e) {}
                $(this).remove();
            });

            // === Quick templates ===
            const QUICK_TEMPLATES = {
                'seo-description': 'Redacta una descripción SEO optimizada del producto en HTML. Usa <h2> para el título principal, un párrafo de apertura con la palabra clave en <strong>, una lista <ul> con 4-6 beneficios clave (cada uno con icono emoji al inicio), y un párrafo final con CTA. Incluye la marca y el código del producto.',
                'tech-sheet': 'Genera una ficha técnica del producto en formato HTML con una tabla <table class="table"> de dos columnas (Característica / Valor). Incluye al menos 8 filas. Antes de la tabla, añade un <h3> con el nombre del producto. Después de la tabla, un bloque <div class="alert alert-info"> con una nota técnica relevante.',
                'faq': 'Redacta 6 preguntas frecuentes sobre este producto en HTML. Cada pregunta debe ir en un <h4> y la respuesta en un <p>. Usa <strong> para resaltar puntos clave. Comienza con un <h2>Preguntas frecuentes</h2>.',
                'features-grid': 'Crea un grid de 4-6 características destacadas en HTML usando <div class="row"> con <div class="col-md-6"> por cada feature. Cada col debe contener un <div class="card"> con <h5> título y <p> descripción corta. Usa clases bg-light para fondo suave.',
                'pros-cons': 'Genera una comparativa de pros y contras en HTML. Usa dos columnas con <div class="row"><div class="col-md-6">: una con <h4 class="text-success">Ventajas</h4> y lista con checkmarks (✓) en verde, otra con <h4 class="text-danger">Consideraciones</h4> y lista con (⚠). Mínimo 4 items en cada lista.',
                'comparison': 'Genera una tabla HTML comparando este producto con 2-3 alternativas típicas del mercado. Usa <table class="table table-striped"> con <thead> y <tbody>. Columnas: Característica, Este producto, Alternativa A, Alternativa B. Resalta las ventajas en verde con <span style="color: #13deb9">✓</span>.',
                'warning-box': 'Redacta la ficha del producto en HTML incluyendo: <h2> título, <p> introducción de 2-3 líneas, <ul> lista de características principales, y al final un <div class="alert alert-warning"> con avisos legales/de uso relevantes para este tipo de producto.',
            };

            $(document).on('click', '.quick-tpl', function () {
                const key = $(this).data('tpl');
                const tpl = QUICK_TEMPLATES[key];
                if (!tpl) return;
                sendMessage(tpl, '');
            });

            function scrollToBottom() {
                const area = document.getElementById('messagesArea');
                area.scrollTop = area.scrollHeight;
            }

            function buildSourcesHtml(sources, webSearchUsed) {
                if (sources && sources.length > 0) {
                    const items = sources.map(s => `<li><a href="${s.url || '#'}" target="_blank" rel="noopener noreferrer">${escapeHtml(s.title || s.url || 'Fuente')}</a></li>`).join('');
                    return `<div class="sources-block">
                <strong class="text-muted small"><i class="fas fa-globe me-1 text-primary"></i>Fuentes web</strong>
                <ul class="mb-0 mt-1 ps-3 small">${items}</ul>
            </div>`;
                }
                if (webSearchUsed) {
                    return `<div class="sources-block"><small class="text-muted"><i class="fas fa-globe me-1 text-primary"></i>Enriquecido con búsqueda web</small></div>`;
                }
                return '';
            }

            function escapeHtml(text) {
                return String(text ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
            }

            function buildMetaHtml(data) {
                const bits = [];
                if (data.model) bits.push(escapeHtml(data.model));
                if (data.cost) bits.push('$' + Number(data.cost).toFixed(6));
                if (data.tokens?.total) bits.push(Number(data.tokens.total).toLocaleString() + ' tokens');
                if (data.latency_ms) bits.push(Number(data.latency_ms).toLocaleString() + 'ms');
                return bits.length ? '<div class="msg-meta">' + bits.map((b, i) => `<span>${i ? '· ' : ''}${b}</span>`).join('') + '</div>' : '';
            }

            function appendUserMessage(content) {
                $('#emptyState').remove();
                const html = `<div class="d-flex justify-content-end">
            <div class="chat-bubble user">
                <div class="md">${escapeHtml(content)}</div>
            </div>
        </div>`;
                $('#messagesArea').append(html);
                scrollToBottom();
            }

            function buildActionsHtml() {
                return `<div class="msg-actions">
            <div class="view-toggle" role="group">
                <button class="vt-render active" data-view="render" title="Vista renderizada">Vista</button>
                <button class="vt-source" data-view="source" title="Código fuente">Código</button>
            </div>
            <button class="icon-btn btn-preview-shop" title="Ver como en tienda"><i class="fas fa-eye"></i></button>
            <button class="icon-btn btn-copy" title="Copiar texto"><i class="fas fa-copy"></i></button>
            <button class="icon-btn btn-copy-html" title="Copiar HTML"><i class="fas fa-code"></i></button>
            <button class="icon-btn btn-edit" title="Editar"><i class="fas fa-pen"></i></button>
            <button class="icon-btn btn-regenerate" title="Regenerar"><i class="fas fa-redo"></i></button>
            <button class="icon-btn btn-save" title="Guardar como contenido"><i class="fas fa-save"></i></button>
        </div>`;
            }

            function appendAssistantMessage(data) {
                $('#emptyState').remove();
                MESSAGE_SOURCES[data.message_id] = data.content;
                MESSAGE_SOURCES_URLS[data.message_id] = data.sources || [];
                const sourcesHtml = buildSourcesHtml(data.sources || [], data.web_search_used || false);
                const metaHtml = buildMetaHtml(data);
                const html = `<div class="d-flex justify-content-start">
            <div class="chat-bubble assistant" data-message-id="${data.message_id}" data-view="render">
                <div class="md">${renderContent(data.content)}</div>
                ${sourcesHtml}
                ${buildActionsHtml()}
                ${metaHtml}
            </div>
        </div>`;
                $('#messagesArea').append(html);
                scrollToBottom();
            }

            function replaceLastAssistantMessage(data) {
                const $last = $('.chat-bubble.assistant').last();
                if (!$last.length) { appendAssistantMessage(data); return; }
                MESSAGE_SOURCES[data.message_id] = data.content;
                MESSAGE_SOURCES_URLS[data.message_id] = data.sources || [];
                $last.attr('data-message-id', data.message_id).attr('data-view', 'render');
                $last.find('.md').html(renderContent(data.content));
                $last.find('.sources-block, .msg-actions, .msg-meta').remove();
                $last.append(buildSourcesHtml(data.sources || [], data.web_search_used || false));
                $last.append(buildActionsHtml());
                $last.append(buildMetaHtml(data));
            }

            function appendLoadingBubble() {
                const html = `<div class="d-flex justify-content-start" id="loadingBubble">
            <div class="chat-bubble assistant">
                <div class="typing-dots"><span></span><span></span><span></span></div>
            </div>
        </div>`;
                $('#messagesArea').append(html);
                scrollToBottom();
            }

            function setWaiting(state) {
                isWaiting = state;
                $('#messageInput').prop('disabled', state);
                $('#btnCancel').toggleClass('d-none', !state);
                if (state) {
                    $('#btnSend').prop('disabled', true);
                    $('#btnSendPrompt').prop('disabled', true)
                        .html('<span class="spinner-border spinner-border-sm me-1"></span>Ejecutando…');
                } else {
                    $('#btnSend').prop('disabled', $('#messageInput').val().trim().length === 0);
                    const hasPrompt = !!$('#promptSelector').val();
                    $('#btnSendPrompt').prop('disabled', !hasPrompt)
                        .text('Ejecutar prompt');
                    $('#messageInput').focus();
                }
            }

            function updateGlobalTotals(totalCost, totalTokens) {
                $('#chatStatsGroup').removeClass('invisible-hidden');
                $('#totalCost').text('$' + Number(totalCost).toFixed(5));
                $('#totalTokens').text(Number(totalTokens).toLocaleString());
            }

            function sendMessage(message, promptUid) {
                if (isWaiting) return;

                const payload = {
                    _token: csrf,
                    chat_uid: chatUid,
                    model: $('#modelSelector').val(),
                    web_search: parseInt($('#webSearchToggle').val() || 0),
                };
                if (promptUid) payload.prompt_uid = promptUid;
                if (message)   payload.message    = message;

                if (message && !promptUid) appendUserMessage(message);
                appendLoadingBubble();
                setWaiting(true);

                activeXhr = $.ajax({
                    url: urls.send,
                    method: 'POST',
                    data: payload,
                    success: function (res) {
                        $('#loadingBubble').remove();
                        if (!res.success) {
                            toastr.error(res.message || 'Error al generar');
                            return;
                        }

                        chatUid = res.chat_uid;
                        // Update URL without reload
                        const newUrl = urls.show + '?chat=' + chatUid;
                        window.history.replaceState({}, '', newUrl);

                        // If it was a prompt, render the generated user message
                        if (promptUid) appendUserMessage(res.user_message);

                        appendAssistantMessage({
                            message_id: res.message_id,
                            content: res.content,
                            sources: res.sources,
                            web_search_used: res.web_search_used,
                            model: res.model,
                            cost: res.cost,
                            tokens: res.tokens,
                            latency_ms: res.latency_ms,
                        });

                        updateGlobalTotals(res.total_cost, res.total_tokens);
                    },
                    error: function (xhr) {
                        if (xhr.statusText === 'abort') return;
                        $('#loadingBubble').remove();
                        toastr.error(xhr.responseJSON?.message || 'Error de conexión');
                    },
                    complete: function () {
                        activeXhr = null;
                        $('#messageInput').val('');
                        autoResize(document.getElementById('messageInput'));
                        $('#charCounter').css('visibility', 'hidden');
                        setWaiting(false);
                    }
                });
            }

            function regenerateLastMessage() {
                if (isWaiting || !chatUid) return;
                const $last = $('.chat-bubble.assistant').last();
                if (!$last.length) return;

                // Guardar estado completo del bubble para poder restaurarlo si se cancela
                regenerateBackup = { $bubble: $last, html: $last.html() };

                $last.find('.md').html('<div class="typing-dots"><span></span><span></span><span></span></div>');
                $last.find('.sources-block, .msg-actions, .msg-meta').remove();
                setWaiting(true);

                activeXhr = $.ajax({
                    url: urls.regenerate,
                    method: 'POST',
                    data: { _token: csrf, chat_uid: chatUid },
                    success: function (res) {
                        regenerateBackup = null;
                        if (!res.success) { toastr.error(res.message || 'Error'); return; }
                        replaceLastAssistantMessage({
                            message_id: res.message_id,
                            content: res.content,
                            sources: res.sources,
                            web_search_used: res.web_search_used,
                            model: res.model,
                            cost: res.cost,
                            tokens: res.tokens,
                            latency_ms: res.latency_ms,
                        });
                        updateGlobalTotals(res.total_cost, res.total_tokens);
                        toastr.success('Respuesta regenerada');
                    },
                    error: function (xhr) {
                        if (xhr.statusText === 'abort') return;
                        toastr.error(xhr.responseJSON?.message || 'Error de conexión');
                    },
                    complete: function () {
                        activeXhr = null;
                        regenerateBackup = null;
                        setWaiting(false);
                    }
                });
            }

            // === Event handlers ===
            $('#btnSend').on('click', function () {
                const msg = $('#messageInput').val().trim();
                if (!msg) return;
                sendMessage(msg, '');
            });

            $('#btnCancel').on('click', function () {
                if (activeXhr) {
                    activeXhr.abort();
                    activeXhr = null;
                }
                // Restaurar bubble si se canceló una regeneración
                if (regenerateBackup) {
                    regenerateBackup.$bubble.html(regenerateBackup.html);
                    regenerateBackup = null;
                } else {
                    $('#loadingBubble').remove();
                }
                $('#messageInput').val('');
                autoResize(document.getElementById('messageInput'));
                $('#charCounter').css('visibility', 'hidden');
                setWaiting(false);
                toastr.info('Petición cancelada');
            });

            $('#btnSendPrompt').on('click', function () {
                const promptUid = $('#promptSelector').val();
                if (!promptUid) return;
                sendMessage('', promptUid);
            });

            $('#messageInput').on('keydown', function (e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    if (!isWaiting) $('#btnSend').trigger('click');
                }
            });

            $('#btnReset').on('click', function () {
                if (!chatUid) { toastr.info('No hay conversación para vaciar'); return; }
                const $btn = $(this);
                if (!$btn.data('confirming')) {
                    $btn.data('confirming', true).text('¿Confirmar?').addClass('btn-danger').removeClass('btn-outline-secondary');
                    setTimeout(() => $btn.data('confirming', false).text('Vaciar chat actual').removeClass('btn-danger').addClass('btn-outline-secondary'), 3000);
                    return;
                }
                $btn.data('confirming', false).text('Vaciar chat actual').removeClass('btn-danger').addClass('btn-outline-secondary');
                $.ajax({
                    url: urls.reset,
                    method: 'POST',
                    data: { _token: csrf, _method: 'DELETE', chat_uid: chatUid },
                    success: function () { window.location = urls.show; },
                    error: function () { toastr.error('Error al vaciar'); }
                });
            });

            $('#btnNewChat').on('click', function () { window.location = urls.show; });

            $(document).on('click', '.delete-chat', function (e) {
                e.preventDefault();
                e.stopPropagation();
                const $btn = $(this);
                const uid = $btn.data('chat-uid');
                if (!$btn.data('confirming')) {
                    $btn.data('confirming', true).html('<i class="fas fa-exclamation"></i>').addClass('text-danger');
                    setTimeout(() => $btn.data('confirming', false).html('<i class="fas fa-times"></i>').removeClass('text-danger'), 3000);
                    return;
                }
                $btn.data('confirming', false);
                $.ajax({
                    url: urls.destroy.replace('__UID__', uid),
                    method: 'POST',
                    data: { _token: csrf, _method: 'DELETE' },
                    success: function () {
                        if (uid === chatUid) window.location = urls.show;
                        else $('a[data-chat-uid="' + uid + '"]').remove();
                    }
                });
            });

            $('#btnForkChat').on('click', function () {
                if (!chatUid) return;
                const $btn = $(this).prop('disabled', true).text('Bifurcando…');

                $.ajax({
                    url: urls.fork.replace('__UID__', chatUid),
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf },
                    success: function (res) {
                        if (res.success) {
                            toastr.success('Conversación bifurcada: ' + res.title, 'Fork');
                            setTimeout(() => { window.location = res.redirect_url; }, 600);
                        } else {
                            toastr.error(res.message || 'Error al bifurcar', 'Error');
                            $btn.prop('disabled', false).html('<i class="fas fa-code-branch me-2"></i>Fork conversación');
                        }
                    },
                    error: function (xhr) {
                        toastr.error(xhr.responseJSON?.message || 'Error al bifurcar', 'Error');
                        $btn.prop('disabled', false).html('<i class="fas fa-code-branch me-2"></i>Fork conversación');
                    }
                });
            });

            // Empty-state prompt quick launch: previsualiza el prompt renderizado, no envía directamente
            $(document).on('click', '.prompt-quick', function () {
                const uid = $(this).data('prompt-uid');
                $('#promptSelector').val(uid).trigger('change');
                const tpl = uid ? (PROMPT_TEMPLATES[uid] || '') : '';
                const rendered = renderTemplate(tpl);
                if (rendered && !isWaiting) {
                    $('#messageInput').val(rendered).focus();
                    autoResize(document.getElementById('messageInput'));
                }
                toastr.info('Revisa el prompt y pulsa Enviar (o "Ejecutar prompt" sin editar).', 'Prompt cargado');
            });

            function syncWebSearchFromPrompt($option, autoSwitchModel) {
                // Búsqueda web siempre activa — no se cambia modelo automáticamente
                $('#webSearchIndicator').show();
            }

            const DEPRECATED_MODELS_JS = {'gemini-2.0-flash': 'gemini-2.5-flash', 'gemini-2.0-flash-lite': 'gemini-2.5-flash'};

            $('#promptSelector').on('change', function () {
                const uid      = $(this).val();
                const $selected = $(this).find(':selected');
                const tpl = uid ? (PROMPT_TEMPLATES[uid] || '') : '';
                if (tpl && !isWaiting) {
                    $('#messageInput').val(renderTemplate(tpl)).focus();
                    autoResize(document.getElementById('messageInput'));
                }
                syncWebSearchFromPrompt($selected, true);

                // Auto-seleccionar el modelo configurado en el prompt
                if (uid) {
                    let promptModel = ($selected.data('ai-model') || '').trim();
                    promptModel = DEPRECATED_MODELS_JS[promptModel] || promptModel;
                    if (promptModel && $('#modelSelector option[value="' + promptModel + '"]').length) {
                        $('#modelSelector').val(promptModel).trigger('change');
                    }
                }

                $('#btnSendPrompt').prop('disabled', !uid);
                const taLen = $('#messageInput').val().length;
                $('#btnSend').prop('disabled', taLen === 0);
                $('#charCounter').css('visibility', 'hidden');
            });

            // Sync on initial load (sin auto-switch de modelo)
            syncWebSearchFromPrompt($('#promptSelector').find(':selected'), false);
            $('#btnSendPrompt').prop('disabled', !$('#promptSelector').val());

            $('#modelSelector').on('change', function () {
                updateModelPriceLabel();
                localStorage.setItem('chat_pref_model', $(this).val());
            }).trigger('change');

            // Restaurar preferencias guardadas
            (function restorePrefs() {
                const prefModel = localStorage.getItem('chat_pref_model') || 'gpt-4o-mini';
                if ($('#modelSelector option[value="' + prefModel + '"]').length) {
                    $('#modelSelector').val(prefModel);
                    updateModelPriceLabel();
                }
            })();

            function updateModelPriceLabel() {
                const opt = $('#modelSelector option:selected');
                const inp = parseFloat(opt.data('cost-in'));
                const out = parseFloat(opt.data('cost-out'));
                $('#modelDisplay').text(opt.val());
                if (!isNaN(inp)) {
                    $('#modelPriceLabel').html(
                        'Entrada: $' + inp.toFixed(5) + '/1K · ' +
                        'Salida: $' + out.toFixed(5) + '/1K'
                    );
                }
            }

            // Message actions (copy, edit, regenerate, save)
            $(document).on('click', '.btn-copy', function () {
                const $bubble = $(this).closest('.chat-bubble');
                const id = $bubble.data('message-id');
                const source = MESSAGE_SOURCES[id] ?? $bubble.find('.md').text();
                const text = $('<div>').html(renderContent(source)).text(); // plain text from rendered
                navigator.clipboard.writeText(text).then(
                    () => toastr.success('Texto copiado'),
                    () => toastr.error('No se pudo copiar')
                );
            });

            $(document).on('click', '.btn-copy-html', function () {
                const $bubble = $(this).closest('.chat-bubble');
                const id = $bubble.data('message-id');
                const source = MESSAGE_SOURCES[id] ?? '';
                const html = renderContent(source);
                navigator.clipboard.writeText(html).then(
                    () => toastr.success('HTML copiado al portapapeles'),
                    () => toastr.error('No se pudo copiar HTML')
                );
            });

            // Toggle Vista / Código fuente
            $(document).on('click', '.view-toggle button', function () {
                const $btn = $(this);
                const $bubble = $btn.closest('.chat-bubble');
                const id = $bubble.data('message-id');
                const view = $btn.data('view');
                const source = MESSAGE_SOURCES[id] ?? '';

                $btn.siblings().removeClass('active');
                $btn.addClass('active');
                $bubble.attr('data-view', view);

                if (view === 'source') {
                    $bubble.find('.md').html(renderAsSource(source));
                } else {
                    $bubble.find('.md').html(renderContent(source));
                }
            });

            // Preview como en tienda
            $(document).on('click', '.btn-preview-shop', function () {
                const $bubble = $(this).closest('.chat-bubble');
                const id = $bubble.data('message-id');
                const source = MESSAGE_SOURCES[id] ?? '';
                $('#previewContent').html(renderContent(source));
                previewModal.show();
            });

            let currentEditMessageId = null;
            const editModal = new bootstrap.Modal(document.getElementById('editModal'));
            let quillEditEditor = null;

            function initQuillEditEditor() {
                if (quillEditEditor) return quillEditEditor;
                quillEditEditor = new Quill('#quillEditEditor', {
                    theme: 'snow',
                    modules: {
                        toolbar: [
                            [{ 'header': [1, 2, 3, 4, false] }],
                            ['bold', 'italic', 'underline', 'strike'],
                            [{ 'color': [] }, { 'background': [] }],
                            [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                            [{ 'align': [] }],
                            ['blockquote', 'code-block'],
                            ['link'],
                            ['clean'],
                        ]
                    }
                });
                quillEditEditor.on('text-change', function () {
                    $('#editCharCount').text(quillEditEditor.getText().length + ' caracteres');
                    $('#htmlEditSource').val(quillEditEditor.root.innerHTML);
                });
                return quillEditEditor;
            }

            $(document).on('click', '.btn-edit', function () {
                const $bubble = $(this).closest('.chat-bubble');
                currentEditMessageId = $bubble.data('message-id');
                const source = MESSAGE_SOURCES[currentEditMessageId] ?? '';
                const html = renderContent(source);

                initQuillEditEditor();
                quillEditEditor.clipboard.dangerouslyPasteHTML(html);
                $('#htmlEditSource').val(html);
                $('#editPreviewArea').html(html);
                $('#editCharCount').text(quillEditEditor.getText().length + ' caracteres');
                editModal.show();
            });

            // Sync edit modal tabs
            $('button[data-bs-target="#tabEditEditor"]').on('shown.bs.tab', function () {
                if (!quillEditEditor) return;
                const htmlFromSource = $('#htmlEditSource').val();
                if (htmlFromSource && htmlFromSource !== quillEditEditor.root.innerHTML) {
                    quillEditEditor.clipboard.dangerouslyPasteHTML(htmlFromSource);
                }
            });
            $('button[data-bs-target="#tabEditPreview"]').on('shown.bs.tab', function () {
                if (!quillEditEditor) return;
                $('#editPreviewArea').html(DOMPurify.sanitize(quillEditEditor.root.innerHTML, PURIFY_CFG));
            });
            $('#htmlEditSource').on('input', function () {
                $('#editPreviewArea').html(DOMPurify.sanitize($(this).val(), PURIFY_CFG));
            });

            $('#btnConfirmEdit').on('click', function () {
                if (!currentEditMessageId) return;
                const html = $('#htmlEditSource').val() || (quillEditEditor ? quillEditEditor.root.innerHTML : '');
                const sanitized = DOMPurify.sanitize(html, PURIFY_CFG);

                // Update local source and re-render bubble
                MESSAGE_SOURCES[currentEditMessageId] = sanitized;
                const $bubble = $('.chat-bubble[data-message-id="' + currentEditMessageId + '"]');
                const view = $bubble.attr('data-view') || 'render';
                if (view === 'source') {
                    $bubble.find('.md').html(renderAsSource(sanitized));
                } else {
                    $bubble.find('.md').html(renderContent(sanitized));
                }

                editModal.hide();
                toastr.info('Cambios aplicados. Usa "Guardar" para persistir como contenido.');
            });

            $(document).on('click', '.btn-regenerate', regenerateLastMessage);

            // Prevent global select2 from hijacking Quill toolbar selects inside modals
            function killSelect2InQuillToolbars($modal) {
                $modal.find('.ql-toolbar select').each(function () {
                    $(this).attr('data-no-select2', '');
                    if ($(this).hasClass('select2-hidden-accessible')) {
                        try { $(this).select2('destroy'); } catch (e) {}
                    }
                    $(this).next('.select2-container').remove();
                });
            }
            $('#saveModal, #editModal').on('shown.bs.modal', function () {
                killSelect2InQuillToolbars($(this));
            });

            function initQuillEditor() {
                if (quillEditor) return quillEditor;
                quillEditor = new Quill('#quillSaveEditor', {
                    theme: 'snow',
                    modules: {
                        toolbar: [
                            [{ 'header': [1, 2, 3, 4, false] }],
                            ['bold', 'italic', 'underline', 'strike'],
                            [{ 'color': [] }, { 'background': [] }],
                            [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                            [{ 'align': [] }],
                            ['blockquote', 'code-block'],
                            ['link'],
                            ['clean'],
                        ]
                    }
                });
                quillEditor.on('text-change', function () {
                    $('#saveCharCount').text(quillEditor.getText().length + ' caracteres');
                    $('#htmlSaveSource').val(quillEditor.root.innerHTML);
                });
                return quillEditor;
            }

            let $currentSaveBtn = null;

            $(document).on('click', '.btn-save', function () {
                const $bubble = $(this).closest('.chat-bubble');
                const id = $bubble.data('message-id');
                const source = MESSAGE_SOURCES[id] ?? '';
                const html = renderContent(source);
                _pendingSaveSources = MESSAGE_SOURCES_URLS[id] || [];

                // Auto-extract product name from first heading in generated content
                const $tempDiv = $('<div>').html(html);
                const headingText = $tempDiv.find('h1, h2, h3').first().text().trim();
                if (headingText && headingText.length > 1 && headingText.length < 250) {
                    $('#saveProductName').val(headingText);
                }

                $currentSaveBtn = $(this);
                initQuillEditor();
                quillEditor.clipboard.dangerouslyPasteHTML(html);
                $('#htmlSaveSource').val(html);
                $('#shopPreviewArea').html(html);
                $('#saveChatRef').text(chatUid?.substring(0, 12) + '...');
                $('#saveCharCount').text(quillEditor.getText().length + ' caracteres');
                saveModal.show();
            });

            // Sync between modal tabs
            $('button[data-bs-target="#tabEditor"]').on('shown.bs.tab', function () {
                if (!quillEditor) return;
                const htmlFromSource = $('#htmlSaveSource').val();
                if (htmlFromSource && htmlFromSource !== quillEditor.root.innerHTML) {
                    quillEditor.clipboard.dangerouslyPasteHTML(htmlFromSource);
                }
            });
            $('button[data-bs-target="#tabShopPreview"]').on('shown.bs.tab', function () {
                if (!quillEditor) return;
                $('#shopPreviewArea').html(DOMPurify.sanitize(quillEditor.root.innerHTML, PURIFY_CFG));
            });
            $('#htmlSaveSource').on('input', function () {
                $('#shopPreviewArea').html(DOMPurify.sanitize($(this).val(), PURIFY_CFG));
            });

            $('#btnConfirmSave').on('click', function () {
                if (!chatUid) return;
                // Use htmlSaveSource as source of truth: it's updated by Quill text-change
                // AND by direct HTML tab edits — so it's always current regardless of active tab.
                const html = $('#htmlSaveSource').val() || (quillEditor ? quillEditor.root.innerHTML : '');
                const sanitized = DOMPurify.sanitize(html, PURIFY_CFG);
                if (!sanitized.trim() || sanitized === '<p><br></p>') {
                    toastr.warning('El contenido está vacío');
                    return;
                }

                const $btn = $(this).prop('disabled', true);
                $.ajax({
                    url: urls.save,
                    method: 'POST',
                    data: {
                        _token:   csrf,
                        chat_uid: chatUid,
                        content:  sanitized,
                        name:     $('#saveProductName').val().trim(),
                        brand:    $('#saveProductBrand').val().trim(),
                        sources:  JSON.stringify(_pendingSaveSources || []),
                    },
                    success: function (res) {
                        if (res.success) {
                            saveModal.hide();
                            if ($currentSaveBtn) {
                                $currentSaveBtn.addClass('saved').html('<i class="fas fa-check"></i>').attr('title', 'Ya guardado');
                            }
                            if (res.redirect_url) {
                                $('#btnGoToContent').attr('href', res.redirect_url);
                            }
                            saveSuccessModal.show();
                        } else {
                            toastr.error(res.message);
                        }
                    },
                    error: function () { toastr.error('Error al guardar'); },
                    complete: function () { $btn.prop('disabled', false); }
                });
            });

            // Resetear nombre al valor original cuando se cierra el saveModal (sin guardar)
            document.getElementById('saveModal').addEventListener('hidden.bs.modal', function () {
                $('#saveProductName').val(@json($product->name));
                $('#btnConfirmSave').prop('disabled', false);
            });

            // Char counter in composer
            $('#messageInput').on('input', function () {
                const len = this.value.length;
                $('#btnSend').prop('disabled', len === 0);
            });

            // Estado inicial del botón enviar
            $('#btnSend').prop('disabled', $('#messageInput').val().length === 0);

            // Textarea auto-resize
            function autoResize(el) {
                el.style.height = 'auto';
                if (el.scrollHeight > 400) {
                    el.style.height = '400px';
                    el.style.overflowY = 'auto';
                } else {
                    el.style.height = el.scrollHeight + 'px';
                    el.style.overflowY = 'hidden';
                }
            }
            document.getElementById('messageInput').addEventListener('input', function () { autoResize(this); });

            // Scroll to bottom on load if there are messages
            if ($('#messagesArea .chat-bubble').length) scrollToBottom();

            // History search — client-side filter (no page reload)
            function filterHistory(q) {
                q = (q || '').toLowerCase().trim();
                let visible = 0;
                $('.chat-history-item').each(function () {
                    const match = !q || $(this).find('.title').text().toLowerCase().includes(q);
                    $(this).toggle(match);
                    if (match) visible++;
                });
                let $empty = $('#historyNoResults');
                if (!visible && q) {
                    if (!$empty.length) {
                        $('.chat-history-item').first().closest('.card-body')
                            .append('<p id="historyNoResults" class="text-muted small text-center py-2 mb-0">Sin resultados</p>');
                    }
                } else {
                    $empty.remove();
                }
            }

            $('#historySearch').on('input', function () { filterHistory($(this).val()); });

            // Aplicar filtro si la URL tenía ?history_search= (ahora client-side)
            const initialSearch = $('#historySearch').val();
            if (initialSearch) filterHistory(initialSearch);

            // Shortcuts modal
            const shortcutsModal = new bootstrap.Modal(document.getElementById('shortcutsModal'));
            $('#btnShortcuts').on('click', () => shortcutsModal.show());

            // Keyboard shortcuts
            $(document).on('keydown', function (e) {
                const mod = e.metaKey || e.ctrlKey;
                const target = e.target.tagName;
                const isEditing = ['TEXTAREA', 'INPUT'].includes(target) && e.target.id !== 'messageInput';

                if (mod && e.key === 'n' && !isEditing) {
                    e.preventDefault();
                    window.location = urls.show;
                } else if (mod && e.key === 'k' && !isEditing) {
                    e.preventDefault();
                    $('#historySearch').focus();
                } else if (mod && e.key === '/' && !isEditing) {
                    e.preventDefault();
                    shortcutsModal.show();
                } else if (mod && e.key === 'Enter' && !isWaiting) {
                    const selectedPrompt = $('#promptSelector').val();
                    if (selectedPrompt && (target === 'BODY' || target === 'TEXTAREA')) {
                        e.preventDefault();
                        $('#btnSendPrompt').trigger('click');
                    }
                }
            });
        });
    </script>
@endpush
