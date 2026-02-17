<?php

namespace Modules\Menu\View\Components;

use Illuminate\View\Component;
use Modules\Menu\Models\Menu as MenuModel;

class Menu extends Component
{
    public string $location;

    public string $class;

    public ?MenuModel $menu;

    /**
     * Create a new component instance.
     */
    public function __construct(string $location, string $class = '')
    {
        $this->location = $location;
        $this->class = $class;

        $this->menu = MenuModel::where('location', $location)
            ->where('status', true)
            ->with(['items' => function ($query) {
                $query->with('children');
            }])
            ->first();
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render()
    {
        return view('menu::components.menu');
    }
}
