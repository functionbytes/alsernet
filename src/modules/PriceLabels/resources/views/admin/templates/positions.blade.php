@extends('layouts.theme')

@section('title', $pageTitle)

@push('styles')
<link rel="stylesheet" href="{{ asset('modules/pricelabels/css/editor.css') }}">
@endpush

@section('content')

    @php
        $availableOrientations = [];
        if (in_array($template->orientation, ['vertical', 'both'], true)) {
            $availableOrientations[] = 'vertical';
        }
        if (in_array($template->orientation, ['horizontal', 'both'], true)) {
            $availableOrientations[] = 'horizontal';
        }

        $activeOrientation = request('orientation');
        if (! in_array($activeOrientation, $availableOrientations, true)) {
            $activeOrientation = $availableOrientations[0] ?? 'vertical';
        }

        $orientationLabels = ['vertical' => 'Vertical', 'horizontal' => 'Horizontal'];

        $showVertical = $activeOrientation === 'vertical';
        $showHorizontal = $activeOrientation === 'horizontal';
    @endphp

    @include('core::components.card', ['title' => $pageTitle.' ('.$orientationLabels[$activeOrientation].'): '.$template->name])

    <div class="mb-3 d-flex flex-wrap gap-2 align-items-center">
        <a href="{{ route('pricelabels.edit', $template) }}" class="btn btn-sm btn-light">
            Volver a la edicion completa
        </a>

        @if(count($availableOrientations) > 1)
            <ul class="nav nav-pills ms-auto">
                @foreach($availableOrientations as $orientation)
                    <li class="nav-item">
                        <a class="nav-link {{ $activeOrientation === $orientation ? 'active' : '' }}"
                           href="{{ route('pricelabels.positions.edit', $template) }}?orientation={{ $orientation }}">
                            {{ $orientationLabels[$orientation] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <div class="row g-3">
        @include('pricelabels::admin.templates.partials.positions-canvas')
    </div>

    <script>
        window.PRICE_LABELS_EDITOR = {
            urls: {
                savePositions: "{{ route('pricelabels.positions.save', $template) }}",
            },
            positions: {
                vertical: @json($positionsVertical),
                horizontal: @json($positionsHorizontal),
            },
            fields: @json($fields),
            fieldKeys: @json($fieldKeys),
            fieldLabels: @json($fieldLabels),
            labelText: @json($template->label_text),
            grid: {
                vertical: { rows: {{ $template->vertical_rows }}, columns: {{ $template->vertical_columns }} },
                horizontal: { rows: {{ $template->horizontal_rows }}, columns: {{ $template->horizontal_columns }} },
            },
            sampleRow: @json($sampleRow ?? null),
        };
    </script>

@endsection

@push('scripts')
<script src="{{ asset('modules/pricelabels/js/vendor/interact.min.js') }}"></script>
<script src="{{ asset('modules/pricelabels/js/generate-shared.js') }}"></script>
<script src="{{ asset('modules/pricelabels/js/editor.js') }}"></script>
<script>
$(document).ready(function () {
    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Exito');
    @endif
});
</script>
@endpush
