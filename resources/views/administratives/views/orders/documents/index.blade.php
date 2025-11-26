@extends('layouts.administratives')

@section('content')

    @include('managers.includes.card', ['title' => 'Documentos'])

    <div class="widget-content searchable-container list">
        <div class="card card-body">
            <div class="row">
                <div class="col-md-12 col-xl-12">
                    <form class="position-relative form-search" action="{{ request()->fullUrl() }}" method="GET">
                        <div class="row justify-content-between g-2 ">
                            <div class="col-auto flex-grow-1">
                                <div class="tt-search-box">
                                    <div class="input-group">
                                        <span class="position-absolute top-50 start-0 translate-middle-y ms-2"> <i data-feather="search"></i></span>
                                        <input class="form-control rounded-start w-100" type="text" id="search" name="search" placeholder="Buscar" @isset($searchKey) value="{{ $searchKey }}" @endisset>
                                    </div>
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="input-group">
                                    <select class="form-select select2" name="proccess" data-minimum-results-for-search="Infinity">
                                        <option value="">Seleccionar estado</option>
                                        <option value="1" @isset($proccess) @if ($proccess==1) selected @endif @endisset>  Cargados</option>
                                        <option value="0" @isset($proccess) @if ($proccess==0) selected  @endif @endisset>  Pendiente</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="input-group">
                                    <input type="text" class="form-control daterange" id="daterange" placeholder="Seleccionar rango de fechas">
                                    <input type="hidden" id="date_from" name="date_from" @isset($dateFrom) value="{{ $dateFrom }}" @endisset>
                                    <input type="hidden" id="date_to" name="date_to" @isset($dateTo) value="{{ $dateTo }}" @endisset>
                                </div>
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-primary" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Buscar">
                                    <i class="fa-duotone fa-magnifying-glass"></i>
                                </button>
                            </div>
                            <div class="col-auto">
                                <a href="{{ route('administrative.documents') }}" class="btn btn-secondary" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Limpiar filtros">
                                    <i class="fa-duotone fa-xmark"></i>
                                </a>
                            </div>
                            <div class="col-auto">
                                <a href="{{ route('administrative.documents.import') }}" class="btn btn-warning" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Importar órdenes específicas">
                                    <i class="fa-duotone fa-file-import"></i>
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
                        <th>Orden</th>
                        <th>Cliente</th>
                        <th>Origen</th>
                        <th>Documentos</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($documents as $key => $document)
                        <tr class="search-items">
                            <td>
                                {{ $document->order_id }}
                            </td>
                            <td>
                                {{ strtoupper($document->customer_firstname) }}  {{ strtoupper($document->customer_lastname) }}
                            </td>
                            <td>
                                @if($document->source)

                                    {{ ucfirst($document->source) }}
                                @else
                                    Sin origen
                                @endif
                            </td>
                            <td>
                      <span class="badge {{ $document->confirmed_at!=null && $document->media->count()>0 == 1 ? 'bg-light-primary' : 'bg-light-secondary' }} rounded-3 py-2 text-primary fw-semibold fs-2 d-inline-flex align-items-center gap-1">
                           {{ $document->confirmed_at!=null && $document->media->count()>0 ? 'Cargados' : 'Pendiente' }}
                      </span>
                            </td>

                            <td>
                      <span class="badge {{ $document->proccess == 1 ? 'bg-light-primary' : 'bg-light-secondary' }} rounded-3 py-2 text-primary fw-semibold fs-2 d-inline-flex align-items-center gap-1">
                           {{ $document->proccess == 1 ? 'Gestionado' : 'Pendiente' }}
                      </span>
                            </td>
                            <td>
                                <span class="usr-ph-no">{{ date('Y-m-d', strtotime($document->created_at)) }}</span>
                            </td>
                            <td class="text-left">
                                <div class="dropdown dropstart">
                                    <a href="#" class="text-muted" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ti ti-dots fs-5"></i>
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-3" href="{{ route('administrative.documents.edit', $document->uid) }}">Editar</a>
                                        </li>
                                        <li class="{{ $document->media->count()>0 ? '' : 'd-none'}}">
                                            <a class="dropdown-item d-flex align-items-center gap-3" href="{{ route('administrative.documents.summary', $document->uid) }}">Documentos</a>
                                        </li>
                                        <li>
                                            <button class="dropdown-item d-flex align-items-center gap-3 resend-reminder-btn" data-uid="{{ $document->uid }}" type="button">
                                                <i class="ti ti-mail fs-4"></i> Reenviar correo
                                            </button>
                                        </li>
                                        <li class="{{ $document->confirmed_at!=null && $document->media->count()>0 && !$document->confirmed_at ? '' : 'd-none'}}">
                                            <button class="dropdown-item d-flex align-items-center gap-3 confirm-upload-btn" data-uid="{{ $document->uid }}" type="button">
                                                <i class="ti ti-check fs-4"></i> Confirmar carga
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="result-body ">
                <span>Mostrar {{ $documents->firstItem() }}-{{ $documents->lastItem() }} de {{ $documents->total() }} resultados</span>
                <nav>
                    {{ $documents->appends(request()->input())->links() }}
                </nav>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
    <script>


        // Reenviar correo
        document.querySelectorAll('.resend-reminder-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const uid = this.getAttribute('data-uid');

                if (!confirm('¿Estás seguro de que deseas reenviar el correo de recordatorio?')) {
                    return;
                }

                fetch(`/administratives/documents/${uid}/resend-reminder`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'Content-Type': 'application/json',
                    },
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Correo de recordatorio reenviado correctamente');
                            // Aquí puedes recargar la página o actualizar la fila
                        } else {
                            alert('Error: ' + (data.message || 'No se pudo reenviar el correo'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error al procesar la solicitud');
                    });
            });
        });

        // Confirmar carga de documento
        document.querySelectorAll('.confirm-upload-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const uid = this.getAttribute('data-uid');

                if (!confirm('¿Estás seguro de que deseas confirmar la carga del documento?')) {
                    return;
                }

                fetch(`/administratives/documents/${uid}/confirm-upload`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'Content-Type': 'application/json',
                    },
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Carga de documento confirmada correctamente');
                            // Recargar la página para actualizar los estados
                            location.reload();
                        } else {
                            alert('Error: ' + (data.message || 'No se pudo confirmar la carga'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error al procesar la solicitud');
                    });
            });
        });
    </script>
@endsection


