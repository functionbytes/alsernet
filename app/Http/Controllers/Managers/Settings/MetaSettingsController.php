<?php

namespace App\Http\Controllers\Managers\Settings;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Http\Controllers\Controller;
use App\Models\Setting\Setting;
use Illuminate\Http\Request;

class MetaSettingsController extends Controller
{

   public function index(){

        $meta = Setting::key('meta_image')->getMedia('meta')->count() > 0 ? true : false;

        return view('managers.views.settings.metadata.setting')->with([
            'metadata' => $meta,
        ]);

   }

    public function update(Request $request){

        $data['meta_title']  =  $request->meta_title;
        $data['meta_description']  =  $request->meta_description;
        $data['meta_keywords']  =  $request->meta_keywords;
        updateSettings($data);

        $response = [
            'status' => true,
            'message' => 'Se ha actualizado correctamente',
        ];

        return response()->json($response);

    }

    public function storeMetas(Request $request){

        if($request->hasFile('file') && $request->file('file')->isValid()){
            $setting = Setting::key($request->setting);
            $setting->addMediaFromRequest('file')->toMediaCollection('meta');
            return response()->json(['status' => "success", 'setting' => $setting->uid]);
        }

    }

    public function deleteMetas($id){

        Media::find($id)->delete();
        return response()->json(['status' => "success"]);

    }

    public function getMetas($uid){

        $setting = Setting::key($uid);


        $images = $setting->getMedia('meta')->map(function ($thumbnail) {
            return [
                'id' => $thumbnail->id,
                'uuid' => $thumbnail->uuid,
                'name' => $thumbnail->name,
                'file' => $thumbnail->file_name,
                'path' => $thumbnail->getFullUrl(),
                'size' => $thumbnail->size,
            ];
        });

        return response()->json($images);

    }


}
