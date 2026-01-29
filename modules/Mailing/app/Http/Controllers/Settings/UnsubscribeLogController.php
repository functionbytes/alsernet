<?php

namespace Modules\Mailing\Http\Controllers\Settings;

use Illuminate\Http\Request;
use Modules\Mailing\Http\Controllers\Controller;

class UnsubscribeLogController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->user()->admin->getPermission('report_unsubscribe_log') == 'no') {
            return $this->notAuthorized();
        }

        $items = \Modules\Mailing\Models\UnsubscribeLog::getAll();

        return view('admin.unsubscribe_logs.index', [
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
        if ($request->user()->admin->getPermission('report_unsubscribe_log') == 'no') {
            return $this->notAuthorized();
        }

        $items = \Modules\Mailing\Models\UnsubscribeLog::search($request)->paginate($request->per_page);

        return view('admin.unsubscribe_logs._list', [
            'items' => $items,
        ]);
    }
}
