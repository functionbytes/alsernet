<?php

namespace Modules\Chat\Traits;

use Illuminate\Support\Facades\Log;

trait BatchProcessingTrait
{
    /**
     * Calculate optimal batch size based on message count and complexity.
     *
     * Larger batches for simple text messages, smaller for messages with attachments.
     *
     * @param  array  $messages  Array of messages to batch
     * @return int Optimal batch size
     */
    protected function calculateBatchSize(array $messages): int
    {
        $messageCount = count($messages);

        // Single message - no batching needed
        if ($messageCount === 1) {
            return 1;
        }

        // Check if messages have attachments
        $hasAttachments = collect($messages)->contains(function ($message) {
            return ! empty($message['message']['attachments'] ?? []);
        });

        // Messages with attachments: smaller batches to prevent memory issues
        if ($hasAttachments) {
            return min($messageCount, 20);
        }

        // Text-only messages: larger batches for maximum throughput
        return min($messageCount, 50);
    }

    /**
     * Chunk messages into optimal batch sizes.
     *
     * Prevents memory exhaustion and database deadlocks from massive batches.
     *
     * @param  array  $messages  Array of messages to chunk
     * @return array Array of message chunks
     */
    protected function chunkMessages(array $messages): array
    {
        $batchSize = $this->calculateBatchSize($messages);

        return array_chunk($messages, $batchSize);
    }

    /**
     * Calculate average message size in bytes.
     *
     * @param  array  $messages  Array of messages
     * @return int Average size in bytes
     */
    protected function getAverageMessageSize(array $messages): int
    {
        if (empty($messages)) {
            return 0;
        }

        $totalSize = array_reduce($messages, function ($carry, $message) {
            $textSize = strlen($message['message']['text'] ?? '');
            $attachmentCount = count($message['message']['attachments'] ?? []);

            return $carry + $textSize + ($attachmentCount * 1024); // Estimate 1KB per attachment metadata
        }, 0);

        return (int) ($totalSize / count($messages));
    }

    /**
     * Estimate memory usage for batch processing.
     *
     * @param  array  $messages  Array of messages
     * @return array Memory estimation details
     */
    protected function estimateMemoryUsage(array $messages): array
    {
        $messageCount = count($messages);
        $avgSize = $this->getAverageMessageSize($messages);
        $estimatedMemory = $messageCount * $avgSize * 3; // 3x multiplier for overhead

        return [
            'message_count' => $messageCount,
            'avg_message_size_bytes' => $avgSize,
            'estimated_memory_bytes' => $estimatedMemory,
            'estimated_memory_mb' => round($estimatedMemory / 1024 / 1024, 2),
        ];
    }

    /**
     * Log batch processing metrics.
     *
     * @param  string  $platform  Platform name (Facebook/Instagram)
     * @param  int  $totalMessages  Total messages processed
     * @param  int  $senderCount  Number of unique senders
     * @param  float  $startTime  Start timestamp
     */
    protected function logBatchMetrics(string $platform, int $totalMessages, int $senderCount, float $startTime): void
    {
        $endTime = microtime(true);
        $processingTime = ($endTime - $startTime) * 1000;
        $throughput = $totalMessages > 0 ? ($totalMessages / ($processingTime / 1000)) : 0;

        Log::info("[BATCH METRICS] {$platform} batch completed", [
            'total_messages' => $totalMessages,
            'sender_count' => $senderCount,
            'processing_time_ms' => round($processingTime, 2),
            'avg_time_per_message_ms' => round($processingTime / max($totalMessages, 1), 2),
            'throughput_msgs_per_sec' => round($throughput, 2),
            'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
        ]);
    }
}
