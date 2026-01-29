<?php

namespace Modules\Mailing\Http\Controllers\Settings;

use Illuminate\Http\Request;
use Modules\Mailing\Http\Controllers\Controller;

class OpenLogController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->user()->admin->getPermission('report_open_log') == 'no') {
            return $this->notAuthorized();
        }

        $items = \Modules\Mailing\Models\OpenLog::getAll();

        return view('admin.open_logs.index', [
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
        if ($request->user()->admin->getPermission('report_open_log') == 'no') {
            return $this->notAuthorized();
        }

        $items = \Modules\Mailing\Models\OpenLog::search($request)->paginate($request->per_page);

        return view('admin.open_logs._list', [
            'items' => $items,
        ]);
    }
}
