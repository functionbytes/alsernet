<?php

namespace Modules\Subscriber\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Jobs\UpdateSubscriberCategoriesJob;
use app\Library\Facades\Hook;
use App\Models\Categorie;
use App\Models\Lang;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Horizon\Models\JobMonitor;
use Modules\Subscriber\Models\Subscriber;
use Modules\Subscriber\Models\SubscriberImport;
use Modules\Warehouse\Models\Product\Product;

class SubscribersController extends Controller
{
    public function index(Request $request)
    {

        $searchKey = null ?? $request->search;

        $subscribers = Subscriber::search($searchKey);

        $subscribers = $subscribers->paginate(100);

        return view('theme.views.subscribers.subscribers.index')->with([
            'subscribers' => $subscribers,
            'searchKey' => $searchKey,
        ]);

    }

    public function edit($uid)
    {

        $subscriber = Subscriber::uid($uid);
        $categories = $subscriber->lang->categories()->available()->get()->pluck('title', 'id');
        $langs = Lang::available()->get()->prepend('', '')->pluck('title', 'id');

        return view('theme.views.subscribers.subscribers.edit')->with([
            'subscriber' => $subscriber,
            'categories' => $categories,
            'langs' => $langs,
        ]);

    }

    public function logs(Request $request, $uid)
    {

        $subscriber = Subscriber::uid($uid);

        if (! $subscriber) {
            abort(404, 'Suscriptor no encontrado');
        }

        $query = $subscriber->logs()->with('causer')->orderBy('created_at', 'desc');

        if ($request->has('search') && ! empty($request->search)) {
            $query->where('log_name', 'LIKE', '%'.$request->search.'%')
                ->orWhereHas('causer', function ($q) use ($request) {
                    $q->where('name', 'LIKE', '%'.$request->search.'%');
                });
        }

        $logs = $query->paginate(20);

        return view('theme.views.subscribers.subscribers.logs')->with([
            'subscriber' => $subscriber,
            'logs' => $logs,
        ]);

    }

    public function create()
    {

        $categories = Categorie::available()->get()->pluck('title', 'id');
        $langs = Lang::available()->get()->prepend('', '')->pluck('title', 'id');

        return view('theme.views.subscribers.subscribers.create')->with([
            'categories' => $categories,
            'langs' => $langs,
        ]);

    }

    public function update(Request $request)
    {
        $auth = app('theme');
        $subscriber = Subscriber::uid($request->uid);

        if (! $subscriber) {
            return response()->json([
                'success' => false,
                'message' => 'Suscriptor no encontrado.',
            ]);
        }

        $data = [
            'firstname' => Str::upper($request->firstname),
            'lastname' => Str::upper($request->lastname),
            'email' => Str::lower($request->email),
            'commercial' => $request->commercial,
            'parties' => $request->parties,
            'observation' => $request->observation,
            'lang_id' => $request->lang,
        ];

        $previousLangId = (int) $subscriber->lang_id;
        $currentLangId = (int) $request->lang;

        $categories = is_array($request->categories) ? $request->categories : (empty($request->categories) ? [] : explode(',', $request->categories));

        $hasLangChanged = $previousLangId !== $currentLangId;
        $hasCategoryChanges = $subscriber->categories()->count() !== count($categories);
        $changes = collect($data)->filter(fn ($value, $key) => $value !== $subscriber->$key)->isNotEmpty();

        if ($changes || $hasCategoryChanges || $hasLangChanged) {

            if ($changes) {
                $subscriber->updateWithLog($data, $auth);
            }

            if ($hasCategoryChanges || $hasLangChanged) {
                // $subscriber->updateCategoriesWithLog($categories, $auth, $hasLangChanged, $currentLangId,$previousLangId);
                UpdateSubscriberCategoriesJob::dispatch(
                    $subscriber,
                    $categories,
                    $auth,
                    $hasLangChanged,
                    $currentLangId,
                    $previousLangId
                );
            }

            return response()->json([
                'success' => true,
                'uid' => $subscriber->uid,
                'message' => 'Suscriptor actualizado correctamente.',
            ]);

        } else {

            return response()->json([
                'success' => false,
                'message' => 'No hay cambios para actualizar.',
            ]);

        }

    }

