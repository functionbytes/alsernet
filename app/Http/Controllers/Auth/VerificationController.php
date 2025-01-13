<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Artesaos\SEOTools\Facades\OpenGraph;
use Artesaos\SEOTools\Facades\SEOTools;
use Artesaos\SEOTools\Facades\SEOMeta;
use Artesaos\SEOTools\Facades\JsonLd;
use App\Http\Controllers\Controller;
use App\Models\Setting\Setting;
use Illuminate\Http\Request;


class VerificationController extends Controller
{
    protected $redirectTo = '/home';

    public function __construct(){
        //$this->middleware('auth');
        // $this->middleware('signed')->only('verify');
        //$this->middleware('throttle:120,1')->only('verify', 'resend');
    }

    public function redirectPath(){

        if (method_exists($this, 'redirectTo')) {
            return $this->redirectTo();
        }

        return property_exists($this, 'redirectTo') ? $this->redirectTo : '/home';
    }

    public function show(Request $request){

        $setting = Setting::first();

        SEOMeta::setTitle(getSetting()->meta_title);
        SEOMeta::setDescription(getSetting()->meta_description);
        SEOMeta::setCanonical(getUrl());

        SEOTools::setTitle(getSetting()->meta_title);
        SEOTools::setDescription(getSetting()->meta_description);
        SEOTools::opengraph()->setUrl(getUrl());
        SEOTools::setCanonical(getUrl());
        SEOTools::opengraph()->addProperty('type', 'articles');
        SEOTools::twitter()->setSite('@bpmsandiego');
        SEOTools::jsonLd()->addImage(getMeta());

        OpenGraph::setTitle(getSetting()->meta_title);
        OpenGraph::setDescription(getSetting()->meta_description);
        OpenGraph::setUrl(getUrl());
        OpenGraph::addProperty('type', 'article');
        OpenGraph::addProperty('locale', 'en-En');
        OpenGraph::addImage(getMeta());

        JsonLd::setTitle(getSetting()->meta_title);
        JsonLd::setDescription(getSetting()->meta_description);
        JsonLd::addImage(getMeta());

        //if ($request->user()->hasVerifiedEmail()) {
        return redirect($this->redirectPath());
        //}else{
        //VerificationMails::dispatch($request->user())->onQueue('verification');
        //return view('auth.verify');
        //}
    }

    public function verify(EmailVerificationRequest $request){
        $request->fulfill();
        return redirect()->route('verified')->with('verified', true);

    }

    public function verified(){

        $setting = Setting::first();

        SEOMeta::setTitle(getSetting()->meta_title);
        SEOMeta::setDescription(getSetting()->meta_description);
        SEOMeta::setCanonical(getUrl());

        SEOTools::setTitle(getSetting()->meta_title);
        SEOTools::setDescription(getSetting()->meta_description);
        SEOTools::opengraph()->setUrl(getUrl());
        SEOTools::setCanonical(getUrl());
        SEOTools::opengraph()->addProperty('type', 'articles');
        SEOTools::twitter()->setSite('@bpmsandiego');
        SEOTools::jsonLd()->addImage(getMeta());

        OpenGraph::setTitle(getSetting()->meta_title);
        OpenGraph::setDescription(getSetting()->meta_description);
        OpenGraph::setUrl(getUrl());
        OpenGraph::addProperty('type', 'article');
        OpenGraph::addProperty('locale', 'en-En');
        OpenGraph::addImage(getMeta());

        JsonLd::setTitle(getSetting()->meta_title);
        JsonLd::setDescription(getSetting()->meta_description);
        JsonLd::addImage(getMeta());

        return view('pages.views.verified.verified');

    }

    public function resend(Request $request){



    }


}
