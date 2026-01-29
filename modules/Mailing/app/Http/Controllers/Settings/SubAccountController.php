<?php

namespace Modules\Mailing\Http\Controllers\Settings;

use Illuminate\Http\Request;
use Modules\Mailing\Http\Controllers\Controller;

class SubAccountController extends Controller
{
    /**
     * Searching.
     */
    public function search($request)
    {
        if (! $request->user()->admin->can('read', new \Modules\Mailing\Models\SubAccount)) {
            return $this->notAuthorized();
        }

        // If admin can view all sending domains
        if (! $request->user()->admin->can('readAll', new \Modules\Mailing\Models\SubAccount)) {
            $request->merge(['admin_id' => $request->user()->admin->id]);
        }

        return \Modules\Mailing\Models\SubAccount::search($request);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $accounts = $this->search($request);

        return view('admin.sub_accounts.index', [
            'accounts' => $accounts,
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listing(Request $request)
    {
        $accounts = $this->search($request)->paginate($request->per_page);

        return view('admin.sub_accounts._list', [
            'accounts' => $accounts,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function delete(Request $request, $uid)
    {
        try {
            $account = \Modules\Mailing\Models\SubAccount::findByUid($uid);

            // authorize
            if ($request->user()->admin->can('delete', $account)) {
                $account = \Modules\Mailing\Models\SubAccountSendGrid::findByUid($uid);
                $account->delete();
            }

            // Redirect to my lists page
            echo trans('messages.sub_accounts.deleted');
        } catch (\Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);

            return;
        }
    }

    /**
     * Delete confirm message.
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function deleteConfirm(Request $request, $uid)
    {
        // @todo hard-coded for SendGrid
        $account = \Modules\Mailing\Models\SubAccountSendGrid::findByUid($uid);

        return view('admin.sub_accounts.delete_confirm', [
            'account' => $account,
        ]);
    }
}
