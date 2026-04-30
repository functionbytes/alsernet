<?php

namespace Modules\Campaign\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Modules\Campaign\Models\Campaign;
use Modules\Campaign\Services\DeliverabilityTester;
use Modules\Campaign\Services\SpamScoreChecker;

class CampaignTestController extends Controller
{
    /**
     * Envía un email de prueba + devuelve spam score y sugerencias.
     */
    public function sendTest(Request $request, string $uid): JsonResponse
    {
        $campaign = Campaign::where('uid', $uid)->firstOrFail();

        $data = $request->validate([
            'email' => ['required', 'email'],
            'html' => ['nullable', 'string'],
        ]);

        $html = $data['html'] ?? $campaign->template?->content ?? '<p>Sin contenido</p>';

        // Spam score
        $checker = new SpamScoreChecker;
        $spamResult = $checker->score($html, $campaign->subject);

        // Enviar test
        try {
            Mail::html($html, function ($message) use ($data, $campaign): void {
                $message->to($data['email'])
                    ->subject('[TEST] '.$campaign->subject);
            });
            $sent = true;
        } catch (\Throwable $e) {
            $sent = false;
        }

        return response()->json([
            'status' => $sent ? 'success' : 'error',
            'email' => $data['email'],
            'spam_score' => $spamResult,
            'suggestions' => $this->suggestions($spamResult),
        ]);
    }

    /**
     * SPF/DKIM/DMARC check para el dominio from_email de la campaña.
     */
    public function deliverability(string $uid): JsonResponse
    {
        $campaign = Campaign::where('uid', $uid)->firstOrFail();
        $domain = substr(strrchr($campaign->from_email ?? '', '@'), 1);

        if (empty($domain)) {
            return response()->json(['error' => 'Invalid from_email'], 422);
        }

        $tester = new DeliverabilityTester;
        $result = $tester->check($domain);

        return response()->json([
            'domain' => $domain,
            'score' => $result['score'],
            'spf' => $result['spf'],
            'dmarc' => $result['dmarc'],
            'mx' => $result['mx'],
            'recommendations' => $this->deliverabilityRecommendations($result),
        ]);
    }

    protected function suggestions(array $spamResult): array
    {
        $suggestions = [];
        if ($spamResult['score'] >= 40) {
            $suggestions[] = 'Spam score alto. Revisa palabras y formato.';
        }
        if (in_array('Subject en MAYÚSCULAS', $spamResult['reasons'] ?? [], true)) {
            $suggestions[] = 'Evita el subject completamente en mayúsculas.';
        }
        if (in_array('Imagen sin atributo alt', $spamResult['reasons'] ?? [], true)) {
            $suggestions[] = 'Añade atributos alt a todas las imágenes.';
        }

        return $suggestions;
    }

    protected function deliverabilityRecommendations(array $result): array
    {
        $recs = [];
        if (! ($result['spf']['present'] ?? false)) {
            $recs[] = 'Falta registro SPF para el dominio.';
        }
        if (! ($result['dmarc']['present'] ?? false)) {
            $recs[] = 'Falta registro DMARC para el dominio.';
        }
        if (! ($result['mx']['present'] ?? false)) {
            $recs[] = 'No se detectaron registros MX.';
        }

        return $recs;
    }
}
