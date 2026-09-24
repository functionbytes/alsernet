{{-- Fila del repetidor de motivos de vale. $i = índice (o __i__ en la plantilla). --}}
<div class="psc-settings-row" data-settings-row>
    <label class="psc-field psc-settings-key">
        <span class="lbl">Clave</span>
        <input type="text" class="mono" name="vouchers[reasons][{{ $i }}][key]" value="{{ $row['key'] ?? '' }}"
               maxlength="40" pattern="[a-z][a-z0-9_\-]{1,39}" autocomplete="off" @disabled($disabled ?? false)>
    </label>
    <label class="psc-field psc-field--grow">
        <span class="lbl">Texto que ve el agente</span>
        <input type="text" name="vouchers[reasons][{{ $i }}][label]" value="{{ $row['label'] ?? '' }}" maxlength="120" @disabled($disabled ?? false)>
    </label>
    <button type="button" class="psc-btn psc-btn--outline psc-settings-remove" data-settings-remove @disabled($disabled ?? false)>Quitar</button>
    @if (is_int($i))
        <div class="psc-settings-rowerr">
            @include('helpdeskprestashop::ext.settings._err', ['field' => 'vouchers.reasons.'.$i.'.key'])
            @include('helpdeskprestashop::ext.settings._err', ['field' => 'vouchers.reasons.'.$i.'.label'])
        </div>
    @endif
</div>
