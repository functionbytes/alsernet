<?php

namespace Modules\HelpdeskChatFlow\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\HelpdeskChatFlow\Http\Requests\StoreChatFlowTestCaseRequest;
use Modules\HelpdeskChatFlow\Models\ChatFlow;
use Modules\HelpdeskChatFlow\Models\ChatFlowTestCase;
use Modules\HelpdeskChatFlow\Services\ChatFlowTestRunner;

class ChatFlowTestCasesController extends Controller
{
    public function __construct(
        private readonly ChatFlowTestRunner $runner,
    ) {}

    public function index(ChatFlow $chatFlow): View
    {
        $this->authorize('view', $chatFlow);

        $testCases = $chatFlow->testCases()->latest()->get();

        return view('chatflow::test-cases', compact('chatFlow', 'testCases'));
    }

    public function store(ChatFlow $chatFlow, StoreChatFlowTestCaseRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $chatFlow->testCases()->create([
            'name' => $validated['name'],
            'steps' => array_values($validated['steps']),
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Escenario de prueba creado.');
    }

    public function destroy(ChatFlow $chatFlow, ChatFlowTestCase $testCase): RedirectResponse
    {
        $this->authorize('update', $chatFlow);

        $testCase->delete();

        return back()->with('success', 'Escenario eliminado.');
    }

    public function run(ChatFlow $chatFlow, ChatFlowTestCase $testCase): JsonResponse
    {
        $this->authorize('view', $chatFlow);

        $result = $this->runner->run($chatFlow, $testCase->steps ?? []);

        $testCase->update([
            'last_result' => $result['passed'] ? 'passed' : 'failed',
            'last_run_at' => now(),
        ]);

        return response()->json($result);
    }

    public function runAll(ChatFlow $chatFlow): JsonResponse
    {
        $this->authorize('view', $chatFlow);

        $results = [];
        $passed = 0;

        foreach ($chatFlow->testCases as $testCase) {
            $result = $this->runner->run($chatFlow, $testCase->steps ?? []);
            $testCase->update([
                'last_result' => $result['passed'] ? 'passed' : 'failed',
                'last_run_at' => now(),
            ]);

            $results[$testCase->id] = $result['passed'];
            $passed += $result['passed'] ? 1 : 0;
        }

        return response()->json([
            'total' => count($results),
            'passed' => $passed,
            'failed' => count($results) - $passed,
            'results' => $results,
        ]);
    }
}
