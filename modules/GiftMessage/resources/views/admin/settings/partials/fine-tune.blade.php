{{--
    Ajuste fino por numeros (%) de la posicion/tamano de T1 y T2.
    Alternativa a arrastrar con el raton, imprescindible cuando la caja queda
    demasiado pequena y los bordes de redimensionar ocupan todo el centro,
    sin dejar espacio para agarrarla y moverla.

    Variables esperadas: $canvas ('envelope'|'card'), $prefix ('env'|'card'), $config
--}}
<div class="giftmessage-fine-tune mt-3">
    <p class="small text-muted mb-2">
        Ajuste fino (%): escribe los numeros si la caja queda demasiado pequena para arrastrarla con el raton.
    </p>
    <div class="table-responsive">
        <table class="table table-sm giftmessage-fine-tune-table mb-0">
            <thead>
                <tr>
                    <th></th>
                    <th>X</th>
                    <th>Y</th>
                    <th>Ancho</th>
                    <th>Alto</th>
                </tr>
            </thead>
            <tbody>
                @foreach(['t1' => 'T1', 't2' => 'T2'] as $slot => $label)
                    <tr>
                        <td class="fw-semibold">{{ $label }}</td>
                        @foreach(['x' => ['min' => 0, 'max' => 100], 'y' => ['min' => 0, 'max' => 100], 'w' => ['min' => 1, 'max' => 100], 'h' => ['min' => 1, 'max' => 100]] as $axis => $bounds)
                            <td>
                                <input type="number" class="form-control form-control-sm giftmessage-pos-input"
                                       data-canvas="{{ $canvas }}" data-slot="{{ $slot }}" data-axis="{{ $axis }}"
                                       step="0.1" min="{{ $bounds['min'] }}" max="{{ $bounds['max'] }}"
                                       value="{{ $config->{$prefix.'_'.$slot.'_'.$axis} }}">
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
