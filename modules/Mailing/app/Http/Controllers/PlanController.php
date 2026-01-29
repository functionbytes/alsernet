<?php

namespace Modules\Mailing\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Mailing\Models\PlanGeneral;

class PlanController extends Controller
{
    /**
     * Select2 plan.
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function select2(Request $request)
    {
        echo \Modules\Mailing\Models\PlanGeneral::select2($request);
    }

    public function publicListPage(Request $request)
    {
        if (app_profile('plan.disable_public_page') === true) {
            abort(404);
        }

        $plans = PlanGeneral::getAvailableGeneralPlans();
        $style = $request->style ?? 'default';

        return view('plans.publicListPage', [
            'plans' => $plans,
            'style' => $style,
        ]);
    }
}
