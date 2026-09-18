@extends('layouts.theme')
@section('title', 'Heatmap horas pico · Helpdesk')
@section('page_header')
    @include('core::components.card', ['title' => 'Heatmap horas pico · Helpdesk'])
@endsection

@push('css')
    <link rel="stylesheet" href="{{ asset('vendor/helpdesk/conversations.css') }}?v={{ @filemtime(public_path('vendor/helpdesk/conversations.css')) }}"/>
@endpush

@section('content')
    <h1 class="h3 mb-4"><i class="fas fa-fire text-dark me-2"></i>Heatmap de horas pico</h1>
    <p class="text-muted">Mensajes recibidos por día de la semana y hora (últimos 30 días).</p>
    @php
        $days = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
        $matrix = $matrix ?? [];
        $max = collect($matrix)->flatten()->max() ?: 1;
    @endphp
    <div class="card border-0 shadow-sm">
        <div class="card-body p-3 overflow-auto">
            <table class="table table-borderless mb-0 bv-fs-11">
                <thead>
                    <tr>
                        <th scope="col"></th>
                        @for($h = 0; $h < 24; $h++)<th scope="col" class="text-center">{{ str_pad($h,2,'0',STR_PAD_LEFT) }}</th>@endfor
                    </tr>
                </thead>
                <tbody>
                    @for($d = 1; $d <= 7; $d++)
                        <tr>
                            <th scope="row" class="text-end pe-3">{{ $days[$d-1] ?? '?' }}</th>
                            @for($h = 0; $h < 24; $h++)
                                @php
                                    $val = (int) ($matrix[$d][$h] ?? 0);
                                    $intensity = $val > 0 ? min(1, $val / $max) : 0;
                                    $color = sprintf('rgba(144, 187, 19, %.2f)', $intensity * 0.85);
                                @endphp
                                <td class="text-center bv-heatmap-cell" style="--bv-cell-bg: {{ $color }}; --bv-cell-fg: {{ $intensity > 0.5 ? '#fff' : '#222' }};" title="{{ $days[$d-1] }} {{ $h }}h: {{ $val }} mensajes">
                                    {{ $val ?: '·' }}
                                </td>
                            @endfor
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>
    </div>
@endsection
