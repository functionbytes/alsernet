<?php

namespace Modules\Widget\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Modules\Widget\Events\RenderingWidgetSettings;
use Modules\Widget\Facades\WidgetGroup;
use Modules\Widget\Models\Widget;

class WidgetController extends Controller
{
    public function index()
    {
        RenderingWidgetSettings::dispatch();

        $widgets = Widget::query()->where('theme', Widget::getThemeName())->get();

        $groups = WidgetGroup::getGroups();
        foreach ($widgets as $widget) {
            if (! Arr::has($groups, $widget->sidebar_id)) {
                continue;
            }

            WidgetGroup::group($widget->sidebar_id)
                ->position($widget->position)
                ->addWidget($widget->widget_id, $widget->data);
        }

        return view('widget::list');
    }

    public function update(Request $request): JsonResponse
    {
        try {
            $sidebarId = $request->input('sidebar_id');
            $themeName = Widget::getThemeName();

            Widget::query()->where([
                'sidebar_id' => $sidebarId,
                'theme' => $themeName,
            ])->delete();

            foreach (array_filter($request->input('items', [])) as $key => $item) {
                parse_str($item, $data);

                if (empty($data['id'])) {
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
        $widgetId = $request->input('widget_id');

        if (! class_exists($widgetId)) {
            return response()->json(['error' => true, 'message' => 'Widget class not found'], 404);
        }

        $widget = new $widgetId;

        return response()->json([
            'data' => ['html' => $widget->form($request->input('sidebar_id'), $request->input('position', 0))],
        ]);
    }
}
