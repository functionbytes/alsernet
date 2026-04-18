<?php

namespace Modules\Forms\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Modules\Forms\Models\Form;
use Modules\Forms\Models\FormSubmission;
use Modules\Forms\Models\FormSubmissionAction;
use Modules\Forms\Models\FormSubmissionFile;

class FormsInboxController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('Forms.submissions.index');

        $query = FormSubmission::query()->with(['form', 'assignedTo', 'values']);

        $this->applyFilters($query, $request);

        $submissions = $query->latest()->paginate(config('pagination.forms_submissions'))->withQueryString();
        $stats = $this->buildStats();
        $forms = Form::orderBy('name')->get(['id', 'name']);
        $users = User::orderBy('firstname')->get(['id', 'firstname', 'lastname']);

        return view('forms::inbox.index', [
            'submissions' => $submissions,
            'forms' => $forms,
            'users' => $users,
            'stats' => $stats,
            'search' => $request->input('search'),
            'formId' => $request->input('form_id'),
            'statusFilter' => $request->input('status'),
            'assignedTo' => $request->input('assigned_to'),
            'isSpam' => $request->input('is_spam', '0'),
            'isStarred' => $request->input('is_starred'),
            'dateFrom' => $request->input('date_from'),
            'dateTo' => $request->input('date_to'),
        ]);
    }

    public function show(FormSubmission $submission): View
    {
        $this->authorize('Forms.submissions.index');

        $submission->load(['form', 'assignedTo', 'values', 'notes.user', 'user']);

        if (! $submission->is_read) {
            $submission->update(['is_read' => true]);
        }

        $users = User::orderBy('firstname')->get(['id', 'firstname', 'lastname']);

        return view('forms::inbox.show', compact('submission', 'users'));
    }

    public function manage(FormSubmission $submission): View
    {
        $this->authorize('Forms.submissions.index');

        $submission->load([
            'form', 'assignedTo', 'values', 'notes.user', 'user',
            'actions.user', 'emails.sender', 'files.uploader',
        ]);

        if (! $submission->is_read) {
            $submission->update(['is_read' => true]);
        }

        $users = User::orderBy('firstname')->get(['id', 'firstname', 'lastname']);
        $citizenInfo = $this->extractCitizenInfo($submission);

        return view('forms::inbox.manage', compact('submission', 'users', 'citizenInfo'));
    }

    public function updateManage(Request $request, FormSubmission $submission): JsonResponse
    {
        $this->authorize('Forms.submissions.edit');

        $changes = [];

        if ($request->filled('status') && $request->status !== $submission->status) {
            $old = $submission->status;
            $submission->status = $request->status;
            $changes[] = 'Estado actualizado';

            $statusLabels = [
                'new' => 'Nuevo',
                'in_review' => 'En revisión',
                'resolved' => 'Resuelto',
                'rejected' => 'Rechazado',
            ];

            FormSubmissionAction::create([
                'submission_id' => $submission->id,
                'user_id' => auth()->id(),
                'action' => 'status_changed',
                'description' => sprintf(
                    '%s → %s',
                    $statusLabels[$old] ?? $old,
                    $statusLabels[$request->status] ?? $request->status
                ),
            ]);
        }

        if ($request->has('assigned_to') && $request->assigned_to != $submission->assigned_to) {
            $submission->assigned_to = $request->assigned_to ?: null;
            $changes[] = 'Asignación actualizada';

            $assignedName = $submission->assigned_to
                ? (User::find($submission->assigned_to)?->full_name ?? 'Usuario')
                : 'Sin asignar';

            FormSubmissionAction::create([
                'submission_id' => $submission->id,
                'user_id' => auth()->id(),
                'action' => 'assigned',
                'description' => 'Asignado a: '.$assignedName,
            ]);
        }

        if (empty($changes)) {
            return response()->json(['success' => false, 'message' => 'No hay cambios para guardar'], 422);
        }

        $submission->save();

        return response()->json(['success' => true, 'message' => 'Cambios guardados correctamente']);
    }

    public function uploadFile(Request $request, FormSubmission $submission): JsonResponse
    {
        $this->authorize('Forms.submissions.edit');

        $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
        ]);

        $file = $request->file('file');
        $filename = $file->getClientOriginalName();
        $path = $file->store("form-submissions/{$submission->id}", 'public');

        $record = FormSubmissionFile::create([
            'submission_id' => $submission->id,
            'uploaded_by' => auth()->id(),
            'filename' => $filename,
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        FormSubmissionAction::create([
            'submission_id' => $submission->id,
            'user_id' => auth()->id(),
            'action' => 'file_uploaded',
            'description' => 'Archivo adjunto: '.$filename,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Archivo cargado correctamente.',
            'file' => [
                'id' => $record->id,
                'filename' => $record->filename,
                'url' => $record->url,
                'size' => $record->size_formatted,
                'mime_type' => $record->mime_type,
            ],
        ]);
    }

    public function bulkAction(Request $request): JsonResponse
    {
        $this->authorize('Forms.submissions.edit');

        $validated = $request->validate([
            'action' => ['required', 'string', 'in:mark_read,mark_unread,mark_spam,unmark_spam,delete'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $query = FormSubmission::query()->whereIn('id', $validated['ids']);
        $count = $query->count();

        match ($validated['action']) {
            'mark_read' => $query->update(['is_read' => true]),
            'mark_unread' => $query->update(['is_read' => false]),
            'mark_spam' => $query->update(['is_spam' => true]),
            'unmark_spam' => $query->update(['is_spam' => false]),
            'delete' => $query->delete(),
        };

        return response()->json(['success' => true, 'message' => "{$count} registro(s) procesados.", 'count' => $count]);
    }

    public function deleteFile(FormSubmission $submission, FormSubmissionFile $file): JsonResponse
    {
        $this->authorize('Forms.submissions.delete');

        if ($file->submission_id !== $submission->id) {
            return response()->json(['success' => false, 'message' => 'Archivo no pertenece a esta solicitud.'], 403);
        }

        Storage::disk('public')->delete($file->path);

        FormSubmissionAction::create([
            'submission_id' => $submission->id,
            'user_id' => auth()->id(),
            'action' => 'file_deleted',
            'description' => 'Archivo eliminado: '.$file->filename,
        ]);

        $file->delete();

        return response()->json(['success' => true, 'message' => 'Archivo eliminado correctamente.']);
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        $isSpam = $request->input('is_spam', '0');

        if ($isSpam !== '') {
            $query->where('is_spam', (bool) $isSpam);
        }

        if ($request->filled('form_id')) {
            $query->where('form_id', $request->integer('form_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->input('is_read') !== null && $request->input('is_read') !== '') {
            $query->where('is_read', (bool) $request->integer('is_read'));
        }

        if ($request->input('is_starred') !== null && $request->input('is_starred') !== '') {
            $query->where('is_starred', (bool) $request->integer('is_starred'));
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->integer('assigned_to'));
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('created_at', [
                $request->input('date_from'),
                $request->input('date_to'),
            ]);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('values', fn ($q) => $q->where('value', 'like', "%{$search}%"));
        }
    }

    /**
     * @return array{total: int, unread: int, spam: int, starred: int, assigned_to_me: int}
     */
    private function buildStats(): array
    {
        return [
            'total' => FormSubmission::query()->where('is_spam', false)->count(),
            'unread' => FormSubmission::query()->where('is_read', false)->where('is_spam', false)->count(),
            'spam' => FormSubmission::query()->where('is_spam', true)->count(),
            'starred' => FormSubmission::query()->where('is_starred', true)->count(),
            'assigned_to_me' => FormSubmission::query()->where('assigned_to', auth()->id())->count(),
        ];
    }

    /**
     * Extrae información de contacto del ciudadano desde los valores del formulario.
     *
     * @return array{name: ?string, lastname: ?string, phone: ?string}
     */
    private function extractCitizenInfo(FormSubmission $submission): array
    {
        $info = ['name' => null, 'lastname' => null, 'phone' => null];

        foreach ($submission->values as $value) {
            $key = strtolower($value->field_key ?? '');

            if (! $info['name'] && (
                str_contains($key, 'nombre') || str_contains($key, 'name') ||
                str_contains($key, 'first') || str_contains($key, 'primer')
            )) {
                $info['name'] = $value->value;

                continue;
            }

            if (! $info['lastname'] && (
                str_contains($key, 'apellido') || str_contains($key, 'lastname') ||
                str_contains($key, 'last') || str_contains($key, 'segundo')
            )) {
                $info['lastname'] = $value->value;

                continue;
            }

            if (! $info['phone'] && (
                str_contains($key, 'telefon') || str_contains($key, 'phone') ||
                str_contains($key, 'celular') || str_contains($key, 'movil') ||
                str_contains($key, 'tel')
            )) {
                $info['phone'] = $value->value;
            }
        }

        return $info;
    }
}
