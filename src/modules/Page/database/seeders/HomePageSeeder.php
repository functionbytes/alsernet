<?php

namespace Modules\Page\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Locales\Models\Locale;
use Modules\Page\Enums\PageStatus;
use Modules\Page\Models\Page;

/**
 * Crea una página "Inicio" con `template='homepage'` + su traducción en el
 * locale por defecto. Sin esto, `/` devuelve 404 porque
 * `PublicController::showHomepage()` no encuentra ninguna Page con ese
 * template. Idempotente: corre varias veces sin duplicar.
 */
class HomePageSeeder extends Seeder
{
    public function run(): void
    {
        // Usuario autor: toma el primero disponible o crea un placeholder admin.
        $user = User::query()->first();
        if (! $user) {
            $user = User::factory()->create([
                'name' => 'CMS Bot',
                'email' => 'cms@example.local',
            ]);
        }

        // Locale por defecto (ej. 'es').
        $code = config('app.locale', 'es');
        $defaultLocale = Locale::query()->firstOrCreate(
            ['code' => $code],
            ['name' => 'Español', 'native_name' => 'Español']
        );

        // Página Inicio con template=homepage — idempotente por template+slug.
        $page = Page::query()->firstOrCreate(
            ['template' => 'homepage'],
            [
                'title' => 'Inicio',
                'slug' => 'inicio',
                'content' => '<section class="container py-5"><h1>Bienvenido</h1><p>Esta es la página de inicio generada por HomePageSeeder.</p></section>',
                'status' => PageStatus::Published->value,
                'user_id' => $user->id,
                'seo_title' => 'Inicio',
                'seo_description' => 'Página de inicio del sitio.',
                'published_at' => now(),
            ]
        );

        // Traducción idempotente en el locale default.
        $page->translations()->firstOrCreate(
            ['locale_id' => $defaultLocale->id],
            [
                'title' => 'Inicio',
                'slug' => 'inicio',
                'content' => $page->content,
                'status' => PageStatus::Published->value,
                'seo_title' => 'Inicio',
                'seo_description' => 'Página de inicio del sitio.',
            ]
        );
    }
}
