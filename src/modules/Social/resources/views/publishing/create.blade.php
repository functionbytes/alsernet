@extends('layouts.theme')

@section('title', 'Nueva Publicación')

@section('content')

    <div class="widget-content">

        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.social.publishing.index') }}">Publicaciones</a></li>
                <li class="breadcrumb-item active">Nueva Publicación</li>
            </ol>
        </nav>

        <form action="{{ route('admin.social.publishing.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="row">
                <!-- Main Content -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-edit me-2"></i>Contenido de la Publicación
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Quick Tools -->
                            <div class="mb-3 d-flex gap-2">
                                @if(isset($templates) && $templates->count() > 0)
                                    <div class="dropdown flex-fill">
                                        <button class="btn btn-light w-100 dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            <i class="fas fa-file-code me-1"></i> Usar Plantilla
                                        </button>
                                        <ul class="dropdown-menu w-100">
                                            @foreach($templates as $template)
                                                <li>
                                                    <a class="dropdown-item template-option"
                                                       href="#"
                                                       data-content="{{ $template->content }}"
                                                       data-hashtags="{{ is_array($template->hashtags) ? implode(' ', $template->hashtags) : '' }}">
                                                        {{ $template->name }}
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                @if(isset($hashtagGroups) && $hashtagGroups->count() > 0)
                                    <div class="dropdown flex-fill">
                                        <button class="btn btn-light w-100 dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            <i class="fas fa-hashtag me-1"></i> Añadir Hashtags
                                        </button>
                                        <ul class="dropdown-menu w-100">
                                            @foreach($hashtagGroups as $group)
                                                <li>
                                                    <a class="dropdown-item hashtag-option"
                                                       href="#"
                                                       data-hashtags="{{ $group->formatted_hashtags }}">
                                                        {{ $group->name }}
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>

                            <!-- AI Tools -->
                            <div class="mb-3">
                                <label class="form-label fw-semibold mb-2">
                                    <i class="fas fa-sparkles text-primary me-1"></i>Herramientas de IA
                                </label>
                                <div class="d-flex gap-2 flex-wrap">
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="aiGenerateBtn">
                                        <i class="fas fa-wand-magic-sparkles"></i> Generar Contenido
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-success" id="aiHashtagsBtn">
                                        <i class="fas fa-hashtag"></i> Sugerir Hashtags
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-info" id="aiImproveBtn">
                                        <i class="fas fa-lightbulb"></i> Mejorar Texto
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-warning" id="aiVariationsBtn">
                                        <i class="fas fa-copy"></i> Crear Variaciones
                                    </button>
                                </div>
                            </div>

                            <!-- Post Content -->
                            <div class="mb-4">
                                <label for="content" class="form-label fw-semibold">
                                    Texto de la publicación <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control @error('content') is-invalid @enderror"
                                          id="content"
                                          name="content"
                                          rows="8"
                                          placeholder="¿Qué quieres compartir con tu audiencia?"
                                          required>{{ old('content') }}</textarea>
                                @error('content')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="d-flex justify-content-between mt-1">
                                    <small class="text-muted">Soporta emojis y saltos de línea</small>
                                    <small class="text-muted">
                                        <span id="charCount">0</span> caracteres
                                    </small>
                                </div>
                            </div>

                            <!-- Post Type -->
                            <div class="mb-4">
                                <label class="form-label fw-semibold">Tipo de publicación <span class="text-danger">*</span></label>
                                <div class="row g-2">
                                    @foreach($postTypes as $type)
                                        <div class="col-md-3">
                                            <input type="radio"
                                                   class="btn-check"
                                                   name="type"
                                                   id="type_{{ $type->value }}"
                                                   value="{{ $type->value }}"
                                                   {{ old('type', 'text') === $type->value ? 'checked' : '' }}
                                                   required>
                                            <label class="btn btn-outline-primary w-100"
                                                   for="type_{{ $type->value }}">
                                                <i class="{{ $type->icon() }} d-block mb-1"></i>
                                                {{ $type->label() }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                                @error('type')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Link URL (conditional) -->
                            <div id="linkSection" style="display: none;">
                                <div class="mb-3">
                                    <label for="link_url" class="form-label fw-semibold">URL del enlace</label>
                                    <div class="input-group">
                                        <input type="url"
                                               class="form-control @error('link_url') is-invalid @enderror"
                                               id="link_url"
                                               name="link_url"
                                               value="{{ old('link_url') }}"
                                               placeholder="https://ejemplo.com">
                                        <button class="btn btn-outline-secondary" type="button" id="shortenUrlBtn">
                                            <i class="fas fa-link"></i> Acortar
                                        </button>
                                    </div>
                                    @error('link_url')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Puedes acortar la URL para mejor tracking</small>
                                </div>
                            </div>

                            <!-- Media Upload (conditional) -->
                            <div id="mediaSection" style="display: none;">
                                <div class="mb-3">
                                    <label for="media" class="form-label fw-semibold">Archivos multimedia</label>
                                    <input type="file"
                                           class="form-control @error('media') is-invalid @enderror"
                                           id="media"
                                           name="media[]"
                                           accept="image/*,video/*"
                                           multiple>
                                    @error('media')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Imágenes o videos. Máximo 10 archivos, 20MB cada uno.</small>
                                </div>
                                <div id="mediaPreview" class="row g-2 mt-2"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <!-- Publish Settings -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-cog me-2"></i>Configuración
                            </h6>
                        </div>
                        <div class="card-body">
                            <!-- Social Account -->
                            <div class="mb-3">
                                <label for="social_account_id" class="form-label fw-semibold">
                                    Cuenta Social <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('social_account_id') is-invalid @enderror"
                                        id="social_account_id"
                                        name="social_account_id"
                                        required>
                                    <option value="">Seleccionar cuenta...</option>
                                    @foreach($socialAccounts as $socialAccount)
                                        <option value="{{ $socialAccount->id }}"
                                                {{ old('social_account_id') == $socialAccount->id ? 'selected' : '' }}>
                                            {{ $socialAccount->network->label() }} - {{ $socialAccount->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('social_account_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Campaign -->
                            <div class="mb-3">
                                <label for="campaign_id" class="form-label fw-semibold">Campaña</label>
                                <select class="form-select @error('campaign_id') is-invalid @enderror"
                                        id="campaign_id"
                                        name="campaign_id">
                                    <option value="">Sin campaña</option>
                                    @foreach($campaigns as $campaign)
                                        <option value="{{ $campaign->id }}"
                                                {{ old('campaign_id') == $campaign->id ? 'selected' : '' }}>
                                            {{ $campaign->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('campaign_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Scheduled Date -->
                            <div class="mb-3">
                                <label for="scheduled_at" class="form-label fw-semibold">
                                    Programar publicación
                                </label>
                                <div class="input-group">
                                    <input type="datetime-local"
                                           class="form-control @error('scheduled_at') is-invalid @enderror"
                                           id="scheduled_at"
                                           name="scheduled_at"
                                           value="{{ old('scheduled_at') }}"
                                           min="{{ now()->format('Y-m-d\TH:i') }}">
                                    <button type="button"
                                            class="btn btn-outline-primary"
                                            id="suggestBestTimeBtn"
                                            title="Usar mejor horario basado en IA">
                                        <i class="fas fa-chart-line"></i>
                                    </button>
                                </div>
                                @error('scheduled_at')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Dejar vacío para guardar como borrador</small>
                                <div id="bestTimeInfo" class="alert alert-info mt-2" style="display: none;">
                                    <small>
                                        <i class="fas fa-sparkles"></i>
                                        <span id="bestTimeMessage"></span>
                                    </small>
                                </div>
                            </div>

                            <!-- Labels -->
                            <div class="mb-0">
                                <label class="form-label fw-semibold">Etiquetas</label>
                                @if($labels->count() > 0)
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach($labels as $label)
                                            <div class="form-check">
                                                <input class="form-check-input"
                                                       type="checkbox"
                                                       name="labels[]"
                                                       value="{{ $label->id }}"
                                                       id="label_{{ $label->id }}"
                                                       {{ in_array($label->id, old('labels', [])) ? 'checked' : '' }}>
                                                <label class="form-check-label badge"
                                                       for="label_{{ $label->id }}"
                                                       style="background-color: {{ $label->color }};">
                                                    {{ $label->name }}
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-muted small mb-0">
                                        <a href="{{ route('admin.social.labels.create') }}" target="_blank">
                                            Crear primera etiqueta
                                        </a>
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="card">
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>
                                    <span id="submitText">Guardar Borrador</span>
                                </button>
                                <a href="{{ route('admin.social.publishing.index') }}" class="btn btn-light">
                                    <i class="fas fa-times me-1"></i> Cancelar
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

@endsection

@push('scripts')
<script>
    // Character counter
    const contentArea = document.getElementById('content');
    const charCount = document.getElementById('charCount');

    contentArea.addEventListener('input', function() {
        charCount.textContent = this.value.length;
    });

    // Show/hide conditional sections based on post type
    const typeInputs = document.querySelectorAll('input[name="type"]');
    const linkSection = document.getElementById('linkSection');
    const mediaSection = document.getElementById('mediaSection');

    typeInputs.forEach(input => {
        input.addEventListener('change', function() {
            linkSection.style.display = this.value === 'link' ? 'block' : 'none';
            mediaSection.style.display = ['media', 'video'].includes(this.value) ? 'block' : 'none';
        });
    });

    // Update submit button text based on scheduled date
    const scheduledInput = document.getElementById('scheduled_at');
    const submitText = document.getElementById('submitText');

    scheduledInput.addEventListener('change', function() {
        submitText.textContent = this.value ? 'Programar Publicación' : 'Guardar Borrador';
    });

    // Media preview
    const mediaInput = document.getElementById('media');
    const mediaPreview = document.getElementById('mediaPreview');

    if (mediaInput) {
        mediaInput.addEventListener('change', function() {
            mediaPreview.innerHTML = '';
            Array.from(this.files).forEach(file => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const col = document.createElement('div');
                    col.className = 'col-3';
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'img-fluid rounded';
                    col.appendChild(img);
                    mediaPreview.appendChild(col);
                };
                reader.readAsDataURL(file);
            });
        });
    }

    // Trigger initial type check
    const checkedType = document.querySelector('input[name="type"]:checked');
    if (checkedType) {
        checkedType.dispatchEvent(new Event('change'));
    }

    // ===== NEW FEATURES =====

    // Apply template
    document.querySelectorAll('.template-option').forEach(option => {
        option.addEventListener('click', function(e) {
            e.preventDefault();
            const content = this.getAttribute('data-content');
            const hashtags = this.getAttribute('data-hashtags');

            // Set content
            contentArea.value = content;
            charCount.textContent = content.length;

            // Append hashtags if any
            if (hashtags) {
                contentArea.value += '\n\n' + hashtags;
                charCount.textContent = contentArea.value.length;
            }
        });
    });

    // Insert hashtags
    document.querySelectorAll('.hashtag-option').forEach(option => {
        option.addEventListener('click', function(e) {
            e.preventDefault();
            const hashtags = this.getAttribute('data-hashtags');

            // Insert at cursor position or append
            const cursorPos = contentArea.selectionStart;
            const textBefore = contentArea.value.substring(0, cursorPos);
            const textAfter = contentArea.value.substring(cursorPos);

            contentArea.value = textBefore + '\n\n' + hashtags + textAfter;
            charCount.textContent = contentArea.value.length;

            // Move cursor after inserted hashtags
            const newCursorPos = textBefore.length + hashtags.length + 2;
            contentArea.setSelectionRange(newCursorPos, newCursorPos);
            contentArea.focus();
        });
    });

    // Shorten URL
    const shortenUrlBtn = document.getElementById('shortenUrlBtn');
    if (shortenUrlBtn) {
        shortenUrlBtn.addEventListener('click', async function() {
            const urlInput = document.getElementById('link_url');
            const originalUrl = urlInput.value;

            if (!originalUrl) {
                alert('Ingresa una URL primero');
                return;
            }

            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Acortando...';

            try {
                const response = await fetch('{{ route('admin.social.short-urls.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ original_url: originalUrl })
                });

                const data = await response.json();

                if (data.short_url) {
                    urlInput.value = data.short_url;
                    this.innerHTML = '<i class="fas fa-check"></i> Acortada';
                    this.classList.add('btn-success');
                    this.classList.remove('btn-outline-secondary');
                } else {
                    throw new Error('Error al acortar URL');
                }
            } catch (error) {
                alert('Error al acortar la URL');
                this.innerHTML = '<i class="fas fa-link"></i> Acortar';
            } finally {
                this.disabled = false;
            }
        });
    }

    // ===== AI TOOLS =====

    // AI Generate Content
    const aiGenerateBtn = document.getElementById('aiGenerateBtn');
    if (aiGenerateBtn) {
        aiGenerateBtn.addEventListener('click', async function() {
            const topic = prompt('¿Sobre qué tema quieres generar contenido?');
            if (!topic) return;

            const tone = confirm('¿Usar tono profesional? (Cancelar para tono casual)') ? 'professional' : 'casual';

            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Generando...';

            try {
                const response = await fetch('{{ route('admin.social.ai.generate') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ topic, tone, max_length: 280 })
                });

                const data = await response.json();

                if (data.success) {
                    contentArea.value = data.content;
                    charCount.textContent = data.content.length;
                    alert('✨ Contenido generado con IA!');
                } else {
                    throw new Error(data.error || 'Error desconocido');
                }
            } catch (error) {
                alert('Error: ' + error.message);
            } finally {
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-wand-magic-sparkles"></i> Generar Contenido';
            }
        });
    }

    // AI Suggest Hashtags
    const aiHashtagsBtn = document.getElementById('aiHashtagsBtn');
    if (aiHashtagsBtn) {
        aiHashtagsBtn.addEventListener('click', async function() {
            if (!contentArea.value) {
                alert('Primero escribe algo de contenido');
                return;
            }

            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Generando...';

            try {
                const response = await fetch('{{ route('admin.social.ai.hashtags') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ content: contentArea.value, count: 8 })
                });

                const data = await response.json();

                if (data.success && data.hashtags) {
                    contentArea.value += '\n\n' + data.hashtags.join(' ');
                    charCount.textContent = contentArea.value.length;
                    alert('✨ Hashtags sugeridos agregados!');
                } else {
                    throw new Error(data.error || 'Error desconocido');
                }
            } catch (error) {
                alert('Error: ' + error.message);
            } finally {
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-hashtag"></i> Sugerir Hashtags';
            }
        });
    }

    // AI Improve Content
    const aiImproveBtn = document.getElementById('aiImproveBtn');
    if (aiImproveBtn) {
        aiImproveBtn.addEventListener('click', async function() {
            if (!contentArea.value) {
                alert('Primero escribe algo de contenido');
                return;
            }

            if (!confirm('¿Mejorar el contenido actual? Se reemplazará el texto.')) return;

            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Mejorando...';

            try {
                const response = await fetch('{{ route('admin.social.ai.improve') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ content: contentArea.value, goal: 'engagement' })
                });

                const data = await response.json();

                if (data.success) {
                    contentArea.value = data.improved_content;
                    charCount.textContent = data.improved_content.length;
                    alert('✨ Contenido mejorado con IA!');
                } else {
                    throw new Error(data.error || 'Error desconocido');
                }
            } catch (error) {
                alert('Error: ' + error.message);
            } finally {
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-lightbulb"></i> Mejorar Texto';
            }
        });
    }

    // AI Generate Variations
    const aiVariationsBtn = document.getElementById('aiVariationsBtn');
    if (aiVariationsBtn) {
        aiVariationsBtn.addEventListener('click', async function() {
            if (!contentArea.value) {
                alert('Primero escribe algo de contenido');
                return;
            }

            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Creando...';

            try {
                const response = await fetch('{{ route('admin.social.ai.variations') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ content: contentArea.value, count: 3 })
                });

                const data = await response.json();

                if (data.success && data.variations) {
                    let message = '✨ Variaciones generadas:\n\n';
                    data.variations.forEach((v, i) => {
                        message += `${i + 1}. ${v}\n\n`;
                    });
                    message += '\n¿Cuál quieres usar? (1-' + data.variations.length + ')';

                    const choice = prompt(message);
                    const index = parseInt(choice) - 1;

                    if (index >= 0 && index < data.variations.length) {
                        contentArea.value = data.variations[index];
                        charCount.textContent = data.variations[index].length;
                    }
                } else {
                    throw new Error(data.error || 'Error desconocido');
                }
            } catch (error) {
                alert('Error: ' + error.message);
            } finally {
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-copy"></i> Crear Variaciones';
            }
        });
    }

    // ===== BEST TIME TO POST AI =====

    const suggestBestTimeBtn = document.getElementById('suggestBestTimeBtn');
    const scheduledInput = document.getElementById('scheduled_at');
    const bestTimeInfo = document.getElementById('bestTimeInfo');
    const bestTimeMessage = document.getElementById('bestTimeMessage');
    const socialAccountSelect = document.getElementById('social_account_id');

    if (suggestBestTimeBtn) {
        suggestBestTimeBtn.addEventListener('click', async function() {
            const socialAccountId = socialAccountSelect.value;

            if (!socialAccountId) {
                alert('Primero selecciona una cuenta social');
                socialAccountSelect.focus();
                return;
            }

            // Disable button and show loading
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

            try {
                const response = await fetch('{{ route('admin.social.best-time.suggest', ':id') }}'.replace(':id', socialAccountId), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (data.success && data.suggested_time) {
                    // Convert ISO string to datetime-local format
                    const date = new Date(data.suggested_time);
                    const localDateTime = date.getFullYear() + '-' +
                        String(date.getMonth() + 1).padStart(2, '0') + '-' +
                        String(date.getDate()).padStart(2, '0') + 'T' +
                        String(date.getHours()).padStart(2, '0') + ':' +
                        String(date.getMinutes()).padStart(2, '0');

                    scheduledInput.value = localDateTime;
                    scheduledInput.dispatchEvent(new Event('change'));

                    // Show success message
                    bestTimeMessage.textContent = `Mejor momento: ${data.formatted_time} (${data.from_now})`;
                    bestTimeInfo.style.display = 'block';

                    // Reset button
                    this.innerHTML = '<i class="fas fa-check"></i>';
                    this.classList.remove('btn-outline-primary');
                    this.classList.add('btn-success');

                    setTimeout(() => {
                        this.innerHTML = '<i class="fas fa-chart-line"></i>';
                        this.classList.add('btn-outline-primary');
                        this.classList.remove('btn-success');
                    }, 2000);
                } else {
                    // Show error message
                    alert(data.message || 'No hay suficientes datos para hacer una recomendación. Publica al menos 10 posts para obtener recomendaciones basadas en IA.');
                    bestTimeInfo.style.display = 'none';
                }
            } catch (error) {
                alert('Error al obtener recomendación: ' + error.message);
                bestTimeInfo.style.display = 'none';
            } finally {
                this.disabled = false;
                if (this.innerHTML.includes('spinner')) {
                    this.innerHTML = '<i class="fas fa-chart-line"></i>';
                }
            }
        });
    }
</script>
@endpush
