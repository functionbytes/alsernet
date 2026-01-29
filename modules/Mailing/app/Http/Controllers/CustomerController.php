<?php

namespace Modules\Mailing\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Mailing\Models\Subscription;

class CustomerController extends Controller
{
    /**
     * Log in back user.
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function loginBack(Request $request)
    {
        if ($request->user()->admin) {
            return redirect()->action('Admin\HomeController@index');
        }

        $id = \Session::pull('orig_customer_id');
        $orig_user = \App\Models\User::findByUid($id);

        \Auth::login($orig_user);

        return redirect()->action('Admin\CustomerController@index');
    }

    /**
     * Admin area.
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function adminArea(Request $request)
    {
        $id = \Session::get('orig_customer_id');
        $orig_user = \App\Models\User::findByUid($id);

        // Get current subscription
        $customer = $request->user()->customer;
        $subscription = $customer->getNewOrActiveGeneralSubscription();

        $next_billing_date = null;
        if ($subscription) {
            $next_billing_date = $subscription->current_period_ends_at;
        }

        return view('customers.admin_area', [
            'customer' => $customer,
            'subscription' => $subscription,
            'next_billing_date' => $next_billing_date,
        ]);
    }
}
