<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Helpdesk\Actions\Customers\CustomerMergeAction;
use Modules\Helpdesk\Http\Requests\MergeCustomerRequest;
use Modules\Helpdesk\Http\Requests\StoreCustomerRequest;
use Modules\Helpdesk\Http\Requests\UpdateCustomerRequest;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Services\CustomerStatsService;
use Modules\HelpdeskEmailLog\Models\EmailLog;

class CustomersController extends Controller
{
    public function __construct(private CustomerStatsService $customerStatsService) {}

    /**
     * Display a listing of customers.
     */
    public function index(Request $request)
    {
        // Authorize using Spatie Permission
        $this->authorize('viewAny', Customer::class);

        $query = Customer::query()->forAgent($request->user());

        // Apply filters
        if ($request->has('search')) {
            $query->search($request->search);
        }

        // Filter by status
        if ($request->tab === 'verified') {
            $query->verified();
        } elseif ($request->tab === 'banned') {
            $query->banned();
        } elseif ($request->tab === 'active') {
            $query->active();
        }

        // Sort and paginate
        $customers = $query
            ->latest('created_at')
            ->paginate(50)
            ->appends($request->query());

        $stats = $this->customerStatsService->getStats();

        // Load selected customer for 2-column detail panel
        $selected = null;
        if ($request->filled('selected')) {
            $selected = Customer::find($request->integer('selected'));

            if ($selected) {
                $this->authorize('view', $selected);
            }
        }

        return view('helpdesk::helpdesk.customers.index', [
            'customers' => $customers,
            'stats' => $stats,
            'selected' => $selected,
            'tabs' => [
                'all' => $stats['total'],
                'verified' => $stats['verified'],
                'banned' => $stats['banned'],
                'active' => $stats['active'],
            ],
        ]);
    }

    /**
     * Show the form for creating a new customer.
     */
    public function create()
    {
        $this->authorize('create', Customer::class);

        return view('helpdesk::helpdesk.customers.create');
    }

