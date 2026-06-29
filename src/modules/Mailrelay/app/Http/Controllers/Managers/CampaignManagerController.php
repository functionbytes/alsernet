<?php

namespace Modules\Mailrelay\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Mailer\Models\MailerLang;
use Modules\Mailer\Models\MailerTemplate;
use Modules\Mailrelay\Entities\Campaign;
use Modules\Mailrelay\Entities\MailProvider;
use Modules\Mailrelay\Enums\CampaignStatus;
use Modules\Mailrelay\Http\Requests\Managers\ScheduleCampaignRequest;
use Modules\Mailrelay\Http\Requests\Managers\SendCampaignManagerRequest;
use Modules\Mailrelay\Http\Requests\Managers\StoreCampaignRequest;
use Modules\Mailrelay\Http\Requests\Managers\UpdateCampaignRequest;
use Modules\Mailrelay\Services\CampaignService;

/**
 * Controlador unificado para gestión de Campaigns
 * Integra templates de Mailer + providers multi-vendor + envío de campaigns
 */
class CampaignManagerController extends Controller
{
    protected CampaignService $campaignService;

    public function __construct(CampaignService $campaignService)
    {
        $this->campaignService = $campaignService;
    }

    /**
     * Listar todas las campaigns
     */
    public function index(Request $request)
    {
        $query = Campaign::with(['mailerTemplate', 'mailProvider', 'language'])
            ->orderBy('created_at', 'desc');

        // Filtros
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('provider')) {
            $query->where('mail_provider_id', $request->provider);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('subject', 'like', "%{$request->search}%");
            });
        }

        $campaigns = $query->paginate(15)->withQueryString();

        $providers = MailProvider::where('is_active', true)->get();

        $statuses = [
            CampaignStatus::DRAFT->value => 'Borrador',
            CampaignStatus::SCHEDULED->value => 'Programado',
            CampaignStatus::SENDING->value => 'Enviando',
            CampaignStatus::SENT->value => 'Enviado',
            CampaignStatus::FAILED->value => 'Fallido',
        ];

        return view('mailrelay::campaigns.index', [
            'campaigns' => $campaigns,
            'providers' => $providers,
            'statuses' => $statuses,
            'pageTitle' => 'Campaigns de Email',
            'breadcrumb' => 'Marketing / Campaigns',
        ]);
    }

    /**
     * Mostrar formulario de creación
     */
    public function create()
    {
        // Obtener templates de Mailer disponibles
        $templates = MailerTemplate::where('is_enabled', true)
            ->orderBy('name')
            ->get();

        // Obtener providers activos
        $providers = MailProvider::where('is_active', true)
            ->orderBy('is_default', 'desc')
            ->orderBy('priority', 'desc')
            ->get();

        // Obtener idiomas disponibles
        $languages = MailerLang::where('is_active', true)->get();

        return view('mailrelay::campaigns.create', [
            'templates' => $templates,
            'providers' => $providers,
            'languages' => $languages,
            'pageTitle' => 'Nueva Campaign',
            'breadcrumb' => 'Marketing / Campaigns / Nueva',
        ]);
    }

    /**
     * Guardar nueva campaign
     */
    public function store(StoreCampaignRequest $request)
    {
        $validated = $request->validated();

        try {
            $campaign = $this->campaignService->create($validated);

            return redirect()
                ->route('managers.mailrelay.campaigns.edit', $campaign->id)
                ->with('success', "Campaign '{$campaign->name}' creada exitosamente");
        } catch (\Exception $e) {
            Log::error('Mailrelay campaign create failed', ['error' => $e->getMessage()]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al crear campaign. Por favor, inténtalo de nuevo.');
        }
    }

    /**
     * Mostrar detalles de una campaign
     */
    public function show(int $id)
    {
        $campaign = Campaign::with(['mailerTemplate', 'mailProvider', 'language', 'analytics'])
            ->findOrFail($id);

        // Obtener estadísticas si la campaign fue enviada
        $stats = null;
        if ($campaign->provider_campaign_id) {
            $stats = $this->campaignService->getStats($campaign);
        }

        return view('mailrelay::campaigns.show', [
            'campaign' => $campaign,
            'stats' => $stats,
            'pageTitle' => $campaign->name,
            'breadcrumb' => 'Marketing / Campaigns / '.$campaign->name,
        ]);
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit(int $id)
    {
        $campaign = Campaign::with(['mailerTemplate', 'mailProvider'])->findOrFail($id);

        // Solo permitir editar si está en draft o failed
        if (! in_array($campaign->status, [CampaignStatus::DRAFT, CampaignStatus::FAILED])) {
            return redirect()
                ->route('managers.mailrelay.campaigns.show', $id)
                ->with('error', 'Solo se pueden editar campaigns en estado DRAFT o FAILED');
        }

        $templates = MailerTemplate::where('is_enabled', true)->orderBy('name')->get();
        $providers = MailProvider::where('is_active', true)->orderBy('priority', 'desc')->get();
        $languages = MailerLang::where('is_active', true)->get();

        return view('mailrelay::campaigns.edit', [
            'campaign' => $campaign,
            'templates' => $templates,
            'providers' => $providers,
            'languages' => $languages,
            'pageTitle' => 'Editar Campaign',
            'breadcrumb' => 'Marketing / Campaigns / Editar',
        ]);
    }

    /**
     * Actualizar campaign
     */
    public function update(UpdateCampaignRequest $request, int $id)
    {
        $campaign = Campaign::findOrFail($id);

        $validated = $request->validated();

        try {
            $this->campaignService->update($campaign, $validated);

            return redirect()
                ->route('managers.mailrelay.campaigns.edit', $id)
                ->with('success', 'Campaign actualizada exitosamente');
        } catch (\Exception $e) {
            Log::error('Mailrelay campaign update failed', ['error' => $e->getMessage()]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al actualizar. Por favor, inténtalo de nuevo.');
        }
    }

    /**
     * Eliminar campaign
     */
    public function destroy(int $id)
    {
        $campaign = Campaign::findOrFail($id);

        try {
            $name = $campaign->name;
            $this->campaignService->delete($campaign);

            return redirect()
                ->route('managers.mailrelay.campaigns.index')
                ->with('success', "Campaign '{$name}' eliminada exitosamente");
        } catch (\Exception $e) {
            Log::error('Mailrelay campaign delete failed', ['error' => $e->getMessage()]);

            return redirect()
                ->back()
                ->with('error', 'Error al eliminar. Por favor, inténtalo de nuevo.');
        }
    }

    /**
     * Duplicar campaign
     */
    public function duplicate(int $id)
    {
        $campaign = Campaign::findOrFail($id);

        try {
            $newCampaign = $this->campaignService->duplicate($campaign);

            return redirect()
                ->route('managers.mailrelay.campaigns.edit', $newCampaign->id)
                ->with('success', "Campaign duplicada como '{$newCampaign->name}'");
        } catch (\Exception $e) {
            Log::error('Mailrelay campaign duplicate failed', ['error' => $e->getMessage()]);

            return redirect()
                ->back()
                ->with('error', 'Error al duplicar. Por favor, inténtalo de nuevo.');
        }
    }

    /**
     * Preview de la campaign
     */
    public function preview(int $id)
    {
        $campaign = Campaign::findOrFail($id);

        try {
            $html = $this->campaignService->getPreview($campaign);

            return view('mailrelay::campaigns.preview', [
                'campaign' => $campaign,
                'html' => $html,
            ]);
        } catch (\Exception $e) {
            Log::error('Mailrelay campaign preview failed', ['error' => $e->getMessage()]);

            return redirect()
                ->back()
                ->with('error', 'Error generando preview. Por favor, inténtalo de nuevo.');
        }
    }

    /**
     * Enviar email de prueba
     */
    public function sendTest(Request $request, int $id)
    {
        $campaign = Campaign::findOrFail($id);

        // Single-rule inline validate is justified here (trivial, no reuse)
        $validated = $request->validate(['test_email' => 'required|email']);

        try {
            $result = $this->campaignService->sendTest($campaign, $validated['test_email']);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Ha ocurrido un error. Por favor, inténtalo de nuevo.',
            ], 500);
        }
    }

    /**
     * Enviar campaign a lista de destinatarios
     */
    public function send(SendCampaignManagerRequest $request, int $id)
    {
        $campaign = Campaign::findOrFail($id);

        $validated = $request->validated();

        try {
            if ($validated['send_async'] ?? false) {
                // Envío asíncrono (queue)
                $this->campaignService->sendAsync($campaign, $validated['recipients']);

                return response()->json([
                    'success' => true,
                    'message' => 'Campaign encolada para envío',
                    'async' => true,
                ]);
            } else {
                // Envío síncrono
                $result = $this->campaignService->send($campaign, $validated['recipients']);

                return response()->json($result);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Ha ocurrido un error. Por favor, inténtalo de nuevo.',
            ], 500);
        }
    }

    /**
     * Programar envío de campaign
     */
    public function schedule(ScheduleCampaignRequest $request, int $id)
    {
        $campaign = Campaign::findOrFail($id);

        $validated = $request->validated();

        try {
            $scheduledAt = new \DateTime($validated['scheduled_at']);
            $this->campaignService->schedule($campaign, $scheduledAt);

            return response()->json([
                'success' => true,
                'message' => 'Campaign programada exitosamente',
                'scheduled_at' => $scheduledAt->format('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Ha ocurrido un error. Por favor, inténtalo de nuevo.',
            ], 500);
        }
    }
}
