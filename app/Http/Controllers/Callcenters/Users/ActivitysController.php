<?php

namespace App\Http\Controllers\Callcenters\Users;

use Spatie\Activitylog\Models\Activity;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class ActivitysController extends Controller
{
    public function index(Request $request,$uid){

        $modelSearch = null ?? $request->model;
        $propertySearch = null ?? $request->property;

        $user = User::uid($uid);

        $query = Activity::causedBy($user);

        $models = [
            'Enterprise' => 'Empresas',
            'User' => 'Usuarios',
            'Distributor' => 'Distributidores',
            'Order' => 'Ordenes',
            'Invoice' => 'Facturas',
        ];

        $activitiesFilter = $query->orderBy('created_at', 'desc')->get()
            ->groupBy(function($activity) {
                return class_basename($activity->subject_type);
        });

        $activities = $query->orderBy('created_at', 'desc')->get();


        if ($propertySearch) {
            $query->where('properties->' . $propertySearch, '!=', null);
        }

        if ($modelSearch) {
            $model = 'App\\Models\\' . $modelSearch;
        }

        $counts = [];

        foreach ($models as $key => $friendlyName) {
            $counts[$key] = $activities->has($key) ? $activities[$key]->count() : 0;
        }


        return view('callcenters.views.users.activitys.index')->with([
            'user' => $user,
            'activities' => $activities,
            'counts' => $counts,
            'model' => $modelSearch,
            'models' => $models,
        ]);

    }
    public function lists($uid){
    }
    public function detail($uid){
    }

}
