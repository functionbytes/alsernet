<?php

namespace Modules\Template\Theme;

use Illuminate\Support\Facades\URL;

class Breadcrumb
{
    public array $crumbs = [];

    public function enabled(): bool
    {
        return (bool) theme_option('theme_breadcrumb_enabled', 1) == 1;
    }

    public function add(string|array|null $label, ?string $url = ''): self
    {
        if (! $this->enabled()) {
            return $this;
        }

        if (is_array($label)) {
            foreach ($label as $crumb) {
                $crumb = array_merge(['label' => '', 'url' => ''], $crumb);
                $this->add($crumb['label'], $crumb['url']);
            }

            return $this;
        }

        $label = trim(strip_tags((string) $label, '<i><b><strong>'));

        if ($url && ! preg_match('|^https?|', $url)) {
            $url = URL::to($url);
        }

        $this->crumbs[] = ['label' => $label, 'url' => $url ?? ''];

        return $this;
    }

    public function render(string $view = 'template::partials.breadcrumb'): string
    {
        if (! view()->exists($view)) {
            return '';
        }

        return view($view, ['breadcrumb' => $this])->render();
    }

    public function getCrumbs(): array
    {
        if (! $this->enabled()) {
            return [];
        }

        return collect($this->crumbs)->unique('label')->values()->toArray();
    }
}
