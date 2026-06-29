<?php

namespace Modules\Campaign\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use League\Csv\Writer;
use Modules\Campaign\Models\CampaignMaillist;

/**
 * Exporta los suscriptores de una lista a CSV.
 * Output: storage/app/campaign/exports/{listUid}.csv
 *
 * El CSV incluye columnas: email, first_name, last_name, status, subscribed_at,
 * y las claves del JSON `attributes` que aparezcan en al menos un row.
 */
class ExportSubscribersJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 1800;

    public int $tries = 1;

    public function __construct(protected int $mailListId) {}

    public function handle(): void
    {
        $list = CampaignMaillist::find($this->mailListId);
        if (! $list) {
            return;
        }

        $dir = storage_path('app/campaign/exports');
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $path = "{$dir}/{$list->uid}.csv";
        $writer = Writer::createFromPath($path, 'w');
        $writer->setOutputBOM(Writer::BOM_UTF8);

        // 1ª pasada: descubrir todas las claves de attributes del JSON
        $attrKeys = collect();
        $list->subscribers()->chunk(1000, function ($chunk) use ($attrKeys): void {
            foreach ($chunk as $sub) {
                if (is_array($sub->attributes)) {
                    foreach (array_keys($sub->attributes) as $k) {
                        $attrKeys->push($k);
                    }
                }
            }
        });
        $attrKeys = $attrKeys->unique()->sort()->values()->all();

        // Header
        $writer->insertOne(array_merge(
            ['email', 'first_name', 'last_name', 'status', 'subscribed_at'],
            $attrKeys,
        ));

        // 2ª pasada: escribir los datos
        $list->subscribers()->chunk(1000, function ($chunk) use ($writer, $attrKeys): void {
            foreach ($chunk as $sub) {
                $row = [
                    $sub->email,
                    $sub->first_name ?? '',
                    $sub->last_name ?? '',
                    $sub->pivot->status ?? 'subscribed',
                    $sub->pivot->subscribed_at ?? '',
                ];

                foreach ($attrKeys as $k) {
                    $row[] = is_array($sub->attributes) && isset($sub->attributes[$k])
                        ? (string) $sub->attributes[$k]
                        : '';
                }

                $writer->insertOne($row);
            }
        });

        \Log::info("ExportSubscribersJob: lista {$list->uid} exportada a {$path}");
    }
}
