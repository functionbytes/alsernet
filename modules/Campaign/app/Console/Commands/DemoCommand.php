<?php

namespace Modules\Campaign\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Modules\Campaign\Library\EmailBuilder\Renderer;
use Modules\Campaign\Models\CampaignField;
use Modules\Campaign\Models\CampaignMaillist;
use Modules\Campaign\Models\CampaignSubscriber;
use Modules\Campaign\Models\Template\Template;

/**
 * Crea datos demo (lista + suscriptores + plantilla rica con todos los
 * bloques) e imprime la URL del builder lista para abrir en Chrome.
 *
 *   php artisan campaign:demo
 *   php artisan campaign:demo --fresh   # borra demo previo si existe
 */
class DemoCommand extends Command
{
    protected $signature = 'campaign:demo {--fresh : Borra el demo anterior antes de crear uno nuevo}';

    protected $description = 'Crea una plantilla demo rica con todos los bloques + lista demo + 5 suscriptores. Imprime la URL para abrir en Chrome.';

    public function handle(): int
    {
        if ($this->option('fresh')) {
            $this->warn('Borrando demo previo…');
            CampaignSubscriber::where('email', 'like', 'demo+%@example.com')->forceDelete();
            CampaignMaillist::where('name', 'Demo Builder')->forceDelete();
            Template::where('name', 'like', 'Demo Builder %')->forceDelete();
        }

        // 1. Lista demo
        $list = CampaignMaillist::firstOrCreate(
            ['name' => 'Demo Builder'],
            [
                'description' => 'Lista de prueba para experimentar con el builder',
                'from_email' => 'noreply@'.parse_url((string) config('app.url'), PHP_URL_HOST),
                'from_name' => 'Demo Builder',
                'default_subject' => 'Email de prueba',
                'subscribe_confirmation' => 0,
                'send_welcome_email' => 0,
            ],
        );
        $this->line("✓ Lista creada: {$list->name} (uid: {$list->uid})");

        // 2. Campos custom (genera variables {{BIRTHDAY}}, {{CITY}}, {{COMPANY}})
        $defaultFields = [
            ['tag' => 'FIRST_NAME', 'label' => 'Nombre', 'type' => 'text', 'order' => 1, 'visible' => true],
            ['tag' => 'LAST_NAME', 'label' => 'Apellido', 'type' => 'text', 'order' => 2, 'visible' => true],
            ['tag' => 'BIRTHDAY', 'label' => 'Cumpleaños', 'type' => 'date', 'order' => 3, 'visible' => true],
            ['tag' => 'CITY', 'label' => 'Ciudad', 'type' => 'text', 'order' => 4, 'visible' => true],
            ['tag' => 'COMPANY', 'label' => 'Empresa', 'type' => 'text', 'order' => 5, 'visible' => false],
        ];
        foreach ($defaultFields as $f) {
            CampaignField::firstOrCreate(
                ['mail_list_id' => $list->id, 'tag' => $f['tag']],
                $f,
            );
        }
        $this->line('✓ 5 campos creados (FIRST_NAME, LAST_NAME, BIRTHDAY, CITY, COMPANY)');

        // 3. 5 suscriptores demo
        $demoData = [
            ['email' => 'demo+ana@example.com', 'first_name' => 'Ana', 'last_name' => 'García', 'attrs' => ['BIRTHDAY' => '1990-05-15', 'CITY' => 'Madrid', 'COMPANY' => 'Acme Inc']],
            ['email' => 'demo+luis@example.com', 'first_name' => 'Luis', 'last_name' => 'Martínez', 'attrs' => ['BIRTHDAY' => '1985-09-23', 'CITY' => 'Barcelona', 'COMPANY' => 'TechCorp']],
            ['email' => 'demo+maria@example.com', 'first_name' => 'María', 'last_name' => 'López', 'attrs' => ['BIRTHDAY' => '1992-12-04', 'CITY' => 'Valencia', 'COMPANY' => 'StartupX']],
            ['email' => 'demo+pedro@example.com', 'first_name' => 'Pedro', 'last_name' => 'Sánchez', 'attrs' => ['BIRTHDAY' => '1988-03-18', 'CITY' => 'Sevilla', 'COMPANY' => 'BigCo']],
            ['email' => 'demo+laura@example.com', 'first_name' => 'Laura', 'last_name' => 'Ruiz', 'attrs' => ['BIRTHDAY' => '1995-07-30', 'CITY' => 'Bilbao', 'COMPANY' => 'Innovate Ltd']],
        ];

        foreach ($demoData as $d) {
            $sub = CampaignSubscriber::firstOrCreate(
                ['email' => $d['email']],
                [
                    'first_name' => $d['first_name'],
                    'last_name' => $d['last_name'],
                    'attributes' => $d['attrs'],
                    'subscribed_at' => now(),
                    'source' => 'demo',
                ],
            );
            DB::table('campaign_maillists_subscribers')->updateOrInsert(
                ['mail_list_id' => $list->id, 'subscriber_id' => $sub->id],
                [
                    'uid' => (string) Str::uuid(),
                    'status' => 'subscribed',
                    'subscribed_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
        $this->line('✓ 5 suscriptores creados (Ana, Luis, María, Pedro, Laura)');

        // 4. Plantilla demo rica con todos los 14 bloques
        $blocks = $this->buildRichTemplate();
        $globals = [
            'content_width' => '600px',
            'background_color' => '#f4f6f9',
            'content_background_color' => '#ffffff',
            'text_color' => '#1f2937',
            'link_color' => '#4f46e5',
            'font_family' => "-apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif",
        ];

        $renderer = new Renderer($blocks, $globals);

        $template = Template::create([
            'name' => 'Demo Builder · '.now()->format('Y-m-d H:i'),
            'subject' => 'Hola {{FIRST_NAME}} 👋, te traemos las novedades',
            'blocks' => $blocks,
            'global_settings' => $globals,
            'html' => $renderer->render(),
            'shared' => false,
        ]);
        $blockCount = count($blocks);
        $this->line("✓ Plantilla creada con {$blockCount} bloques: {$template->name}");

        // 5. Output con URLs Chrome-ready
        // Si SESSION_DOMAIN no es subdominio de APP_URL, las cookies se descartan en el browser.
        // Detectamos eso y construimos la URL contra el dominio donde la cookie SÍ va a persistir.
        $base = rtrim((string) config('app.url'), '/');
        $sessionDomain = config('session.domain');
        if ($sessionDomain) {
            $cookieHost = ltrim($sessionDomain, '.');
            $appHost = parse_url($base, PHP_URL_HOST) ?: '';
            $cookieMatchesApp = $appHost === $cookieHost
                || str_ends_with(".{$appHost}", ".{$cookieHost}");
            if (! $cookieMatchesApp) {
                $base = 'https://'.$cookieHost;
                $this->warn("⚠️  APP_URL ({$appHost}) y SESSION_DOMAIN ({$sessionDomain}) no comparten dominio.");
                $this->warn("    Usando {$base} para que la cookie de sesión persista.");
            }
        }
        $url = $base.'/panel/campaign/manager/templates/'.$template->uid.'/builder';
        $previewUrl = $base.'/panel/campaign/manager/templates/'.$template->uid.'/builder/preview';
        $listFieldsUrl = $base.'/panel/campaign/manager/maillists/'.$list->uid.'/fields';
        $galleryUrl = $base.'/panel/campaign/manager/templates/gallery';

        // 6. Magic link de auto-login (token persistido en cache, 1h validez)
        $admin = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['super-admin', 'super-settings']))
            ->orderBy('id')
            ->first();
        $magicUrl = null;
        if ($admin) {
            $token = bin2hex(random_bytes(24));
            Cache::put(
                "campaign:demo-login:{$token}",
                [
                    'user_id' => $admin->id,
                    'redirect' => '/panel/campaign/manager/templates/'.$template->uid.'/builder',
                ],
                now()->addHour(),
            );
            $magicUrl = $base.'/campaign/demo-login?token='.$token;
        }

        $this->newLine();
        $this->info('🚀 Demo listo. Abre en Chrome:');
        $this->newLine();
        if ($magicUrl) {
            $this->line('  ⚡ MAGIC LINK (auto-login, 1h):');
            $this->line("     <fg=green>{$magicUrl}</>");
            $this->line("     <fg=gray>(usuario: {$admin->email})</>");
            $this->newLine();
        }
        $this->line("  📝 BUILDER:  <fg=cyan>{$url}</>");
        $this->line("  👁  PREVIEW: <fg=cyan>{$previewUrl}</>");
        $this->line("  🏷️  CAMPOS:  <fg=cyan>{$listFieldsUrl}</>");
        $this->line("  🛍️  GALERÍA: <fg=cyan>{$galleryUrl}</>");
        $this->newLine();
        $this->line('Selecciona "Demo Builder" en el sidebar Variables del builder');
        $this->line('y prueba el botón "👤 Con datos" para ver el email con los suscriptores reales.');
        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Plantilla rica con todos los tipos de bloque + variables.
     */
    protected function buildRichTemplate(): array
    {
        return [
            // Header
            [
                'type' => 'header',
                'settings' => ['background_color' => '#4f46e5', 'text_color' => '#ffffff', 'padding' => '32px 30px', 'align' => 'center', 'font_size' => '32px'],
                'content' => ['title' => '🎨 Demo Builder', 'subtitle' => 'Hola {{FIRST_NAME}}, todo el potencial del editor en un email'],
            ],

            // Hero con CTA
            [
                'type' => 'hero',
                'settings' => ['padding' => '40px 30px', 'align' => 'center', 'background_color' => '#ffffff'],
                'content' => [
                    'image_url' => 'https://images.unsplash.com/photo-1556761175-5973dc0f32e7?w=540&h=300&fit=crop',
                    'image_alt' => 'Dashboard',
                    'title' => 'Bienvenido a {{COMPANY}}',
                    'subtitle' => 'Estás en {{CITY}} y celebramos contigo. Mira lo que tenemos preparado.',
                    'button_text' => 'Empezar →',
                    'button_url' => 'https://example.com/start',
                ],
            ],

            // Texto con variables
            [
                'type' => 'text',
                'settings' => ['padding' => '24px 30px', 'align' => 'left', 'font_size' => '16px', 'line_height' => '1.6'],
                'content' => ['html' => '<p>Hola <strong>{{FIRST_NAME}} {{LAST_NAME}}</strong>,</p><p>Acabamos de lanzar nuevas funcionalidades pensadas para empresas como {{COMPANY}}. Lee el artículo completo:</p>'],
            ],

            // Botón
            [
                'type' => 'button',
                'settings' => ['padding' => '0 30px 24px', 'align' => 'left', 'background_color' => '#4f46e5', 'text_color' => '#ffffff', 'border_radius' => '8px', 'font_size' => '16px'],
                'content' => ['text' => 'Leer artículo completo', 'url' => 'https://example.com/article'],
            ],

            // Divider
            ['type' => 'divider', 'settings' => ['padding' => '8px 30px', 'color' => '#e5e7eb', 'thickness' => '1px'], 'content' => []],

            // Columns 2-col
            [
                'type' => 'columns',
                'settings' => ['padding' => '24px 30px'],
                'content' => ['columns' => [
                    ['html' => '<h4 style="margin:0 0 8px;color:#4f46e5;">📚 Recursos</h4><p style="margin:0;font-size:14px;color:#666;">Guías, tutoriales y ejemplos de uso para tu empresa.</p>'],
                    ['html' => '<h4 style="margin:0 0 8px;color:#10b981;">💬 Soporte</h4><p style="margin:0;font-size:14px;color:#666;">Habla con el equipo. Estamos en {{CITY}} también.</p>'],
                ]],
            ],

            // Image solo
            [
                'type' => 'image',
                'settings' => ['padding' => '24px 30px', 'align' => 'center'],
                'content' => ['url' => 'https://images.unsplash.com/photo-1551434678-e076c223a692?w=540&h=200&fit=crop', 'alt' => 'Workspace', 'link' => ''],
            ],

            // Video con play overlay
            [
                'type' => 'video',
                'settings' => ['padding' => '0 30px 24px', 'align' => 'center'],
                'content' => ['thumbnail_url' => 'https://images.unsplash.com/photo-1626785774573-4b799315345d?w=540&h=300&fit=crop', 'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'play_overlay' => true],
            ],

            // Quote
            [
                'type' => 'quote',
                'settings' => ['padding' => '24px 30px', 'background_color' => '#f9fafb', 'border_color' => '#4f46e5', 'font_size' => '18px'],
                'content' => ['text' => 'Esta plataforma ha transformado cómo gestionamos email marketing.', 'author' => 'María, CMO de {{COMPANY}}'],
            ],

            // List
            [
                'type' => 'list',
                'settings' => ['padding' => '24px 30px', 'list_type' => 'ul', 'font_size' => '16px'],
                'content' => ['items' => [
                    'Drag-and-drop intuitivo con 14 tipos de bloques',
                    'Variables dinámicas que personalizan cada email',
                    'Preview con datos reales antes de enviar',
                    'Auto-save cada 30 segundos',
                    'Send test directo desde el builder',
                ]],
            ],

            // Spacer
            ['type' => 'spacer', 'settings' => ['height' => '20px'], 'content' => []],

            // Header secundario para sección "Tu cumple"
            [
                'type' => 'header',
                'settings' => ['background_color' => '#fef3c7', 'text_color' => '#92400e', 'padding' => '20px 30px', 'align' => 'center', 'font_size' => '20px'],
                'content' => ['title' => '🎂 ¡Feliz mes!', 'subtitle' => 'Tu cumpleaños es el {{BIRTHDAY}}'],
            ],

            // HTML custom
            [
                'type' => 'html',
                'settings' => ['padding' => '20px 30px'],
                'content' => ['html' => '<div style="background:#ecfdf5;border:1px solid #10b981;border-radius:8px;padding:16px;text-align:center;color:#065f46;"><strong>🎁 Cupón especial: AHORRA20</strong><br><small>Válido hasta fin de mes en {{COMPANY}}</small></div>'],
            ],

            // Social
            [
                'type' => 'social',
                'settings' => ['padding' => '24px 30px', 'align' => 'center', 'icon_size' => '32px'],
                'content' => ['networks' => [
                    ['name' => 'Twitter', 'url' => 'https://twitter.com/example'],
                    ['name' => 'Instagram', 'url' => 'https://instagram.com/example'],
                    ['name' => 'LinkedIn', 'url' => 'https://linkedin.com/example'],
                    ['name' => 'YouTube', 'url' => 'https://youtube.com/example'],
                ]],
            ],

            // Footer
            [
                'type' => 'footer',
                'settings' => ['padding' => '24px 30px', 'background_color' => '#f9fafb', 'text_color' => '#6b7280', 'font_size' => '12px', 'align' => 'center'],
                'content' => ['text' => 'Recibes este email porque estás suscrito a la lista <strong>Demo Builder</strong>.<br>'.config('app.name').' · '.now()->format('Y').'<br><a href="{{UNSUBSCRIBE_URL}}" style="color:#6b7280;">Darme de baja</a> · <a href="{{MANAGE_URL}}" style="color:#6b7280;">Preferencias</a> · <a href="{{WEB_VIEW_URL}}" style="color:#6b7280;">Ver en navegador</a>'],
            ],
        ];
    }
}
