<?php

namespace Modules\Template\Theme;

class Asset
{
    public static string $path = '';

    protected array $containers = [];

    public function addPath(string $path): void
    {
        // asset() genera URL absoluta; rtrim+/ restaura la barra final que asset() elimina
        static::$path = rtrim(asset(rtrim($path, '/')), '/').'/';

        foreach ($this->containers as $container) {
            $container->setThemePath(static::$path);
        }
    }

    public function container(string $name = 'default'): AssetContainer
    {
        if (! isset($this->containers[$name])) {
            $this->containers[$name] = new AssetContainer($name, static::$path);
        }

        return $this->containers[$name];
    }

    public function flush(): void
    {
        $this->containers = [];
        static::$path = '';
    }

    /**
     * Delega llamadas al contenedor 'default'
     * Permite: $theme->asset()->usePath()->add(...)
     */
    public function __call(string $method, array $parameters): mixed
    {
        return call_user_func_array([$this->container('default'), $method], $parameters);
    }
}
