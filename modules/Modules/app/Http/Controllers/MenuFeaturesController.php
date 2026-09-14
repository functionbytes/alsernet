<?php

namespace Modules\Modules\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Modules\Http\Requests\UpdateMenuFeaturesRequest;
use Modules\Modules\Models\NavItemSetting;
use Modules\Theme\Services\NavService;

class MenuFeaturesController extends Controller
{
    public function index(): View
    {
        $this->authorize('modules.manage');

        $items = collect(NavService::getAllItemsForAdmin())
            ->groupBy('group');

        return view('modules::menu-features', [
            'groups' => $items,
        ]);
    }

    public function update(UpdateMenuFeaturesRequest $request): RedirectResponse
    {
        $enabledKeys = collect($request->validated('enabled', []));

        foreach (NavService::getAllItemsForAdmin() as $item) {
            NavItemSetting::setEnabled($item['key'], $enabledKeys->contains($item['key']));
        }

        cache()->forget('module_nav_flags');

        return back()->with('success', 'Funcionalidades de menú actualizadas correctamente.');
    }
}
