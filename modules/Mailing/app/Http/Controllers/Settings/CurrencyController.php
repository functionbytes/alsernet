<?php

namespace Modules\Mailing\Http\Controllers\Settings;

use Illuminate\Http\Request;
use Modules\Mailing\Http\Controllers\Controller;

class CurrencyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // authorize
        if (\Gate::denies('read', new \Modules\Mailing\Models\Currency)) {
            return $this->notAuthorized();
        }

        // If admin can view all sending domains
        if (! $request->user()->admin->can('readAll', new \Modules\Mailing\Models\Currency)) {
            $request->merge(['admin_id' => $request->user()->admin->id]);
        }

        $currencies = \Modules\Mailing\Models\Currency::search($request);

        return view('admin.currencies.index', [
            'currencies' => $currencies,
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listing(Request $request)
    {
        // authorize
        if (\Gate::denies('read', new \Modules\Mailing\Models\Currency)) {
            return $this->notAuthorized();
        }

        // If admin can view all sending domains
        if (! $request->user()->admin->can('readAll', new \Modules\Mailing\Models\Currency)) {
            $request->merge(['admin_id' => $request->user()->admin->id]);
        }

        $currencies = \Modules\Mailing\Models\Currency::search($request)->paginate($request->per_page);

        return view('admin.currencies._list', [
            'currencies' => $currencies,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $currency = new \Modules\Mailing\Models\Currency;
        $currency->status = 'active';

        // authorize
        if (\Gate::denies('create', $currency)) {
            return $this->notAuthorized();
        }

        if (! empty($request->old())) {
            $currency->fill($request->old());
        }

        return view('admin.currencies.create', [
            'currency' => $currency,
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
        $user = $request->user();
        $currency = new \Modules\Mailing\Models\Currency;

        // authorize
        if (\Gate::denies('create', $currency)) {
            return $this->notAuthorized();
        }

        // save posted data
        if ($request->isMethod('post')) {
            $rules = $currency->rules();

            $this->validate($request, $rules);

            // Save current currency info
            $currency->admin_id = $user->admin->id;
            $currency->fill($request->all());
            $currency->status = 'active';

            if ($currency->save()) {
                $request->session()->flash('alert-success', trans('messages.currency.created'));

                return redirect()->action('Settings\CurrencyController@index');
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
        $currency = \Modules\Mailing\Models\Currency::findByUid($id);

        // authorize
        if (\Gate::denies('update', $currency)) {
            return $this->notAuthorized();
        }

        if (! empty($request->old())) {
            $currency->fill($request->old());
        }

        return view('admin.currencies.edit', [
            'currency' => $currency,
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
        $currency = \Modules\Mailing\Models\Currency::findByUid($id);

        // Prenvent save from demo mod
        if (config('app.demo')) {
            return view('somethingWentWrong', ['message' => trans('messages.operation_not_allowed_in_demo')]);
        }

        // authorize
        if (\Gate::denies('update', $currency)) {
            return $this->notAuthorized();
        }

        // save posted data
        if ($request->isMethod('patch')) {
            $rules = $currency->rules();

            $this->validate($request, $rules);

            // Save currency
            $currency->fill($request->all());

            if ($currency->save()) {
                $request->session()->flash('alert-success', trans('messages.currency.updated'));

                return redirect()->action('Settings\CurrencyController@index');
            }
        }
    }

    /**
     * Enable item.
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function enable(Request $request)
    {
        $items = \Modules\Mailing\Models\Currency::whereIn(
            'uid',
            is_array($request->uids) ? $request->uids : explode(',', $request->uids)
        );

        foreach ($items->get() as $item) {
            // authorize
            if (\Gate::allows('update', $item)) {
                $item->enable();
            }
        }

        // Redirect to my lists page
        echo trans('messages.currencies.enabled');
    }

    /**
     * Disable item.
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function disable(Request $request)
    {
        $items = \Modules\Mailing\Models\Currency::whereIn(
            'uid',
            is_array($request->uids) ? $request->uids : explode(',', $request->uids)
        );

        foreach ($items->get() as $item) {
            // authorize
            if (\Gate::allows('update', $item)) {
                $item->disable();
            }
        }

        // Redirect to my lists page
        echo trans('messages.currencies.disabled');
    }

    /**
     * Remove the specified resource from storage.
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function delete(Request $request)
    {
        $items = \Modules\Mailing\Models\Currency::whereIn(
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
        echo trans('messages.currencies.deleted');
    }

    /**
     * Select2 plan.
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function select2(Request $request)
    {
        echo \Modules\Mailing\Models\Currency::select2($request);
    }
}
