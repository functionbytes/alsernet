<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAlertThresholdRequest;
use App\Http\Requests\UpdateAlertThresholdRequest;
use App\Models\AlertThreshold;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AlertThresholdController extends Controller
{
    public function index(): View
    {
        $thresholds = AlertThreshold::query()->latest()->get();

        return view('settings.alerts.index', compact('thresholds'));
    }

    public function store(StoreAlertThresholdRequest $request): RedirectResponse
    {
        $data = $request->validated();

        AlertThreshold::create([
            'name' => $data['name'],
            'type' => $data['type'],
            'conditions' => $this->parseConditions($data['type'], $data['conditions']),
            'channels' => $data['channels'],
            'recipients' => $this->parseRecipients($data['recipients'] ?? ''),
            'cooldown_minutes' => $data['cooldown_minutes'],
            'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : true,
        ]);

        return redirect()
            ->route('settings.alerts.index')
            ->with('success', 'Alerta creada correctamente.');
    }

    public function update(UpdateAlertThresholdRequest $request, AlertThreshold $alert): RedirectResponse
    {
        $data = $request->validated();

        $alert->update([
            'name' => $data['name'],
            'type' => $data['type'],
            'conditions' => $this->parseConditions($data['type'], $data['conditions']),
            'channels' => $data['channels'],
            'recipients' => $this->parseRecipients($data['recipients'] ?? ''),
            'cooldown_minutes' => $data['cooldown_minutes'],
            'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : false,
        ]);

        return redirect()
            ->route('settings.alerts.index')
            ->with('success', 'Alerta actualizada correctamente.');
    }

    public function destroy(AlertThreshold $alert): RedirectResponse
    {
        $alert->delete();

        return redirect()
            ->route('settings.alerts.index')
            ->with('success', 'Alerta eliminada correctamente.');
    }

    public function toggle(AlertThreshold $alert): JsonResponse
    {
        $alert->update(['is_active' => ! $alert->is_active]);

        return response()->json([
            'success' => true,
            'is_active' => $alert->is_active,
            'message' => $alert->is_active ? 'Alerta activada.' : 'Alerta desactivada.',
        ]);
    }

    /**
     * Build conditions array based on alert type from form inputs.
     */
    private function parseConditions(string $type, array $raw): array
    {
        return match ($type) {
            'tickets_overdue' => [
                'hours' => (int) ($raw['hours'] ?? 24),
                'min_count' => (int) ($raw['min_count'] ?? 1),
            ],
            'reviews_negative' => [
                'hours' => (int) ($raw['hours'] ?? 1),
                'min_count' => (int) ($raw['min_count'] ?? 3),
                'max_rating' => (int) ($raw['max_rating'] ?? 2),
            ],
            'forms_unread' => [
                'min_count' => (int) ($raw['min_count'] ?? 10),
            ],
            'attention_pending' => [
                'min_count' => (int) ($raw['min_count'] ?? 5),
            ],
            default => $raw,
        };
    }

    /**
     * Parse a comma-separated string of emails into an array.
     */
    private function parseRecipients(string $raw): array
    {
        if (trim($raw) === '') {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', explode(',', $raw))
        ));
    }
}