    /**
     * Store a newly created customer in storage.
     */
    public function store(StoreCustomerRequest $request)
    {
        $customer = Customer::create($request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Contacto '{$customer->name}' creado.",
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'avatar_url' => $customer->getAvatarUrl(),
                    'total_conversations' => 0,
                ],
            ], 201);
        }

        return redirect()
            ->route('manager.helpdesk.customers.show', $customer)
            ->with('success', "Customer '{$customer->name}' created successfully.");
    }

    /**
     * Display the specified customer.
     */
    public function show(Customer $customer)
    {
        $this->authorize('view', $customer);

        $customer->load([
            'conversations' => fn ($q) => $q->latest()->limit(10),
            'sessions' => fn ($q) => $q->latest('created_at')->limit(5),
            'latestSession',
        ]);

        $recentEmails = filled($customer->email)
            ? EmailLog::query()
                ->whereJsonContains('to_addresses', $customer->email)
                ->latest()
                ->limit(5)
                ->get()
            : collect();

        return view('helpdesk::helpdesk.customers.show', [
            'customer' => $customer,
            'recentEmails' => $recentEmails,
        ]);
    }

    /**
     * Show the form for editing the specified customer.
     */
    public function edit(Customer $customer)
    {
        $this->authorize('update', $customer);

        return view('helpdesk::helpdesk.customers.edit', [
            'customer' => $customer,
        ]);
    }

    /**
     * Update the specified customer in storage.
     */
    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        $customer->update($request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Contacto '{$customer->name}' actualizado.",
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                ],
            ]);
        }

        return redirect()
            ->route('manager.helpdesk.customers.show', $customer)
            ->with('success', "Customer '{$customer->name}' updated successfully.");
    }

    /**
     * Search customers by name, email or phone. Returns JSON array.
     */
    public function search(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Customer::class);

        $q = $request->string('q')->trim()->toString();

        $customers = Customer::query()
            ->forAgent($request->user())
            ->when($q, fn ($query) => $query->search($q))
            ->latest('last_seen_at')
            ->limit(10)
            ->get(['id', 'name', 'email', 'phone', 'whatsapp_phone', 'avatar_url', 'total_conversations']);

        return response()->json(
            $customers->map(fn (Customer $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'email' => $c->email,
                'phone' => $c->phone ?: $c->whatsapp_phone,
                'avatar_url' => $c->getAvatarUrl(),
                'total_conversations' => (int) $c->total_conversations,
            ])
        );
    }

    /**
     * Remove the specified customer from storage (soft delete).
     */
    public function destroy(Customer $customer)
    {
        $this->authorize('delete', $customer);

        $name = $customer->name;
        $customer->delete();

        return redirect()
            ->route('manager.helpdesk.customers.index')
            ->with('success', "Customer '{$name}' deleted successfully.");
    }

    /**
     * Restore a soft-deleted customer.
     */
    public function restore($id)
    {
        $customer = Customer::withTrashed()->findOrFail($id);
        $this->authorize('restore', $customer);

        $customer->restore();

        return redirect()
            ->route('manager.helpdesk.customers.show', $customer)
            ->with('success', 'Customer restored successfully.');
    }

    /**
     * Ban a customer.
     */
    public function ban(Customer $customer)
    {
        $this->authorize('update', $customer);

        $customer->update([
            'is_banned' => true,
            'banned_at' => now(),
        ]);

        return redirect()
            ->back()
            ->with('success', __('helpdesk::helpdesk.messages.customer_banned', ['name' => $customer->name]));
    }

    /**
     * Unban a customer.
     */
    public function unban(Customer $customer)
    {
        $this->authorize('update', $customer);

        $customer->update([
            'is_banned' => false,
            'banned_at' => null,
        ]);

        return redirect()
            ->back()
            ->with('success', __('helpdesk::helpdesk.messages.customer_unbanned', ['name' => $customer->name]));
    }

    /**
     * Return all media attachments across all conversations for a customer.
     * Supports ?type=image|audio|video|document and ?conversation={id} filters.
     */
    public function media(Customer $customer, Request $request): JsonResponse
    {
        $this->authorize('view', $customer);

        $typeFilter = $request->string('type')->trim()->toString() ?: null;
        $convFilter = $request->integer('conversation') ?: null;

        $conversationIds = Conversation::query()
            ->where('customer_id', $customer->id)
            ->when($convFilter, fn ($q) => $q->where('id', $convFilter))
            ->pluck('id');

        $items = ConversationItem::query()
            ->whereIn('conversation_id', $conversationIds)
            ->whereNotNull('attachment_urls')
            ->where('attachment_urls', '!=', '[]')
            ->with(['conversation:id,subject'])
            ->latest()
            ->get();

        $media = $items->flatMap(function (ConversationItem $item): array {
            $urls = $item->attachment_urls ?? [];

            return collect($urls)->map(function (mixed $url) use ($item): array {
                $name = null;
                $size = null;

                if (is_array($url)) {
                    $name = $url['name'] ?? null;
                    $size = $url['size'] ?? null;
                    $url = $url['url'] ?? $url['path'] ?? '';
                }

                $url = (string) $url;
                $path = parse_url($url, PHP_URL_PATH) ?? '';
                $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

                return [
                    'url' => $url,
                    'name' => $name ?? basename($path),
                    'ext' => $ext,
                    'type' => $this->resolveMediaType($ext),
                    'size' => $size,
                    'conversation_id' => $item->conversation_id,
                    'conversation_subject' => $item->conversation?->subject ?? '#'.$item->conversation_id,
                    'sent_at' => $item->created_at?->toIso8601String(),
                    'is_outgoing' => ! empty($item->user_id),
                ];
            })->all();
        });

        if ($typeFilter) {
            $media = $media->filter(fn ($m) => $m['type'] === $typeFilter);
        }

        $page = max(1, $request->integer('page', 1));
        $perPage = 60;
        $total = $media->count();
        $paginated = $media->values()->slice(($page - 1) * $perPage, $perPage)->values();

        return response()->json([
            'success' => true,
            'data' => $paginated,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'last_page' => (int) ceil($total / $perPage),
            ],
        ]);
    }

    /**
     * Merge two customers — base is kept, mergee is soft-deleted.
     * Conversations, inboxes, sessions and external IDs are transferred to the base.
     */
    public function merge(MergeCustomerRequest $request): JsonResponse
    {
        $base = Customer::findOrFail($request->integer('base_customer_id'));
        $mergee = Customer::findOrFail($request->integer('mergee_customer_id'));

        // Ambos contactos deben ser accesibles por el agente (aislamiento por inbox),
        // consistente con show/update/delete: el gate de ruta solo valida el permiso
        // plano helpdesk.customers.merge, no la pertenencia (CustomerPolicy::view).
        $this->authorize('view', $base);
        $this->authorize('view', $mergee);

        $merged = (new CustomerMergeAction($base, $mergee))->execute();

        return response()->json([
            'success' => true,
            'message' => 'Contactos fusionados correctamente.',
            'data' => [
                'id' => $merged->id,
                'name' => $merged->name,
                'email' => $merged->email,
                'phone' => $merged->phone,
            ],
        ]);
    }

    private function resolveMediaType(string $ext): string
    {
        return match (true) {
            in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp']) => 'image',
            in_array($ext, ['mp3', 'wav', 'ogg', 'm4a', 'aac', 'flac']) => 'audio',
            in_array($ext, ['mp4', 'webm', 'mov', 'avi', 'mkv']) => 'video',
            default => 'document',
        };
    }

    /**
     * Return the last 10 conversations for a customer (AJAX, JSON).
     */
    public function conversations(Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        $conversations = Conversation::query()
            ->where('customer_id', $customer->id)
            ->with(['status'])
            ->latest('last_message_at')
            ->limit(10)
            ->get()
            ->map(fn (Conversation $c) => [
                'id' => $c->id,
                'subject' => $c->subject ?? '#'.$c->id,
                'channel' => $c->channel ?? 'web',
                'channel_icon' => $c->channel_info['icon'],
                'preview' => mb_strimwidth(strip_tags((string) ($c->getLatestMessage()?->body ?? '')), 0, 80, '…'),
                'time' => $c->last_message_at?->diffForHumans() ?? $c->created_at?->diffForHumans() ?? '—',
                'status' => $c->status?->name,
                'status_open' => (bool) $c->status?->is_open,
            ]);

        return response()->json(['success' => true, 'data' => $conversations]);
    }

    public function emails(Customer $customer)
    {
        $this->authorize('view', $customer);

        abort_unless(filled($customer->email), 404);

        $mails = EmailLog::query()
            ->whereJsonContains('to_addresses', $customer->email)
            ->latest()
            ->paginate(25);

        return view('helpdesk::helpdesk.customers.emails', compact('customer', 'mails'));
    }

    public function emailsData(Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        if (! filled($customer->email)) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $data = EmailLog::query()
            ->whereJsonContains('to_addresses', $customer->email)
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (EmailLog $log) => [
                'uid' => $log->uid,
                'subject' => $log->subject,
                'module' => $log->module,
                'status' => $log->status,
                'status_label' => $log->status_label,
                'status_color' => $log->status_color,
                'time' => $log->created_at->diffForHumans(),
                'preview_url' => route('helpdeskemaillog.show', $log->uid),
            ]);

        return response()->json(['success' => true, 'data' => $data]);
    }
}
