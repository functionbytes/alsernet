@extends('layouts.theme')
@section('title', 'Heatmap horas pico · Helpdesk')
@section('content')
    <h1 class="h3 mb-4"><i class="fas fa-fire text-danger me-2"></i>Heatmap de horas pico</h1>
    <p class="text-muted">Mensajes recibidos por día de la semana y hora (últimos 30 días).</p>
    @php
        $days = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
        $matrix = $matrix ?? [];
        $max = collect($matrix)->flatten()->max() ?: 1;
    @endphp
    <div class="card border-0 shadow-sm">
        <div class="card-body p-3 overflow-auto">
            <table class="table table-borderless mb-0" style="font-size:11px">
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
                                    $color = sprintf('rgba(177, 1, 0, %.2f)', $intensity * 0.85);
                                @endphp
                                <td class="text-center" style="background: {{ $color }}; color: {{ $intensity > 0.5 ? '#fff' : '#222' }}; min-width:32px; height:36px;" title="{{ $days[$d-1] }} {{ $h }}h: {{ $val }} mensajes">
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
