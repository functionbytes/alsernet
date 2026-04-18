<?php

namespace Modules\Forms\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Modules\Forms\Jobs\ExportFormSubmissionsJob;
use Modules\Forms\Models\Form;
use Modules\Forms\Models\FormSubmission;
use Modules\Forms\Models\FormSubmissionNote;
use Modules\Forms\Services\FormEmailService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FormSubmissionController extends Controller
{
    public function __construct(
        private readonly FormEmailService $emailService
    ) {}

    public function index(Request $request, Form $form): View
    {
        $this->authorize('Forms.submissions.index');

        $query = FormSubmission::query()
            ->where('form_id', $form->id)
            ->with(['assignedTo', 'values']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('read')) {
            $query->where('is_read', (bool) $request->read);
        }

        if ($request->has('spam') && $request->spam !== '') {
            $query->where('is_spam', (bool) $request->spam);
        }

        if ($request->filled('starred')) {
            $query->where('is_starred', (bool) $request->starred);
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereIn('id', function ($sub) use ($search) {
                $sub->select('submission_id')
                    ->from('form_submission_values')
                    ->where('value', 'like', "%{$search}%");
            });
        }

        $submissions = $query->latest()->paginate(config('pagination.forms_submissions'))->appends($request->query());

        $row = FormSubmission::query()
            ->where('form_id', $form->id)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread,
                SUM(CASE WHEN is_spam = 1 THEN 1 ELSE 0 END) as spam,
                SUM(CASE WHEN is_starred = 1 THEN 1 ELSE 0 END) as starred
            ')
            ->first();

        $stats = [
            'total' => (int) $row->total,
            'unread' => (int) $row->unread,
            'spam' => (int) $row->spam,
            'starred' => (int) $row->starred,
        ];

        $users = User::query()->orderBy('firstname')->get(['id', 'firstname', 'lastname', 'email']);

        return view('forms::settings.submissions.index', compact('form', 'submissions', 'stats', 'users'));
    }

    public function show(Form $form, FormSubmission $submission): View
    {
        $this->authorize('Forms.submissions.index');

        $submission->load(['values', 'notes.user', 'form', 'user', 'assignedTo']);

        if (! $submission->is_read) {
            $submission->update(['is_read' => true]);
        }

        $users = User::query()->orderBy('firstname')->get(['id', 'firstname', 'lastname', 'email']);

        return view('forms::settings.submissions.show', compact('form', 'submission', 'users'));
    }

    public function destroy(Form $form, FormSubmission $submission): JsonResponse
    {
        $this->authorize('Forms.submissions.delete');

        $submission->delete();

        return response()->json(['success' => true]);
    }

    public function export(Request $request, Form $form): RedirectResponse
    {
        $this->authorize('Forms.submissions.export');

        ExportFormSubmissionsJob::dispatch(auth()->user(), $form, $request->all());

        return redirect()
            ->back()
            ->with('success', 'La exportación está en proceso. Recibirás una notificación cuando esté lista.');
    }

    public function exportDownload(Request $request): StreamedResponse|RedirectResponse
    {
        $this->authorize('Forms.submissions.export');

        $file = $request->query('file');

        if (! $file || str_contains($file, '/') || str_contains($file, '..')) {
            abort(400);
        }

        $filepath = "exports/forms/{$file}";

        if (! Storage::disk('local')->exists($filepath)) {
            return redirect()->back()->with('error', 'El archivo no existe o ya expiró.');
        }

        return Storage::disk('local')->download($filepath, $file);
    }

    public function bulkDelete(Request $request, Form $form): JsonResponse
    {
        $this->authorize('Forms.submissions.delete');

        $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $deleted = FormSubmission::query()
            ->where('form_id', $form->id)
            ->whereIn('id', $request->ids)
            ->delete();

        return response()->json(['success' => true, 'deleted' => $deleted]);
    }

    public function updateStatus(Request $request, Form $form, FormSubmission $submission): JsonResponse
    {
        $this->authorize('Forms.submissions.update');

        $request->validate([
            'status' => ['required', 'in:new,in_review,resolved,rejected'],
        ]);

        $submission->update(['status' => $request->status]);

        return response()->json(['success' => true, 'status' => $submission->status]);
    }

    public function toggleSpam(Request $request, Form $form, FormSubmission $submission): JsonResponse
    {
        $this->authorize('Forms.submissions.update');

        $submission->update(['is_spam' => ! $submission->is_spam]);

        return response()->json(['success' => true, 'is_spam' => $submission->is_spam]);
    }

    public function toggleStar(Request $request, Form $form, FormSubmission $submission): JsonResponse
    {
        $this->authorize('Forms.submissions.update');

        $submission->update(['is_starred' => ! $submission->is_starred]);

        return response()->json(['success' => true, 'is_starred' => $submission->is_starred]);
    }

    public function assign(Request $request, Form $form, FormSubmission $submission): JsonResponse
    {
        $request->validate([
            'assigned_to' => ['nullable', 'exists:users,id'],
        ]);

        $submission->update(['assigned_to' => $request->assigned_to]);

        return response()->json(['success' => true]);
    }

    public function addNote(Request $request, Form $form, FormSubmission $submission): JsonResponse
    {
        $request->validate([
            'note' => ['required', 'string', 'max:5000'],
        ]);

        $note = FormSubmissionNote::query()->create([
            'submission_id' => $submission->id,
            'user_id' => auth()->id(),
            'note' => $request->note,
        ]);

        $note->load('user');

        return response()->json([
            'success' => true,
            'note' => [
                'id' => $note->id,
                'note' => $note->note,
                'created_at' => $note->created_at->diffForHumans(),
                'user' => [
                    'id' => $note->user->id,
                    'full_name' => $note->user?->full_name ?? 'Sistema',
                ],
            ],
        ]);
    }

    public function deleteNote(Request $request, Form $form, FormSubmission $submission, FormSubmissionNote $note): JsonResponse
    {
        if ($note->submission_id !== $submission->id) {
            abort(404);
        }

        $isAuthor = $note->user_id === auth()->id();
        $isAdmin = auth()->user()?->hasRole('admin') || auth()->user()?->hasRole('super-admin');

        if (! $isAuthor && ! $isAdmin) {
            abort(403, 'No tienes permiso para eliminar esta nota.');
        }

        $note->delete();

        return response()->json(['success' => true]);
    }

    public function resendEmail(Request $request, Form $form, FormSubmission $submission): JsonResponse
    {
        $this->authorize('Forms.submissions.update');

        $request->validate([
            'type' => ['required', 'in:admin,client'],
        ]);

        if ($request->type === 'admin') {
            $this->emailService->notifyAdmin($form, $submission);
        } else {
            $this->emailService->sendConfirmation($form, $submission);
        }

        return response()->json(['success' => true, 'message' => 'Email enviado correctamente']);
    }

    public function exportPdf(Form $form, FormSubmission $submission): Response
    {
        $this->authorize('Forms.submissions.index');

        $submission->load(['values', 'form', 'user', 'assignedTo']);

        $pdf = Pdf::loadView('forms::settings.submissions.pdf', compact('form', 'submission'));

        return $pdf->download("submission-{$submission->id}.pdf");
    }

    public function bulkAnonymize(Request $request, Form $form): JsonResponse
    {
        $this->authorize('Forms.submissions.delete');

        $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $submissions = FormSubmission::query()
            ->where('form_id', $form->id)
            ->whereIn('id', $request->ids)
            ->get();

        $piiFieldTypes = ['email', 'tel'];
        $piiKeywords = ['email', 'phone', 'name', 'telefon', 'nombre'];

        foreach ($submissions as $submission) {
            $submission->update([
                'ip_address' => null,
                'user_agent' => null,
                'country' => null,
                'city' => null,
                'utm_source' => null,
                'utm_medium' => null,
                'utm_campaign' => null,
                'utm_term' => null,
                'utm_content' => null,
            ]);

            $submission->values()
                ->where(function ($q) use ($piiFieldTypes, $piiKeywords) {
                    $q->whereIn('field_type', $piiFieldTypes);
                    foreach ($piiKeywords as $keyword) {
                        $q->orWhere('field_key', 'like', "%{$keyword}%");
                    }
                })
                ->update(['value' => '[anonimizado]']);
        }

        return response()->json(['success' => true, 'anonymized' => $submissions->count()]);
    }
}
