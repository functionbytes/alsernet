@extends('layouts.theme')

@section('title', 'Grupos de Hashtags')

@section('content')

    <div class="widget-content searchable-container list">

        <!-- Alerts -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Main Card -->
        <div class="card">
            <!-- Header -->
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">
                            <i class="fas fa-hashtag me-2"></i>Grupos de Hashtags
                        </h5>
                        <p class="small mb-0 text-muted">Organiza tus hashtags en grupos reutilizables</p>
                    </div>
                    <a href="{{ route('admin.social.hashtags.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Nuevo Grupo
                    </a>
                </div>
            </div>

            <!-- Hashtag Groups Grid -->
            <div class="card-body">
                @if($hashtagGroups->count() > 0)
                    <div class="row g-3">
                        @foreach($hashtagGroups as $group)
                            <div class="col-md-6 col-lg-4">
                                <div class="card border h-100">
                                    <div class="card-body">
                                        <!-- Header -->
                                        <div class="d-flex align-items-start justify-content-between mb-3">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rounded-circle p-2" style="background-color: {{ $group->color }}20;">
                                                    <i class="fas fa-hashtag fs-5" style="color: {{ $group->color }}"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0 fw-bold">{{ $group->name }}</h6>
                                                    @if($group->category)
                                                        <small class="text-muted">{{ $group->category }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                            <span class="badge bg-light text-dark">
                                                {{ count($group->hashtags_array) }} tags
                                            </span>
                                        </div>

                                        <!-- Hashtags Preview -->
                                        <div class="mb-3">
                                            <div class="p-2 bg-light rounded" style="max-height: 100px; overflow-y: auto;">
                                                <small class="text-muted">
                                                    {{ Str::limit($group->formatted_hashtags, 150) }}
                                                </small>
                                            </div>
                                        </div>

                                        <!-- Stats -->
                                        <div class="mb-3">
                                            <small class="text-muted">
                                                <i class="fas fa-copy me-1"></i>
                                                Usado <strong>{{ $group->usage_count }}</strong> veces
                                                @if($group->last_used_at)
                                                    <br>
                                                    <i class="fas fa-clock me-1"></i>
                                                    {{ $group->last_used_at->diffForHumans() }}
                                                @endif
                                            </small>
                                        </div>

                                        <!-- Actions -->
                                        <div class="d-flex gap-2">
                                            <button type="button"
                                                    class="btn btn-sm btn-light flex-fill copy-hashtags"
                                                    data-hashtags="{{ $group->formatted_hashtags }}">
                                                <i class="fas fa-copy me-1"></i> Copiar
                                            </button>
                                            <a href="{{ route('admin.social.hashtags.edit', $group) }}"
                                               class="btn btn-sm btn-light">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('admin.social.hashtags.destroy', $group) }}"
                                                  method="POST"
                                                  onsubmit="return confirm('¿Eliminar este grupo?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-light-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <i class="fas fa-hashtag fs-1 text-muted"></i>
                        </div>
                        <h5 class="text-muted mb-2">No hay grupos de hashtags</h5>
                        <p class="text-muted mb-4">Crea grupos de hashtags para reutilizar en tus publicaciones</p>
                        <a href="{{ route('admin.social.hashtags.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Crear Primer Grupo
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    // Copy hashtags to clipboard
    document.querySelectorAll('.copy-hashtags').forEach(button => {
        button.addEventListener('click', function() {
            const hashtags = this.getAttribute('data-hashtags');
            navigator.clipboard.writeText(hashtags).then(() => {
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-check me-1"></i> Copiado';
                this.classList.add('btn-success');
                this.classList.remove('btn-light');

                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.classList.remove('btn-success');
                    this.classList.add('btn-light');
                }, 2000);
            });
        });
    });
</script>
@endpush
