<?php

namespace Modules\Widget\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Widget\Events\RenderingWidgetSettings;
use Modules\Widget\Facades\WidgetGroup;
use Modules\Widget\Models\Widget;

class WidgetController extends Controller
{
    public function index(): View
    {
        $this->authorize('settings');

        RenderingWidgetSettings::dispatch();

        $themeName = Widget::getThemeName();
        $savedWidgets = Widget::query()->where('theme', $themeName)->orderBy('position')->get();

        // Build a lookup of saved widgets keyed by sidebar_id for the view
        $savedBySidebar = $savedWidgets->groupBy('sidebar_id');

        $groups = WidgetGroup::getGroups();
        $availableWidgets = \Modules\Widget\Facades\Widget::getWidgets(); // widget_id => class

        return view('widget::list', compact('groups', 'availableWidgets', 'savedBySidebar', 'themeName'));
    }

    public function update(Request $request): JsonResponse
    {
        $this->authorize('settings');

        try {
            $sidebarId = $request->input('sidebar_id');
            $themeName = Widget::getThemeName();

            Widget::query()->where([
                'sidebar_id' => $sidebarId,
                'theme' => $themeName,
            ])->delete();

            $allowedWidgets = \Modules\Widget\Facades\Widget::getWidgets();

            foreach (array_filter($request->input('items', [])) as $key => $item) {
                parse_str($item, $data);

                if (empty($data['id'])) {
                    continue;
                }

                if (! in_array($data['id'], $allowedWidgets, true)) {
                    continue;
                }

                Widget::query()->create([
                    'sidebar_id' => $sidebarId,
                    'widget_id' => $data['id'],
                    'theme' => $themeName,
                    'position' => $key,
                    'data' => $data,
                ]);
            }

            $widgetAreas = Widget::query()->where([
                'sidebar_id' => $sidebarId,
                'theme' => $themeName,
            ])->get();

            return response()->json([
                'data' => view('widget::item', compact('widgetAreas'))->render(),
                'message' => trans('widget::widget.save_success'),
            ]);
        } catch (Exception $exception) {
            return response()->json([
                'error' => true,
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function destroy(Request $request): JsonResponse
    {
        $this->authorize('settings');

        try {
            Widget::query()->where([
                'theme' => Widget::getThemeName(),
                'sidebar_id' => $request->input('sidebar_id'),
                'position' => $request->input('position'),
                'widget_id' => $request->input('widget_id'),
            ])->delete();

            $sidebarId = $request->input('sidebar_id');
            $themeName = Widget::getThemeName();

            $widgetAreas = Widget::query()->where([
                'sidebar_id' => $sidebarId,
                'theme' => $themeName,
            ])->get();

            return response()->json([
                'data' => view('widget::item', compact('widgetAreas'))->render(),
                'message' => trans('widget::widget.delete_success'),
            ]);
        } catch (Exception $exception) {
            return response()->json([
                'error' => true,
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function showWidget(Request $request): JsonResponse
    {
        $this->authorize('settings');

        $widgetId = $request->input('widget_id');
        $widget = $this->resolveWidget($widgetId);

        return response()->json([
            'data' => ['html' => $widget->form($request->input('sidebar_id'), $request->input('position', 0))],
        ]);
    }

    private function resolveWidget(string $widgetId): object
    {
        $allowed = \Modules\Widget\Facades\Widget::getWidgets();

        if (! in_array($widgetId, $allowed, true)) {
            abort(422, 'Widget no permitido');
        }

        return app($widgetId);
    }
}
