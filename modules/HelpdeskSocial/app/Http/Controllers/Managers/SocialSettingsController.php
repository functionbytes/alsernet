<?php

namespace Modules\HelpdeskSocial\Http\Controllers\Managers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Redirect;
use Modules\HelpdeskSocial\Http\Requests\StoreSocialAccountRequest;
use Modules\HelpdeskSocial\Http\Requests\StoreSocialRuleRequest;
use Modules\HelpdeskSocial\Http\Requests\StoreSocialTemplateRequest;
use Modules\HelpdeskSocial\Http\Requests\UpdateSocialAccountRequest;
use Modules\HelpdeskSocial\Http\Requests\UpdateSocialRuleRequest;
use Modules\HelpdeskSocial\Http\Requests\UpdateSocialTemplateRequest;
use Modules\HelpdeskSocial\Models\SocialAccount;
use Modules\HelpdeskSocial\Models\SocialApprovalRequest;
use Modules\HelpdeskSocial\Models\SocialAssignmentRule;
use Modules\HelpdeskSocial\Models\SocialComment;
use Modules\HelpdeskSocial\Models\SocialCompetitor;
use Modules\HelpdeskSocial\Models\SocialConversation;
use Modules\HelpdeskSocial\Models\SocialMention;
use Modules\HelpdeskSocial\Models\SocialRule;
use Modules\HelpdeskSocial\Models\SocialSlaPolicy;
use Modules\HelpdeskSocial\Models\SocialTag;
use Modules\HelpdeskSocial\Models\SocialTemplate;

class SocialSettingsController extends Controller
{
    public function accounts(Request $request)
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.manage-accounts'), 403);

        $accounts = SocialAccount::orderBy('name')->paginate(20);

