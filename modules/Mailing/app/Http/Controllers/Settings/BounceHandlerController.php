<?php

namespace Modules\Mailing\Http\Controllers\Settings;

use Illuminate\Http\Request;
use Modules\Mailing\Http\Controllers\Controller;

use function Modules\Mailing\Helpers\generatePublicPath;

class BounceHandlerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (\Gate::denies('read', new \Modules\Mailing\Models\BounceHandler)) {
            return $this->notAuthorized();
        }

        // If admin can view all sending domains
        if (! $request->user()->admin->can('readAll', new \Modules\Mailing\Models\BounceHandler)) {
            $request->merge(['admin_id' => $request->user()->admin->id]);
        }

        $items = \Modules\Mailing\Models\BounceHandler::search($request);

        return view('admin.bounce_handlers.index', [
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
        if (\Gate::denies('read', new \Modules\Mailing\Models\BounceHandler)) {
            return $this->notAuthorized();
        }

        // If admin can view all sending domains
        if (! $request->user()->admin->can('readAll', new \Modules\Mailing\Models\BounceHandler)) {
            $request->merge(['admin_id' => $request->user()->admin->id]);
        }

        $items = \Modules\Mailing\Models\BounceHandler::search($request)->paginate($request->per_page);

        return view('admin.bounce_handlers._list', [
            'items' => $items,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $server = new \Modules\Mailing\Models\BounceHandler;
        $server->status = 'active';
        $server->uid = '0';
        $server->fill($request->old());

        // authorize
        if (\Gate::denies('create', $server)) {
            return $this->notAuthorized();
        }

        return view('admin.bounce_handlers.create', [
            'server' => $server,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Get current user
        $current_user = $request->user();
        $server = new \Modules\Mailing\Models\BounceHandler;

        // authorize
        if (\Gate::denies('create', $server)) {
            return $this->notAuthorized();
        }

        // save posted data
        if ($request->isMethod('post')) {
            $this->validate($request, \Modules\Mailing\Models\BounceHandler::rules());

            // Save current user info
            $server->fill($request->all());
            $server->admin_id = $request->user()->admin->id;
            $server->status = 'active';

            if ($server->save()) {
                $request->session()->flash('alert-success', trans('messages.bounce_handler.created'));

                return redirect()->action('Settings\BounceHandlerController@index');
            }
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id) {}

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id)
    {
        $server = \Modules\Mailing\Models\BounceHandler::findByUid($id);

        // authorize
        if (\Gate::denies('update', $server)) {
            return $this->notAuthorized();
        }

        $server->fill($request->old());

        return view('admin.bounce_handlers.edit', [
            'server' => $server,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // Get current user
        $current_user = $request->user();
        $server = \Modules\Mailing\Models\BounceHandler::findByUid($id);

        // authorize
        if (\Gate::denies('update', $server)) {
            return $this->notAuthorized();
        }

        // save posted data
        if ($request->isMethod('patch')) {
            $this->validate($request, \Modules\Mailing\Models\BounceHandler::rules());

            // Save current user info
            $server->fill($request->all());

            if ($server->save()) {
                $request->session()->flash('alert-success', trans('messages.bounce_handler.updated'));

                return redirect()->action('Settings\BounceHandlerController@index');
            }
        }
    }

    /**
     * Custom sort items.
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function sort(Request $request)
    {
        echo trans('messages._deleted_');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function delete(Request $request)
    {
        $items = \Modules\Mailing\Models\BounceHandler::whereIn(
            'uid',
            is_array($request->uids) ? $request->uids : explode(',', $request->uids)
        );

        foreach ($items->get() as $item) {
            // authorize
            if (\Gate::denies('delete', $item)) {
                return;
            }
        }

        foreach ($items->get() as $item) {
            $item->delete();
        }

        // Redirect to my lists page
        echo trans('messages.bounce_handlers.deleted');
    }

    /**
     * Test Bounce handler.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function test(Request $request, $uid)
    {
        // Get current user
        $current_user = $request->user();

        // Fill new server info
        if ($uid) {
            $server = \Modules\Mailing\Models\BounceHandler::findByUid($uid);
        } else {
            $server = new \Modules\Mailing\Models\BounceHandler;
        }

        $server->fill($request->all());

        // authorize
        if (\Gate::denies('test', $server)) {
            return $this->notAuthorized();
        }

        try {
            $server->test();

            return response()->json([
                'status' => 'success', // or success
                'message' => trans('messages.bounce_handler.test_success'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error', // or success
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function run(Request $request, $uid)
    {
        $handler = \Modules\Mailing\Models\BounceHandler::findByUid($uid);
        $todayLogFile = storage_path('logs/'.php_sapi_name().'/handler-'.$handler->uid.'-'.date('Y-m-d').'.log');
        echo url(generatePublicPath($todayLogFile));
        $handler->start();
    }
}
