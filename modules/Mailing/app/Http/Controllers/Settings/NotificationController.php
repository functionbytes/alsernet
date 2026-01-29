<?php

namespace Modules\Mailing\Http\Controllers\Settings;

use Illuminate\Http\Request;
use Modules\Mailing\Http\Controllers\Controller;
use Modules\Mailing\Models\Notification;

class NotificationController extends Controller
{
    /**
     * Notification index.
     */
    public function index(Request $request)
    {
        return view('admin.notifications.index');
    }

    /**
     * Notification listing.
     */
    public function listing(Request $request)
    {
        $notifications = Notification::search($request)->paginate($request->per_page);

        return view('admin.notifications.listing', [
            'notifications' => $notifications,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function delete(Request $request)
    {
        if (isSiteDemo()) {
            echo trans('messages.operation_not_allowed_in_demo');

            return;
        }

        $notifications = Notification::whereIn(
            'uid',
            is_array($request->uids) ? $request->uids : explode(',', $request->uids)
        );
        $count = $notifications->count();

        foreach ($notifications->get() as $notification) {
            $notifications->delete();
        }

        // Redirect to my lists page
        echo trans('messages.notifications.deleted', ['number' => $count]);
    }

    public function popup(Request $request)
    {
        return view('admin.notifications.popup');
    }
}
