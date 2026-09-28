<?php

namespace Modules\HelpdeskAiPrompts\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Helpdesk\Models\Inbox;
use Modules\HelpdeskAiPrompts\Http\Requests\StoreAiPromptCaseRequest;
use Modules\HelpdeskAiPrompts\Http\Requests\TestDraftCaseRequest;
use Modules\HelpdeskAiPrompts\Http\Requests\UpdateAiPromptCaseRequest;
use Modules\HelpdeskAiPrompts\Models\AiPromptBlock;
use Modules\HelpdeskAiPrompts\Models\AiPromptCase;
use Modules\HelpdeskAiPrompts\Models\AiPromptVersion;
use Modules\HelpdeskAiPrompts\Services\PromptTestRunner;
use Modules\HelpdeskAiPrompts\Support\ToolCatalog;
use Modules\HelpdeskAiPrompts\Support\VersionDiff;

class AiPromptCaseController extends Controller
{
    public function create(): View
    {
        return view('helpdeskaiprompts::cases.form', $this->formData(new AiPromptCase));
    }

    public function edit(AiPromptCase $case): View
    {
        return view('helpdeskaiprompts::cases.form', $this->formData($case));
    }

    public function store(StoreAiPromptCaseRequest $request): RedirectResponse
    {
        $case = AiPromptCase::query()->create($this->withAuthor($request->validated()));

        return redirect()->route('helpdesk-ai-prompts.index', ['tab' => 'casos'])
            ->with('success', __('helpdeskaiprompts::ai-prompts.case_created', ['name' => $case->name]));
    }

    public function update(UpdateAiPromptCaseRequest $request, AiPromptCase $case): RedirectResponse
    {
        $case->update($this->withAuthor($request->validated()));

        return redirect()->route('helpdesk-ai-prompts.index', ['tab' => 'casos'])
            ->with('success', __('helpdeskaiprompts::ai-prompts.case_updated', ['name' => $case->name]));
    }

    public function destroy(AiPromptCase $case): RedirectResponse
    {
        $case->delete();

        return redirect()->route('helpdesk-ai-prompts.index', ['tab' => 'casos'])
            ->with('success', __('helpdeskaiprompts::ai-prompts.case_deleted'));
    }

    public function toggleActive(AiPromptCase $case): JsonResponse
    {
        $case->update(['is_active' => ! $case->is_active, 'updated_by' => auth()->id()]);

        return response()->json(['is_active' => $case->is_active]);
    }

    public function duplicate(Request $request, AiPromptCase $case): RedirectResponse
    {
        $request->validate([
            'channel' => ['required', 'string', 'in:'.implode(',', Inbox::CHANNEL_TYPES)],
        ]);

        $copy = $case->replicate(['version']);
        $copy->channel = $request->string('channel')->value();
        $copy->updated_by = auth()->id();
        $copy->save();

        return redirect()->route('helpdesk-ai-prompts.cases.edit', $copy)
            ->with('success', __('helpdeskaiprompts::ai-prompts.case_duplicated'));
    }

    public function testDraft(TestDraftCaseRequest $request, PromptTestRunner $runner): JsonResponse
    {
        return response()->json(['results' => $runner->run($request->validated())]);
    }

    public function test(AiPromptCase $case, PromptTestRunner $runner): JsonResponse
    {
        return response()->json(['results' => $runner->run($case)]);
    }

    public function history(AiPromptCase $case): View
    {
        $current = $case->attributesToArray();
        $versions = $case->versions()->get();

        return view('helpdeskaiprompts::history', [
            'subjectLabel' => $case->name,
            'versions' => $versions,
            'currentVersion' => $case->version,
            'backLabel' => __('helpdeskaiprompts::ai-prompts.tab_casos'),
            'backRoute' => route('helpdesk-ai-prompts.index', ['tab' => 'casos']),
            'restoreRoute' => fn (AiPromptVersion $version) => route('helpdesk-ai-prompts.cases.versions.restore', [$case, $version]),
            'diffs' => $versions->mapWithKeys(
                fn (AiPromptVersion $version) => [$version->id => VersionDiff::changedFields((array) $version->snapshot, $current)]
            ),
        ]);
    }

    public function restoreVersion(AiPromptCase $case, AiPromptVersion $version): RedirectResponse
    {
        try {
            $case->restoreVersion($version);
        } catch (\InvalidArgumentException) {
            abort(404);
        }

        return redirect()->route('helpdesk-ai-prompts.cases.history', $case)
            ->with('success', __('helpdeskaiprompts::ai-prompts.version_restored'));
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(AiPromptCase $case): array
    {
        return [
            'case' => $case,
            'channels' => Inbox::CHANNEL_TYPES,
            'tools' => ToolCatalog::TOOLS,
            'knowledgeBlocks' => AiPromptBlock::query()->where('kind', 'knowledge')->orderBy('name')->get(),
        ];
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
