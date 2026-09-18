<?php

namespace Modules\HelpdeskEmailActivity\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Modules\HelpdeskEmailActivity\Enums\SuppressionReason;
use Modules\HelpdeskEmailActivity\Http\Requests\Settings\StoreEmailSuppressionRequest;
use Modules\HelpdeskEmailActivity\Models\EmailLog;
use Modules\HelpdeskEmailActivity\Models\EmailSuppression;
use Modules\HelpdeskEmailActivity\Services\EmailSuppressionService;

/**
 * Gestión manual de la lista de supresión — las entradas automáticas
 * (hard bounce/queja) las crea EmailLogObserver; aquí también se pueden dar
 * de alta manualmente (p.ej. baja voluntaria pedida por teléfono) y quitar
 * cualquier entrada.
 */
class EmailSuppressionController extends Controller
{
    public function __construct(private readonly EmailSuppressionService $suppressions)
    {
        $this->middleware('can:helpdeskemailactivity.settings.view')->only('index');
        $this->middleware('can:helpdeskemailactivity.settings.update')->only(['store', 'destroy']);
    }

    public function index(): View
    {
        return view('helpdeskemailactivity::settings.suppressions', [
            'suppressions' => EmailSuppression::query()->latest('id')->paginate(25),
            'availableModules' => EmailLog::query()
                ->whereNotNull('module')
                ->distinct()
                ->orderBy('module')
                ->pluck('module'),
        ]);
    }

    public function store(StoreEmailSuppressionRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->suppressions->suppress(
            email: $validated['email'],
            reason: SuppressionReason::from($validated['reason']),
            module: $validated['module'] ?: null,
            notes: $validated['notes'] ?? null,
        );

        return redirect()
            ->route('settings.helpdeskemailactivity.suppressions.index')
            ->with('success', __('helpdeskemailactivity::emaillog.suppressions.created'));
    }

    public function destroy(EmailSuppression $suppression): RedirectResponse
    {
        $this->suppressions->unsuppress($suppression->id);

        return redirect()
            ->route('settings.helpdeskemailactivity.suppressions.index')
            ->with('success', __('helpdeskemailactivity::emaillog.suppressions.deleted'));
    }
}
