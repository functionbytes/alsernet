<?php

namespace Modules\HelpdeskHelpcenter\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Modules\HelpdeskHelpcenter\Http\Requests\VoteArticleRequest;
use Modules\HelpdeskHelpcenter\Models\HelpCenterArticle;
use Modules\HelpdeskHelpcenter\Models\HelpCenterArticleVote;

class ArticleVoteController extends Controller
{
    public function vote(VoteArticleRequest $request, string $slug): JsonResponse
    {
        $article = HelpCenterArticle::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->first();

        if (! $article) {
            return response()->json(['error' => 'Article not found'], 404);
        }

        $cookieId = $request->cookie('hd_voter_id') ?? Str::uuid()->toString();
        $salt = config('helpdeskhelpcenter.vote_ip_salt') ?: config('app.key');
        $ipHash = hash('sha256', $request->ip().$salt);
        $voteValue = (int) $request->input('vote');

        $existing = HelpCenterArticleVote::query()
            ->where('article_id', $article->id)
            ->where(fn ($q) => $q->where('cookie_id', $cookieId)->orWhere('ip_hash', $ipHash))
            ->first();

        if ($existing) {
            if ($existing->vote === $voteValue) {
                return response()->json(['already_voted' => true])
                    ->cookie('hd_voter_id', $cookieId, 60 * 24 * 365);
            }

            $existing->update(['vote' => $voteValue, 'comment' => $request->input('comment')]);

            return response()->json(['success' => true, 'updated' => true])
                ->cookie('hd_voter_id', $cookieId, 60 * 24 * 365);
        }

        HelpCenterArticleVote::create([
            'article_id' => $article->id,
            'cookie_id' => $cookieId,
            'ip_hash' => $ipHash,
            'vote' => $voteValue,
            'comment' => $request->input('comment'),
        ]);

        return response()->json(['success' => true])
            ->cookie('hd_voter_id', $cookieId, 60 * 24 * 365);
    }
}
