<?php

namespace Modules\Engagement\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait SoftDeletesActions
{
    public function trashed(Request $request): JsonResponse
    {
        $modelClass = $this->getModelClass();

        $rows = $modelClass::query()
            ->onlyTrashed()
            ->when($request->input('inbox_id'), fn ($q, $id) => $q->where('inbox_id', (int) $id))
            ->latest('deleted_at')
            ->limit(200)
            ->get();

        return response()->json(['success' => true, 'data' => $rows]);
    }

    public function restore(int $id): JsonResponse
    {
        $modelClass = $this->getModelClass();
        $row = $modelClass::query()->onlyTrashed()->findOrFail($id);
        $row->restore();

        return response()->json(['success' => true, 'data' => $row->fresh()]);
    }

    abstract protected function getModelClass(): string;
}
