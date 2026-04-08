@extends('layouts.theme')

@section('content')

  @include('core::components.card', ['title' => 'Centro de Ayuda - Artículos'])

  <div class="widget-content searchable-container list">

    <div class="card card-body">
      <div class="row">
        <div class="col-md-12 col-xl-12">
          <form class="position-relative form-search" action="{{ request()->fullUrl() }}" method="GET">
            <div class="row justify-content-between g-2">
              <div class="col-auto flex-grow-1">
                <div class="tt-search-box">
                  <div class="input-group">
                    <span class="position-absolute top-50 start-0 translate-middle-y ms-2">
                      <i data-feather="search"></i>
                    </span>
                    <input class="form-control rounded-start w-100" type="text" id="search" name="search"
                           placeholder="Buscar artículos" @isset($searchKey) value="{{ $searchKey }}" @endisset>
                  </div>
                </div>
              </div>
              <div class="col-auto">
                <select class="form-select" name="draft" onchange="this.form.submit()">
                  <option value="">Todos los estados</option>
                  <option value="1" {{ request('draft') === '1' ? 'selected' : '' }}>Borradores</option>
                  <option value="0" {{ request('draft') === '0' ? 'selected' : '' }}>Publicados</option>
                </select>
              </div>
              <div class="col-auto">
                <button type="submit" class="btn btn-primary" data-bs-toggle="tooltip" data-bs-placement="top"
                        data-bs-original-title="Buscar">
                  <i class="fa-duotone fa-magnifying-glass"></i>
                </button>
              </div>
              <div class="col-auto">
                <a href="{{ route('manager.helpdesk.helpcenter.articles.create') }}" class="btn btn-primary">
                  <i class="fa-duotone fa-plus"></i> Nuevo Artículo
                </a>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="card card-body">
      <div class="table-responsive">
        <table class="table search-table align-middle text-nowrap">
          <thead class="header-item">
            <tr>
              <th>Título</th>
              <th>Descripción</th>
              <th>Secciones</th>
              <th>Estado</th>
              <th>Vistas</th>
              <th>Útil</th>
              <th>Autor</th>
              <th>Fecha</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($articles as $article)
              <tr class="search-items">
                <td>
                  <span class="fw-semibold">{{ $article->title }}</span>
                </td>
                <td>
                  <span class="text-muted">{{ Str::limit($article->description, 50) }}</span>
                </td>
                <td>
                  @if($article->categories->count() > 0)
                    @foreach($article->categories as $section)
                      <span class="badge bg-light-info text-info rounded-3 py-1 px-2 me-1">
                        {{ $section->name }}
                      </span>
                    @endforeach
                  @else
                    <span class="text-muted">Sin sección</span>
                  @endif
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
                    <span class="text-muted">{{ $article->author->name }}</span>
                  @else
                    <span class="text-muted">-</span>
                  @endif
                </td>
                <td>
                  <span class="text-muted">{{ $article->created_at->format('d/m/Y') }}</span>
                </td>
                <td>
                  <div class="dropdown dropstart">
                    <a href="#" class="text-muted" id="dropdownMenuButton{{ $article->id }}" data-bs-toggle="dropdown"
                       aria-expanded="false">
                      <i class="fa-duotone fa-solid fa-ellipsis"></i>
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton{{ $article->id }}">
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
            @empty
              <tr>
                <td colspan="9" class="text-center py-4">
                  <p class="text-muted mb-0">No hay artículos disponibles</p>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="result-body">
        <span>Mostrar {{ $articles->firstItem() }}-{{ $articles->lastItem() }} de {{ $articles->total() }} resultados</span>
        <nav>
          {{ $articles->appends(request()->input())->links() }}
        </nav>
      </div>
    </div>
  </div>
@endsection
