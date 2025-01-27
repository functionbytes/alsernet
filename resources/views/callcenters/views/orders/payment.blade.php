@extends('layouts.callcenters')

@push('css')
    <link rel="stylesheet" href="{{ url('pages/css/print.css') }}">
@endpush

@section('content')


    <section class="checkout-area ptb-100">
        <div class="container">

            <form>
                <div class="row">
                    <div class="col-lg-12 col-md-12">
                        <div class="order-details">
                            <h3 class="title">Tu Orden</h3>
                            <div class="account-conten">
                                <div class=" container-fluid   container-fixed-lg bg-white">
                                    <!-- START card -->
                                    <div class="card card-transparent">

                                        <div class="">
                                            <div id="tableWithSearch_wrapper" class="dataTables_wrapper no-footer">
                                                <div>
                                                    <table
                                                        class="table table-hover demo-table-search table-responsive-block dataTable no-footer"
                                                        id="tableWithSearch" role="grid"
                                                        aria-describedby="tableWithSearch_info">
                                                        <thead>
                                                            <tr>
                                                                <th>CURSO</th>
                                                                <th>TOTAL</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                                <tr>

                                                                    <td class="v-align-middle">
                                                                        <p>{{ $order->course->title }}</p>
                                                                    </td>
                                                                    <td class="v-align-middle">
                                                                        <p>$ {{ number_format($order->total, 0, ',', '.') }}
                                                                        </p>
                                                                    </td>
                                                                </tr>


                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- END card -->
                                </div>
                            </div>

                            <div class="payment-box">
                                <div class="payment-method">
                                    <p>
                                        Cuando pagas directamente con Bpm puedes tener un descuento de tu compra.
                                    </p>
                                    <p>
                                        Una vez el pago de tu reserva es confirmado nos comunicaremos lo notificaremos en tu
                                        correo electrónico junto con las instrucciones
                                        para guiarte a tu próxima house.
                                    </p>


                                    <div class="pb-3 pt-1">
                                        <div class="custom-control custom-radio mb-2 mt-2">
                                            <input type="radio" id="wompi" name="payment" value="wompi" checked=""
                                                class="custom-control-input">
                                            <label for="wompi" class="font-weight-500 mb-0 custom-control-label">
                                                <span class="fs-12 text-heading d-inline-block mr-1"><i
                                                        class="fas fa-credit-card"></i></span>
                                                <font style="vertical-align: inherit;">
                                                    <font style="vertical-align: inherit;">
                                                        Pagar con Wompi</font>
                                                </font>
                                            </label>
                                        </div>
                                        <div class="custom-control custom-radio mb-2">
                                            <input type="radio" id="direct" name="payment" value="direct"
                                                class="custom-control-input">
                                            <label for="direct" class="font-weight-500 mb-0 custom-control-label"><span
                                                    class="fs-12 text-heading d-inline-block mr-1"><i
                                                        class="fas fa-money-bill-wave"></i></span>
                                                <font style="vertical-align: inherit;">
                                                    <font style="vertical-align: inherit;">Paga con directamente</font>
                                                </font>
                                            </label>
                                        </div>
                                    </div>


                                </div>
                                <a href="#" class="default-btn actionPayment text-center"><span
                                        class="label">Pagar</span></a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <div class="none">

        <form id="formWompi">
            <script src="https://checkout.wompi.co/widget.js" data-render="button" data-public-key="{{ $wompi->public }}"
                        data-currency="{{ $wompi->currency }}" data-amount-in-cents="{{ $wompi->amount }}00"
                        data-reference="{{ $wompi->reference }}" data-redirect-url="{{ $wompi->redirect }}">
            </script>
        </form>

    </div>

@endsection



@push('scripts')

    <script type="text/javascript">
        $(document).ready(function() {

            $(".actionPayment").click(function() {

                var payment = $('input:radio[name=payment]:checked').val();

                if (payment == 'wompi') {

                    $(".waybox-button").click();

                } else {
                    var cellphone = @json($setting->whatsapp);
                    var win = window.open('https://api.whatsapp.com/send?phone=57' + cellphone, '_blank');
                    win.focus();
                }
            });

        });
    </script>

@endpush
