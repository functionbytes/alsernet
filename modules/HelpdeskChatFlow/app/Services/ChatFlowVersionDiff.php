<?php

namespace Modules\HelpdeskChatFlow\Services;

/**
 * Compares two node trees (a version snapshot against the current draft, or
 * against another version) by node `id`, reporting added/removed nodes and,
 * for nodes present in both, which fields changed.
 */
class ChatFlowVersionDiff
{
    private const TRACKED_FIELDS = ['type', 'label', 'parentId'];

    private const TRUNCATE_LENGTH = 120;

    /**
     * @param  array<int, array<string, mixed>>  $fromNodes  the older/base snapshot
     * @param  array<int, array<string, mixed>>  $toNodes  the newer snapshot to compare it against
     * @return array{added: array<int, array<string, mixed>>, removed: array<int, array<string, mixed>>, changed: array<int, array{id: string, label: string, fields: array<string, array{old: string, new: string}>}>}
     */
    public function compare(array $fromNodes, array $toNodes): array
    {
        $from = collect($fromNodes)->keyBy('id');
        $to = collect($toNodes)->keyBy('id');

        $added = $to->except($from->keys())->values()->all();
        $removed = $from->except($to->keys())->values()->all();

        $changed = $from->intersectByKeys($to->all())
            ->map(fn (array $fromNode, string $id) => $this->diffNode($id, $fromNode, $to->get($id)))
            ->filter(fn (?array $node) => $node !== null)
            ->values()
            ->all();

        return ['added' => $added, 'removed' => $removed, 'changed' => $changed];
    }

    /**
     * @return array{id: string, label: string, fields: array<string, array{old: string, new: string}>}|null
     */
    private function diffNode(string $id, array $fromNode, array $toNode): ?array
    {
        $fields = $this->diffFields($fromNode, $toNode);

        if (empty($fields)) {
            return null;
        }

        return [
            'id' => $id,
            'label' => $toNode['label'] ?? $fromNode['label'] ?? $id,
            'fields' => $fields,
        ];
    }

    /**
     * @return array<string, array{old: string, new: string}>
     */
    private function diffFields(array $fromNode, array $toNode): array
    {
        $fields = [];

        foreach (self::TRACKED_FIELDS as $key) {
            $old = $fromNode[$key] ?? null;
            $new = $toNode[$key] ?? null;

            if ($old !== $new) {
                $fields[$key] = ['old' => $this->truncate($old), 'new' => $this->truncate($new)];
            }
        }

        $dataKeys = array_unique([
            ...array_keys($fromNode['data'] ?? []),
            ...array_keys($toNode['data'] ?? []),
        ]);

        foreach ($dataKeys as $key) {
            $old = ($fromNode['data'] ?? [])[$key] ?? null;
            $new = ($toNode['data'] ?? [])[$key] ?? null;

            if ($old !== $new) {
                $fields["data.{$key}"] = ['old' => $this->truncate($old), 'new' => $this->truncate($new)];
            }
        }

        return $fields;
    }

    private function truncate(mixed $value): string
    {
        $text = is_scalar($value) || $value === null
            ? (string) $value
            : json_encode($value, JSON_UNESCAPED_UNICODE);

        return mb_strlen($text) > self::TRUNCATE_LENGTH
            ? mb_substr($text, 0, self::TRUNCATE_LENGTH - 3).'...'
            : $text;
    }
}
