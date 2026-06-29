<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Dev;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\HelpdeskTickets\Jobs\Helpdesks\FetchTicketEmailsJob;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Throwable;

class EmailTestController extends Controller
{
    public function index(): View
    {
        return view('helpdesktickets::dev.email-test');
    }

    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_name' => ['required', 'string', 'max:100'],
            'from_email' => ['required', 'email'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
        ]);

        try {
            $transport = new EsmtpTransport('127.0.0.1', 1025, false);
            $mailer = new Mailer($transport);

            $email = (new Email)
                ->from(new Address($validated['from_email'], $validated['from_name']))
                ->to('soporte@empresa.com')
                ->subject($validated['subject'])
                ->text($validated['body']);

            $mailer->send($email);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }

        return response()->json(['success' => true, 'message' => 'Email enviado']);
    }

    public function sync(): JsonResponse
    {
        try {
            app()->call([app(FetchTicketEmailsJob::class), 'handle']);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }

        $tickets = DB::connection('helpdesk')
            ->table('helpdesk_tickets')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'ticket_number', 'subject', 'source', 'created_at']);

        return response()->json(['success' => true, 'tickets' => $tickets]);
    }
}
