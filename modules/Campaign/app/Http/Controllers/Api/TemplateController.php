<?php

namespace Modules\Campaign\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Campaign\Models\Template\Template;

class TemplateController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Template::orderBy('created_at', 'desc')->paginate(50),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'html' => ['nullable', 'string'],
            'plain' => ['nullable', 'string'],
            'builder' => ['boolean'],
        ]);

        $template = Template::create([
            'uid' => (string) Str::uuid(),
            ...$data,
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $template,
        ], 201);
    }

    public function show(string $uid): JsonResponse
    {
        $template = Template::where('uid', $uid)->firstOrFail();

        return response()->json(['data' => $template]);
    }

    public function update(Request $request, string $uid): JsonResponse
    {
        $template = Template::where('uid', $uid)->firstOrFail();

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'html' => ['nullable', 'string'],
            'plain' => ['nullable', 'string'],
            'builder' => ['boolean'],
        ]);

        $template->update($data);

        return response()->json([
            'status' => 'success',
            'data' => $template,
        ]);
    }

    public function delete(string $uid): JsonResponse
    {
        $template = Template::where('uid', $uid)->firstOrFail();
        $template->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Template deleted.',
        ]);
    }

    public function copy(string $uid): JsonResponse
    {
        $template = Template::where('uid', $uid)->firstOrFail();

        $copy = $template->replicate();
        $copy->uid = (string) Str::uuid();
        $copy->name = $copy->name.' (copy)';
        $copy->created_at = now();
        $copy->updated_at = now();
        $copy->save();

        return response()->json([
            'status' => 'success',
            'data' => $copy,
        ], 201);
    }
}
