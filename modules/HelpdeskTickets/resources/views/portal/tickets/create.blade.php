@extends('helpdesktickets::portal.layout')

@section('content')
    <div class="mb-3">
        <a href="{{ route('portal.tickets') }}" class="text-muted">
            <i class="fas fa-arrow-left me-1"></i>Back to my tickets
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h5 class="card-title mb-4">
                        <i class="fas fa-plus-circle me-2 text-primary"></i>New support ticket
                    </h5>

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('portal.tickets.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                id="subject"
                                name="subject"
                                class="form-control @error('subject') is-invalid @enderror"
                                value="{{ old('subject') }}"
                                maxlength="255"
                                required
                                placeholder="Brief description of your issue"
                            >
                            @error('subject')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div id="kb-suggestions" class="mt-2"></div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea
                                id="description"
                                name="description"
                                class="form-control @error('description') is-invalid @enderror"
                                rows="6"
                                maxlength="5000"
                                required
                                placeholder="Provide as much detail as possible..."
                            >{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        @if ($categories->isNotEmpty())
                            <div class="mb-3">
                                <label for="category_id" class="form-label">Category</label>
                                <select id="category_id" name="category_id" class="form-select">
                                    <option value="">— Select a category —</option>
                                    @foreach ($categories as $category)
                                        <option
                                            value="{{ $category->id }}"
                                            {{ old('category_id') == $category->id ? 'selected' : '' }}
                                        >{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="mb-3">
                            <label class="form-label">Attachments <span class="text-muted">(optional, max 5MB each)</span></label>
                            <input type="file" name="attachments[]" class="form-control @error('attachments.*') is-invalid @enderror" multiple accept="image/*,.pdf,.doc,.docx,.txt,.zip">
                            <div class="form-text">Allowed: images, PDF, Word, text, ZIP. Max 5MB per file.</div>
                            @error('attachments.*')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="priority" class="form-label">Priority</label>
                            <select id="priority" name="priority" class="form-select">
                                <option value="low" {{ old('priority') === 'low' ? 'selected' : '' }}>Low</option>
                                <option value="normal" {{ old('priority', 'normal') === 'normal' ? 'selected' : '' }}>Normal</option>
                                <option value="high" {{ old('priority') === 'high' ? 'selected' : '' }}>High</option>
                                <option value="urgent" {{ old('priority') === 'urgent' ? 'selected' : '' }}>Urgent</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-1"></i>Submit ticket
                        </button>
                        <a href="{{ route('portal.tickets') }}" class="btn btn-outline-secondary ms-2">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(function () {
    // Deflexión: artículos que podrían resolver la duda antes de crear el
    // ticket. Antes esto llamaba directamente al buscador del centro de ayuda
    // con lo tecleado en el asunto; ahora pasa por el endpoint del portal, que
    // además considera la descripción y descarta lo que no responde de verdad
    // a la consulta (ver TicketDeflectionService).
    const $subject = $('input[name="subject"]');
    const $description = $('textarea[name="description"]');
    const $container = $('#kb-suggestions');

    let kbTimer;
    let lastQuery = '';

    function suggest() {
        const subject = $subject.val().trim();
        const description = $description.val().trim();
        const signature = subject + '|' + description;

        if ((subject + ' ' + description).trim().length < 12) {
            $container.empty();
            lastQuery = '';
            return;
        }

        // Sin cambios reales desde la última consulta, no se repite: cada
        // llamada cuesta, y el cliente sigue escribiendo mucho después de
        // haber dicho ya de qué va su problema.
        if (signature === lastQuery) return;
        lastQuery = signature;

        $.ajax({
            url: '{{ route('portal.tickets.suggest-articles') }}',
            method: 'POST',
            dataType: 'json',
            data: { subject: subject, description: description },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        }).done(function (res) {
            $container.empty();

            if (!res.articles || !res.articles.length) return;

            const $list = $('<div class="list-group mt-2">');

            res.articles.forEach(function (a) {
                $list.append(
                    $('<a target="_blank" class="list-group-item list-group-item-action small py-2">')
                        .attr('href', a.url)
                        .append($('<i class="fas fa-book me-2 text-muted">'))
                        .append(document.createTextNode(a.title))
                );
            });

            $container.append(
                $('<div class="alert alert-info p-2 mb-0">').append(
                    $('<strong class="small">').append(
                        $('<i class="fas fa-lightbulb me-1">'),
                        document.createTextNode(' Could this solve it?')
                    ),
                    $list,
                    $('<div class="small text-muted mt-2">').text(
                        'If not, just carry on — your ticket will be created normally.'
                    )
                )
            );
        });
    }

    // 1200 ms, no 500: detrás hay una llamada con coste, no una búsqueda local.
    $subject.add($description).on('input', function () {
        clearTimeout(kbTimer);
        kbTimer = setTimeout(suggest, 1200);
    });
});
</script>
@endpush
