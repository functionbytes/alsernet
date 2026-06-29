<?php

namespace Modules\Shortcode\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ShortcodeMakeCommand extends Command
{
    protected $signature = 'shortcode:make
                            {name : Nombre del shortcode (ej: my-widget)}
                            {--module= : Módulo de destino (ej: Blog); por defecto: Shortcode}';

    protected $description = 'Genera un esqueleto de shortcode registrado en el ServiceProvider del módulo elegido';

    public function handle(): int
    {
        $name = Str::kebab((string) $this->argument('name'));
        $module = $this->option('module') ?: 'Shortcode';

        if (! preg_match('/^[a-z][a-z0-9-]*$/', $name)) {
            $this->error('El nombre solo admite minúsculas, números y guiones (kebab-case).');

            return self::FAILURE;
        }

        $modulePath = base_path("modules/{$module}");
        if (! is_dir($modulePath)) {
            $this->error("El módulo [{$module}] no existe en {$modulePath}.");

            return self::FAILURE;
        }

        $stubPath = $modulePath.'/app/Shortcodes/'.Str::studly($name).'Shortcode.php';
        if (file_exists($stubPath)) {
            $this->error("Ya existe: {$stubPath}");

            return self::FAILURE;
        }

        @mkdir(dirname($stubPath), 0755, true);

        $namespace = "Modules\\{$module}\\Shortcodes";
        $className = Str::studly($name).'Shortcode';

        $stub = <<<PHP
<?php

namespace {$namespace};

class {$className}
{
    public const NAME = '{$name}';

    public const META = [
        'description' => 'Descripción del shortcode {$name}.',
        'example' => '[{$name}]contenido[/{$name}]',
        'attributes' => [
            // 'key' => 'Descripción del atributo',
        ],
        // 'raw' => true,        // el contenido no se re-procesa como shortcodes
        // 'cacheable' => false, // saltear cache global si aparece este shortcode
        // 'deprecated' => '2.0',
    ];

    /**
     * @param  array<string, string>  \$attrs
     */
    public function __invoke(array \$attrs, string \$content): string
    {
        \$attrs = shortcode_atts([
            'class' => '',
        ], \$attrs);

        return sprintf(
            '<div class="%s">%s</div>',
            htmlspecialchars(\$attrs['class']),
            \$content
        );
    }
}
PHP;

        file_put_contents($stubPath, $stub);

        $this->info("Shortcode creado: {$stubPath}");
        $this->line('');
        $this->line('Registralo en el boot() del ServiceProvider del módulo:');
        $this->line('');
        $this->line('    use '.$namespace.'\\'.$className.';');
        $this->line('');
        $this->line('    app(\'shortcode\')->register(');
        $this->line("        {$className}::NAME,");
        $this->line("        app({$className}::class),");
        $this->line("        {$className}::META");
        $this->line('    );');
        $this->line('');

        return self::SUCCESS;
    }
}
