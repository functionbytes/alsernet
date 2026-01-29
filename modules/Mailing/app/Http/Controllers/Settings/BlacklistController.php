<?php

namespace Modules\Mailing\Http\Controllers\Settings;

use Exception;
use Illuminate\Http\Request;
use Modules\Mailing\Http\Controllers\Controller;
use Modules\Mailing\Jobs\ImportBlacklistJob;
use Modules\Mailing\Models\Blacklist;
use Modules\Mailing\Models\JobMonitor;

class BlacklistController extends Controller
{
    /**
     * Search items.
     */
    public function search($request)
    {
        $request->merge(['admin_id' => $request->user()->admin->id]);
        $blacklists = \Modules\Mailing\Models\Blacklist::search($request);

        return $blacklists;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $admin = $request->user()->admin;

        if (! $admin->can('read', new \Modules\Mailing\Models\Blacklist)) {
            return $this->notAuthorized();
        }

        $blacklists = $this->search($request);

        // Get current job

        return view('admin.blacklists.index', [
            'blacklists' => $blacklists,

        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listing(Request $request)
    {
        if (! $request->user()->admin->can('read', new \Modules\Mailing\Models\Blacklist)) {
            return $this->notAuthorized();
        }

        $blacklists = $this->search($request)->paginate($request->per_page);

        return view('admin.blacklists._list', [
            'blacklists' => $blacklists,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function delete(Request $request)
    {
        if ($request->select_tool == 'all_items') {
            $blacklists = $this->search($request);
        } else {
            $blacklists = \Modules\Mailing\Models\Blacklist::whereIn(
                'id',
                is_array($request->uids) ? $request->uids : explode(',', $request->uids)
            );
        }

        foreach ($blacklists->get() as $blacklist) {
            // authorize
            if ($request->user()->admin->can('delete', $blacklist)) {
                // Log
                $blacklist->delist();
                $blacklist->delete();
            }
        }

        // Redirect to my lists page
        echo trans('messages.blacklists.deleted');
    }

    /**
     * Start import process.
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function import(Request $request)
    {
        $admin = $request->user()->admin;
        $job = $admin->importBlacklistJobs()->first();

        if ($job) {
            return view('admin.blacklists.import', [
                'currentJobUid' => $job->uid,
                'cancelUrl' => action('Settings\BlacklistController@cancelImport', ['job_uid' => $job->uid]),
                'progressCheckUrl' => action('Settings\BlacklistController@importProgress', ['job_uid' => $job->uid]),
            ]);
        } else {
            return view('admin.blacklists.import', [
                //
            ]);
        }
    }

    public function startImport(Request $request)
    {
        $admin = $request->user()->admin;

        // authorize
        if (! $admin->can('import', new \Modules\Mailing\Models\Blacklist)) {
            return $this->notAuthorized();
        }

        if ($request->hasFile('file')) {
            $filepath = Blacklist::upload($request->file('file'));

            // Start system job
            $job = $admin->dispatchWithMonitor(new ImportBlacklistJob($filepath, $customer = null));

            return redirect()->action('Settings\BlacklistController@import');
        } else {
            throw new Exception('no upload file');
        }
    }

    /**
     * Check import proccessing.
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function importProgress(Request $request)
    {
        $admin = $request->user()->admin;

        // authorize
        if (! $admin->can('read', new \Modules\Mailing\Models\Blacklist)) {
            return $this->notAuthorized();
        }

        $job = JobMonitor::findByUid($request->job_uid);

        if (is_null($job)) {
            throw new Exception(sprintf('Blacklist import job #%s does not exist', $request->job_uid));
        }

        $progress = $job->getJsonData();
        $progress['status'] = $job->status;
        $progress['error'] = $job->error;

        // Get progress updated by the import process and status of the final job monitor
        return response()->json($progress);
    }

    /**
     * Cancel importing job.
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function cancelImport(Request $request)
    {
        $job = JobMonitor::findByUid($request->job_uid);
        if (is_null($job)) {
            throw new Exception(sprintf('Verification job #%s does not exist', $request->job_uid));
        }

        $job->cancel();

        return response()->json();
    }

    /**
     * Reason.
     *
     * @return \Illuminate\Http\Response
     */
    public function reason(Request $request, $id)
    {
        $blacklist = \Modules\Mailing\Models\Blacklist::find($id);

        return view('admin.blacklists.reason', [
            'blacklist' => $blacklist,
        ]);
    }
}
