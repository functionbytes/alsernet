<?php

namespace Modules\HelpdeskEmailActivity\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Modules\HelpdeskEmailActivity\Http\Requests\Settings\StoreBounceMailboxRequest;
use Modules\HelpdeskEmailActivity\Http\Requests\Settings\UpdateBounceMailboxRequest;
use Modules\HelpdeskEmailActivity\Models\EmailLog;
use Modules\HelpdeskEmailActivity\Services\BounceMailboxesRepository;

/**
 * Buzones IMAP vigilados por email-logs:process-bounces (ver
 * BounceProcessorService). Antes de esta pantalla, el único buzón de
 * rebotes (el de Document) solo se podía configurar por tinker — no existía
 * ninguna UI para esto.
 */
class BounceMailboxesController extends Controller
{
    public function __construct(private readonly BounceMailboxesRepository $mailboxes)
    {
        $this->middleware('can:helpdeskemailactivity.settings.view')->only('index');
        $this->middleware('can:helpdeskemailactivity.settings.update')->only(['store', 'update', 'destroy']);
    }

    public function index(): View
    {
        return view('helpdeskemailactivity::settings.bounce-mailboxes', [
            'mailboxes' => $this->mailboxes->all(),
            'availableModules' => EmailLog::query()
                ->whereNotNull('module')
                ->distinct()
                ->orderBy('module')
                ->pluck('module'),
        ]);
    }

    public function store(StoreBounceMailboxRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['port'] = ($validated['port'] ?? null) ?: 993;
        $validated['encryption'] = ($validated['encryption'] ?? null) ?: 'ssl';
        $validated['folder'] = ($validated['folder'] ?? null) ?: 'INBOX';
        $validated['module_scope'] = $validated['module_scope'] ?? [];
        $validated['enabled'] = $request->boolean('enabled');

        $this->mailboxes->create($validated);

        return redirect()
            ->route('settings.helpdeskemailactivity.bounce-mailboxes.index')
            ->with('success', __('helpdeskemailactivity::emaillog.bounce_mailboxes.created'));
    }

    public function update(UpdateBounceMailboxRequest $request, string $mailbox): RedirectResponse
    {
        $validated = $request->validated();
        $validated['port'] = ($validated['port'] ?? null) ?: 993;
        $validated['encryption'] = ($validated['encryption'] ?? null) ?: 'ssl';
        $validated['folder'] = ($validated['folder'] ?? null) ?: 'INBOX';
        $validated['module_scope'] = $validated['module_scope'] ?? [];
        $validated['enabled'] = $request->boolean('enabled');

        $updated = $this->mailboxes->update($mailbox, $validated);

        abort_if($updated === null, 404);

        return redirect()
            ->route('settings.helpdeskemailactivity.bounce-mailboxes.index')
            ->with('success', __('helpdeskemailactivity::emaillog.bounce_mailboxes.updated'));
    }

    public function destroy(string $mailbox): RedirectResponse
    {
        $this->mailboxes->delete($mailbox);

        return redirect()
            ->route('settings.helpdeskemailactivity.bounce-mailboxes.index')
            ->with('success', __('helpdeskemailactivity::emaillog.bounce_mailboxes.deleted'));
    }
}
