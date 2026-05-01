<?php

namespace Modules\Helpdesk\Services;

use Illuminate\Support\Facades\Mail;
use Modules\Helpdesk\Models\Customer;

class PortalAuthService
{
    /**
     * Send a magic login link to the customer's email.
     * Returns false if no customer found for that email.
     */
    public function sendMagicLink(string $email): bool
    {
        $customer = Customer::where('email', $email)->first();

        if (! $customer) {
            return false;
        }

        $token = $customer->generatePortalToken();
        $url = route('helpdesk.portal.verify', ['token' => $token]);

        Mail::send([], [], function ($message) use ($customer, $url) {
            $message->to($customer->email, $customer->name)
                ->subject('Tu enlace de acceso al portal')
                ->html(view('helpdesk::portal.email.magic-link', compact('customer', 'url'))->render());
        });

        return true;
    }

    /**
     * Verify a portal token. Returns the Customer on success, null on failure.
     */
    public function verifyToken(string $token): ?Customer
    {
        $customer = Customer::where('portal_token', $token)->first();

        if (! $customer) {
            return null;
        }

        if ($customer->portal_token_expires_at && $customer->portal_token_expires_at->isPast()) {
            return null;
        }

        // Consume the token
        $customer->update([
            'portal_token' => null,
            'portal_token_expires_at' => null,
        ]);

        return $customer;
    }
}
