<?php

namespace Modules\HelpdeskAiPrompts\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Helpdesk\Models\Inbox;
use Modules\HelpdeskAiPrompts\Http\Requests\StoreAiPromptBlockRequest;
use Modules\HelpdeskAiPrompts\Http\Requests\UpdateAiPromptBlockRequest;
use Modules\HelpdeskAiPrompts\Models\AiPromptBlock;
use Modules\HelpdeskAiPrompts\Models\AiPromptVersion;
use Modules\HelpdeskAiPrompts\Support\VersionDiff;

class AiPromptBlockController extends Controller
{
    public function create(Request $request): View
    {
        return view('helpdeskaiprompts::blocks.form', $this->formData(new AiPromptBlock([
            'kind' => in_array($request->query('kind'), ['base', 'knowledge'], true) ? $request->query('kind') : 'knowledge',
        ])));
    }

    public function edit(AiPromptBlock $block): View
    {
        return view('helpdeskaiprompts::blocks.form', $this->formData($block));
    }

    public function store(StoreAiPromptBlockRequest $request): RedirectResponse
    {
        $block = AiPromptBlock::query()->create($this->withAuthor($request->validated()));

        return redirect()->route('helpdesk-ai-prompts.index', ['tab' => $this->tabFor($block)])
            ->with('success', __('helpdeskaiprompts::ai-prompts.block_created', ['name' => $block->name]));
    }

    public function update(UpdateAiPromptBlockRequest $request, AiPromptBlock $block): RedirectResponse
    {
        $block->update($this->withAuthor($request->validated()));

        return redirect()->route('helpdesk-ai-prompts.index', ['tab' => $this->tabFor($block)])
            ->with('success', __('helpdeskaiprompts::ai-prompts.block_updated', ['name' => $block->name]));
    }

    public function destroy(AiPromptBlock $block): RedirectResponse
    {
        $tab = $this->tabFor($block);
        $block->delete();

        return redirect()->route('helpdesk-ai-prompts.index', ['tab' => $tab])
            ->with('success', __('helpdeskaiprompts::ai-prompts.block_deleted'));
    }

    public function toggleActive(AiPromptBlock $block): JsonResponse
    {
        $block->update(['is_active' => ! $block->is_active, 'updated_by' => auth()->id()]);

        return response()->json(['is_active' => $block->is_active]);
    }

    public function history(AiPromptBlock $block): View
    {
        $current = $block->attributesToArray();
        $versions = $block->versions()->get();

        return view('helpdeskaiprompts::history', [
            'subjectLabel' => $block->name,
            'versions' => $versions,
            'currentVersion' => $block->version,
            'backLabel' => __('helpdeskaiprompts::ai-prompts.tab_'.($block->kind === 'base' ? 'prompt_base' : 'conocimiento')),
            'backRoute' => route('helpdesk-ai-prompts.index', ['tab' => $this->tabFor($block)]),
            'restoreRoute' => fn (AiPromptVersion $version) => route('helpdesk-ai-prompts.blocks.versions.restore', [$block, $version]),
            'diffs' => $versions->mapWithKeys(
                fn (AiPromptVersion $version) => [$version->id => VersionDiff::changedFields((array) $version->snapshot, $current)]
            ),
        ]);
    }

    public function restoreVersion(AiPromptBlock $block, AiPromptVersion $version): RedirectResponse
    {
        try {
            $block->restoreVersion($version);
        } catch (\InvalidArgumentException) {
            abort(404);
        }

        return redirect()->route('helpdesk-ai-prompts.blocks.history', $block)
            ->with('success', __('helpdeskaiprompts::ai-prompts.version_restored'));
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(AiPromptBlock $block): array
    {
        return [
            'block' => $block,
            'channels' => Inbox::CHANNEL_TYPES,
        ];
    }

    private function tabFor(AiPromptBlock $block): string
    {
        return $block->kind === 'base' ? 'prompt-base' : 'conocimiento';
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function withAuthor(array $data): array
    {
        $data['updated_by'] = auth()->id();

        return $data;
    }
}
