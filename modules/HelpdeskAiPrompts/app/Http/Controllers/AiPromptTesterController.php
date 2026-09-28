<?php

namespace Modules\HelpdeskAiPrompts\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\HelpdeskAiPrompts\Http\Requests\RunTesterRequest;
use Modules\HelpdeskAiPrompts\Services\PromptComposer;
use Modules\HelpdeskChatFlow\Services\ChatFlowAgentService;

class AiPromptTesterController extends Controller
{
    /**
     * Detects the case for a free question and returns the exact system
     * prompt PromptComposer would assemble, without calling the AI.
     */
    public function detect(RunTesterRequest $request, PromptComposer $composer): JsonResponse
    {
        $data = $request->validated();
        $composed = $composer->compose($data['question'], $this->context($data));

        return response()->json($composed);
    }

    /**
     * Same detection, then actually runs the question through the real
     * agent (ChatFlowAgentService), forcing the detected case so the
     * answer matches what was just shown.
     */
    public function execute(RunTesterRequest $request, PromptComposer $composer): JsonResponse
    {
        if (! class_exists(ChatFlowAgentService::class)) {
            return response()->json([
                'message' => __('helpdeskaiprompts::ai-prompts.tester_agent_unavailable'),
            ], 422);
        }

        $data = $request->validated();
        $composed = $composer->compose($data['question'], $this->context($data));

        $response = app(ChatFlowAgentService::class)->run(
            $data['question'],
            [
                '_trace_id' => 'tester-'.uniqid(),
                'identity_verified' => (bool) ($data['logged_in'] ?? false),
            ],
            [
                'use_prompt_library' => true,
                '_forced_case' => $composed['case_key'],
                '_channel' => $data['channel'] ?? null,
            ],
            $data['locale'] ?? 'es',
        );

        return response()->json([
            'case_key' => $composed['case_key'],
            'case_name' => $composed['case_name'],
            'routed_by' => $composed['routed_by'],
            'answer' => $response['text'] ?? '',
            'action' => $response['action'] ?? '',
            'used_tools' => $response['used_tools'] ?? [],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function context(array $data): array
    {
        return [
            'channel' => $data['channel'] ?? null,
            'locale' => $data['locale'] ?? 'es',
            'page_url' => null,
            'logged_in' => (bool) ($data['logged_in'] ?? false),
            'now' => now(),
        ];
    }
}
