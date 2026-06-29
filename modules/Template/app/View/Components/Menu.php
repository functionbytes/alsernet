<?php

namespace Modules\Template\View\Components;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\Component;
use Illuminate\View\View;
use Modules\Template\Models\Menu as MenuModel;
use Modules\Template\Models\MenuItem;

class Menu extends Component
{
    public string $location;

    public string $class;

    public ?MenuModel $menu;

    /**
     * @var Collection<int, MenuItem>
     */
    public $items;

    /**
     * Create a new component instance.
     */
    public function __construct(string $location, string $class = '')
    {
        $this->location = $location;
        $this->class = $class;

        $this->menu = MenuModel::query()
            ->with([
                'items' => fn ($q) => $q->orderBy('order'),
                'items.children' => fn ($q) => $q->orderBy('order'),
            ])
            ->where('location', $location)
            ->where('status', true)
            ->first();

        $this->items = $this->menu?->items ?? collect();
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('template::components.menu');
    }
}
