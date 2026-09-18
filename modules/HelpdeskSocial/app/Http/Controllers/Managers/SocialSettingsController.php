<?php

namespace Modules\HelpdeskSocial\Http\Controllers\Managers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Redirect;
use Modules\HelpdeskSocial\Contracts\SocialApiClientInterface;
use Modules\HelpdeskSocial\Http\Requests\AssignSocialCommentRequest;
use Modules\HelpdeskSocial\Http\Requests\Managers\StoreSocialAssignmentRuleRequest;
use Modules\HelpdeskSocial\Http\Requests\Managers\StoreSocialCompetitorRequest;
use Modules\HelpdeskSocial\Http\Requests\Managers\UpdateSocialAssignmentRuleRequest;
use Modules\HelpdeskSocial\Http\Requests\Managers\UpdateSocialCompetitorRequest;
use Modules\HelpdeskSocial\Http\Requests\ReplySocialCommentRequest;
use Modules\HelpdeskSocial\Http\Requests\StoreSocialAccountRequest;
use Modules\HelpdeskSocial\Http\Requests\StoreSocialRuleRequest;
use Modules\HelpdeskSocial\Http\Requests\StoreSocialSlaPolicyRequest;
use Modules\HelpdeskSocial\Http\Requests\StoreSocialTagRequest;
use Modules\HelpdeskSocial\Http\Requests\StoreSocialTemplateRequest;
use Modules\HelpdeskSocial\Http\Requests\UpdateSocialAccountRequest;
use Modules\HelpdeskSocial\Http\Requests\UpdateSocialRuleRequest;
use Modules\HelpdeskSocial\Http\Requests\UpdateSocialSlaPolicyRequest;
use Modules\HelpdeskSocial\Http\Requests\UpdateSocialTagRequest;
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
    public function __construct(
        private readonly SocialApiClientInterface $apiClient,
    ) {}

    public function accounts(Request $request)
    {
        abort_if(! auth()->user()?->can('helpdesksocial.accounts.manage'), 403);

        $accounts = SocialAccount::orderBy('name')->paginate(20);

        return view('helpdesksocial::managers.social-accounts.index', compact('accounts'));
    }

    public function createAccount(Request $request)
    {
        abort_if(! auth()->user()?->can('helpdesksocial.accounts.manage'), 403);

        return view('helpdesksocial::managers.social-accounts.create');
    }

    public function storeAccount(StoreSocialAccountRequest $request)
    {
        abort_if(! auth()->user()?->can('helpdesksocial.accounts.manage'), 403);

        $validated = $request->validated();
        $validated['connected_by_user_id'] = auth()->id();
        SocialAccount::create($validated);

        return Redirect::route('helpdesksocial.accounts.index')
            ->with('success', 'Cuenta creada correctamente.');
    }

    public function editAccount(Request $request, SocialAccount $account)
    {
        abort_if(! auth()->user()?->can('helpdesksocial.accounts.manage'), 403);

        return view('helpdesksocial::managers.social-accounts.edit', compact('account'));
    }

    public function updateAccount(UpdateSocialAccountRequest $request, SocialAccount $account)
    {
        abort_if(! auth()->user()?->can('helpdesksocial.accounts.manage'), 403);

        $account->update($request->validated());

        return Redirect::route('helpdesksocial.accounts.index')
            ->with('success', 'Cuenta actualizada correctamente.');
    }

    public function destroyAccount(SocialAccount $account)
    {
        abort_if(! auth()->user()?->can('helpdesksocial.accounts.manage'), 403);

        $account->delete();

        return Redirect::route('helpdesksocial.accounts.index')
            ->with('success', 'Cuenta eliminada correctamente.');
    }

    public function inbox(Request $request)
    {
        abort_if(! auth()->user()?->can('helpdesksocial.view'), 403);

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
        abort_if(! auth()->user()?->can('helpdesksocial.view'), 403);

        $comment->load(['socialAccount', 'intent', 'conversation']);

        return view('helpdesksocial::managers.social-inbox.show', compact('comment'));
    }

    public function replyComment(ReplySocialCommentRequest $request, SocialComment $comment)
    {
        $validated = $request->validated();
        $account = $comment->socialAccount;

        $replyId = $this->apiClient->replyToComment(
            $comment->external_comment_id,
            $validated['body'],
            $account->page_access_token,
            $comment->platform
        );

        if (! $replyId) {
            return Redirect::back()->withErrors(['body' => 'Error al enviar la respuesta a la red social.']);
        }

        $comment->markAsReplied($validated['body'], auth()->id(), $replyId, 'manual');

        return Redirect::route('helpdesksocial.inbox.index')
            ->with('success', 'Respuesta enviada correctamente.');
    }

    public function assignComment(AssignSocialCommentRequest $request, SocialComment $comment)
    {
        $comment->assignTo($request->validated()['user_id']);

        return Redirect::route('helpdesksocial.inbox.index')
            ->with('success', 'Comentario asignado correctamente.');
    }

    public function markCommentAsSpam(SocialComment $comment)
    {
        abort_if(! auth()->user()?->can('helpdesksocial.manage'), 403);

        $comment->markAsSpam();

        return Redirect::route('helpdesksocial.inbox.index')
            ->with('success', 'Comentario marcado como spam.');
    }

    public function escalateComment(SocialComment $comment)
    {
        abort_if(! auth()->user()?->can('helpdesksocial.manage'), 403);

        $comment->markAsEscalated();

        return Redirect::route('helpdesksocial.inbox.index')
            ->with('success', 'Comentario escalado correctamente.');
    }

    public function rules(Request $request)
    {
        abort_if(! auth()->user()?->can('helpdesksocial.rules.manage'), 403);

        $rules = SocialRule::ordered()->paginate(20);

        return view('helpdesksocial::managers.social-rules.index', compact('rules'));
    }

    public function createRule(Request $request)
    {
        abort_if(! auth()->user()?->can('helpdesksocial.rules.manage'), 403);

        $templates = SocialTemplate::active()->get();

        return view('helpdesksocial::managers.social-rules.create', compact('templates'));
    }

    public function storeRule(StoreSocialRuleRequest $request)
    {
        abort_if(! auth()->user()?->can('helpdesksocial.rules.manage'), 403);

        $validated = $request->validated();
        $validated['created_by_user_id'] = auth()->id();
        $validated['is_active'] = true;
        SocialRule::create($validated);

        return Redirect::route('helpdesksocial.rules.index')
            ->with('success', 'Regla creada correctamente.');
    }

    public function editRule(Request $request, SocialRule $rule)
    {
        abort_if(! auth()->user()?->can('helpdesksocial.rules.manage'), 403);

        $templates = SocialTemplate::active()->get();

        return view('helpdesksocial::managers.social-rules.edit', compact('rule', 'templates'));
    }

    public function updateRule(UpdateSocialRuleRequest $request, SocialRule $rule)
    {
        abort_if(! auth()->user()?->can('helpdesksocial.rules.manage'), 403);

        $rule->update($request->validated());

        return Redirect::route('helpdesksocial.rules.index')
            ->with('success', 'Regla actualizada correctamente.');
    }

    public function destroyRule(SocialRule $rule)
    {
        abort_if(! auth()->user()?->can('helpdesksocial.rules.manage'), 403);

        $rule->delete();

        return Redirect::route('helpdesksocial.rules.index')
            ->with('success', 'Regla eliminada correctamente.');
    }

    public function templates(Request $request)
    {
        abort_if(! auth()->user()?->can('helpdesksocial.templates.manage'), 403);

        $templates = SocialTemplate::orderBy('name')->paginate(20);

        return view('helpdesksocial::managers.social-templates.index', compact('templates'));
    }

    public function createTemplate(Request $request)
    {
        abort_if(! auth()->user()?->can('helpdesksocial.templates.manage'), 403);

        return view('helpdesksocial::managers.social-templates.create');
    }

    public function storeTemplate(StoreSocialTemplateRequest $request)
    {
        abort_if(! auth()->user()?->can('helpdesksocial.templates.manage'), 403);

        $validated = $request->validated();
        $validated['created_by_user_id'] = auth()->id();
        $validated['is_active'] = true;
        SocialTemplate::create($validated);

        return Redirect::route('helpdesksocial.templates.index')
            ->with('success', 'Plantilla creada correctamente.');
    }

    public function editTemplate(Request $request, SocialTemplate $template)
    {
        abort_if(! auth()->user()?->can('helpdesksocial.templates.manage'), 403);

        return view('helpdesksocial::managers.social-templates.edit', compact('template'));
    }

    public function updateTemplate(UpdateSocialTemplateRequest $request, SocialTemplate $template)
    {
        abort_if(! auth()->user()?->can('helpdesksocial.templates.manage'), 403);

        $template->update($request->validated());

        return Redirect::route('helpdesksocial.templates.index')
            ->with('success', 'Plantilla actualizada correctamente.');
    }

    public function destroyTemplate(SocialTemplate $template)
    {
        abort_if(! auth()->user()?->can('helpdesksocial.templates.manage'), 403);

        $template->delete();

        return Redirect::route('helpdesksocial.templates.index')
            ->with('success', 'Plantilla eliminada correctamente.');
    }

    public function analytics(Request $request)
    {
        abort_if(! auth()->user()?->can('helpdesksocial.analytics.view'), 403);

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
        abort_if(! auth()->user()?->can('helpdesksocial.view'), 403);

        $tags = SocialTag::orderBy('name')->paginate(20);

        return view('helpdesksocial::managers.social-tags.index', compact('tags'));
    }

    public function storeTag(StoreSocialTagRequest $request): RedirectResponse
    {
        $data = $request->safe()->all();
        $data['is_active'] = $request->has('is_active') ? '1' : '0';

        SocialTag::create($data);

        return Redirect::route('helpdesksocial.tags.index')
            ->with('success', 'Etiqueta creada correctamente.');
    }

    public function updateTag(UpdateSocialTagRequest $request, SocialTag $tag): RedirectResponse
    {
        $data = $request->safe()->all();
        $data['is_active'] = $request->has('is_active') ? '1' : '0';

        $tag->update($data);

        return Redirect::route('helpdesksocial.tags.index')
            ->with('success', 'Etiqueta actualizada correctamente.');
    }

    public function destroyTag(SocialTag $tag): RedirectResponse
    {
        abort_if(! auth()->user()?->can('helpdesksocial.rules.manage'), 403);

        $tag->delete();

        return Redirect::route('helpdesksocial.tags.index')
            ->with('success', 'Etiqueta eliminada correctamente.');
    }

    public function slaPolicies(Request $request)
    {
        abort_if(! auth()->user()?->can('helpdesksocial.view'), 403);

        $policies = SocialSlaPolicy::orderBy('name')->paginate(20);

        return view('helpdesksocial::managers.social-sla.index', compact('policies'));
    }

    public function storeSlaPolicy(StoreSocialSlaPolicyRequest $request): RedirectResponse
    {
        $data = $request->safe()->all();
        $data['is_active'] = $request->has('is_active') ? '1' : '0';

        SocialSlaPolicy::create($data);

        return Redirect::route('helpdesksocial.sla-policies.index')
            ->with('success', 'Política SLA creada correctamente.');
    }

    public function updateSlaPolicy(UpdateSocialSlaPolicyRequest $request, SocialSlaPolicy $policy): RedirectResponse
    {
        $data = $request->safe()->all();
        $data['is_active'] = $request->has('is_active') ? '1' : '0';

        $policy->update($data);

        return Redirect::route('helpdesksocial.sla-policies.index')
            ->with('success', 'Política SLA actualizada correctamente.');
    }

    public function destroySlaPolicy(SocialSlaPolicy $policy): RedirectResponse
    {
        abort_if(! auth()->user()?->can('helpdesksocial.rules.manage'), 403);

        $policy->delete();

        return Redirect::route('helpdesksocial.sla-policies.index')
            ->with('success', 'Política SLA eliminada correctamente.');
    }

    public function conversations(Request $request)
    {
        abort_if(! auth()->user()?->can('helpdesksocial.view'), 403);

        $conversations = SocialConversation::with('account')
            ->orderByDesc('last_message_at')
            ->paginate(20);

        return view('helpdesksocial::managers.social-conversations.index', compact('conversations'));
    }

    public function assignmentRules(Request $request)
    {
        abort_if(! auth()->user()?->can('helpdesksocial.view'), 403);

        $rules = SocialAssignmentRule::with('assignee')
            ->ordered()
            ->paginate(20);

        return view('helpdesksocial::managers.social-rules.assignment', compact('rules'));
    }

    public function storeAssignmentRule(StoreSocialAssignmentRuleRequest $request): RedirectResponse
    {
        $data = $request->safe()->all();
        $data['conditions'] = $data['conditions'] ?? [];
        $data['is_active'] = $request->has('is_active') ? '1' : '0';

        SocialAssignmentRule::create($data);

        return Redirect::route('helpdesksocial.assignment-rules.index')
            ->with('success', 'Regla de asignación creada correctamente.');
    }

    public function updateAssignmentRule(UpdateSocialAssignmentRuleRequest $request, SocialAssignmentRule $rule): RedirectResponse
    {
        $data = $request->safe()->all();
        $data['is_active'] = $request->has('is_active') ? '1' : '0';

        $rule->update($data);

        return Redirect::route('helpdesksocial.assignment-rules.index')
            ->with('success', 'Regla de asignación actualizada correctamente.');
    }

    public function destroyAssignmentRule(SocialAssignmentRule $rule): RedirectResponse
    {
        abort_if(! auth()->user()?->can('helpdesksocial.rules.manage'), 403);

        $rule->delete();

        return Redirect::route('helpdesksocial.assignment-rules.index')
            ->with('success', 'Regla de asignación eliminada correctamente.');
    }

    public function mentions(Request $request)
    {
        abort_if(! auth()->user()?->can('helpdesksocial.view'), 403);

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
        abort_if(! auth()->user()?->can('helpdesksocial.view'), 403);

        $requests = SocialApprovalRequest::with(['comment.socialAccount', 'requester', 'approver'])
            ->latest('created_at')
            ->paginate(20);

        return view('helpdesksocial::managers.social-approvals.index', compact('requests'));
    }

    public function competitors(Request $request)
    {
        abort_if(! auth()->user()?->can('helpdesksocial.view'), 403);

        $competitors = SocialCompetitor::with(['account', 'metrics'])
            ->orderBy('name')
            ->paginate(20);

        return view('helpdesksocial::managers.social-competitors.index', compact('competitors'));
    }

    public function storeCompetitor(StoreSocialCompetitorRequest $request): RedirectResponse
    {
        $data = $request->safe()->all();
        $data['is_active'] = $request->has('is_active') ? '1' : '0';

        SocialCompetitor::create($data);

        return Redirect::route('helpdesksocial.competitors.index')
            ->with('success', 'Competidor creado correctamente.');
    }

    public function updateCompetitor(UpdateSocialCompetitorRequest $request, SocialCompetitor $competitor): RedirectResponse
    {
        $data = $request->safe()->all();
        $data['is_active'] = $request->has('is_active') ? '1' : '0';

        $competitor->update($data);

        return Redirect::route('helpdesksocial.competitors.index')
            ->with('success', 'Competidor actualizado correctamente.');
    }

    public function destroyCompetitor(SocialCompetitor $competitor): RedirectResponse
    {
        abort_if(! auth()->user()?->can('helpdesksocial.analytics.view'), 403);

        $competitor->delete();

        return Redirect::route('helpdesksocial.competitors.index')
            ->with('success', 'Competidor eliminado correctamente.');
    }

    public function agentPerformance(Request $request)
    {
        abort_if(! auth()->user()?->can('helpdesksocial.analytics.view'), 403);

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
        abort_if(! auth()->user()?->can('helpdesksocial.view'), 403);

        $templates = SocialTemplate::active()
            ->forPlatform($request->get('platform'))
            ->orderBy('name')
            ->get(['id', 'name', 'body', 'category']);

        return response()->json($templates);
    }
}