    public function store(Request $request)
    {

        $subscriber = new Subscriber;
        $subscriber->uid = $this->generate_uid('subscribers');
        $subscriber->firstname = Str::upper($request->firstname);
        $subscriber->lastname = Str::upper($request->lastname);
        $subscriber->email = Str::upper($request->email);
        $subscriber->parties = $request->parties;
        $subscriber->commercial = $request->commercial;
        $subscriber->lang_id = $request->lang;
        $subscriber->check_at = $request->check_at;
        $subscriber->update();

        if ($request->has('categories')) {
            $categoriesIds = array_filter(explode(',', $request->categories));
            $subscriber->categories()->attach($categoriesIds);
        }

        return response()->json([
            'success' => true,
            'uid' => $subscriber->uid,
            'message' => 'Se creo el producto correctamente',
        ]);

    }

    public function destroy($uid)
    {
        $subscriber = Product::uid($uid);
        $subscriber->delete();

        return redirect()->route('manager.inventaries');
    }

    public function createImport(Request $request)
    {
        $import = new SubscriberImport;
        $import->save();

        return redirect()->route('manager.subscribers.import', $import->uid);
    }

    public function createImports($uid)
    {
        $import = SubscriberImport::findByUid($uid);

        if (! $import) {
            return abort(404, 'Import not found');
        }

        $currentJob = $import->importListsJobs()?->first();

        $importNotifications = Hook::execute('list_import_notifications');

        if ($currentJob != null) {
            return view('theme.views.subscribers.subscribers.imports', [
                'import' => $import,
                'currentJobUid' => $currentJob->uid,
                'progressCheckUrl' => route('manager.subscribers.import.progress', ['job_uid' => $currentJob->uid, 'import_uid' => $import->uid]),
                'cancelUrl' => route('manager.subscribers.import.cancel', ['job_uid' => $currentJob->uid]),
                'logDownloadUrl' => route('manager.subscribers.import.log.download', ['job_uid' => $currentJob->uid]),
                'importNotifications' => $importNotifications,
            ]);
        } else {
            return view('theme.views.subscribers.subscribers.imports', [
                'import' => $import,
                'importNotifications' => $importNotifications,
            ]);
        }
    }

    public function dispatchImportListsJobs(Request $request)
    {

        $import = SubscriberImport::findByUid($request->import_uid);
        $filepath = $request->hasFile('file') ? $import->uploadCsv($request->file('file')) : null;

        Hook::registerIfEmpty('dispatch_list_import_lists_job', function ($import, $filepath) {
            return $import->dispatchImportJob($filepath);
        });

        $currentJob = Hook::perform('dispatch_list_import_lists_job', [$import, $filepath]);

        return response()->json([
            'currentJobUid' => $currentJob->uid,
            'progressCheckUrl' => route('manager.subscribers.import.progress', ['job_uid' => $currentJob->uid, 'import_uid' => $import->uid]),
            'cancelUrl' => route('manager.subscribers.import.cancel', ['job_uid' => $currentJob->uid]),
            'logDownloadUrl' => route('manager.subscribers.import.log.download', ['job_uid' => $currentJob->uid]),
        ]);

    }

    public function importListsProgress(Request $request)
    {
        $import = SubscriberImport::findByUid($request->import_uid);
        $job = $import->importListsJobs()->first();

        $progress = $import->getProgress($job);

        return response()->json($progress);

    }

    public function downloadImportListsLog(Request $request)
    {
        $job = JobMonitor::findByUid($request->job_uid);

        return response()->download($job->getJsonData()['logfile']);
    }

    public function cancelImportLists(Request $request)
    {
        $job = JobMonitor::findByUid($request->job_uid);

        try {
            $job->cancel();

            return response()->json(['status' => 'done']);
        } catch (\Exception $ex) {
            $job->delete(); // delete anyway if already done or failed, to make it simple to user

            return response()->json(['status' => '']);
        }
    }
}
