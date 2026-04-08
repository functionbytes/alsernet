@props([
    'title',
    'value',
    'icon'     => '',
    'subtitle' => '',
    'id'       => '',
    'kpi'      => '',
    'sparkId'  => '',
    'color'    => 'primary',
])

<div class="card w-100" {{ $id ? "id=$id" : '' }}>
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-8">
                <h5 class="card-title fw-semibold mb-3">{{ $title }}</h5>
                <h4 class="fw-semibold mb-2" {{ $kpi ? "data-kpi=$kpi" : '' }}>{{ $value }}</h4>
                @if ($subtitle)
                    <p class="fs-3 mb-0 text-muted">{{ $subtitle }}</p>
                @endif
                @if ($sparkId)
                    <div id="{{ $sparkId }}-cmp" class="mt-1"></div>
                @endif
            </div>
            <div class="col-4">
                <div class="d-flex justify-content-center">
                    @if ($sparkId)
                        <div id="{{ $sparkId }}"></div>
                    @elseif ($icon)
                        <span class="rounded-circle d-flex align-items-center justify-content-center"
                              style="width:44px;height:44px;background:#fce8e8;flex-shrink:0;">
                            <i class="{{ $icon }}" style="color:#b10100;"></i>
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
