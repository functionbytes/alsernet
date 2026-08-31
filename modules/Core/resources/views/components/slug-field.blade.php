{{--
    Campo slug con generacion desde otro campo del formulario.

    Un slug ya escrito no se pisa solo: mientras este vacio sigue al campo de
    origen, y en cuanto tiene valor solo lo regenera el boton de la varita. Asi
    editar el nombre de un registro publicado no le cambia el identificador sin
    querer.

        @include('core::components.slug-field', [
            'value' => old('slug', $category->slug),
            'from'  => 'input[name=name]',
        ])

    @param string      $value       Slug actual
    @param string|null $from        Selector del campo de origen (por defecto input[name=name])
    @param string      $name        Name del input (por defecto slug)
    @param string|null $id          Id del input (por defecto el propio name)
    @param string|null $url         Endpoint que devuelve {"slug": "..."} ya libre de
                                    colisiones; sin el, el slug se calcula en el navegador
    @param int|null    $ignoreId    Id a excluir al comprobar unicidad (solo con url)
    @param string|null $placeholder Texto de ayuda dentro del campo
    @param string|null $pattern     Patron HTML de validacion
    @param bool        $required    Marca el campo como obligatorio
    @param string|null $mirror      Selector de un elemento cuyo texto refleja el slug
--}}
@php
    $sfName = $name ?? 'slug';
    $sfId = $id ?? $sfName;
    $sfValue = (string) ($value ?? '');
    $sfFrom = $from ?? 'input[name=name]';
    // $errors solo existe tras ShareErrorsFromSession: el componente tiene que
    // poder renderizarse tambien fuera de una request web.
    $sfInvalid = isset($errors) && $errors->has(str_replace(['[', ']'], ['.', ''], $sfName));
@endphp

<div class="ts-slug"
     data-from="{{ $sfFrom }}"
     @isset($url) data-url="{{ $url }}" @endisset
     @isset($ignoreId) data-ignore-id="{{ $ignoreId }}" @endisset
     @isset($mirror) data-mirror="{{ $mirror }}" @endisset>
    <div class="input-group">
        <input type="text" name="{{ $sfName }}" id="{{ $sfId }}"
               class="form-control ts-slug__input {{ $sfInvalid ? 'is-invalid' : '' }}"
               value="{{ $sfValue }}" spellcheck="false" autocomplete="off"
               @isset($placeholder) placeholder="{{ $placeholder }}" @endisset
               @isset($pattern) pattern="{{ $pattern }}" @endisset
               @if($required ?? false) required @endif>
        <button type="button" class="btn btn-primary ts-slug__generate"
                title="Generar el slug desde el nombre">
            <i class="fas fa-wand-magic-sparkles"></i>
        </button>
    </div>
</div>

@once
    @push('scripts')
        <script>
            window.initSlugFields = (function () {
                // Misma normalizacion que Str::slug() en el servidor: sin
                // acentos, sin simbolos y sin guiones repetidos.
                function toSlug(text) {
                    return String(text || '').toLowerCase()
                        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                        .replace(/[^a-z0-9\s_-]/g, '')
                        .trim()
                        .replace(/\s+/g, '-')
                        .replace(/-+/g, '-')
                        .replace(/^-|-$/g, '');
                }

                function init(root) {
                    if (root.dataset.slugFieldReady) return;
                    root.dataset.slugFieldReady = '1';

                    var input = root.querySelector('.ts-slug__input');
                    var button = root.querySelector('.ts-slug__generate');
                    var source = document.querySelector(root.dataset.from);
                    var mirror = root.dataset.mirror ? document.querySelector(root.dataset.mirror) : null;

                    if (!input) return;

                    // Con valor de partida el slug esta en uso: solo el boton lo
                    // regenera. Vacio, sigue al campo de origen.
                    var locked = input.value.trim() !== '';
                    var timer;

                    function reflect() {
                        if (mirror) mirror.textContent = input.value || 'slug';
                    }

                    function propose(text, done) {
                        var url = root.dataset.url;

                        if (!url) {
                            done(toSlug(text));
                            return;
                        }

                        var body = { name: text };

                        if (root.dataset.ignoreId) body.ignoreId = root.dataset.ignoreId;

                        fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') || {}).content || ''
                            },
                            body: JSON.stringify(body)
                        })
                            .then(function (r) { return r.ok ? r.json() : Promise.reject(r); })
                            .then(function (data) { done(data.slug); })
                            .catch(function () { done(toSlug(text)); }); // reserva local
                    }

                    function fill(text) {
                        propose(text, function (slug) {
                            input.value = slug;
                            reflect();
                        });
                    }

                    if (source) {
                        source.addEventListener('input', function () {
                            if (locked) return;

                            if (!source.value.trim()) {
                                input.value = '';
                                reflect();
                                return;
                            }

                            clearTimeout(timer);
                            timer = setTimeout(function () { fill(source.value); }, 400);
                        });
                    }

                    input.addEventListener('input', function () {
                        locked = true;
                        reflect();
                    });

                    // Los modales que sirven para crear y para editar llaman a
                    // form.reset(): sin esto el campo se vaciaria pero seguiria
                    // considerandose escrito a mano.
                    var form = input.form;

                    if (form) {
                        form.addEventListener('reset', function () {
                            setTimeout(function () {
                                locked = input.value.trim() !== '';
                                reflect();
                            }, 0);
                        });
                    }

                    button.addEventListener('click', function () {
                        if (!source) return;

                        if (!source.value.trim()) {
                            source.focus();
                            return;
                        }

                        fill(source.value);
                        locked = true;
                    });

                    reflect();
                }

                function initAll(scope) {
                    (scope || document).querySelectorAll('.ts-slug').forEach(init);
                }

                document.addEventListener('DOMContentLoaded', function () { initAll(); });
                initAll();

                return initAll;
            })();
        </script>
    @endpush
@endonce
