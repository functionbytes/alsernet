@extends('layouts.theme')

@section('content')

  @include('core::components.card', ['title' => 'Centro de Ayuda - Categorías'])

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
                           placeholder="Buscar categorías" @isset($searchKey) value="{{ $searchKey }}" @endisset>
                  </div>
                </div>
              </div>
              <div class="col-auto">
                <button type="submit" class="btn btn-primary" data-bs-toggle="tooltip" data-bs-placement="top"
                        data-bs-original-title="Buscar">
                  <i class="fa-duotone fa-magnifying-glass"></i>
                </button>
              </div>
              <div class="col-auto">
                <a href="{{ route('manager.helpdesk.helpcenter.categories.create') }}" class="btn btn-primary">
                  <i class="fa-duotone fa-plus"></i> Nueva Categoría
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
              <th>Icono</th>
              <th>Nombre</th>
              <th>Descripción</th>
              <th>Secciones</th>
              <th>Artículos</th>
              <th>Posición</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($categories as $category)
              <tr class="search-items">
                <td>
                  @if($category->icon)
                    <i class="{{ $category->icon }} fs-6"></i>
                  @else
                    <i class="fa-duotone fa-folder text-muted"></i>
                  @endif
                </td>
                <td>
                  <span class="fw-semibold">{{ $category->name }}</span>
                </td>
                <td>
                  <span class="text-muted">{{ Str::limit($category->description, 50) }}</span>
                </td>
                <td>
                  <span class="badge bg-light-primary text-primary rounded-3 py-2 px-3">
                    {{ $category->sections_count }} secciones
                  </span>
                </td>
                <td>
                  <span class="badge bg-light-info text-info rounded-3 py-2 px-3">
                    {{ $category->articles_count }} artículos
                  </span>
                </td>
                <td>
                  <span class="badge bg-light-secondary text-secondary rounded-3 py-2 px-3">
                    #{{ $category->position }}
                  </span>
                </td>
                <td>
                  <div class="dropdown dropstart">
                    <a href="#" class="text-muted" id="dropdownMenuButton" data-bs-toggle="dropdown"
                       aria-expanded="false">
                      <i class="fa-duotone fa-solid fa-ellipsis"></i>
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                      <li>
                        <a class="dropdown-item d-flex align-items-center gap-3"
                           href="{{ route('manager.helpdesk.helpcenter.categories.show', $category->id) }}">
                          <i class="fa-duotone fa-eye"></i> Ver Categoría
                        </a>
                      </li>
                      <li>
                        <a class="dropdown-item d-flex align-items-center gap-3"
                           href="{{ route('manager.helpdesk.helpcenter.sections.create', ['parent_id' => $category->id]) }}">
                          <i class="fa-duotone fa-plus"></i> Añadir Sección
                        </a>
                      </li>
                      <li>
                        <hr class="dropdown-divider">
                      </li>
                      <li>
                        <a class="dropdown-item d-flex align-items-center gap-3"
                           href="{{ route('manager.helpdesk.helpcenter.categories.edit', $category->id) }}">
                          <i class="fa-duotone fa-edit"></i> Editar
                        </a>
                      </li>
                      <li>
                        <a class="dropdown-item d-flex align-items-center gap-3 confirm-delete"
                           data-href="{{ route('manager.helpdesk.helpcenter.categories.destroy', $category->id) }}">
                          <i class="fa-duotone fa-trash"></i> Eliminar
                        </a>
                      </li>
                    </ul>
                  </div>
                </td>
              </tr>

              @if($category->sections_count > 0)
                @foreach($category->sections as $section)
                  <tr class="search-items bg-light-subtle">
                    <td>
                      <i class="fa-duotone fa-layer-group text-muted"></i>
                    </td>
                    <td class="ps-3">
                      <i class="fa-duotone fa-arrow-turn-down-right me-2 text-muted"></i>
                      <a href="{{ route('manager.helpdesk.helpcenter.sections.show', $section->id) }}"
                         class="text-decoration-none">
                        {{ $section->name }}
                      </a>
                    </td>
                    <td>
                      <span class="text-muted">{{ Str::limit($section->description, 50) }}</span>
                    </td>
                    <td>-</td>
                    <td>
                      <span class="badge bg-light-info text-info rounded-3 py-2 px-3">
                        {{ $section->articles_count }} artículos
                      </span>
                    </td>
                    <td>
                      <span class="badge bg-light-secondary text-secondary rounded-3 py-2 px-3">
                        #{{ $section->position }}
                      </span>
                    </td>
                    <td>
                      <div class="dropdown dropstart">
                        <a href="#" class="text-muted" id="dropdownMenuButton{{ $section->id }}"
                           data-bs-toggle="dropdown" aria-expanded="false">
                          <i class="fa-duotone fa-solid fa-ellipsis"></i>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton{{ $section->id }}">
                          <li>
                            <a class="dropdown-item d-flex align-items-center gap-3"
                               href="{{ route('manager.helpdesk.helpcenter.sections.show', $section->id) }}">
                              <i class="fa-duotone fa-eye"></i> Ver Sección
                            </a>
                          </li>
                          <li>
                            <a class="dropdown-item d-flex align-items-center gap-3"
                               href="{{ route('manager.helpdesk.helpcenter.sections.articles.create', $section->id) }}">
                              <i class="fa-duotone fa-plus"></i> Añadir Artículo
                            </a>
                          </li>
                          <li><hr class="dropdown-divider"></li>
                          <li>
                            <a class="dropdown-item d-flex align-items-center gap-3"
                               href="{{ route('manager.helpdesk.helpcenter.sections.edit', $section->id) }}">
                              <i class="fa-duotone fa-edit"></i> Editar
                            </a>
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
              @endif
            @empty
              <tr>
                <td colspan="7" class="text-center py-4">
                  <p class="text-muted mb-0">No hay categorías disponibles</p>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="result-body">
        <span>Mostrar {{ $categories->firstItem() }}-{{ $categories->lastItem() }} de {{ $categories->total() }} resultados</span>
        <nav>
          {{ $categories->appends(request()->input())->links() }}
        </nav>
      </div>
    </div>
  </div>
@endsection
