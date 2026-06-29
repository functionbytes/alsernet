<?php

namespace Modules\Ecommerce\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Modules\Ecommerce\Models\SavedSearch;

class NotifySavedSearchesCommand extends Command
{
    protected $signature = 'ecommerce:notify-saved-searches';

    protected $description = 'Envia email a clientes cuando hay nuevos productos que coinciden con sus busquedas guardadas';

    public function handle(): int
    {
        $sent = 0;

        SavedSearch::query()
            ->where('is_active', true)
            ->with('customer')
            ->chunk(100, function ($searches) use (&$sent) {
                foreach ($searches as $search) {
                    if (! $search->customer) {
                        continue;
                    }

                    $since = $search->last_notified_at ?? $search->created_at;
                    $matches = $search->findNewMatches($since);

                    if ($matches->isEmpty()) {
                        continue;
                    }

                    $this->sendNotification($search, $matches);
                    $search->update(['last_notified_at' => now()]);
                    $sent++;
                }
            });

        $this->info("Notificaciones de busquedas guardadas enviadas: {$sent}");

        return self::SUCCESS;
    }

    private function sendNotification(SavedSearch $search, $matches): void
    {
        $html = view('ecommerce::emails.saved-search-matches', [
            'customer' => $search->customer,
            'matches' => $matches,
            'searchName' => $search->name,
        ])->render();

        Mail::html($html, function ($m) use ($search) {
            $m->to($search->customer->email)
                ->subject("Nuevos productos en \"{$search->name}\"");
        });
    }
}
