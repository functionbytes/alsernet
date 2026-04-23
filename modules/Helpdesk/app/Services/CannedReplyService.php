<?php

namespace Modules\Helpdesk\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\CannedReply;
use Modules\HelpdeskTickets\Models\Ticket;

class CannedReplyService
{
    /**
     * Search for canned replies
     */
    public function search(string $query, ?int $categoryId = null): Collection
    {
        $searchQuery = CannedReply::where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('title', 'LIKE', "%{$query}%")
                    ->orWhere('slug', 'LIKE', "%{$query}%")
                    ->orWhere('content', 'LIKE', "%{$query}%");
            });

        if ($categoryId) {
            $searchQuery->where('category_id', $categoryId);
        }

        return $searchQuery->orderBy('usage_count', 'desc')
            ->orderBy('title', 'asc')
            ->get();
    }

    /**
     * Increment usage count for a canned reply
     */
    public function incrementUsageCount(CannedReply $reply): void
    {
        $reply->increment('usage_count');

        Log::info('Canned reply usage incremented', [
            'reply_id' => $reply->id,
            'new_count' => $reply->usage_count,
        ]);
    }

    /**
     * Parse variables in content and replace with ticket data
     */
    public function parseVariables(string $content, Ticket $ticket): string
    {
        $variables = [
            '{{ticket_number}}' => $ticket->ticket_number,
            '{{customer_name}}' => $ticket->customer_name,
            '{{customer_email}}' => $ticket->customer_email,
            '{{subject}}' => $ticket->subject,
            '{{priority}}' => $ticket->priority?->name ?? 'N/A',
            '{{category}}' => $ticket->category?->name ?? 'N/A',
            '{{current_date}}' => now()->format('Y-m-d'),
            '{{current_time}}' => now()->format('H:i:s'),
            '{{current_datetime}}' => now()->format('Y-m-d H:i:s'),
            '{{agent_name}}' => auth()->user()?->name ?? 'Support Agent',
            '{{agent_email}}' => auth()->user()?->email ?? '',
            '{{status}}' => $ticket->status?->name ?? 'N/A',
        ];

        return str_replace(
            array_keys($variables),
            array_values($variables),
            $content
        );
    }

    /**
     * Get most used canned replies
     */
    public function getMostUsed(int $limit = 10): Collection
    {
        return CannedReply::where('is_active', true)
            ->orderByDesc('usage_count')
            ->orderBy('title', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get canned replies by category
     */
    public function getByCategory(int $categoryId): Collection
    {
        return CannedReply::where('category_id', $categoryId)
            ->where('is_active', true)
            ->orderBy('title', 'asc')
            ->get();
    }

    /**
     * Get all available variables
     */
    public function getAvailableVariables(): array
    {
        return [
            '{{ticket_number}}' => 'Número del ticket',
            '{{customer_name}}' => 'Nombre del cliente',
            '{{customer_email}}' => 'Email del cliente',
            '{{subject}}' => 'Asunto del ticket',
            '{{priority}}' => 'Prioridad del ticket',
            '{{category}}' => 'Categoría del ticket',
            '{{status}}' => 'Estado del ticket',
            '{{current_date}}' => 'Fecha actual',
            '{{current_time}}' => 'Hora actual',
            '{{current_datetime}}' => 'Fecha y hora actual',
            '{{agent_name}}' => 'Nombre del agente',
            '{{agent_email}}' => 'Email del agente',
        ];
    }
}
