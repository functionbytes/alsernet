<?php

namespace Modules\HelpdeskAiPrompts\Services\Quality;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\HelpdeskAiPrompts\Models\AiPromptCase;
use Modules\HelpdeskAiPrompts\Models\AiPromptRun;

/**
 * Reúne las preguntas con las que se prueba un caso: las últimas preguntas
 * reales de clientes (run -> mensaje del bot -> mensaje del cliente anterior)
 * y, si no alcanzan, las test_questions del caso.
 */
class RegressionQuestionCollector
{
    private const MAX_QUESTION_LENGTH = 500;

    /** Mensajes anteriores a la respuesta del bot donde buscar la pregunta. */
    private const LOOKBACK_ITEMS = 8;

    /**
     * @return Collection<int, array{question: string, source: string, original: array{answer: string, used_tools: array<int,string>, action: string, length: int, run_id: int}|null}>
     */
    public function collect(AiPromptCase $case, int $limit): Collection
    {
        $real = $this->realQuestions($case->key, $limit);

        if ($real->count() >= $limit) {
            return $real;
        }

        $seen = $real->pluck('question')->map(fn (string $q) => Str::lower($q))->all();

        $fromTests = collect((array) $case->test_questions)
            ->map(fn ($test) => trim((string) ($test['question'] ?? '')))
            ->filter(fn (string $q) => $q !== '' && ! in_array(Str::lower($q), $seen, true))
            ->unique()
            ->take($limit - $real->count())
            ->map(fn (string $q) => ['question' => $q, 'source' => 'test_question', 'original' => null]);

        return $real->concat($fromTests)->values();
    }

    /**
     * @return Collection<int, array{question: string, source: string, original: array<string, mixed>}>
     */
    private function realQuestions(string $caseKey, int $limit): Collection
    {
        $runs = AiPromptRun::query()
            ->where('case_key', $caseKey)
            ->whereNotNull('item_id')
            ->whereNotNull('conversation_id')
            ->where('trace_id', 'not like', 'test-%')
            ->latest('id')
            ->limit($limit * 3)
            ->get();

        $answers = ConversationItem::query()
            ->whereIn('id', $runs->pluck('item_id'))
            ->get()
            ->keyBy('id');

        $questions = collect();

        foreach ($runs as $run) {
            $answer = $answers->get($run->item_id);
            $question = $answer === null ? null : $this->customerQuestionBefore($answer);

            if ($question === null) {
                continue;
            }

            $text = $this->plain((string) $answer->body);

            $questions->push([
                'question' => $question,
                'source' => 'real',
                'original' => [
                    'answer' => $text,
                    'used_tools' => array_values((array) $run->used_tools),
                    'action' => (string) $run->action,
                    'length' => mb_strlen($text),
                    'run_id' => $run->id,
                ],
            ]);

            if ($questions->count() >= $limit) {
                break;
            }
        }

        return $questions;
    }

    private function customerQuestionBefore(ConversationItem $answer): ?string
    {
        $previous = ConversationItem::query()
            ->where('conversation_id', $answer->conversation_id)
            ->where('type', 'message')
            ->where('is_internal', false)
            ->where('id', '<', $answer->id)
            ->latest('id')
            ->limit(self::LOOKBACK_ITEMS)
            ->get();

        $customerItem = $previous->first(fn (ConversationItem $item) => $item->user_id === null
            && ! ($item->metadata['ai_agent'] ?? false)
            && ! ($item->metadata['sent_by_chatflow'] ?? false));

        $text = $customerItem === null ? '' : $this->plain((string) $customerItem->body);

        return $text === '' ? null : Str::limit($text, self::MAX_QUESTION_LENGTH, '');
    }

    private function plain(string $html): string
    {
        return trim(preg_replace('/\s+/u', ' ', strip_tags($html)) ?? '');
    }
}
