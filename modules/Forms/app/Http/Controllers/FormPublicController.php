<?php

namespace Modules\Forms\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Forms\Models\Form;
use Modules\Forms\Models\FormAbandonTracking;
use Modules\Forms\Models\FormAccessToken;
use Modules\Forms\Services\FormSubmissionService;

class FormPublicController extends Controller
{
    public function __construct(
        private readonly FormSubmissionService $submissionService
    ) {}

    public function show(string $slug): View
    {
        $form = Form::query()
            ->where('slug', $slug)
            ->active()
            ->with(['fields' => fn ($q) => $q->visible()->ordered()])
            ->firstOrFail();

        return view('forms::public.show', compact('form'));
    }

    public function submit(Request $request, string $slug): JsonResponse|RedirectResponse
    {
        $form = Form::query()
            ->with('fields')
            ->where('slug', $slug)
            ->active()
            ->firstOrFail();

        if ($form->is_expired) {
            return $this->submitError($request, 'Este formulario ha expirado.');
        }

        if ($form->is_full) {
            return $this->submitError($request, 'Este formulario ha alcanzado el límite de envíos.');
        }

        if (! $form->isAccessibleBy(auth()->user())) {
            return $this->submitError($request, 'No tienes permiso para enviar este formulario.', 403);
        }

        if ($form->is_password_protected) {
            $hasAccess = session("form_access_{$form->id}")
                || hash_equals((string) $form->password, (string) $request->input('_password'));

            if (! $hasAccess) {
                return $this->submitError($request, 'Contraseña incorrecta.', 403);
            }
        }

        if ($form->honeypot_enabled && $request->input('_hp') !== null && $request->input('_hp') !== '') {
            return $request->expectsJson()
                ? response()->json(['success' => true])
                : redirect()->back()->with('success', $form->success_message ?? 'Formulario enviado correctamente.');
        }

        $rules = ['_hp' => 'nullable|size:0'];
        $attributeNames = [];

        $fields = $form->fields()->visible()->ordered()->get();

        foreach ($fields as $field) {
            if ($field->isLayoutField()) {
                continue;
            }

            $fieldRules = $field->is_required ? ['required'] : ['nullable'];

            match ($field->type) {
                'email' => $fieldRules[] = 'email',
                'url' => $fieldRules[] = 'url',
                'number', 'slider', 'rating', 'nps' => $fieldRules[] = 'numeric',
                'date' => $fieldRules[] = 'date',
                'file' => array_push($fieldRules, 'file', 'max:'.(config('forms.max_file_size_mb', 10) * 1024)),
                default => null,
            };

            foreach ($field->validation_rules ?? [] as $rule) {
                $fieldRules[] = $rule;
            }

            $rules[$field->key] = $fieldRules;
            $attributeNames[$field->key] = $field->label;
        }

        if ($form->captcha_enabled && class_exists(\Modules\Captcha\Facades\Captcha::class)) {
            $captchaRules = \Modules\Captcha\Facades\Captcha::rules();
            if (! empty($captchaRules)) {
                $rules = array_merge($rules, $captchaRules);
            }
        }

        $request->validate($rules, [], $attributeNames);

        $submission = $this->submissionService->process($form, $request);

        if ($request->filled('_session_token')) {
            FormAbandonTracking::query()
                ->where('form_id', $form->id)
                ->where('session_token', $request->_session_token)
                ->update(['is_completed' => true]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $form->success_message ?? 'Formulario enviado correctamente.',
                'redirect' => $form->redirect_url,
            ]);
        }

        if ($form->redirect_url) {
            $parsedRedirect = parse_url($form->redirect_url);
            $parsedApp = parse_url(config('app.url'));

            $isSameDomain = isset($parsedRedirect['host'])
                && $parsedRedirect['host'] === ($parsedApp['host'] ?? '');
            $isRelative = ! isset($parsedRedirect['host']);

            if ($isRelative || $isSameDomain) {
                return redirect()->to($form->redirect_url)
                    ->with('success', $form->success_message ?? 'Formulario enviado correctamente.');
            }
        }

        return redirect()->back()
            ->with('success', $form->success_message ?? 'Formulario enviado correctamente.');
    }

    public function embed(string $slug): View
    {
        $form = Form::query()
            ->with(['fields' => fn ($q) => $q->visible()->ordered()])
            ->where('slug', $slug)
            ->active()
            ->firstOrFail();

        $captchaEnabled = $form->captcha_enabled
            && class_exists(\Modules\Captcha\Facades\Captcha::class);

        return view('forms::public.embed', compact('form', 'captchaEnabled'));
    }

    public function accessByToken(Request $request, string $token): RedirectResponse
    {
        $accessToken = FormAccessToken::query()
            ->where('token', $token)
            ->with('form')
            ->firstOrFail();

        if (! $accessToken->isValid()) {
            abort(403, 'Este enlace de acceso no es válido o ha expirado.');
        }

        session(["form_access_{$accessToken->form_id}" => true]);

        $accessToken->markUsed();

        return redirect()->route('forms.public.embed', ['slug' => $accessToken->form->slug]);
    }

    public function trackAbandon(Request $request, string $slug): JsonResponse
    {
        $form = Form::query()
            ->where('slug', $slug)
            ->active()
            ->firstOrFail();

        $validated = $request->validate([
            'session_token' => ['required', 'string', 'max:255'],
            'partial_data' => ['nullable', 'json'],
            'current_step' => ['nullable', 'integer', 'min:1'],
            'last_field_key' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
        ]);

        $partialData = $validated['partial_data'] ? json_decode($validated['partial_data'], true) : null;

        $existing = FormAbandonTracking::query()
            ->where('form_id', $form->id)
            ->where('session_token', $validated['session_token'])
            ->first();

        if ($existing) {
            $existing->update([
                'partial_data' => $partialData,
                'current_step' => $validated['current_step'] ?? $existing->current_step,
                'last_field_key' => $validated['last_field_key'] ?? $existing->last_field_key,
                'email' => $validated['email'] ?? $existing->email,
                'last_activity_at' => now(),
            ]);
        } else {
            FormAbandonTracking::query()->create([
                'form_id' => $form->id,
                'session_token' => $validated['session_token'],
                'partial_data' => $partialData,
                'current_step' => $validated['current_step'] ?? 1,
                'last_field_key' => $validated['last_field_key'] ?? null,
                'email' => $validated['email'] ?? null,
                'started_at' => now(),
                'last_activity_at' => now(),
                'is_completed' => false,
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function restoreAbandoned(Request $request, string $slug): JsonResponse
    {
        $form = Form::query()
            ->where('slug', $slug)
            ->active()
            ->firstOrFail();

        $tracking = FormAbandonTracking::query()
            ->where('form_id', $form->id)
            ->where('session_token', $request->input('token'))
            ->where('is_completed', false)
            ->first();

        if (! $tracking) {
            return response()->json(['success' => false, 'data' => null]);
        }

        return response()->json([
            'success' => true,
            'data' => $tracking->partial_data,
        ]);
    }

    private function submitError(Request $request, string $message, int $status = 422): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => $message], $status);
        }

        return redirect()->back()->with('error', $message);
    }
}
