<?php

namespace App\Http\Controllers\Managers\Settings;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Http\Controllers\Controller;
use App\Models\Setting\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{

    public function index(){

        $logo = Setting::key('page_logo')->getMedia('logo')->count() > 0 ? true : false;
        $favicon = Setting::key('page_favicon')->getMedia('favicon')->count() > 0 ? true : false;

        return view('managers.views.settings.settings.setting')->with([
            'logo' => $logo,
            'favicon' => $favicon
        ]);
    }

    public function update(Request $request){

        $exp = array("<p class='ql-align-justify'><br></p>", "<p> </p>", "<p></p>", "<p></p>");

        $data['page_title']  =  $request->page_title;
        $data['page_copyright']  =  $request->page_copyright;
        $data['page_email']  =  $request->page_email;
        $data['page_phone']  =  $request->page_phone;
        $data['page_cellphone']  =  $request->page_cellphone;
        $data['page_whatsapp']  =  $request->page_whatsapp;
        $data['page_description']  =  str_replace($exp, '', $request->page_description);
        $data['page_politic']  =  str_replace($exp, '', $request->page_politic);
        $data['page_term']  =  str_replace($exp, '', $request->page_term);
        $data['page_address']  =  $request->page_address;
        $data['page_map']  =  $request->page_map;
        $data['social_media_facebook']  =  $request->social_media_facebook;
        $data['social_media_instagram']  =  $request->social_media_instagram;
        $data['social_media_twitter']  =  $request->social_media_twitter;
        $data['social_media_youtube']  =  $request->social_media_youtube;
        $data['social_media_linkedin']  =  $request->social_media_linkedin;
        $data['page_hour_weekend']  =  $request->page_hour_weekend;
        $data['page_hour_weekends']  =  $request->page_hour_weekends;
        updateSettings($data);

        return response()->json([
            'success' => true,
            'message' => 'Se ha actualizado correctamente',
        ]);

    }

    public function getLogo($uid){

        $setting = Setting::key($uid);

        if ($setting->getMedia('logo')->count()>0) {

            $thumbnails = $setting->getMedia('logo');

            foreach ($thumbnails as $thumbnail) {

                $images[] = [
                    'id' => $thumbnail->id,
                    'uuid' => $thumbnail->uuid,
                    'name' => $thumbnail->name,
                    'file' => $thumbnail->file_name,
                    'path' => $thumbnail->getfullUrl(),
                    'size' =>  $thumbnail->size
                ];
            }

            return response()->json($images);
        }

        $images = [];

        return response()->json($images);

    }

    public function storeLogo(Request $request){

        if($request->hasFile('file') && $request->file('file')->isValid()){
            $setting = Setting::key($request->setting);
            $setting->addMediaFromRequest('file')->toMediaCollection('logo');
            return response()->json(['status' => "success", 'setting' => $setting->key]);
        }

    }

    public function deleteLogo($id){
        Media::find($id)->delete();
        return response()->json(['status' => "success"]);
    }

    public function getFavicon($uid){

        $setting = Setting::key($uid);

        if ($setting->getMedia('favicon')->count()>0) {

            $thumbnails = $setting->getMedia('favicon');

            foreach ($thumbnails as $thumbnail) {

                $images[] = [
                    'id' => $thumbnail->id,
                    'uuid' => $thumbnail->uuid,
                    'name' => $thumbnail->name,
                    'file' => $thumbnail->file_name,
                    'path' => $thumbnail->getfullUrl(),
                    'size' =>  $thumbnail->size
                ];
            }

            return response()->json($images);
        }

        $images = [];

        return response()->json($images);

    }

    public function storeFavicon(Request $request){

        if($request->hasFile('file') && $request->file('file')->isValid()){
            $setting = Setting::key($request->setting);
            $setting->addMediaFromRequest('file')->toMediaCollection('favicon');
            return response()->json(['status' => "success", 'setting' => $setting->key]);

        }
    }

    public function deleteFavicon($id){
        Media::find($id)->delete();
        return response()->json(['status' => "success"]);
    }

}
