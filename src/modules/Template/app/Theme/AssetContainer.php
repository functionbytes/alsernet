<?php

namespace Modules\Template\Theme;

class AssetContainer
{
    protected bool $usePath = false;

    protected array $assets = [];

    protected string $themePath = '';

    public function __construct(
        protected string $name,
        string $themePath = ''
    ) {
        $this->themePath = $themePath ? rtrim($themePath, '/').'/' : '';
    }

    public function setThemePath(string $path): void
    {
        $this->themePath = rtrim($path, '/').'/';
    }

    /**
     * Activa el uso del path del tema para el siguiente add()
     */
    public function usePath(bool $use = true): self
    {
        $this->usePath = $use;

        return $this;
    }

    public function isUsePath(): bool
    {
        return $this->usePath;
    }

    /**
     * Añade un asset (CSS o JS) al contenedor.
     * La extensión del archivo determina si es style o script.
     */
    public function add(
        string $name,
        string $source,
        array $dependencies = [],
        array $attributes = [],
        ?string $version = null
    ): self {
        $sourceClean = strtok($source, '?');
        $ext = strtolower(pathinfo($sourceClean, PATHINFO_EXTENSION));
        $type = ($ext === 'css') ? 'style' : 'script';

        if ($this->usePath && $this->themePath && ! preg_match('#^https?://#', $source)) {
            $source = $this->themePath.ltrim($source, '/');
            $this->usePath = false;
        }

        if ($version && ! str_contains($source, '?')) {
            $source .= '?v='.$version;
        }

        $this->assets[$type][$name] = compact('source', 'dependencies', 'attributes');

        return $this;
    }

    /**
     * Añade un CSS explícitamente
     */
    public function style(string $name, string $source, array $dependencies = [], array $attributes = []): self
    {
        if ($this->usePath && $this->themePath && ! preg_match('#^https?://#', $source)) {
            $source = $this->themePath.ltrim($source, '/');
            $this->usePath = false;
        }

        if (! array_key_exists('media', $attributes)) {
            $attributes['media'] = 'all';
        }

        $this->assets['style'][$name] = compact('source', 'dependencies', 'attributes');

        return $this;
    }

    /**
     * Añade un JS explícitamente
     */
    public function script(string $name, string $source, array $dependencies = [], array $attributes = []): self
    {
        if ($this->usePath && $this->themePath && ! preg_match('#^https?://#', $source)) {
            $source = $this->themePath.ltrim($source, '/');
            $this->usePath = false;
        }

        $this->assets['script'][$name] = compact('source', 'dependencies', 'attributes');

        return $this;
    }

    /**
     * Devuelve el HTML de todos los estilos CSS registrados
     */
    public function styles(): string
    {
        return $this->group('style');
    }

    /**
     * Devuelve el HTML de todos los scripts JS registrados
     */
    public function scripts(): string
    {
        return $this->group('script');
    }

    protected function group(string $group): string
    {
        if (empty($this->assets[$group])) {
            return '';
        }

        $html = '';

        foreach ($this->arrange($this->assets[$group]) as $name => $asset) {
            $source = $asset['source'];
            $attributes = $asset['attributes'];

            if ($group === 'style') {
                $media = $attributes['media'] ?? 'all';
                $extras = '';
                foreach ($attributes as $key => $value) {
                    if ($key === 'media') {
                        continue;
                    }
                    if (is_bool($value)) {
                        if ($value) {
                            $extras .= ' '.$key;
                        }
                    } else {
                        $extras .= ' '.$key.'="'.e($value).'"';
                    }
                }
                $html .= '<link rel="stylesheet" href="'.e($source).'" media="'.e($media).'"'.$extras.'>'.PHP_EOL;
            } else {
                $extras = '';
                foreach ($attributes as $key => $value) {
                    if (is_bool($value)) {
                        if ($value) {
                            $extras .= ' '.$key;
                        }
                    } elseif ($key !== 'media') {
                        $extras .= ' '.$key.'="'.e($value).'"';
                    }
                }
                $html .= '<script src="'.e($source).'"'.$extras.'></script>'.PHP_EOL;
            }
        }

        return $html;
    }

    /**
     * Ordena assets por dependencias (topological sort simplificado)
     */
    protected function arrange(array $assets): array
    {
        $sorted = [];
        $visited = [];

        $visit = function (string $name) use (&$visit, &$sorted, &$visited, $assets): void {
            if (isset($visited[$name])) {
                return;
            }
            $visited[$name] = true;

            foreach ($assets[$name]['dependencies'] ?? [] as $dep) {
                if (isset($assets[$dep])) {
                    $visit($dep);
                }
            }

            if (isset($assets[$name])) {
                $sorted[$name] = $assets[$name];
            }
        };

        foreach (array_keys($assets) as $name) {
            $visit($name);
        }

        return $sorted;
    }

    public function getAssets(): array
    {
        return $this->assets;
    }

    public function flush(): void
    {
        $this->assets = [];
    }
}
