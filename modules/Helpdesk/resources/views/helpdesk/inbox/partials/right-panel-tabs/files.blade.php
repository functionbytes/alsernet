{{-- Contenido de la pestaña "Archivos" del panel derecho — cargado bajo
     demanda por RightPanelTabController@files. Recibe $rpFiles, $rpFileCounts,
     $rpFileSizes; el resto (mapas de iconos/colores, formateador de tamaño)
     es estático y se define aquí mismo. --}}
@php
    $rpFormatSize = function ($bytes) {
        if (! $bytes) { return '0 B'; }
        if ($bytes < 1024) { return $bytes.' B'; }
        if ($bytes < 1048576) { return round($bytes / 1024, 1).' KB'; }
        if ($bytes < 1073741824) { return round($bytes / 1048576, 1).' MB'; }
        return round($bytes / 1073741824, 1).' GB';
    };
    $rpDocIcons = [
        'pdf'  => ['icon' => 'fa-file-pdf',        'color' => '#dc2626'],
        'doc'  => ['icon' => 'fa-file-word',       'color' => '#475569'],
        'docx' => ['icon' => 'fa-file-word',       'color' => '#475569'],
        'rtf'  => ['icon' => 'fa-file-word',       'color' => '#64748b'],
        'odt'  => ['icon' => 'fa-file-word',       'color' => '#64748b'],
        'xls'  => ['icon' => 'fa-file-excel',      'color' => '#059669'],
        'xlsx' => ['icon' => 'fa-file-excel',      'color' => '#059669'],
        'ods'  => ['icon' => 'fa-file-excel',      'color' => '#059669'],
        'ppt'  => ['icon' => 'fa-file-powerpoint', 'color' => '#e67000'],
        'pptx' => ['icon' => 'fa-file-powerpoint', 'color' => '#e67000'],
        'odp'  => ['icon' => 'fa-file-powerpoint', 'color' => '#e67000'],
        'zip'  => ['icon' => 'fa-file-zipper',     'color' => '#71717a'],
        'rar'  => ['icon' => 'fa-file-zipper',     'color' => '#71717a'],
        '7z'   => ['icon' => 'fa-file-zipper',     'color' => '#71717a'],
        'gz'   => ['icon' => 'fa-file-zipper',     'color' => '#71717a'],
        'tar'  => ['icon' => 'fa-file-zipper',     'color' => '#71717a'],
        'bz2'  => ['icon' => 'fa-file-zipper',     'color' => '#71717a'],
        'csv'  => ['icon' => 'fa-file-csv',        'color' => '#059669'],
        'txt'  => ['icon' => 'fa-file-lines',      'color' => '#71717a'],
        'md'   => ['icon' => 'fa-file-lines',      'color' => '#71717a'],
        'json' => ['icon' => 'fa-file-code',       'color' => '#f59e0b'],
        'xml'  => ['icon' => 'fa-file-code',       'color' => '#f59e0b'],
        'html' => ['icon' => 'fa-file-code',       'color' => '#f97316'],
        'htm'  => ['icon' => 'fa-file-code',       'color' => '#f97316'],
        'css'  => ['icon' => 'fa-file-code',       'color' => '#06b6d4'],
        'js'   => ['icon' => 'fa-file-code',       'color' => '#eab308'],
        'php'  => ['icon' => 'fa-file-code',       'color' => '#8b5cf6'],
        'svg'  => ['icon' => 'fa-file-image',      'color' => '#10b981'],
        'bmp'  => ['icon' => 'fa-file-image',      'color' => '#71717a'],
        'tiff' => ['icon' => 'fa-file-image',      'color' => '#71717a'],
        'tif'  => ['icon' => 'fa-file-image',      'color' => '#71717a'],
        'mp3'  => ['icon' => 'fa-file-audio',      'color' => '#7c3aed'],
        'wav'  => ['icon' => 'fa-file-audio',      'color' => '#7c3aed'],
        'ogg'  => ['icon' => 'fa-file-audio',      'color' => '#7c3aed'],
        'aac'  => ['icon' => 'fa-file-audio',      'color' => '#7c3aed'],
        'flac' => ['icon' => 'fa-file-audio',      'color' => '#7c3aed'],
        'm4a'  => ['icon' => 'fa-file-audio',      'color' => '#7c3aed'],
        'opus' => ['icon' => 'fa-file-audio',      'color' => '#7c3aed'],
        'avi'  => ['icon' => 'fa-file-video',      'color' => '#ef4444'],
        'mkv'  => ['icon' => 'fa-file-video',      'color' => '#ef4444'],
        'flv'  => ['icon' => 'fa-file-video',      'color' => '#ef4444'],
        'wmv'  => ['icon' => 'fa-file-video',      'color' => '#ef4444'],
    ];
    $rpTypeColors = [
        'image'    => '#b10100',
        'video'    => '#dc2626',
        'audio'    => '#f87171',
        'document' => '#7b0000',
    ];
    $rpTypeLabels = [
        'image'    => 'Imágenes',
        'video'    => 'Vídeo',
        'audio'    => 'Audio',
        'document' => 'Docs',
    ];
