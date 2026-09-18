<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Helpdesk\Http\Requests\Managers\Settings\UpdateWhatsAppPricingRequest;
use Modules\Helpdesk\Http\Requests\Managers\Settings\WhatsAppUsageReportRequest;
use Modules\Helpdesk\Models\Setting;
use Modules\Helpdesk\Models\WhatsAppUsage;
use Modules\Helpdesk\Services\Exports\CsvStreamExporter;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Reporte de gasto de WhatsApp desde el ledger `helpdesk_whatsapp_usage`.
 * Meta factura por categoría de conversación (marketing/utility/authentication
 * vía plantilla HSM); "service" es la respuesta de texto del agente dentro de
 * la ventana de 24h, gratis. Meta no expone tarifas por país vía API, así que
 * el coste se estima con una tarifa fija por categoría configurada a mano
 * (ver updatePricing()) — no varía por país de destino.
 */
class WhatsAppUsageController extends Controller
{
    private const PRICING_CATEGORIES = ['marketing', 'utility', 'authentication'];

    public function __construct()
    {
        $this->middleware('can:helpdesk.whatsapp-templates.view')->only(['index', 'data', 'export']);
        $this->middleware('can:helpdesk.whatsapp-templates.manage')->only(['updatePricing']);
    }

    public function index(): View
    {
        return view('helpdesk::settings.whatsapp-usage.index', [
            'pricing' => $this->pricing(),
        ]);
    }

    public function updatePricing(UpdateWhatsAppPricingRequest $request): RedirectResponse
    {
        $data = $request->validated();

        foreach (self::PRICING_CATEGORIES as $category) {
            Setting::set("whatsapp_pricing.{$category}", (string) $data[$category], 'whatsapp_pricing');
        }

        return back()->with('success', 'Tarifas de WhatsApp actualizadas.');
    }

    public function data(WhatsAppUsageReportRequest $request): JsonResponse
    {
        [$from, $to] = $this->resolveRange($request->validated());

        $totalsRow = WhatsAppUsage::query()
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('COUNT(*) as sent, COALESCE(SUM(success), 0) as success_sent')
            ->first();

        $sent = (int) ($totalsRow->sent ?? 0);
        $successSent = (int) ($totalsRow->success_sent ?? 0);
        $byCategory = $this->aggregateUsageByCategory($from, $to);

        return response()->json([
            'success' => true,
            'range' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'totals' => [
                'sent' => $sent,
                'success_sent' => $successSent,
                'failed_sent' => $sent - $successSent,
                'estimated_cost_eur' => $this->estimateCost($byCategory),
            ],
            'by_category' => $byCategory,
            'top_templates' => $this->topTemplates($from, $to),
            'daily' => $this->dailyUsage($from, $to),
        ]);
    }

    /**
     * Exporta a CSV el desglose diario del rango filtrado (mismo cálculo que
     * alimenta el gráfico de tendencia), para llevar el consumo a contabilidad.
     */
    public function export(WhatsAppUsageReportRequest $request, CsvStreamExporter $exporter): StreamedResponse
    {
        [$from, $to] = $this->resolveRange($request->validated());

        $rows = $this->dailyUsage($from, $to);

        return $exporter->stream(
            'whatsapp-consumo-'.$from->format('Ymd').'-'.$to->format('Ymd').'.csv',
            ['Fecha', 'Enviados', 'Confirmados', 'Fallidos'],
            (function () use ($rows) {
                foreach ($rows as $row) {
                    yield [$row['date'], $row['sent'], $row['success'], $row['failed']];
                }
            })()
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveRange(array $data): array
    {
        $to = isset($data['to']) ? Carbon::parse($data['to'])->endOfDay() : now()->endOfDay();
        $from = isset($data['from']) ? Carbon::parse($data['from'])->startOfDay() : $to->copy()->subDays(29)->startOfDay();

        return [$from, $to];
    }

    /**
     * @return array<string, float>
     */
    private function pricing(): array
    {
        $pricing = [];
        foreach (self::PRICING_CATEGORIES as $category) {
            $pricing[$category] = (float) (Setting::get("whatsapp_pricing.{$category}") ?? 0);
        }

        return $pricing;
    }

    /**
     * @param  array<int, array<string, mixed>>  $byCategory
     */
    private function estimateCost(array $byCategory): float
    {
        $pricing = $this->pricing();
        $total = 0.0;

        foreach ($byCategory as $row) {
            $total += ($pricing[$row['category']] ?? 0) * $row['sent'];
        }

        return round($total, 4);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function aggregateUsageByCategory(Carbon $from, Carbon $to): array
    {
        return WhatsAppUsage::query()
            ->where('success', true)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('COALESCE(category, "desconocida") as category, COUNT(*) as sent')
            ->groupBy('category')
            ->orderByDesc('sent')
            ->get()
            ->map(fn ($row) => ['category' => $row->category, 'sent' => (int) $row->sent])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function topTemplates(Carbon $from, Carbon $to): array
    {
        return WhatsAppUsage::query()
            ->where('success', true)
            ->where('message_type', 'template')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('template_name, category, COUNT(*) as sent')
            ->groupBy('template_name', 'category')
            ->orderByDesc('sent')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'template_name' => $row->template_name,
                'category' => $row->category,
                'sent' => (int) $row->sent,
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function dailyUsage(Carbon $from, Carbon $to): array
    {
        return WhatsAppUsage::query()
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as sent, COALESCE(SUM(success), 0) as success_sent')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->date,
                'sent' => (int) $row->sent,
                'success' => (int) $row->success_sent,
                'failed' => (int) $row->sent - (int) $row->success_sent,
            ])
            ->all();
    }
}
