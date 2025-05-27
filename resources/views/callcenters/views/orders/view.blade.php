@extends('layouts.callcenters')
@section('content')
    @include('customer.includes.card', ['title' => 'Detalle orden '. $order->uid])
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
                                                    <div class="text-left">
                                                        <address>
                                                            <h4>Para</h4>
                                                            <p class="mt-0 mb-0">
                                                                <span class="fw-semibold mb-0">Orden :</span>
                                                                {{ $order->uid}}
                                                            </p>
                                                            <h6 class="fw-bold invoice-customer mb-0">
                                                                <span class="fw-semibold mb-0">Cliente :</span>
                                                                {{Str::ucfirst(Str::lower($order->user->firstname))}}
                                                                {{ Str::lower($order->user->lastname)}}
                                                            </h6>

                                                            <p class="mt-0 mb-0 {{ $order->user->address !=null ? '' : 'd-none' }}">
                                                                <span class="fw-semibold mb-0">Dirección :</span>
                                                                {{Str::ucfirst(Str::lower($order->user->address))}}
                                                            </p>
                                                            <p class="mt-0 mb-0">
                                                                <span class="fw-semibold mb-0">Fecha de la factura :</span>

                                                                {{ date('Y-m-d', strtotime($order->payment_at)) }}
                                                            </p>
                                                            <p class="mt-0 mb-0">
                                                                <span class="fw-semibold mb-0">Fecha de inicio :</span>
                                                                {{ date('Y-m-d', strtotime($order->enroll_start)) }}
                                                            </p>
                                                            <p class="mt-0 mb-0">
                                                                <span class="fw-semibold mb-0">Fecha de vencimiento :</span>
                                                                {{ date('Y-m-d', strtotime($order->enroll_expire)) }}
                                                            </p>
                                                        </address>
                                                    </div>
                                                </div>
                                            </div><hr>
                                            <div class="row">
                                                <div class="table-responsive">
                                                    <table class="table align-middle text-nowrap mb-0">
                                                        <tbody>
                                                        <tr>
                                                            <td class="border-bottom-0">
                                                                <div class="d-flex align-items-center gap-3">
                                                                    <div>
                                                                        <span class="fw-semibold mb-0">{{ $order->course->title }}</span>
                                                                        <p class="mb-0">{{ $order->course->categorie->title }}</p>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                            <div class="order-summary border rounded p-4 my-4">
                                                <div class="p-3">
                                                    <h5 class="fs-5 fw-semibold mb-4">Resumen del pedido</h5>
                                                    <div class="d-flex justify-content-between mb-4">
                                                        <p class="mb-0 fs-4">Sub Total</p>
                                                        <h6 class="mb-0 fs-4 fw-semibold">${{ number_format($order->total) }}</h6>
                                                    </div>
                                                    <div class="d-flex justify-content-between">
                                                        <h6 class="mb-0 fs-4 fw-semibold">Total</h6>
                                                        <h6 class="mb-0 fs-5 fw-semibold">${{ number_format($order->total) }}</h6>
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
