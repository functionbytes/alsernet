<?php

namespace Modules\HelpdeskHelpcenter\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Database\UniqueConstraintViolationException;
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

        // La identidad del votante es SOLO la cookie: un voto existente solo se
        // edita si la cookie coincide. El ip_hash queda como límite anti-abuso
        // (impide revotar borrando cookies) pero nunca como clave para
        // sobrescribir el voto/comentario de otro visitante tras la misma NAT.
        $existing = HelpCenterArticleVote::query()
            ->where('article_id', $article->id)
            ->where('cookie_id', $cookieId)
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

        $ipAlreadyVoted = HelpCenterArticleVote::query()
            ->where('article_id', $article->id)
            ->where('ip_hash', $ipHash)
            ->exists();

        if ($ipAlreadyVoted) {
            return response()->json(['already_voted' => true])
                ->cookie('hd_voter_id', $cookieId, 60 * 24 * 365);
        }

        try {
            HelpCenterArticleVote::create([
                'article_id' => $article->id,
                'cookie_id' => $cookieId,
                'ip_hash' => $ipHash,
                'vote' => $voteValue,
                'comment' => $request->input('comment'),
            ]);
        } catch (UniqueConstraintViolationException) {
            // Concurrent insert with same unique key — treat as already voted.
            return response()->json(['already_voted' => true])
                ->cookie('hd_voter_id', $cookieId, 60 * 24 * 365);
        }

        return response()->json(['success' => true])
            ->cookie('hd_voter_id', $cookieId, 60 * 24 * 365);
    }
}
