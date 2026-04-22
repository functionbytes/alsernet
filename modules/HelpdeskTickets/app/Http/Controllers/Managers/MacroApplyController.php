<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\HelpdeskTickets\Models\Macro;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Services\MacroExecutor;

class MacroApplyController extends Controller
{
    public function __construct(
        private readonly MacroExecutor $executor
    ) {}

    public function list(): JsonResponse
    {
        $macros = Macro::query()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('is_shared', true)
                    ->orWhere('user_id', auth()->id());
            })
            ->select('id', 'name', 'description')
            ->orderBy('name')
            ->get();

        return response()->json(['macros' => $macros]);
    }

    public function apply(Ticket $ticket, Macro $macro): JsonResponse
    {
        $this->executor->run($macro, $ticket);

        return response()->json([
            'success' => true,
            'message' => 'Macro aplicada: '.$macro->name,
        ]);
    }
}
