<?php

namespace Modules\Mailing\Http\Controllers\Settings;

use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;
use Modules\Mailing\Http\Controllers\Controller;
use Modules\Mailing\Library\Facades\Billing;

class PaymentController extends Controller
{
    /**
     * Display all paymentt.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, MessageBag $message_bag)
    {
        $gateways = Billing::getGateways();

        if (! config('app.demo')) {
            $gateways = array_filter($gateways, function ($gateway) {
                return $gateway->getType() != 'coinpayments';
            });
        }

        return view('admin.payments.index', [
            'gateways' => $gateways,
        ]);
    }

    /**
     * Enable payment.
     *
     * @param  int  $name
     * @return \Illuminate\Http\Response
     */
    public function enable(Request $request, $name)
    {
        // enable gateway
        Billing::enablePaymentGateway($name);

        $request->session()->flash('alert-success', trans('messages.payment_gateway.updated'));

        return redirect()->action('Settings\PaymentController@index');
    }

    /**
     * Disable payment.
     *
     * @param  int  $name
     * @return \Illuminate\Http\Response
     */
    public function disable(Request $request, $name)
    {
        // disable gateway
        Billing::disablePaymentGateway($name);

        $request->session()->flash('alert-success', trans('messages.payment_gateway.updated'));

        return redirect()->action('Settings\PaymentController@index');
    }
}
