<?php

namespace Modules\Template\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Modules\Template\Models\Template;

class TemplateDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get first user or create one
        $user = User::first();

        if (!$user) {
            $this->command->warn('No users found in database. Skipping template seeding.');

            return;
        }

        // Create default templates
        $templates = [
            [
                'name' => 'Default Template',
                'slug' => 'default',
                'description' => 'Plantilla predeterminada con layout básico de dos columnas',
                'content' => $this->getDefaultTemplateContent(),
                'template_path' => 'platform/themes/default',
                'status' => 'active',
                'user_id' => $user->id,
                'author' => 'Alsernet',
                'version' => '1.0.0',
                'inherit' => null,
            ],
            [
                'name' => 'Full Width Template',
                'slug' => 'full-width',
                'description' => 'Plantilla de ancho completo sin sidebar',
                'content' => $this->getFullWidthTemplateContent(),
                'template_path' => 'platform/themes/full-width',
                'status' => 'inactive',
                'user_id' => $user->id,
                'author' => 'Alsernet',
                'version' => '1.0.0',
                'inherit' => 'default',
            ],
            [
                'name' => 'Landing Page Template',
                'slug' => 'landing',
                'description' => 'Plantilla optimizada para landing pages',
                'content' => $this->getLandingTemplateContent(),
                'template_path' => 'platform/themes/landing',
                'status' => 'inactive',
                'user_id' => $user->id,
                'author' => 'Alsernet',
                'version' => '1.0.0',
                'inherit' => 'default',
            ],
        ];

        foreach ($templates as $templateData) {
            Template::updateOrCreate(
                ['slug' => $templateData['slug']],
                $templateData
            );
        }

        $this->command->info('Template seeds created successfully.');

        // Seed UI Blocks
        $this->call(UiBlockSeeder::class);

        // Create template directories and JSON files
        $this->createTemplateDirectories($user);
    }

    /**
     * Create template directories and JSON files in platform/themes/
     */
    private function createTemplateDirectories(User $user): void
    {
        $basePath = base_path('platform/themes');

        if (!is_dir($basePath)) {
            mkdir($basePath, 0755, true);
        }

        $templates = [
            'default' => [
                'name' => 'Default Template',
                'namespace' => 'Template\\Default\\',
                'description' => 'Plantilla predeterminada para contenido general',
                'author' => 'Alsernet',
                'version' => '1.0.0',
                'inherit' => null,
                'required_plugins' => [],
            ],
            'full-width' => [
                'name' => 'Full Width Template',
                'namespace' => 'Template\\FullWidth\\',
                'description' => 'Plantilla de ancho completo sin sidebars',
                'author' => 'Alsernet',
                'version' => '1.0.0',
                'inherit' => 'default',
                'required_plugins' => [],
            ],
            'landing' => [
                'name' => 'Landing Page Template',
                'namespace' => 'Template\\Landing\\',
                'description' => 'Plantilla optimizada para landing pages y conversión',
                'author' => 'Alsernet',
                'version' => '1.0.0',
                'inherit' => 'default',
                'required_plugins' => [],
            ],
        ];

        foreach ($templates as $slug => $metadata) {
            $templatePath = $basePath.'/'.$slug;

            // Create template directory structure
            if (!is_dir($templatePath)) {
                mkdir($templatePath, 0755, true);
                mkdir($templatePath.'/layouts', 0755, true);
                mkdir($templatePath.'/partials', 0755, true);
                mkdir($templatePath.'/assets', 0755, true);
                mkdir($templatePath.'/assets/css', 0755, true);
                mkdir($templatePath.'/assets/js', 0755, true);
            }

            // Create template.json
            $jsonPath = $templatePath.'/template.json';
            if (!File::exists($jsonPath)) {
                File::put($jsonPath, json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                $this->command->line("Created: {$jsonPath}");
            }

            // Create config.php
            $configPath = $templatePath.'/config.php';
            if (!File::exists($configPath)) {
                File::put($configPath, $this->getTemplateConfigContent($slug));
                $this->command->line("Created: {$configPath}");
            }

            // Create default layout file
            $layoutPath = $templatePath.'/layouts/default.blade.php';
            if (!File::exists($layoutPath)) {
                File::put($layoutPath, $this->getDefaultLayoutBladeContent());
                $this->command->line("Created: {$layoutPath}");
            }

            // Create placeholder screenshot.png (1x1 transparent PNG)
            $screenshotPath = $templatePath.'/screenshot.png';
            if (!File::exists($screenshotPath)) {
                $this->createPlaceholderScreenshot($screenshotPath);
                $this->command->line("Created: {$screenshotPath}");
            }
        }
    }

    /**
     * Create a placeholder screenshot PNG file
     */
    private function createPlaceholderScreenshot(string $path): void
    {
        // Create a simple 400x300 placeholder image
        $image = imagecreatetruecolor(400, 300);
        $lightGray = imagecolorallocate($image, 240, 240, 240);
        $darkGray = imagecolorallocate($image, 150, 150, 150);

        imagefilledrectangle($image, 0, 0, 400, 300, $lightGray);
        imagestring($image, 3, 150, 140, 'Template Screenshot', $darkGray);

        imagepng($image, $path);
        imagedestroy($image);
    }

    /**
     * Get default template content
     */
    private function getDefaultTemplateContent(): string
    {
        return <<<'BLADE'
<div class="container my-5">
    <div class="row">
        <div class="col-lg-8">
            <article>
                <h1 class="mb-3">{{ $title ?? 'Template Title' }}</h1>
                <p class="text-muted mb-4">{{ $description ?? 'Template description' }}</p>
                <div class="content">
                    {!! $content ?? '<p>No content available</p>' !!}
                </div>
            </article>
        </div>
        <div class="col-lg-4">
            <div class="sidebar">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Sidebar</h5>
                        <p class="card-text">Sidebar content here</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
BLADE;
    }

    /**
     * Get full width template content
     */
    private function getFullWidthTemplateContent(): string
    {
        return <<<'BLADE'
<div class="container-fluid my-5">
    <article class="full-width-content">
        <h1 class="mb-3">{{ $title ?? 'Full Width Template' }}</h1>
        <p class="text-muted mb-4">{{ $description ?? 'Full width content area' }}</p>
        <div class="content">
            {!! $content ?? '<p>No content available</p>' !!}
        </div>
    </article>
</div>
BLADE;
    }

    /**
     * Get landing page template content
     */
    private function getLandingTemplateContent(): string
    {
        return <<<'BLADE'
<div class="landing-page">
    <section class="hero py-5 bg-light">
        <div class="container">
            <h1 class="display-4">{{ $title ?? 'Welcome' }}</h1>
            <p class="lead">{{ $description ?? 'Landing page template' }}</p>
        </div>
    </section>

    <section class="content py-5">
        <div class="container">
            {!! $content ?? '<p>No content available</p>' !!}
        </div>
    </section>

    <section class="cta py-5 bg-light">
        <div class="container text-center">
            <p class="text-muted">Call to action section</p>
        </div>
    </section>
</div>
BLADE;
    }

    /**
     * Get default layout Blade content
     */
    private function getDefaultLayoutBladeContent(): string
    {
        return <<<'BLADE'
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    @yield('css')
</head>
<body>
    <!-- Header -->
    @include('template::partials.header')

    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    @include('template::partials.footer')

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    @yield('js')
</body>
</html>
BLADE;
    }

    /**
     * Get template config.php content
     */
    private function getTemplateConfigContent(string $slug): string
    {
        $configs = [
            'default' => <<<'PHP'
<?php

return [
    'inherit' => null,
    'options' => [
        'sidebar_position' => 'right',
        'layout_type' => 'default',
        'max_width' => '1200px',
    ],
    'assets' => [
        'css' => [
            'style' => 'css/style.css',
        ],
        'js' => [
            'main' => 'js/main.js',
        ],
    ],
];
PHP,
            'full-width' => <<<'PHP'
<?php

return [
    'inherit' => 'default',
    'options' => [
        'sidebar_position' => 'none',
        'layout_type' => 'full-width',
        'max_width' => '100%',
        'padding' => 'normal',
    ],
    'assets' => [
        'css' => [
            'style' => 'css/style.css',
            'full-width' => 'css/full-width.css',
        ],
        'js' => [
            'main' => 'js/main.js',
        ],
    ],
];
PHP,
            'landing' => <<<'PHP'
<?php

return [
    'inherit' => 'default',
    'options' => [
        'sidebar_position' => 'none',
        'layout_type' => 'landing',
        'max_width' => '100%',
        'header_style' => 'minimalist',
        'footer_style' => 'compact',
        'conversion_focused' => true,
    ],
    'assets' => [
        'css' => [
            'style' => 'css/style.css',
            'landing' => 'css/landing.css',
        ],
        'js' => [
            'main' => 'js/main.js',
            'landing' => 'js/landing.js',
        ],
    ],
];
PHP,
        ];

        return $configs[$slug] ?? $configs['default'];
    }
}
