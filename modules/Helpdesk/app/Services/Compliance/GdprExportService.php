<?php

namespace Modules\Helpdesk\Services\Compliance;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Helpdesk\Contracts\GdprExportContributor;
use Modules\Helpdesk\Models\AuditLog;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Services\AuditLogService;
use ZipArchive;

class GdprExportService
{
    /**
     * Build a full structured export of all PII and activity for the given customer.
     *
     * @return array<string, mixed>
     */
    public function exportCustomer(Customer $customer): array
    {
        $conversations = $customer->conversations()
            ->with('items')
            ->get();

        $csatRatings = DB::connection('helpdesk')
            ->table('helpdesk_csat_ratings')
            ->where('customer_id', $customer->id)
            ->get()
            ->toArray();

        $npsRatings = DB::connection('helpdesk')
            ->table('helpdesk_nps_ratings')
            ->where('customer_id', $customer->id)
            ->get()
            ->toArray();

        $dripExecutions = DB::connection('helpdesk')
            ->table('helpdesk_drip_executions')
            ->where('customer_id', $customer->id)
            ->get()
            ->toArray();

        $auditLogs = AuditLog::query()
            ->where('entity_type', Customer::class)
            ->where('entity_id', $customer->id)
            ->orderBy('created_at')
            ->get(['action', 'user_id', 'ip_address', 'created_at'])
            ->toArray();

        $tags = DB::connection('helpdesk')
            ->table('helpdesk_conversation_tags as t')
            ->join('helpdesk_conversation_tag_pivot as p', 'p.tag_id', '=', 't.id')
            ->join('helpdesk_conversations as c', 'c.id', '=', 'p.conversation_id')
            ->where('c.customer_id', $customer->id)
            ->distinct()
            ->select('t.name', 't.slug', 't.color')
            ->get()
            ->toArray();

        $data = [
            'exported_at' => now()->toIso8601String(),
            'customer' => $this->extractPii($customer),
            'conversations' => $conversations->map(fn ($conv) => [
                'id' => $conv->id,
                'channel' => $conv->channel_type ?? null,
                'status_id' => $conv->status_id,
                'created_at' => $conv->created_at?->toIso8601String(),
                'closed_at' => $conv->closed_at?->toIso8601String(),
                'messages' => $conv->items->map(fn ($item) => [
                    'id' => $item->id,
                    'type' => $item->type,
                    'body' => $item->body,
                    'is_internal' => $item->is_internal,
                    'created_at' => $item->created_at?->toIso8601String(),
                    'attachments' => $item->attachment_urls ?? [],
                ])->values(),
            ])->values(),
            'csat_ratings' => $csatRatings,
            'nps_ratings' => $npsRatings,
            'drip_executions' => $dripExecutions,
            'audit_logs' => $auditLogs,
            'tags' => $tags,
        ];

        // Secciones aportadas por los módulos satélite (tickets, sesiones de
        // chatbot, expedientes...). Sin try/catch a propósito: omitir en
        // silencio la sección de un módulo produciría un export "completo" que
        // en realidad está incompleto — un fallo debe abortar y quedar visible,
        // no degradar el derecho de acceso.
        foreach (app()->tagged(GdprExportContributor::TAG) as $contributor) {
            $data[$contributor->sectionKey()] = $contributor->export($customer);
        }

        return $data;
    }

    /**
     * Generate a ZIP archive with the customer export JSON plus any local attachments.
     * Returns the absolute path to the temporary ZIP file.
     */
    public function exportToZip(Customer $customer): string
    {
        $data = $this->exportCustomer($customer);

        // Nombre aleatorio + permisos restrictivos: el export lleva PII completa y
        // vive en /tmp (compartido en host/contenedor) hasta enviarse y borrarse.
        $suffix = bin2hex(random_bytes(8));
        $tmpDir = sys_get_temp_dir().'/gdpr_export_'.$customer->id.'_'.$suffix;
        mkdir($tmpDir, 0700, true);

        file_put_contents(
            $tmpDir.'/customer_data.json',
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        // Collect attachment paths from conversation items
        $attachmentDir = $tmpDir.'/attachments';
        $hasAttachments = false;

        foreach ($data['conversations'] as $conv) {
            foreach ($conv['messages'] as $message) {
                foreach ($message['attachments'] as $attachmentUrl) {
                    $path = $this->urlToStoragePath($attachmentUrl);

                    if ($path && Storage::disk('public')->exists($path)) {
                        if (! $hasAttachments) {
                            mkdir($attachmentDir, 0700, true);
                            $hasAttachments = true;
                        }

                        $filename = basename($path);
                        $content = Storage::disk('public')->get($path);
                        file_put_contents($attachmentDir.'/'.$filename, $content);
                    }
                }
            }
        }

        $zipPath = sys_get_temp_dir().'/gdpr_customer_'.$customer->id.'_'.$suffix.'.zip';

        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $zip->addFile($tmpDir.'/customer_data.json', 'customer_data.json');

        if ($hasAttachments) {
            foreach (glob($attachmentDir.'/*') as $file) {
                $zip->addFile($file, 'attachments/'.basename($file));
            }
        }

        $zip->close();

        // El ZIP con toda la PII queda en /tmp hasta enviarse y borrarse: restringe
        // su lectura a solo el propietario mientras existe.
        @chmod($zipPath, 0600);

        // Clean up temp directory
        $this->rmdirRecursive($tmpDir);

        AuditLogService::record('gdpr.export', $customer, [], ['exported_by' => auth()->id()]);

        return $zipPath;
    }

    /**
     * Extract PII fields from the customer model.
     *
     * @return array<string, mixed>
     */
    private function extractPii(Customer $customer): array
    {
        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'whatsapp_phone' => $customer->whatsapp_phone,
            'facebook_psid' => $customer->facebook_psid,
            'instagram_id' => $customer->instagram_id,
            'country' => $customer->country,
            'state' => $customer->state,
            'city' => $customer->city,
            'postal_code' => $customer->postal_code,
            'language' => $customer->language,
            'timezone' => $customer->timezone,
            'custom_attributes' => $customer->custom_attributes,
            'created_at' => $customer->created_at?->toIso8601String(),
            'last_seen_at' => $customer->last_seen_at?->toIso8601String(),
            'total_conversations' => $customer->total_conversations,
        ];
    }

    /**
     * Attempt to convert a public URL to a relative storage path.
     */
    private function urlToStoragePath(mixed $url): ?string
    {
        if (! is_string($url)) {
            return null;
        }

        $publicPath = parse_url($url, PHP_URL_PATH);
        if (! $publicPath) {
            return null;
        }

        // Strip the /storage/ prefix used by public disk
        $relativePath = preg_replace('#^/storage/#', '', $publicPath);

        return $relativePath ?: null;
    }

    private function rmdirRecursive(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        foreach (glob($dir.'/*') as $file) {
            is_dir($file) ? $this->rmdirRecursive($file) : unlink($file);
        }

        rmdir($dir);
    }
}
