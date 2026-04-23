<?php

namespace Modules\Attention\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\Attention\Enums\AttentionStatus;
use Modules\Attention\Enums\ResponseType;
use Modules\Attention\Events\AttentionCreated;
use Modules\Attention\Http\Requests\PublicSubmitAttentionRequest;
use Modules\Attention\Models\Attention;
use Modules\Attention\Models\AttentionCategory;
use Modules\Attention\Models\AttentionSede;
use Modules\Attention\Models\AttentionType;
use Modules\Attention\Services\AttentionNotificationService;
use Modules\Attention\Services\AttentionRoutingService;
use Throwable;

/**
 * Public-facing controller for peticiones form and tracking
 * No authentication required - accessible by citizens
 */
class AttentionPublicController extends Controller
{
    public function __construct(
        protected AttentionNotificationService $notificationService
    ) {}

    /**
     * Show the public peticiones submission form
     * GET /peticiones
     */
    public function form(): View
    {
        return view('template::views.peticiones.form', [
            'types' => AttentionType::where('is_active', true)->orderBy('name')->limit(200)->get(),
            'categories' => AttentionCategory::where('is_active', true)->orderBy('name')->limit(200)->get(),
            'sedes' => AttentionSede::where('is_active', true)->orderBy('name')->limit(200)->get(),
            'responseTypes' => ResponseType::cases(),
        ]);
    }

    /**
     * Process public peticiones submission
     * POST /peticiones
     */
    public function submit(PublicSubmitAttentionRequest $request): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $attention = Attention::create([
                'radicado' => Attention::generateRadicado(),
                'type_id' => $request->type_id,
                'category_id' => $request->category_id,
                'sede_id' => $request->sede_id,
                'subject' => $request->subject,
                'description' => $request->description,
                'is_anonymous' => $request->boolean('is_anonymous', false),
                'customer_firstname' => $request->customer_firstname,
                'customer_lastname' => $request->customer_lastname,
                'customer_email' => $request->customer_email,
                'customer_cellphone' => $request->customer_cellphone,
                'customer_dni' => $request->customer_dni,
                'customer_address' => $request->customer_address,
                'response_type' => $request->response_type ? ResponseType::from($request->response_type) : ResponseType::EMAIL,
                'status' => AttentionStatus::RECEIVED,
            ]);

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $attention->addDocument($file);
                }
            }

            $attention->logAction('created', 'peticiones creado por el ciudadano via formulario publico', null);

            app(AttentionRoutingService::class)->applyRules($attention);

            if (! $attention->is_anonymous && $attention->customer_email) {
                try {
                    $this->notificationService->sendConfirmation($attention);
                } catch (Throwable $e) {
                    Log::error('Failed to send confirmation email', [
                        'radicado' => $attention->radicado,
                        'code' => $e->getCode(),
                        'file' => class_basename($e->getFile()),
                        'line' => $e->getLine(),
                    ]);
                }
            }

            DB::commit();

            AttentionCreated::dispatch($attention);

            return redirect()
                ->route('peticiones.success', $attention->radicado)
                ->with('success', 'Su solicitud ha sido radicada exitosamente.');

        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('Error creating public attention', [
                'code' => $e->getCode(),
                'file' => class_basename($e->getFile()),
                'line' => $e->getLine(),
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Ocurrio un error al procesar su solicitud. Por favor intentelo nuevamente.');
        }
    }

    /**
     * Show success page after submission
     * GET /peticiones/success/{radicado}
     */
    public function showSuccess(string $radicado): View
    {
        $attention = Attention::byRadicado($radicado)
            ->select(['radicado', 'subject', 'status', 'customer_email', 'is_anonymous', 'created_at'])
            ->firstOrFail();

        return view('template::views.peticiones.success', [
            'attention' => $attention,
        ]);
    }

    /**
     * Show tracking search form (handles ?radicado= query param)
     * GET /peticiones/tracking
     */
    public function trackingForm(Request $request): RedirectResponse|View
    {
        if ($radicado = $request->query('radicado')) {
            return redirect()->route('peticiones.tracking.result', $radicado);
        }

        return view('template::views.peticiones.tracking');
    }

    /**
     * Show tracking result
     * GET /peticiones/tracking/{radicado}
     */
    public function trackingResult(string $radicado): View
    {
        $attention = Attention::with(['type', 'category', 'sede', 'department'])
            ->byRadicado($radicado)
            ->first();

        return view('template::views.peticiones.tracking', [
            'attention' => $attention,
            'radicado' => $radicado,
        ]);
    }
}
