<?php

namespace Modules\Campaign\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Campaign\Models\CampaignField;
use Modules\Campaign\Models\CampaignMaillist;

class FieldController extends Controller
{
    public function index(string $listUid): JsonResponse
    {
        $list = CampaignMaillist::where('uid', $listUid)->firstOrFail();

        return response()->json([
            'data' => CampaignField::where('mail_list_id', $list->id)
                ->orderBy('order')
                ->get(),
        ]);
    }

    public function store(Request $request, string $listUid): JsonResponse
    {
        $list = CampaignMaillist::where('uid', $listUid)->firstOrFail();

        $data = $request->validate([
            'tag' => ['required', 'string', 'max:191', 'regex:/^[a-z0-9_]+$/'],
            'label' => ['required', 'string', 'max:191'],
            'type' => ['required', 'string', 'in:text,number,date,datetime,textarea,select,radio,checkbox'],
            'default_value' => ['nullable', 'string', 'max:191'],
            'required' => ['boolean'],
            'visible' => ['boolean'],
        ]);

        $field = CampaignField::create([
            'uid' => (string) Str::uuid(),
            'mail_list_id' => $list->id,
            'tag' => $data['tag'],
            'label' => $data['label'],
            'type' => $data['type'],
            'default_value' => $data['default_value'] ?? null,
            'required' => $data['required'] ?? false,
            'visible' => $data['visible'] ?? true,
            'order' => CampaignField::where('mail_list_id', $list->id)->max('order') + 1,
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $field,
        ], 201);
    }

    public function show(string $listUid, string $fieldUid): JsonResponse
    {
        $list = CampaignMaillist::where('uid', $listUid)->firstOrFail();
        $field = CampaignField::where('uid', $fieldUid)
            ->where('mail_list_id', $list->id)
            ->firstOrFail();

        return response()->json(['data' => $field]);
    }

    public function update(Request $request, string $listUid, string $fieldUid): JsonResponse
    {
        $list = CampaignMaillist::where('uid', $listUid)->firstOrFail();
        $field = CampaignField::where('uid', $fieldUid)
            ->where('mail_list_id', $list->id)
            ->firstOrFail();

        $data = $request->validate([
            'label' => ['sometimes', 'string', 'max:191'],
            'type' => ['sometimes', 'string', 'in:text,number,date,datetime,textarea,select,radio,checkbox'],
            'default_value' => ['nullable', 'string', 'max:191'],
            'required' => ['sometimes', 'boolean'],
            'visible' => ['sometimes', 'boolean'],
            'order' => ['sometimes', 'integer', 'min:0'],
        ]);

        $field->update($data);

        return response()->json([
            'status' => 'success',
            'data' => $field,
        ]);
    }

    public function delete(string $listUid, string $fieldUid): JsonResponse
    {
        $list = CampaignMaillist::where('uid', $listUid)->firstOrFail();
        $field = CampaignField::where('uid', $fieldUid)
            ->where('mail_list_id', $list->id)
            ->firstOrFail();

        $field->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Field deleted.',
        ]);
    }
}
