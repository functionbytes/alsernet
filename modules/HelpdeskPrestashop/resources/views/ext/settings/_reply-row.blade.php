{{-- Fila del repetidor de respuestas rápidas. $i = índice (o __i__ en la plantilla). --}}
<div class="psc-settings-reply" data-settings-row>
    <div class="psc-fieldrow">
        <label class="psc-field">
            <span class="lbl">Título</span>
            <input type="text" name="replies[{{ $i }}][t]" value="{{ $row['t'] ?? '' }}" maxlength="80" @disabled($disabled ?? false)>
        </label>
        <label class="psc-field">
            <span class="lbl">Subtítulo</span>
            <input type="text" name="replies[{{ $i }}][s]" value="{{ $row['s'] ?? '' }}" maxlength="160" @disabled($disabled ?? false)>
        </label>
    </div>
    <label class="psc-field">
        <span class="lbl">Texto que se inserta en el chat</span>
        <textarea name="replies[{{ $i }}][text]" rows="3" maxlength="2000" data-settings-reply-text @disabled($disabled ?? false)>{{ $row['text'] ?? '' }}</textarea>
    </label>
    <div class="psc-settings-reply-foot">
        <button type="button" class="psc-btn psc-btn--outline psc-settings-remove" data-settings-remove @disabled($disabled ?? false)>Quitar respuesta</button>
    </div>
    @if (is_int($i))
        <div class="psc-settings-rowerr">
            @include('helpdeskprestashop::ext.settings._err', ['field' => 'replies.'.$i.'.t'])
            @include('helpdeskprestashop::ext.settings._err', ['field' => 'replies.'.$i.'.s'])
            @include('helpdeskprestashop::ext.settings._err', ['field' => 'replies.'.$i.'.text'])
        </div>
    @endif
</div>
