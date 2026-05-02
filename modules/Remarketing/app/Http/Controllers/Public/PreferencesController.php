<?php

namespace Modules\Remarketing\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Remarketing\Models\Customer;
use Modules\Remarketing\Models\Message;

class PreferencesController extends Controller
{
    public function show(string $token): View
    {
        $customer = $this->resolveCustomer($token);

        return view('remarketing::public.preferences', compact('customer'));
    }

    public function update(Request $request, string $token): View
    {
        $customer = $this->resolveCustomer($token);

        if ($customer) {
            $customer->forceFill([
                'tags' => $request->input('tags', $customer->tags),
                'attributes' => array_merge((array) $customer->attributes, [
                    'preferences' => $request->input('preferences', []),
                ]),
            ])->save();
        }

        return view('remarketing::public.preferences', compact('customer'))->with('saved', true);
    }

    protected function resolveCustomer(string $token): ?Customer
    {
        $message = Message::query()
            ->where('open_token', $token)
            ->orWhere('click_token', $token)
            ->with('customer')
            ->first();

        return $message?->customer;
    }
}
