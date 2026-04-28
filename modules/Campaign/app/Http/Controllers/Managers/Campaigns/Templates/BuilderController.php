<?php

namespace Modules\Campaign\Http\Controllers\Managers\Campaigns\Templates;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Campaign\Library\EmailBuilder\Blocks;
use Modules\Campaign\Library\EmailBuilder\PrebuiltTemplates;
use Modules\Campaign\Library\EmailBuilder\Renderer;
use Modules\Campaign\Library\EmailBuilder\Variables;
use Modules\Campaign\Library\SpamAnalyzer;
use Modules\Campaign\Models\CampaignMaillist;
use Modules\Campaign\Models\Template\Template;
use Modules\CampaignSendingServers\Models\SendingServer;
use Symfony\Component\Mime\Email;

/**
 * Email Builder Controller.
 *
 * Endpoints (montados bajo /panel/campaign/templates/{uid}/builder/...):
 *   GET  /                    → vista del builder
 *   POST /save                → persiste blocks + globals + compila html
 *   POST /preview             → renderiza HTML actual para iframe (sin guardar)
 *   POST /render-block        → renderiza un solo bloque (preview incremental)
 *   GET  /gallery             → galería de plantillas pre-hechas
 *   POST /from-prebuilt/{key} → crea Template nueva a partir de un preset
 *   GET  /blocks/blank/{type} → JSON con un bloque vacío del tipo dado
 */
class BuilderController extends Controller
{
    public function builder(string $uid): View
    {
        $template = Template::where('uid', $uid)->firstOrFail();

        return view('campaign::manager.templates.builder', [
            'template' => $template,
            'palette' => Blocks::palette(),
            'blocks' => $template->blocks ?? [],
            'globals' => $template->global_settings ?? [],
        ]);
    }

