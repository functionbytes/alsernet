<?php

namespace App\Http\Controllers\Managers\Templates;

use App\Models\Template\TemplateCategory as TemplateCategoryAlias;
use function App\Helpers\xml_to_array;
use App\Http\Controllers\Controller;
use App\Models\Template\Template;
use Illuminate\Http\Request;

class TemplatesController extends Controller
{

    public function index(Request $request){

        $templates = Template::notPrivate()
            ->notPreserved()
            ->email()
            ->categoryUid($request->category_uid)
            ->search($request->keyword)
            ->paginate(20);

        return view('managers.views.templates.templates.index')->with([
            'templates' => $templates,
        ]);

    }

    public function uploadTemplate(Request $request)
    {

        if ($request->isMethod('post')) {
            $asAdmin = true;
            $template = Template::uploadSystemTemplate($request, $asAdmin);

            if (!empty(Setting::get('storage.s3'))) {
                App::make('xstore')->store($template);
            }

            return redirect()->route('manager.templates');

        }

        return view('managers.views.templates.templates.upload');
    }


    public function uploadTemplateAssets(Request $request, $uid)
    {
        $template = Template::findByUid($uid);


        if ($request->assetType == 'upload' || $request->assetType == 'audio') {
            $assetUrl = $template->uploadAsset($request->file('file'));
        } elseif ($request->assetType == 'url') {
            $assetUrl = $template->uploadAssetFromUrl($request->url);
        } elseif ($request->assetType == 'base64') {
            $assetUrl = $template->uploadAssetFromBase64($request->url_base64);
        }

        return response()->json([
            'url' => $assetUrl
        ]);

    }



    public function create(Request $request)
    {

        $user = $request->user();
        $template = new Template();

        if (null !== $request->old()) {
            $template->fill($request->old());
        }

        return view('managers.views.templates.templates.create', [
            'template' => $template,
        ]);
    }