        return view('helpdesksocial::managers.social-accounts.index', compact('accounts'));
    }

    public function createAccount(Request $request)
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.manage-accounts'), 403);

        return view('helpdesksocial::managers.social-accounts.create');
    }

    public function storeAccount(StoreSocialAccountRequest $request)
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.manage-accounts'), 403);

        $validated = $request->validated();
        $validated['connected_by_user_id'] = auth()->id();
        SocialAccount::create($validated);

        return Redirect::route('helpdesksocial.accounts.index')
            ->with('success', 'Cuenta creada correctamente.');
    }

    public function editAccount(Request $request, SocialAccount $account)
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.manage-accounts'), 403);

        return view('helpdesksocial::managers.social-accounts.edit', compact('account'));
    }

    public function updateAccount(UpdateSocialAccountRequest $request, SocialAccount $account)
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.manage-accounts'), 403);

        $account->update($request->validated());

        return Redirect::route('helpdesksocial.accounts.index')
            ->with('success', 'Cuenta actualizada correctamente.');
    }

    public function destroyAccount(SocialAccount $account)
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.manage-accounts'), 403);

        $account->delete();

        return Redirect::route('helpdesksocial.accounts.index')
            ->with('success', 'Cuenta eliminada correctamente.');
    }

    public function inbox(Request $request)
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.view'), 403);

        $comments = SocialComment::with('socialAccount')
            ->notSpam()
            ->latest('posted_at')
            ->paginate(25);

        $stats = [
            'total' => SocialComment::count(),
            'pending' => SocialComment::where('status', 'pending')->count(),
            'replied' => SocialComment::whereNotNull('replied_at')->count(),
            'spam' => SocialComment::where('is_spam', true)->count(),
        ];

        return view('helpdesksocial::managers.social-inbox.index', compact('comments', 'stats'));
    }

    public function showComment(Request $request, SocialComment $comment)
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.view'), 403);

        $comment->load(['socialAccount', 'intent', 'conversation']);

        return view('helpdesksocial::managers.social-inbox.show', compact('comment'));
    }

    public function replyComment(Request $request, SocialComment $comment)
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.view'), 403);

        $validated = $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        $comment->markAsReplied($validated['body'], auth()->id(), null, 'manual');

        return Redirect::route('helpdesksocial.inbox.index')
            ->with('success', 'Respuesta enviada correctamente.');
    }

    public function assignComment(Request $request, SocialComment $comment)
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.view'), 403);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $comment->assignTo($validated['user_id']);

        return Redirect::route('helpdesksocial.inbox.index')
            ->with('success', 'Comentario asignado correctamente.');
    }

    public function markCommentAsSpam(SocialComment $comment)
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.view'), 403);

        $comment->markAsSpam();

        return Redirect::route('helpdesksocial.inbox.index')
            ->with('success', 'Comentario marcado como spam.');
    }

    public function escalateComment(SocialComment $comment)
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.view'), 403);

        $comment->markAsEscalated();

        return Redirect::route('helpdesksocial.inbox.index')
            ->with('success', 'Comentario escalado correctamente.');
    }

    public function rules(Request $request)
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.manage-rules'), 403);

        $rules = SocialRule::ordered()->paginate(20);

        return view('helpdesksocial::managers.social-rules.index', compact('rules'));
    }

    public function createRule(Request $request)
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.manage-rules'), 403);

        $templates = SocialTemplate::active()->get();

        return view('helpdesksocial::managers.social-rules.create', compact('templates'));
    }

    public function storeRule(StoreSocialRuleRequest $request)
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.manage-rules'), 403);

        $validated = $request->validated();
        $validated['created_by_user_id'] = auth()->id();
        $validated['is_active'] = true;
        SocialRule::create($validated);

        return Redirect::route('helpdesksocial.rules.index')
            ->with('success', 'Regla creada correctamente.');
    }

    public function editRule(Request $request, SocialRule $rule)
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.manage-rules'), 403);

        $templates = SocialTemplate::active()->get();

        return view('helpdesksocial::managers.social-rules.edit', compact('rule', 'templates'));
    }

    public function updateRule(UpdateSocialRuleRequest $request, SocialRule $rule)
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.manage-rules'), 403);

        $rule->update($request->validated());

        return Redirect::route('helpdesksocial.rules.index')
            ->with('success', 'Regla actualizada correctamente.');
    }

    public function destroyRule(SocialRule $rule)
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.manage-rules'), 403);

        $rule->delete();

        return Redirect::route('helpdesksocial.rules.index')
            ->with('success', 'Regla eliminada correctamente.');
    }

    public function templates(Request $request)
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.manage-templates'), 403);

        $templates = SocialTemplate::orderBy('name')->paginate(20);

        return view('helpdesksocial::managers.social-templates.index', compact('templates'));
    }

    public function createTemplate(Request $request)
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.manage-templates'), 403);

        return view('helpdesksocial::managers.social-templates.create');
    }

    public function storeTemplate(StoreSocialTemplateRequest $request)
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.manage-templates'), 403);

        $validated = $request->validated();
        $validated['created_by_user_id'] = auth()->id();
        $validated['is_active'] = true;
        SocialTemplate::create($validated);

        return Redirect::route('helpdesksocial.templates.index')
            ->with('success', 'Plantilla creada correctamente.');
    }

    public function editTemplate(Request $request, SocialTemplate $template)
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.manage-templates'), 403);

        return view('helpdesksocial::managers.social-templates.edit', compact('template'));
    }

    public function updateTemplate(UpdateSocialTemplateRequest $request, SocialTemplate $template)
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.manage-templates'), 403);

        $template->update($request->validated());

        return Redirect::route('helpdesksocial.templates.index')
            ->with('success', 'Plantilla actualizada correctamente.');
    }

    public function destroyTemplate(SocialTemplate $template)
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.manage-templates'), 403);

        $template->delete();

        return Redirect::route('helpdesksocial.templates.index')
            ->with('success', 'Plantilla eliminada correctamente.');
    }

    public function analytics(Request $request)
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.view-analytics'), 403);

        $days = $request->get('days', 30);
        $from = now()->subDays($days)->startOfDay();

        $overview = [
            'total_comments' => SocialComment::whereDate('posted_at', '>=', $from)->count(),
            'total_replied' => SocialComment::whereDate('posted_at', '>=', $from)->whereNotNull('replied_at')->count(),
            'avg_response_time' => SocialComment::whereDate('posted_at', '>=', $from)
                ->whereNotNull('replied_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, posted_at, replied_at)) as avg')
                ->value('avg'),
            'intents' => SocialComment::whereDate('posted_at', '>=', $from)
                ->whereNotNull('intent')
                ->selectRaw('intent, COUNT(*) as count')
                ->groupBy('intent')
                ->pluck('count', 'intent'),
        ];

        return view('helpdesksocial::managers.social-analytics.index', compact('overview', 'days'));
    }

    public function tags(Request $request)
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.view'), 403);

        $tags = SocialTag::orderBy('name')->paginate(20);

        return view('helpdesksocial::managers.social-tags.index', compact('tags'));
    }

    public function slaPolicies(Request $request)
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.view'), 403);

        $policies = SocialSlaPolicy::orderBy('name')->paginate(20);

        return view('helpdesksocial::managers.social-sla.index', compact('policies'));
    }

    public function conversations(Request $request)
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.view'), 403);

        $conversations = SocialConversation::with('account')
            ->orderByDesc('last_message_at')
            ->paginate(20);

        return view('helpdesksocial::managers.social-conversations.index', compact('conversations'));
    }

    public function assignmentRules(Request $request)
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.view'), 403);

        $rules = SocialAssignmentRule::with('assignee')
            ->ordered()
            ->paginate(20);

        return view('helpdesksocial::managers.social-rules.assignment', compact('rules'));
    }

    public function mentions(Request $request)
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.view'), 403);

        $query = SocialMention::query()->latest('discovered_at');

        if ($request->filled('platform')) {
            $query->where('platform', $request->get('platform'));
        }

        if ($request->filled('sentiment')) {
            $query->where('sentiment', $request->get('sentiment'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        $mentions = $query->paginate(25)->withQueryString();
        $filters = $request->only(['platform', 'sentiment', 'status']);

        return view('helpdesksocial::managers.social-mentions.index', compact('mentions', 'filters'));
    }

    public function approvalRequests(Request $request)
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.view'), 403);

        $requests = SocialApprovalRequest::with(['comment.socialAccount', 'requester', 'approver'])
            ->latest('created_at')
            ->paginate(20);

        return view('helpdesksocial::managers.social-approvals.index', compact('requests'));
    }

    public function competitors(Request $request)
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.view'), 403);

        $competitors = SocialCompetitor::with(['account', 'metrics'])
            ->orderBy('name')
            ->paginate(20);

        return view('helpdesksocial::managers.social-competitors.index', compact('competitors'));
    }

    public function agentPerformance(Request $request)
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.view-analytics'), 403);

        $days = $request->get('days', 30);
        $from = now()->subDays($days)->startOfDay();

        $agentIds = SocialComment::query()
            ->whereDate('posted_at', '>=', $from)
            ->whereNotNull('assigned_to_user_id')
            ->distinct()
            ->pluck('assigned_to_user_id');

        $agents = User::query()
            ->whereIn('id', $agentIds)
            ->paginate(20);

        $stats = SocialComment::query()
            ->whereDate('posted_at', '>=', $from)
            ->whereNotNull('assigned_to_user_id')
            ->groupBy('assigned_to_user_id')
            ->selectRaw('assigned_to_user_id, COUNT(*) as assigned_count, COUNT(replied_at) as replied_count, AVG(TIMESTAMPDIFF(SECOND, posted_at, replied_at)) as avg_response_time')
            ->get()
            ->keyBy('assigned_to_user_id');

        foreach ($agents as $agent) {
            $stat = $stats->get($agent->id);
            $agent->assigned_count = $stat?->assigned_count ?? 0;
            $agent->replied_count = $stat?->replied_count ?? 0;
            $agent->avg_response_time = $stat?->avg_response_time;
        }

        return view('helpdesksocial::managers.social-analytics.agents', compact('agents', 'days'));
    }

    public function savedReplies(Request $request): JsonResponse
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.view'), 403);

        $templates = SocialTemplate::active()
            ->forPlatform($request->get('platform'))
            ->orderBy('name')
            ->get(['id', 'name', 'body', 'category']);

        return response()->json($templates);
    }
}