@endphp
@if($rpFiles->isEmpty())
    <div class="bv-tab-empty">
        <i class="far fa-folder-open"></i>
        <div class="bv-tab-empty-title">{{ __('helpdesk::helpdesk.inbox.right.no_files_title') }}</div>
        <div class="bv-tab-empty-sub">{{ __('helpdesk::helpdesk.inbox.right.no_files_sub') }}</div>
    </div>
@else

    {{-- Section header --}}
    <div class="media-sec-head">
        @if($rpFileSizes['all'])
            <div class="media-size">{{ $rpFormatSize($rpFileSizes['all']) }}</div>
        @endif
    </div>

    {{-- Progress bar --}}
    @if($rpFileSizes['all'] > 0)
        <div class="media-progress">
            @foreach($rpTypeColors as $t => $color)
                @if($rpFileSizes[$t] > 0)
                    @php $pct = ($rpFileSizes[$t] / $rpFileSizes['all']) * 100; @endphp
                    <div class="seg {{ $t }}"
                         style="width:{{ round($pct, 2) }}%;background:{{ $color }};"
                         data-tooltip="{{ $rpTypeLabels[$t] }}: {{ $rpFileCounts[$t] }} · {{ $rpFormatSize($rpFileSizes[$t]) }}"
                         aria-label="{{ $rpTypeLabels[$t] }}: {{ $rpFileCounts[$t] }} · {{ $rpFormatSize($rpFileSizes[$t]) }}"></div>
                @endif
            @endforeach
        </div>
        <div class="media-legend">
            @foreach($rpTypeColors as $t => $color)
                @if($rpFileCounts[$t] > 0)
                    <span class="item">
                        <span class="d {{ $t }}" style="background:{{ $color }};"></span>
                        {{ $rpTypeLabels[$t] }}
                        <strong>{{ $rpFormatSize($rpFileSizes[$t]) }}</strong>
                    </span>
                @endif
            @endforeach
        </div>
    @endif

    <div class="media-divider"></div>

    {{-- Filter + sort + view toolbar --}}
    <div class="media-filter-row">
        <span class="media-pill bv-files-filter on" data-bv-files-filter="all">
            {{ __('helpdesk::helpdesk.inbox.right.all_label') }} <span class="c">{{ $rpFileCounts['all'] }}</span>
        </span>
        @if($rpFileCounts['image'] > 0)
            <span class="media-pill bv-files-filter" data-bv-files-filter="image">
                <i class="far fa-image"></i> <span class="c">{{ $rpFileCounts['image'] }}</span>
            </span>
        @endif
        @if($rpFileCounts['audio'] > 0)
            <span class="media-pill bv-files-filter" data-bv-files-filter="audio">
                <i class="fas fa-volume-high"></i> <span class="c">{{ $rpFileCounts['audio'] }}</span>
            </span>
        @endif
        @if($rpFileCounts['video'] > 0)
            <span class="media-pill bv-files-filter" data-bv-files-filter="video">
                <i class="fas fa-video"></i> <span class="c">{{ $rpFileCounts['video'] }}</span>
            </span>
        @endif
        @if($rpFileCounts['document'] > 0)
            <span class="media-pill bv-files-filter" data-bv-files-filter="document">
                <i class="far fa-file-lines"></i> <span class="c">{{ $rpFileCounts['document'] }}</span>
            </span>
        @endif

        <span class="spacer"></span>

        <select class="fselect bv-files-sort" id="bv-files-sort" aria-label="{{ __('helpdesk::helpdesk.inbox.right.sort_aria_label') }}">
            <option value="recent">{{ __('helpdesk::helpdesk.inbox.right.sort_recent') }}</option>
            <option value="oldest">{{ __('helpdesk::helpdesk.inbox.right.sort_oldest') }}</option>
            <option value="size-desc">{{ __('helpdesk::helpdesk.inbox.right.sort_size_desc') }}</option>
            <option value="size-asc">{{ __('helpdesk::helpdesk.inbox.right.sort_size_asc') }}</option>
            <option value="name">{{ __('helpdesk::helpdesk.inbox.right.sort_name') }}</option>
        </select>

        <div class="media-view-toggle">
            <button class="bv-files-vt on" data-bv-view="grid" title="{{ __('helpdesk::helpdesk.inbox.right.view_grid_title') }}">
                <i class="fas fa-grip"></i>
            </button>
            <button class="bv-files-vt" data-bv-view="list" title="{{ __('helpdesk::helpdesk.inbox.right.view_list_title') }}">
                <i class="fas fa-list"></i>
            </button>
        </div>
    </div>

    <hr class="bv-files-divider">

    {{-- File grid --}}
    <div class="media-grid bv-files-grid" id="bv-files-grid" data-view="grid">
        @foreach($rpFiles as $f)
            @php
                $fileMeta    = $rpDocIcons[$f->ext] ?? ['icon' => 'fa-file', 'color' => '#71717a'];
                $fileTooltip = $f->name.($f->size ? ' · '.$rpFormatSize($f->size) : '');
            @endphp
            <a href="{{ $f->url }}"
               target="_blank" rel="noopener"
               class="media-card bv-file-card"
               data-bv-file-type="{{ $f->type }}"
               data-bv-file-size="{{ $f->size ?: 0 }}"
               data-bv-file-name="{{ strtolower($f->name) }}"
               data-bv-file-ts="{{ $f->created_at?->timestamp ?? 0 }}"
               data-bv-file-url="{{ $f->url }}"
               data-tooltip="{{ $fileTooltip }}"
               aria-label="{{ $fileTooltip }}">
                <label class="bv-file-select" onclick="event.stopPropagation();">
                    <input type="checkbox" class="bv-file-cb" onclick="event.stopPropagation();">
                    <span class="bv-file-check"></span>
                </label>
                <div class="media-thumb{{ $f->type === 'video' ? ' video' : '' }}">
                    @if($f->type === 'image')
                        <img src="{{ $f->url }}" alt="{{ $f->name }}" loading="lazy"
                             onerror="this.parentElement.classList.add('placeholder'); this.style.display='none';">
                        <i class="fa-regular fa-image bv-img-placeholder"></i>
                        <span class="bv-file-overlay"><i class="fas fa-magnifying-glass-plus"></i></span>
                    @elseif($f->type === 'video')
                        <div class="play"><i class="fas fa-play"></i></div>
                    @elseif($f->type === 'audio')
                        <div class="bv-file-icon-wrap bv-x25">
                            <i class="fas fa-volume-high"></i>
                        </div>
                        <span class="bv-file-overlay"><i class="fas fa-play"></i></span>
                    @else
                        <div class="bv-file-icon-wrap" style="color:{{ $fileMeta['color'] }};">
                            <i class="fas {{ $fileMeta['icon'] }}"></i>
                        </div>
                        <span class="bv-file-overlay"><i class="fas fa-download"></i></span>
                    @endif
                    @if($f->ext)
                        <span class="type-badge">{{ strtoupper($f->ext) }}</span>
                    @endif
                </div>
                <div class="media-info">
                    <span class="name">{{ \Illuminate\Support\Str::limit($f->name, 24) }}</span>
                    <span class="author" title="{{ $f->author_name }}">
                        {{ \Illuminate\Support\Str::limit($f->author_name, 16) }}
                    </span>
                    <div class="bv-file-row">
                        @if($f->size)<span class="size">{{ $rpFormatSize($f->size) }}</span>@endif
                        @if($f->created_at)<span class="date">{{ $f->created_at->diffForHumans(['short' => true]) }}</span>@endif
                    </div>
                </div>
            </a>
        @endforeach
    </div>

    {{-- Footer: descarga y cierre (solo visible con selección activa) --}}
    <div class="bv-files-footer" id="bv-files-footer" style="display:none;">
        <button type="button" class="bv-files-dl-btn" id="bv-files-dl-btn">
            {{ __('helpdesk::helpdesk.inbox.right.download_selection') }}
        </button>
        <button type="button" class="bv-files-close-btn" id="bv-files-close-btn">
            {{ __('helpdesk::helpdesk.inbox.right.cancel_button') }}
        </button>
    </div>

@endif