    public function edit(Request $request, $uid)
    {
        $user = $request->user();
        $template = Template::findByUid($uid);

        if (null !== $request->old()) {
            $template->fill($request->old());
        }

        return view('managers.views.templates.templates.edit', [
            'template' => $template,
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        $template = Template::findByUid($request->uid);


        if ($request->isMethod('patch')) {

            $template->fill($request->all());

            $rules = array(
                'title' => 'required',
                'content' => 'required',
            );

            $validator = \Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $template->save();

            return response()->json([
                'status' => 'success',
                'message' => 'La plantilla se actualizó correctamente',
            ], 201);
        }
    }


    public function preview(Request $request, $id)
    {
        $template = Template::findByUid($id);

        return view('managers.views.templates.templates.preview', [
            'template' => $template,
        ]);
    }

    public function categories(Request $request, $uid)
    {
        $template = Template::findByUid($uid);

        if ($request->isMethod('post')) {
            foreach ($request->categories as $key => $value) {
                $category = \App\Models\Template\TemplateCategory::findByUid($key);
                if ($value == 'true') {
                    $template->addCategory($category);
                } else {
                    $template->removeCategory($category);
                }
            }
        }

        return view('managers.views.templates.templates.categories', [
            'template' => $template,
        ]);
    }

    public function export(Request $request)
    {
        $template = Template::findByUid($request->uid);

        $zipPath = $template->createTmpZip();

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }

    public function changeName(Request $request)
    {
        $template = Template::findByUid($request->uid);

        if ($request->isMethod('post')) {

            $validator = $template->changeName($request->title);

            if ($validator->fails()) {
                return response()->view('managers.views.templates.templates.change', [
                    'template' => $template,
                    'errors' => $validator->errors(),
                ], 400);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Se cambió el nombre de la plantilla.',
            ], 201);
        }

        return view('managers.views.templates.templates.change', [
            'template' => $template,
        ]);
    }


    public function copy(Request $request)
    {
        $template = Template::findByUid($request->uid);

        if ($request->isMethod('post')) {

            $template->copy([
                'title' => $request->title
            ]);

            echo 'La plantilla se copió correctamente';
            return;
        }

        return view('managers.views.templates.templates.copy', [
            'template' => $template,
        ]);
    }

    public function delete(Request $request)
    {

        $templates = Template::whereIn(
            'uid',
            is_array($request->uids) ? $request->uids : explode(',', $request->uids)
        );

        $total = $templates->count();
        $deleted = 0;

        foreach ($templates->get() as $template) {
                $template->deleteAndCleanup();
                $deleted += 1;
        }

        echo "$deleted de $total plantillas se eliminaron correctamente";

    }


    public function chat()
    {
        return view('managers.views.templates.templates.chat');
    }


    public function updateThumbUrl(Request $request, $uid)
    {
        $template = Template::findByUid($uid);


        if ($request->isMethod('post')) {

            $validator = \Validator::make($request->all(), [
                'url' => 'required|url',
            ]);

            if ($validator->fails()) {
                return response()->view('managers.views.templates.templates.thumburl', [
                    'template' => $template,
                    'errors' => $validator->errors(),
                ], 400);
            }

            $template->uploadThumbnailUrl($request->url);

            return response()->json([
                'status' => 'success',
                'message' => trans('messages.template.thumb.uploaded'),
            ], 201);
        }

        return view('managers.views.templates.templates.thumburl', [
            'template' => $template,
        ]);
    }

    public function updateThumb(Request $request, $uid)
    {
        $template = Template::findByUid($uid);


        if ($request->isMethod('post')) {

            $validator = \Validator::make($request->all(), [
                'file' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->view('managers.views.templates.templates.thumb', [
                    'template' => $template,
                    'errors' => $validator->errors(),
                ], 400);
            }

            $template->uploadThumbnail($request->file);

            return response()->json([
                'status' => 'success',
                'message' => 'La miniatura de la plantilla se cargó correctamente',
            ], 201);
        }

        return view('managers.views.templates.templates.thumb', [
            'template' => $template,
        ]);
    }


    public function builderEditContent(Request $request, $uid)
    {
        $user = $request->user();
        $template = Template::findByUid($uid);

        return view('managers.views.templates.builder.content', [
            'content' => $template->content,
        ]);

    }


    public function builderEdit(Request $request, $uid)
    {

        $user = $request->user();
        $template = Template::findByUid($uid);

        if ($request->isMethod('post')) {

            $rules = array(
                'content' => 'required',
            );

            $this->validate($request, $rules);

            $template->content = $request->content;
            $template->save();

            return response()->json([
                'status' => 'success',
            ]);
        }

        return view('managers.views.templates.builder.edit', [
            'template' => $template,
            'templates' => $template->getBuilderAdminTemplates(),
            'admin' => $user,
        ]);
    }


    public function builderChangeTemplate(Request $request, $uid, $change_uid)
    {
        $template = Template::findByUid($uid);
        $changeTemplate = Template::findByUid($change_uid);

        $template->changeTemplate($changeTemplate);
    }

    public function builderTemplates(Request $request)
    {
        $category = TemplateCategoryAlias::findByUid($request->category_uid);

        $templates = $category->templates()->search($request->keyword)
            ->orderBy($request->sort_order, $request->sort_direction)
            ->paginate($request->per_page);

        return view('managers.views.templates.templates.builder.templates', [
            'templates' => $templates,
        ]);
    }

    public function builderCreate(Request $request)
    {
        $template = new Template();
        $template->title = 'Plantilla sin título';

        $templates = Template::notPreserved();
        $categories = TemplateCategoryAlias::all();

        if ($request->isMethod('post')) {

            $currentTemplate = Template::findByUid($request->template);

            $template = $currentTemplate->copy([
                'title' => $request->title,
            ]);

            return redirect()->route('manager.templates.builder.edit', $template->uid);
        }

        return view('managers.views.templates.builder.create', [
            'template' => $template,
            'templates' => $templates,
            'categories' => $categories,
        ]);
    }

}
