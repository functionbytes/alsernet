{{-- Una plantilla de seguimiento. $i = índice (o "__i__" en la plantilla del repetidor). --}}
<div class="era-row" data-era-row>
    <label class="era-field era-field--carrier">
        <span class="era-lbl">Clave del transportista</span>
        <input type="text" name="tracking[{{ $i }}][carrier]" maxlength="60" autocomplete="off"
               value="{{ $row['carrier'] ?? '' }}" placeholder="p. ej. seur" @disabled($disabled ?? false)>
        @if (is_int($i))
            @include('helpdeskerp::admin._err', ['field' => 'tracking.'.$i.'.carrier'])
        @endif
    </label>
    <label class="era-field era-field--grow">
        <span class="era-lbl">Plantilla de URL</span>
        <input type="text" name="tracking[{{ $i }}][url]" maxlength="500" autocomplete="off" class="era-mono"
               value="{{ $row['url'] ?? '' }}" placeholder="https://…?numero={tracking}" @disabled($disabled ?? false)>
        @if (is_int($i))
            @include('helpdeskerp::admin._err', ['field' => 'tracking.'.$i.'.url'])
        @endif
    </label>
    <button type="button" class="era-btn era-btn--ghost era-row-del" data-era-remove @disabled($disabled ?? false)>Quitar</button>
</div>
