<?php

namespace Modules\System\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Core\Models\Setting;

class SettingsController extends Controller
{
    public function index(): View
    {
        $settingLogo = Setting::key('page_logo');
        $settingFavicon = Setting::key('page_favicon');

        $logo = $settingLogo && $settingLogo->getMedia('logo')->count() > 0;
        $favicon = $settingFavicon && $settingFavicon->getMedia('favicon')->count() > 0;

        $setting = Setting::first() ?? new Setting;

        return view('theme::theme.views.settings.metadata.setting')->with([
            'setting' => $setting,
            'logo' => $logo,
            'favicon' => $favicon,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'page_title' => ['nullable', 'string', 'max:255'],
            'page_copyright' => ['nullable', 'string', 'max:255'],
            'page_email' => ['nullable', 'email', 'max:255'],
            'page_phone' => ['nullable', 'string', 'max:30'],
            'page_cellphone' => ['nullable', 'string', 'max:30'],
            'page_whatsapp' => ['nullable', 'string', 'max:30'],
            'page_description' => ['nullable', 'string'],
            'page_politic' => ['nullable', 'string'],
            'page_term' => ['nullable', 'string'],
            'page_address' => ['nullable', 'string', 'max:500'],
            'page_map' => ['nullable', 'string', 'max:2000'],
            'social_media_facebook' => ['nullable', 'url', 'max:500'],
            'social_media_instagram' => ['nullable', 'url', 'max:500'],
            'social_media_twitter' => ['nullable', 'url', 'max:500'],
            'social_media_youtube' => ['nullable', 'url', 'max:500'],
            'social_media_linkedin' => ['nullable', 'url', 'max:500'],
            'page_hour_weekend' => ['nullable', 'string', 'max:255'],
            'page_hour_weekends' => ['nullable', 'string', 'max:255'],
        ]);

        $exp = ["<p class='ql-align-justify'><br></p>", '<p> </p>', '<p></p>', '<p></p>'];

        $data = [
            'page_title' => strip_tags($request->input('page_title', '')),
            'page_copyright' => strip_tags($request->input('page_copyright', '')),
            'page_email' => strip_tags($request->input('page_email', '')),
            'page_phone' => strip_tags($request->input('page_phone', '')),
            'page_cellphone' => strip_tags($request->input('page_cellphone', '')),
            'page_whatsapp' => strip_tags($request->input('page_whatsapp', '')),
            'page_description' => str_replace($exp, '', $request->input('page_description', '')),
            'page_politic' => str_replace($exp, '', $request->input('page_politic', '')),
            'page_term' => str_replace($exp, '', $request->input('page_term', '')),
            'page_address' => strip_tags($request->input('page_address', '')),
            'page_map' => strip_tags($request->input('page_map', '')),
            'social_media_facebook' => $request->input('social_media_facebook', ''),
            'social_media_instagram' => $request->input('social_media_instagram', ''),
            'social_media_twitter' => $request->input('social_media_twitter', ''),
            'social_media_youtube' => $request->input('social_media_youtube', ''),
            'social_media_linkedin' => $request->input('social_media_linkedin', ''),
            'page_hour_weekend' => strip_tags($request->input('page_hour_weekend', '')),
            'page_hour_weekends' => strip_tags($request->input('page_hour_weekends', '')),
        ];

        updateSettings($data);

        return response()->json([
            'success' => true,
            'message' => 'Se ha actualizado correctamente',
        ]);
    }

    public function getLogo(string $uid): JsonResponse
    {
        $setting = Setting::key($uid);
        $images = [];

        foreach ($setting->getMedia('logo') as $thumbnail) {
            $images[] = [
                'id' => $thumbnail->id,
                'uuid' => $thumbnail->uuid,
                'name' => $thumbnail->name,
                'file' => $thumbnail->file_name,
                'path' => $thumbnail->getfullUrl(),
                'size' => $thumbnail->size,
            ];
        }

        return response()->json($images);
    }

    public function getFavicon(string $uid): JsonResponse
    {
        $setting = Setting::key($uid);
        $images = [];

        foreach ($setting->getMedia('favicon') as $thumbnail) {
            $images[] = [
                'id' => $thumbnail->id,
                'uuid' => $thumbnail->uuid,
                'name' => $thumbnail->name,
                'file' => $thumbnail->file_name,
                'path' => $thumbnail->getfullUrl(),
                'size' => $thumbnail->size,
            ];
        }

        return response()->json($images);
    }
}
