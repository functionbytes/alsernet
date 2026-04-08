@extends('layouts.theme')

@section('content')

  <div class="card bg-light-info shadow-none position-relative overflow-hidden">
    <div class="card-body px-4 py-3">
      <div class="row align-items-center">
        <div class="col-9">
          <h4 class="fw-semibold mb-1">
            @if($category->icon)
              <i class="{{ $category->icon }} me-2"></i>
            @endif
            {{ $category->name }}
          </h4>
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
              <li class="breadcrumb-item">
                <a href="{{ route('manager.helpdesk.helpcenter.categories') }}">Centro de Ayuda</a>
              </li>
              <li class="breadcrumb-item active" aria-current="page">{{ $category->name }}</li>
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

  @if($category->description)
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-center gap-3">
          <div class="flex-shrink-0">
            <i class="fa-duotone fa-circle-info text-info fs-7"></i>
          </div>
          <div class="flex-grow-1">
            <p class="mb-0 text-muted">{{ $category->description }}</p>
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
              <i class="fa-duotone fa-folder me-1"></i> {{ $category->sections_count }} Secciones
            </span>
            <span class="badge bg-light-success text-success rounded-3 py-2 px-3 fs-3">
              <i class="fa-duotone fa-file-lines me-1"></i> {{ $category->articles_count }} Artículos
            </span>
          </div>
        </div>
        <div class="col-auto">
          <a href="{{ route('manager.helpdesk.helpcenter.sections.create', ['parent_id' => $category->id]) }}"
             class="btn btn-primary">
            <i class="fa-duotone fa-plus"></i> Nueva Sección
          </a>
        </div>
      </div>
    </div>

    <div class="card card-body">

      @if($category->sections->count() > 0)
        <div class="table-responsive">
          <table class="table search-table align-middle text-nowrap">
            <thead class="header-item">
              <tr>
                <th>Sección</th>
                <th>Artículos</th>
                <th>Posición</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              @foreach($category->sections as $section)
                <tr class="search-items">
                  <td>
                    <div>
                      <a href="{{ route('manager.helpdesk.helpcenter.sections.show', $section->id) }}"
                         class="usr-email-addr fw-semibold text-primary">
                        {{ $section->name }}
                      </a>
                      @if($section->description)
                        <br>
                        <small class="text-muted">{{ Str::limit($section->description, 80) }}</small>
                      @endif
                    </div>
                  </td>
                  <td>
                    <span class="badge bg-light-secondary text-secondary rounded-3 py-2 px-3">
                      <i class="fa-duotone fa-file-lines"></i> {{ $section->articles_count }}
                    </span>
                  </td>
                  <td>
                    <span class="badge bg-light-primary text-primary rounded-3 py-2 px-3">
                      {{ $section->position }}
                    </span>
                  </td>
                  <td>
                    <div class="dropdown dropstart">
                      <a href="#" class="text-muted" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-duotone fa-solid fa-ellipsis"></i>
                      </a>
                      <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                        <li>
                          <a class="dropdown-item d-flex align-items-center gap-3"
                             href="{{ route('manager.helpdesk.helpcenter.sections.show', $section->id) }}">
                            <i class="fa-duotone fa-eye"></i> Ver Sección
                          </a>
                        </li>
                        <li>
                          <a class="dropdown-item d-flex align-items-center gap-3"
                             href="{{ route('manager.helpdesk.helpcenter.sections.edit', $section->id) }}">
                            <i class="fa-duotone fa-edit"></i> Editar
                          </a>
                        </li>
                        <li>
                          <a class="dropdown-item d-flex align-items-center gap-3"
                             href="{{ route('manager.helpdesk.helpcenter.sections.articles.create', $section->id) }}">
                            <i class="fa-duotone fa-plus"></i> Agregar Artículo
                          </a>
                        </li>
                        <li>
                          <hr class="dropdown-divider">
                        </li>
                        <li>
                          <a class="dropdown-item d-flex align-items-center gap-3 confirm-delete"
                             data-href="{{ route('manager.helpdesk.helpcenter.sections.destroy', $section->id) }}">
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
          <i class="fa-duotone fa-folder-open text-muted" style="font-size: 4rem; opacity: 0.3;"></i>
          <h5 class="mt-3 text-muted">No hay secciones en esta categoría</h5>
          <p class="text-muted mb-3">Comienza agregando la primera sección</p>
          <a href="{{ route('manager.helpdesk.helpcenter.sections.create', ['parent_id' => $category->id]) }}"
             class="btn btn-primary">
            <i class="fa-duotone fa-plus me-1"></i> Crear Primera Sección
          </a>
        </div>
      @endif
    </div>
  </div>

@endsection
