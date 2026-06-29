<?php

namespace Modules\Social\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Social\Http\Requests\StoreAbTestRequest;
use Modules\Social\Models\AbTest;
use Modules\Social\Models\Post;

class AbTestController extends Controller
{
    public function index()
    {
        $abTests = AbTest::where('account_id', auth()->user()->account_id)
            ->with(['variantA', 'variantB', 'winner'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('social::ab-tests.index', compact('abTests'));
    }

    public function create()
    {
        $posts = Post::where('account_id', auth()->user()->account_id)
            ->where('status', 'published')
            ->get();

        return view('social::ab-tests.create', compact('posts'));
    }

    public function store(StoreAbTestRequest $request)
    {
        $test = AbTest::create([
            ...$request->validated(),
            'started_at' => now(),
            'status' => 'running',
        ]);

        return redirect()
            ->route('admin.social.ab-tests.show', $test)
            ->with('success', 'Test A/B creado exitosamente');
    }

    public function show(AbTest $abTest)
    {
        $this->authorize('view', $abTest);

        $abTest->load(['variantA', 'variantB', 'winner']);
        $abTest->calculateScores();

        return view('social::ab-tests.show', compact('abTest'));
    }

    public function complete(AbTest $abTest)
    {
        $this->authorize('update', $abTest);

        $abTest->complete();

        return redirect()
            ->route('admin.social.ab-tests.show', $abTest)
            ->with('success', 'Test A/B completado. Ganador: Variante '.$abTest->winner_post_id);
    }

    public function destroy(AbTest $abTest)
    {
        $this->authorize('delete', $abTest);

        $abTest->update(['status' => 'cancelled']);

        return redirect()
            ->route('admin.social.ab-tests.index')
            ->with('success', 'Test A/B cancelado');
    }
}
