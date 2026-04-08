@extends('layouts.theme')

@section('content')

  <div class="card bg-light-info shadow-none position-relative overflow-hidden">
    <div class="card-body px-4 py-3">
      <div class="row align-items-center">
        <div class="col-9">
          <h4 class="fw-semibold mb-1">{{ $section->name }}</h4>
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
              <li class="breadcrumb-item">
                <a href="{{ route('manager.helpdesk.helpcenter.categories') }}">Centro de Ayuda</a>
              </li>
              @if($section->parent)
                <li class="breadcrumb-item">{{ $section->parent->name }}</li>
              @endif
              <li class="breadcrumb-item active" aria-current="page">{{ $section->name }}</li>
            </ol>
          </nav>
        </div>
        <div class="col-3">
          <div class="text-center mb-n5">
            <img src="/managers/images/breadcrumb/ChatBc.png" alt="" class="img-fluid mb-n4" style="max-height: 120px;">
          </div>
        </div>
      </div>
    </div>
  </div>

  @if($section->description)
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-center gap-3">
          <div class="flex-shrink-0">
            <i class="fa-duotone fa-circle-info text-info fs-7"></i>
          </div>
          <div class="flex-grow-1">
            <p class="mb-0 text-muted">{{ $section->description }}</p>
          </div>
        </div>
      </div>
    </div>
  @endif

  <div class="widget-content searchable-container list">
    <div class="card card-body">
      <div class="row justify-content-between g-2 align-items-center">
        <div class="col-auto">
          <div class="d-flex gap-2 align-items-center">
            <span class="badge bg-light-info text-info rounded-3 py-2 px-3 fs-3">
              <i class="fa-duotone fa-file-lines me-1"></i> {{ $section->articles_count }} Artículos
            </span>
            <span class="badge bg-light-success text-success rounded-3 py-2 px-3 fs-3">
              <i class="fa-duotone fa-check me-1"></i> {{ $section->articles->where('draft', false)->count() }} Publicados
            </span>
            <span class="badge bg-light-warning text-warning rounded-3 py-2 px-3 fs-3">
              <i class="fa-duotone fa-file-pen me-1"></i> {{ $section->articles->where('draft', true)->count() }} Borradores
            </span>
          </div>
        </div>
        <div class="col-auto">
          <a href="{{ route('manager.helpdesk.helpcenter.sections.articles.create', $section->id) }}"
             class="btn btn-primary">
            <i class="fa-duotone fa-plus"></i> Nuevo Artículo
          </a>
        </div>
      </div>
    </div>

    <div class="card card-body">

      @if($section->articles->count() > 0)
        <div class="table-responsive">
          <table class="table search-table align-middle text-nowrap">
            <thead class="header-item">
              <tr>
                <th>Título</th>
                <th>Estado</th>
                <th>Vistas</th>
                <th>Útil</th>
                <th>Autor</th>
                <th>Fecha</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              @foreach($section->articles as $article)
                <tr class="search-items">
                  <td>
                    <div>
                      <span class="usr-email-addr fw-semibold">{{ $article->title }}</span>
                      @if($article->description)
                        <br>
                        <small class="text-muted">{{ Str::limit($article->description, 60) }}</small>
                      @endif
                    </div>
                  </td>
                  <td>
                    @if($article->draft)
                      <span class="badge bg-light-warning text-warning rounded-3 py-2 px-3">
                        <i class="fa-duotone fa-file-pen"></i> Borrador
                      </span>
                    @else
                      <span class="badge bg-light-success text-success rounded-3 py-2 px-3">
                        <i class="fa-duotone fa-check"></i> Publicado
                      </span>
                    @endif
                  </td>
                  <td>
                    <span class="badge bg-light-secondary text-secondary rounded-3 py-2 px-3">
                      {{ number_format($article->views) }}
                    </span>
                  </td>
                  <td>
                    <span class="badge bg-light-primary text-primary rounded-3 py-2 px-3">
                      {{ number_format($article->was_helpful) }}
                    </span>
                  </td>
                  <td>
                    @if($article->author)
                      <span class="usr-ph-no text-muted">{{ $article->author->name }}</span>
                    @else
                      <span class="text-muted">-</span>
                    @endif
                  </td>
                  <td>
                    <span class="usr-ph-no text-muted">{{ $article->created_at->format('d/m/Y') }}</span>
                  </td>
                  <td>
                    <div class="dropdown dropstart">
                      <a href="#" class="text-muted" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-duotone fa-solid fa-ellipsis"></i>
                      </a>
                      <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                        <li>
                          <a class="dropdown-item d-flex align-items-center gap-3"
                             href="{{ route('manager.helpdesk.helpcenter.articles.edit', $article->id) }}">
                            <i class="fa-duotone fa-edit"></i> Editar
                          </a>
                        </li>
                        <li>
                          <a class="dropdown-item d-flex align-items-center gap-3 confirm-delete"
                             data-href="{{ route('manager.helpdesk.helpcenter.articles.destroy', $article->id) }}">
                            <i class="fa-duotone fa-trash"></i> Eliminar
                          </a>
                        </li>
                      </ul>
                    </div>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @else
        <div class="text-center py-5">
          <i class="fa-duotone fa-file-circle-question text-muted" style="font-size: 4rem; opacity: 0.3;"></i>
          <h5 class="mt-3 text-muted">No hay artículos en esta sección</h5>
          <p class="text-muted mb-3">Comienza agregando el primer artículo de ayuda</p>
          <a href="{{ route('manager.helpdesk.helpcenter.sections.articles.create', $section->id) }}"
             class="btn btn-primary">
            <i class="fa-duotone fa-plus me-1"></i> Crear Primer Artículo
          </a>
        </div>
      @endif
    </div>
  </div>

@endsection
