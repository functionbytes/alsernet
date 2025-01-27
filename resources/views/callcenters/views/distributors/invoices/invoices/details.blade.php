@extends('layouts.callcenters')
@section('content')

    @include('distributors.includes.card', ['title' => 'Detalle Factura '. $invoice->uid])

    <div class="row">
        <div class="col-lg-12 ">
            <div class="checkout">
                <div class="card ">
                    <div class="card-body p-4">
                        <div class="wizard-content">
                            <form action="#" class="tab-wizard wizard-circle wizard clearfix" role="application" id="steps-uid-0">
                                <div class="steps clearfix">

                                </div>
                                <div class="content clearfix">
                                    <section id="steps-uid-0-p-1" role="tabpanel" >
                                        <div class="billing-address-content">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="text-left mx-3">
                                                        <address>
                                                            <h4 class="mb-3">Para</h4>
                                                            <h6 class="mt-0 mb-0 fw-bold invoice-customer">
                                                                <span>Distribuidor :</span>
                                                                <strong>{{Str::ucfirst(Str::lower($invoice->distributor->title))}}</strong>
                                                            </h6>
                                                            <p class="mt-0 mb-0 {{ $invoice->distributor->address !=null ? '' : 'd-none' }}">
                                                                <span>Dirección :</span>
                                                                <strong>{{Str::ucfirst(Str::lower($invoice->distributor->address))}}</strong>
                                                            </p>
                                                            <p class="mt-0 mb-0">
                                                                <span>Referencia :</span>
                                                                <strong>{{  $invoice->reference}}</strong>
                                                            </p>
                                                            <p class="mt-0 mb-0">
                                                                <span>Fecha de la factura :</span>
                                                                <strong>{{ date('Y-m-d', strtotime($invoice->date)) }}</strong>
                                                            </p>
                                                            <p class="mt-0 mb-0">
                                                                <span>Fecha de inicio :</span>
                                                                <strong>{{ date('Y-m-d', strtotime($invoice->enroll_start)) }}</strong>
                                                            </p>
                                                            <p class="mt-0 mb-0">
                                                                <span>Fecha de vencimiento :</span>
                                                                <strong>{{ date('Y-m-d', strtotime($invoice->enroll_expire)) }}</strong>
                                                            </p>
                                                        </address>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="container">

                                                @foreach($details as $enterprise => $courses)
                                                    <div class="row mb-4">
                                                        <h4 class="invoice-content-enterprise">{{ $enterprise }}</h4>
                                                        <div class="table-responsive">
                                                            <hr>
                                                            <table class="table align-middle text-nowrap mb-0">
                                                                <thead>
                                                                                                                               <tr>
                                                                    <th class="fw-bolder text-uppercase">Descripción</th>
                                                                    <th class="fw-bolder text-uppercase">Cantidad</th>
                                                                    <th class="fw-bolder text-uppercase">Total</th>
                                                                </tr>
                                                                <!-- end row -->
                                                                </thead>

                                                                <tbody>
                                                                @foreach($courses as $item)
                                                                    @if(is_array($item)) <!-- Verificar si es un array de curso -->
                                                                    <tr>
                                                                        <td class="border-bottom-0">
                                                                            <div class="d-flex align-items-center gap-3">
                                                                                <h6 class="fw-semibold fs-4 mb-0">{{ $item['course'] }}</h6>
                                                                            </div>
                                                                        </td>
                                                                        <td class="border-bottom-0">
                                                                            <div class="d-flex align-items-center gap-3">
                                                                                <p class="mb-0">{{ ceil($item['quantity']) }}</p>
                                                                            </div>
                                                                        </td>
                                                                        <td class="border-bottom-0">
                                                                            <div class="d-flex align-items-center gap-3">
                                                                                <p class="mb-0">${{ number_format($item['totalAmount']) }}</p>
                                                                            </div>
                                                                        </td>
                                                                    </tr>
                                                                    @endif
                                                                @endforeach
                                                                </tbody>

                                                                <!-- Fila para mostrar el total de la empresa -->
                                                                <tfoot>
                                                                <tr>
                                                                    <td colspan="2" class="fw-bolder text-uppercase">Total</td>
                                                                    <td class="fw-bolder">${{ number_format($courses['totalEnterprise']) }}</td>
                                                                </tr>
                                                                </tfoot>
                                                            </table>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>

                                            <div class="order-summary border rounded p-4 my-4">
                                                <div class="mt-3 mb-3">
                                                    <h5 class="fs-5 fw-semibold mb-4 text-uppercase">Resumen del pedido</h5>

                                                    @if($invoice->total_discount_amount>0)
                                                        <div class="d-flex justify-content-between mb-4">
                                                            <p class="mb-0 fs-4">Descuentos</p>
                                                            <h6 class="mb-0 fs-4 fw-semibold">${{ number_format($invoice->total_discount_amount) }}</h6>
                                                        </div>
                                                    @endif

                                                    @if($invoice->total_tax_amount>0)
                                                    <div class="d-flex justify-content-between mb-4">
                                                        <p class="mb-0 fs-4">Impuestos</p>
                                                        <h6 class="mb-0 fs-4 fw-semibold">${{ number_format($invoice->total_tax_amount) }}</h6>
                                                    </div>
                                                    @endif

                                                    <div class="d-flex justify-content-between mb-4">
                                                        <h6 class="mb-0 fs-4 ">Subtotal</h6>
                                                        <h6 class="mb-0 fs-5 fw-semibold">${{ number_format($invoice->total_after_discount) }}</h6>
                                                    </div>
                                                    <div class="d-flex justify-content-between">
                                                        <h6 class="mb-0 fs-4 fw-semibold">Total</h6>
                                                        <h6 class="mb-0 fs-5 fw-semibold">${{ number_format($invoice->total_invoices_amount) }}</h6>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                         </section>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
