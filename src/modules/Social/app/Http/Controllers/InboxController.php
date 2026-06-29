<?php

namespace Modules\Social\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;
use Modules\Social\Models\Mention;
use Modules\Social\Models\SocialAccount;
use Modules\Social\Services\Publishers\FacebookPublisher;
use Modules\Social\Services\Publishers\InstagramPublisher;
use Modules\Social\Services\Publishers\LinkedInPublisher;
use Modules\Social\Services\Publishers\TwitterPublisher;

class InboxController extends Controller
{
    /**
     * Display unified inbox
     */
    public function index(Request $request): View
    {
        $query = Mention::with(['post', 'socialAccount', 'repliedBy'])
            ->where('account_id', auth()->user()->account_id)
            ->notArchived();

        // Filter by status
        $status = $request->get('status', 'unread');
        if ($status === 'unread') {
            $query->unread();
        } elseif ($status === 'read') {
            $query->read();
        } elseif ($status === 'replied') {
            $query->replied();
        } elseif ($status === 'not_replied') {
            $query->notReplied();
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->ofType($request->type);
        }

        // Filter by network
        if ($request->filled('network')) {
            $query->fromNetwork($request->network);
        }

        // Filter by post
        if ($request->filled('post_id')) {
            $query->where('post_id', $request->post_id);
        }

        // Filter by sentiment
        if ($request->filled('sentiment')) {
            $query->where('sentiment', $request->sentiment);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('content', 'like', "%{$search}%")
                    ->orWhere('author_name', 'like', "%{$search}%")
                    ->orWhere('author_username', 'like', "%{$search}%");
            });
        }

        $mentions = $query->latest()->paginate(20);

        // Stats
        $stats = [
            'unread' => Mention::where('account_id', auth()->user()->account_id)
                ->unread()
                ->notArchived()
                ->count(),
            'total' => Mention::where('account_id', auth()->user()->account_id)
                ->notArchived()
                ->count(),
            'replied' => Mention::where('account_id', auth()->user()->account_id)
                ->replied()
                ->notArchived()
                ->count(),
            'not_replied' => Mention::where('account_id', auth()->user()->account_id)
                ->notReplied()
                ->notArchived()
                ->count(),
        ];

        // Get available social accounts for filters
        $socialAccounts = SocialAccount::where('account_id', auth()->user()->account_id)
            ->where('status', 1)
            ->get();

        return view('social::inbox.index', compact('mentions', 'stats', 'status', 'socialAccounts'));
    }

    /**
     * Show archived mentions
     */
    public function archived(Request $request): View
    {
        $mentions = Mention::with(['post', 'socialAccount', 'repliedBy'])
            ->where('account_id', auth()->user()->account_id)
            ->archived()
            ->latest()
            ->paginate(20);

        $stats = [
            'archived' => $mentions->total(),
        ];

        return view('social::inbox.archived', compact('mentions', 'stats'));
    }

    /**
     * Mark mention as read
     */
    public function markAsRead(Mention $mention): JsonResponse
    {
        // Verify ownership
        if ($mention->account_id !== auth()->user()->account_id) {
            abort(403);
        }

        $mention->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Marcado como leído',
        ]);
    }

    /**
     * Mark mention as unread
     */
    public function markAsUnread(Mention $mention): JsonResponse
    {
        // Verify ownership
        if ($mention->account_id !== auth()->user()->account_id) {
            abort(403);
        }

        $mention->markAsUnread();

        return response()->json([
            'success' => true,
            'message' => 'Marcado como no leído',
        ]);
    }

    /**
     * Archive mention
     */
    public function archive(Mention $mention): JsonResponse
    {
        // Verify ownership
        if ($mention->account_id !== auth()->user()->account_id) {
            abort(403);
        }

        $mention->archive();

        return response()->json([
            'success' => true,
            'message' => 'Archivado exitosamente',
        ]);
    }

    /**
     * Unarchive mention
     */
    public function unarchive(Mention $mention): JsonResponse
    {
        // Verify ownership
        if ($mention->account_id !== auth()->user()->account_id) {
            abort(403);
        }

        $mention->unarchive();

        return response()->json([
            'success' => true,
            'message' => 'Desarchivado exitosamente',
        ]);
    }

    /**
     * Reply to mention
     */
    public function reply(Request $request, Mention $mention): RedirectResponse
    {
        // Verify ownership
        if ($mention->account_id !== auth()->user()->account_id) {
            abort(403);
        }

        // Validate
        $request->validate([
            'reply_content' => 'required|string|max:5000',
        ]);

        try {
            // Send reply via appropriate publisher
            $success = $this->sendReply($mention, $request->reply_content);

            if ($success) {
                // Mark as replied
                $mention->markAsReplied(auth()->user(), $request->reply_content);

                return back()->with('success', 'Respuesta enviada exitosamente');
            }

            return back()->with('error', 'Error al enviar la respuesta. Intenta nuevamente.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error: '.$e->getMessage());
        }
    }

    /**
     * Bulk mark as read
     */
    public function bulkMarkAsRead(Request $request): JsonResponse
    {
        $request->validate([
            'mention_ids' => 'required|array',
            'mention_ids.*' => 'exists:social_mentions,id',
        ]);

        $mentions = Mention::whereIn('id', $request->mention_ids)
            ->where('account_id', auth()->user()->account_id)
            ->get();

        foreach ($mentions as $mention) {
            $mention->markAsRead();
        }

        return response()->json([
            'success' => true,
            'message' => "{$mentions->count()} menciones marcadas como leídas",
        ]);
    }

    /**
     * Bulk archive
     */
    public function bulkArchive(Request $request): JsonResponse
    {
        $request->validate([
            'mention_ids' => 'required|array',
            'mention_ids.*' => 'exists:social_mentions,id',
        ]);

        $mentions = Mention::whereIn('id', $request->mention_ids)
            ->where('account_id', auth()->user()->account_id)
            ->get();

        foreach ($mentions as $mention) {
            $mention->archive();
        }

        return response()->json([
            'success' => true,
            'message' => "{$mentions->count()} menciones archivadas",
        ]);
    }

    /**
     * Send reply via appropriate network publisher
     */
    protected function sendReply(Mention $mention, string $replyContent): bool
    {
        $network = $mention->socialAccount->network->value;

        $publisher = match ($network) {
            'facebook' => app(FacebookPublisher::class),
            'instagram' => app(InstagramPublisher::class),
            'twitter' => app(TwitterPublisher::class),
            'linkedin' => app(LinkedInPublisher::class),
            default => null,
        };

        if (! $publisher) {
            throw new \Exception('Publisher no disponible para esta red social');
        }

        // Call appropriate reply method based on mention type
        return match ($mention->type) {
            'comment' => $this->replyToComment($publisher, $mention, $replyContent),
            'message' => $this->replyToMessage($publisher, $mention, $replyContent),
            'mention' => $this->replyToMention($publisher, $mention, $replyContent),
            default => throw new \Exception('Tipo de mención no soportado'),
        };
    }

    /**
     * Reply to a comment
     */
    protected function replyToComment($publisher, Mention $mention, string $content): bool
    {
        $token = decrypt($mention->socialAccount->access_token);

        // Facebook/Instagram comment reply
        if (in_array($mention->socialAccount->network->value, ['facebook', 'instagram'])) {
            $response = Http::post(
                "https://graph.facebook.com/v21.0/{$mention->external_id}/comments",
                [
                    'message' => $content,
                    'access_token' => $token,
                ]
            );

            return $response->successful();
        }

        // Twitter reply
        if ($mention->socialAccount->network->value === 'twitter') {
            $response = Http::withToken($token)
                ->post('https://api.twitter.com/2/tweets', [
                    'text' => $content,
                    'reply' => [
                        'in_reply_to_tweet_id' => $mention->external_id,
                    ],
                ]);

            return $response->successful();
        }

        return false;
    }

    /**
     * Reply to a direct message
     */
    protected function replyToMessage($publisher, Mention $mention, string $content): bool
    {
        // Implementation depends on network's messaging API
        // This is a placeholder
        return false;
    }

    /**
     * Reply to a mention
     */
    protected function replyToMention($publisher, Mention $mention, string $content): bool
    {
        // Similar to comment reply for most networks
        return $this->replyToComment($publisher, $mention, $content);
    }
}
