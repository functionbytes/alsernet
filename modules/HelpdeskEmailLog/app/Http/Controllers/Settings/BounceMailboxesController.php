<?php

namespace Modules\HelpdeskEmailLog\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Modules\HelpdeskEmailLog\Http\Requests\Settings\StoreBounceMailboxRequest;
use Modules\HelpdeskEmailLog\Http\Requests\Settings\UpdateBounceMailboxRequest;
use Modules\HelpdeskEmailLog\Models\EmailLog;
use Modules\HelpdeskEmailLog\Services\BounceMailboxesRepository;

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
        $this->middleware('can:helpdeskemaillog.settings.view')->only('index');
        $this->middleware('can:helpdeskemaillog.settings.update')->only(['store', 'update', 'destroy']);
    }

    public function index(): View
    {
        return view('helpdeskemaillog::settings.bounce-mailboxes', [
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
            ->route('settings.helpdeskemaillog.bounce-mailboxes.index')
            ->with('success', __('helpdeskemaillog::emaillog.bounce_mailboxes.created'));
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
            ->route('settings.helpdeskemaillog.bounce-mailboxes.index')
            ->with('success', __('helpdeskemaillog::emaillog.bounce_mailboxes.updated'));
    }

    public function destroy(string $mailbox): RedirectResponse
    {
        $this->mailboxes->delete($mailbox);

        return redirect()
            ->route('settings.helpdeskemaillog.bounce-mailboxes.index')
            ->with('success', __('helpdeskemaillog::emaillog.bounce_mailboxes.deleted'));
    }
}
