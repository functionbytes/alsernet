@extends('layouts.managers')

@section('content')

    <div class="content-body">
        <div class="container-fluid">
            <!-- Add Project -->

                <div class="row bread">
                            <div class="row col-lg-8 col-sm-12">

                                <div class="col-sm-12">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item active"><a href="{{ route('manager.dashboard') }}">Dashboard</a></li>
                                        <li class="breadcrumb-item "><a href="{{ route('manager.products') }}">Equipo</a></li>
                                    </ol>
                                </div>
                            </div>
                            <div class="col-lg-4 col-sm-12">
                                <div class="justify-content-add">
                                    <a href="{{ route('manager.products.create') }}" id="btn-add-contact" class="btn btn-primary rounded text-white content-btn-add">Agregar</a>
                                </div>
                            </div>
                </div>
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm mb-0 table-striped">
                                    <thead>
                                        <tr>
                                            <th>Titulo</th>
                                            <th>Cargo</th>
                                            <th>Estado</th>
                                            <th>Fecha</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody id="customers">
                                        @foreach($products as $product)
                                            <tr class="btn-reveal-trigger">
                                                <td class="py-3">
                                                    <a href="#">
                                                        <div class="media d-flex align-items-center">

                                                            <div class="media-body">
                                                                <h5 class="mb-0 fs--1">{{ $product->title }} </h5>
                                                            </div>
                                                        </div>
                                                    </a>
                                                </td>
                                                <td class="py-3">
                                                    <a href="#">
                                                        <div class="media d-flex align-items-center">

                                                            <div class="media-body">
                                                                <h5 class="mb-0 fs--1">{{ $product->reference }} </h5>
                                                            </div>
                                                        </div>
                                                    </a>
                                                </td>
                                                <td class="py-2">
                                                    @if($product->available == 1)
                                                        <a class="status-active">Activo</a>
                                                    @else
                                                        <a class="status-disabled">Inactivo</a>
                                                    @endif

                                                </td>
                                                <td class="py-2">{{ humanize_date($product->created_at) }}</td>
                                                <td class="py-2 text-center">
                                                    <div class="dropdown">
                                                        <button class="option btn-primary tp-btn-light sharp" type="button" data-toggle="dropdown"><span class="fs--1"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="18px" height="18px" viewbox="0 0 24 24" version="1.1"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><rect x="0" y="0" width="24" height="24"></rect><circle fill="#000000" cx="5" cy="12" r="2"></circle><circle fill="#000000" cx="12" cy="12" r="2"></circle><circle fill="#000000" cx="19" cy="12" r="2"></circle></g></svg></span></button>
                                                        <div class="dropdown-menu dropdown-menu-right border py-0">
                                                            <a class="dropdown-item" href="{{ route('manager.products.edit', $product->uid) }}" >Editar</a>


                                                            <a class="dropdown-item h-modal-delete" data-modal="delete-modal" data-href="/manager/products/destroy/" data-slack="{{ $product->uid }}" >
                                                                Eliminar
                                                            </a>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
