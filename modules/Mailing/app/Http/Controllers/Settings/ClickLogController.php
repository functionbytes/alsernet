<?php

namespace Modules\Mailing\Http\Controllers\Settings;

use Illuminate\Http\Request;
use Modules\Mailing\Http\Controllers\Controller;

class ClickLogController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->user()->admin->getPermission('report_click_log') == 'no') {
            return $this->notAuthorized();
        }

        $items = \Modules\Mailing\Models\ClickLog::getAll();

        return view('admin.click_logs.index', [
            'items' => $items,
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listing(Request $request)
    {
        if ($request->user()->admin->getPermission('report_click_log') == 'no') {
            return $this->notAuthorized();
        }

        $items = \Modules\Mailing\Models\ClickLog::search($request)->paginate($request->per_page);

        return view('admin.click_logs._list', [
            'items' => $items,
        ]);
    }
}
