<?php

namespace Modules\Supplier\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BudgetExceededException extends Exception
{
    public function __construct(
        public readonly string $provider,
        public readonly string $window,
        public readonly float $usage,
        public readonly float $limit,
    ) {
        parent::__construct(
            "AI budget exceeded for {$provider} ({$window}): \${$usage} / \${$limit}",
        );
    }

    /**
     * Render the exception as a structured HTTP response.
     * 402 Payment Required — semantically correct for budget exhaustion.
     */
    public function render(Request $request): ?Response
    {
        if (! $request->expectsJson() && ! $request->is('api/*')) {
            return null;
        }

        return new JsonResponse([
            'error' => 'budget_exceeded',
            'message' => sprintf(
                'Presupuesto %s agotado para %s ($%.2f / $%.2f). La generación está bloqueada.',
                $this->window === 'daily' ? 'diario' : 'mensual',
                $this->provider,
                $this->usage,
                $this->limit,
            ),
            'provider' => $this->provider,
            'window' => $this->window,
            'usage' => $this->usage,
            'limit' => $this->limit,
        ], 402);
    }

    public function context(): array
    {
        return [
            'provider' => $this->provider,
            'window' => $this->window,
            'usage' => $this->usage,
            'limit' => $this->limit,
        ];
    }
}
