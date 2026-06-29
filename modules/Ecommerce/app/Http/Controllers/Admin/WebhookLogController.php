<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Ecommerce\Models\WebhookLog;

class WebhookLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = WebhookLog::query()->latest();

        if ($status = $request->input('status')) {
            $query->where('success', $status === 'success');
        }

        if ($event = $request->input('event')) {
            $query->where('event', $event);
        }

        $logs = $query->paginate(25);

        return view('ecommerce::webhook-logs.index', compact('logs'));
    }

    public function show(WebhookLog $webhookLog): View
    {
        return view('ecommerce::webhook-logs.show', compact('webhookLog'));
    }
}