    public function save(Request $request, string $uid): JsonResponse
    {
        $template = Template::where('uid', $uid)->firstOrFail();

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:998'],
            'blocks' => ['required', 'array'],
            'blocks.*.type' => ['required', 'string'],
            'global_settings' => ['nullable', 'array'],
        ]);

        $renderer = new Renderer($data['blocks'], $data['global_settings'] ?? []);
        $html = $renderer->render();

        $template->update([
            'name' => $data['name'] ?? $template->name,
            'subject' => $data['subject'] ?? $template->subject,
            'blocks' => $data['blocks'],
            'global_settings' => $data['global_settings'] ?? null,
            'html' => $html,
            'plain' => trim(strip_tags(preg_replace('/<style[^>]*>.*?<\/style>/is', '', $html))),
        ]);

        return response()->json([
            'ok' => true,
            'uid' => $template->uid,
            'updated_at' => $template->updated_at,
        ]);
    }

    /**
     * Preview live: renderiza HTML SIN persistir (para iframe).
     */
    public function preview(Request $request, string $uid): Response
    {
        $blocks = $request->input('blocks', []);
        $globals = $request->input('global_settings', []);

        $html = (new Renderer($blocks, $globals))->render();

        return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    /**
     * Render incremental de un solo bloque (al editar settings).
     */
    public function renderBlock(Request $request): Response
    {
        $block = $request->validate([
            'type' => ['required', 'string'],
            'settings' => ['array'],
            'content' => ['array'],
        ]);

        $html = (new Renderer)->renderBlock($block);

        return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    /**
     * Devuelve un bloque "vacío" listo para insertar (usado por el JS al
     * arrastrar un bloque desde la paleta al canvas).
     */
    public function blockBlank(string $type): JsonResponse
    {
        return response()->json(Blocks::blank($type));
    }

    /**
     * Galería de plantillas pre-hechas.
     */
    public function gallery(): View
    {
        return view('campaign::manager.templates.gallery', [
            'catalog' => PrebuiltTemplates::catalog(),
        ]);
    }

    /**
     * Envía un test del template actual a un email de prueba.
     * Usa el primer SendingServer activo encontrado.
     */
    public function sendTest(Request $request, string $uid): JsonResponse
    {
        $template = Template::where('uid', $uid)->firstOrFail();

        $data = $request->validate([
            'to' => ['required', 'email'],
            'blocks' => ['nullable', 'array'],
            'global_settings' => ['nullable', 'array'],
        ]);

        $blocks = $data['blocks'] ?? $template->blocks ?? [];
        $globals = $data['global_settings'] ?? $template->global_settings ?? [];

        $html = (new Renderer($blocks, $globals))->render();

        $server = SendingServer::active()->first();
        if (! $server) {
            return response()->json(['ok' => false, 'message' => 'No hay sending servers activos. Configura uno antes.'], 422);
        }

        $email = (new Email)
            ->from($server->default_from_email ?: 'noreply@'.parse_url((string) config('app.url'), PHP_URL_HOST))
            ->to($data['to'])
            ->subject('[TEST] '.($template->subject ?: $template->name))
            ->html($html);

        try {
            $server->mapType()->send($email);

            return response()->json(['ok' => true, 'message' => "Test enviado a {$data['to']} vía {$server->name}"]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => 'Error: '.$e->getMessage()], 500);
        }
    }

    /**
     * Subida de imagen para uso en el builder. Storage público.
     */
    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120'],
        ]);

        $file = $request->file('image');
        $name = Str::random(16).'.'.$file->getClientOriginalExtension();

        $path = $file->storeAs('campaign/images', $name, 'public');
        $url = Storage::disk('public')->url($path);

        return response()->json([
            'ok' => true,
            'url' => $url,
            'filename' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
        ]);
    }

    /**
     * Variables disponibles para insertar en bloques. Si list_uid se pasa,
     * incluye los CampaignField de esa lista (FIRST_NAME, LAST_NAME, custom).
     */
    public function variables(Request $request): JsonResponse
    {
        $list = null;
        if ($listUid = $request->query('list_uid')) {
            $list = CampaignMaillist::where('uid', $listUid)->first();
        }

        $catalog = Variables::catalogForList($list);

        $groups = [];
        foreach ($catalog as $key => $vars) {
            $groups[] = [
                'key' => $key,
                'label' => Variables::groupLabel($key),
                'variables' => $vars,
            ];
        }

        return response()->json([
            'list_uid' => $listUid ?? null,
            'list_name' => $list?->name,
            'groups' => $groups,
        ]);
    }

    /**
     * Preview con datos reales: toma un subscriber random de la lista y
     * sustituye los tags. Si no hay lista, marca variables no resueltas
     * con un highlight para detectar dónde irán los datos.
     */
    public function previewWithRealData(Request $request, string $uid): Response
    {
        $template = Template::where('uid', $uid)->firstOrFail();

        $blocks = $request->input('blocks', $template->blocks ?? []);
        $globals = $request->input('global_settings', $template->global_settings ?? []);

        $html = (new Renderer($blocks, $globals))->render();

        $listUid = $request->input('list_uid');
        $subscriber = null;
        if ($listUid) {
            $list = CampaignMaillist::where('uid', $listUid)->first();
            if ($list) {
                $subscriber = $list->subscribers()->wherePivot('status', 'subscribed')->first();
            }
        }

        if ($subscriber) {
            $tags = [
                'EMAIL' => $subscriber->email,
                'FIRST_NAME' => $subscriber->first_name ?? '',
                'LAST_NAME' => $subscriber->last_name ?? '',
                'NAME' => trim(($subscriber->first_name ?? '').' '.($subscriber->last_name ?? '')),
                'FULL_NAME' => trim(($subscriber->first_name ?? '').' '.($subscriber->last_name ?? '')),
                'SUBSCRIBER_UID' => $subscriber->uid,
                'UNSUBSCRIBE_URL' => '#unsubscribe-preview',
                'MANAGE_URL' => '#manage-preview',
                'COMPANY_ADDRESS' => $list->contact_address_1 ?? '',
            ];
            foreach (($subscriber->attributes ?? []) as $k => $v) {
                $tags[strtoupper($k)] = (string) $v;
            }

            foreach ($tags as $tag => $value) {
                $html = str_replace(['{{'.$tag.'}}', '{'.$tag.'}'], $value, $html);
            }
        } else {
            // Resaltar variables no resueltas en amarillo para que el admin
            // las detecte visualmente.
            $html = preg_replace(
                '/\{\{?\s*([A-Z_][A-Z0-9_]*)\s*\}\}?/',
                '<span style="background:#fef3c7;padding:1px 4px;border-radius:3px;color:#92400e;font-family:monospace;font-size:90%;">[$1]</span>',
                $html,
            );
        }

        return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    /**
     * Análisis local de spam score del template actual.
     */
    public function spamCheck(Request $request, string $uid): JsonResponse
    {
        $template = Template::where('uid', $uid)->firstOrFail();

        $blocks = $request->input('blocks', $template->blocks ?? []);
        $globals = $request->input('global_settings', $template->global_settings ?? []);

        $html = (new Renderer($blocks, $globals))->render();
        $subject = $request->input('subject', $template->subject ?? '');
        $plain = trim(strip_tags($html));

        return response()->json(SpamAnalyzer::analyze($subject, $html, $plain));
    }

    /**
     * Importar HTML externo: lo mete en un único bloque tipo 'html' y abre el builder.
     */
    public function importHtml(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:998'],
            'html' => ['required', 'string'],
        ]);

        $blocks = [
            ['type' => 'html', 'settings' => ['padding' => '0'], 'content' => ['html' => $data['html']]],
        ];

        $template = Template::create([
            'name' => $data['name'],
            'subject' => $data['subject'] ?? $data['name'],
            'blocks' => $blocks,
            'global_settings' => [],
            'html' => (new Renderer($blocks))->render(),
            'shared' => false,
        ]);

        return redirect()
            ->route('manager.campaigns.templates.builder', $template->uid)
            ->with('success', 'HTML importado. Edítalo en el builder.');
    }

    /**
     * Descarga el HTML compilado como archivo .html.
     */
    public function exportHtml(string $uid): Response
    {
        $template = Template::where('uid', $uid)->firstOrFail();
        $html = $template->html;

        if (! $html && $template->blocks) {
            $html = (new Renderer($template->blocks, $template->global_settings ?? []))->render();
        }

        $filename = Str::slug($template->name).'.html';

        return response((string) $html, 200, [
            'Content-Type' => 'text/html; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Clonar el template actual con nuevo nombre.
     */
    public function saveAs(Request $request, string $uid): JsonResponse
    {
        $template = Template::where('uid', $uid)->firstOrFail();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'blocks' => ['required', 'array'],
            'global_settings' => ['nullable', 'array'],
        ]);

        $renderer = new Renderer($data['blocks'], $data['global_settings'] ?? []);

        $clone = Template::create([
            'name' => $data['name'],
            'subject' => $template->subject,
            'blocks' => $data['blocks'],
            'global_settings' => $data['global_settings'] ?? [],
            'html' => $renderer->render(),
            'shared' => false,
            'layout_id' => $template->layout_id,
        ]);

        return response()->json([
            'ok' => true,
            'uid' => $clone->uid,
            'redirect' => route('manager.campaigns.templates.builder', $clone->uid),
        ]);
    }

    /**
     * Crea una nueva Template desde un preset y abre el builder.
     */
    public function fromPrebuilt(string $key): RedirectResponse
    {
        if (! array_key_exists($key, PrebuiltTemplates::catalog())) {
            abort(404);
        }

        $blocks = PrebuiltTemplates::blocks($key);
        $globals = PrebuiltTemplates::globals($key);
        $catalog = PrebuiltTemplates::catalog()[$key];

        $renderer = new Renderer($blocks, $globals);

        $template = Template::create([
            'name' => $catalog['name'].' '.now()->format('Y-m-d H:i'),
            'subject' => $catalog['name'],
            'blocks' => $blocks,
            'global_settings' => $globals,
            'html' => $renderer->render(),
            'shared' => false,
        ]);

        return redirect()
            ->route('manager.campaigns.templates.builder', $template->uid)
            ->with('success', "Plantilla '{$catalog['name']}' creada. Edítala con el builder.");
    }
}
