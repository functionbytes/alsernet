<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Helpdesk\Http\Requests\Settings\StoreSkillRequest;
use Modules\Helpdesk\Http\Requests\Settings\UpdateSkillRequest;
use Modules\Helpdesk\Models\Skill;

class SkillsController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.skills.view')->only(['index']);
        $this->middleware('can:helpdesk.skills.manage')->only(['create', 'store', 'edit', 'update', 'destroy']);
    }

    public function index(Request $request): View
    {
        $query = Skill::query();

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $skills = $query->latest()->paginate(20);

        $total = Skill::count();
        $withAgents = DB::connection('helpdesk')
            ->table('helpdesk_user_skills')
            ->distinct('skill_id')
            ->count('skill_id');

        $stats = compact('total', 'withAgents');

        return view('helpdesk::settings.skills.index', compact('skills', 'stats'));
    }

    public function create(): View
    {
        return view('helpdesk::settings.skills.create');
    }

    public function store(StoreSkillRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = Str::slug($data['name']);

        Skill::create($data);

        return redirect()
            ->route('settings.helpdesk.skills.index')
            ->with('success', 'Skill creado exitosamente.');
    }

    public function edit(Skill $skill): View
    {
        $skill->loadCount('users');

        return view('helpdesk::settings.skills.edit', compact('skill'));
    }

    public function update(UpdateSkillRequest $request, Skill $skill): RedirectResponse
    {
        $data = $request->validated();

        if ($request->name !== $skill->name) {
            $data['slug'] = Str::slug($request->name);
        }

        $skill->update($data);

        return redirect()
            ->route('settings.helpdesk.skills.index')
            ->with('success', 'Skill actualizado exitosamente.');
    }

    public function destroy(Skill $skill): RedirectResponse
    {
        $agentsCount = DB::connection('helpdesk')
            ->table('helpdesk_user_skills')
            ->where('skill_id', $skill->id)
            ->count();

        if ($agentsCount > 0) {
            return redirect()
                ->route('settings.helpdesk.skills.index')
                ->with('error', 'No se puede eliminar un skill con agentes asignados.');
        }

        $skill->delete();

        return redirect()
            ->route('settings.helpdesk.skills.index')
            ->with('success', 'Skill eliminado exitosamente.');
    }
}
