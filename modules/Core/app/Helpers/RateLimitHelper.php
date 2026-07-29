<?php

/**
 * Rate Limiting and Credit Tracking Helpers
 *
 * Provides functions for executing tasks with rate limiting and credit tracking.
 */
if (! function_exists('execute_with_limits')) {
    /**
     * Execute a task with rate limiting and credit tracking
     *
     * Checks rate limits and available credits before executing a task.
     * Rolls back credits if the task fails or rate limits are exceeded.
     *
     * @param  array  $rateTrackers  Array of rate tracker instances
     * @param  array  $creditTrackers  Array of credit tracker instances
     * @param  \Closure|null  $task  The task to execute
     * @return void
     *
     * @throws \app\Library\Exception\OutOfCredits
     * @throws \app\Library\Exception\RateLimitExceeded
     */
    function execute_with_limits(array $rateTrackers, array $creditTrackers, ?\Closure $task = null)
    {
        $rateTrackers = array_values(array_filter($rateTrackers));
        $creditTrackers = array_values(array_filter($creditTrackers));

        $creditCounted = [];
        try {
            foreach ($creditTrackers as $creditTracker) {
                $creditTracker->count();
                $creditCounted[] = $creditTracker;
            }
        } catch (\app\Library\Exception\OutOfCredits $exception) {
            foreach ($creditCounted as $creditTracker) {
                $creditTracker->rollback();
            }

            throw $exception;
        }

        try {
            foreach ($rateTrackers as $rateTracker) {
                $rateTracker->count();
            }
        } catch (\app\Library\Exception\RateLimitExceeded $exception) {
            foreach ($creditCounted as $creditTracker) {
                $creditTracker->rollback();
            }

            throw $exception;
        }

        try {
            if (is_null($task)) {
                return;
            }

            $task();
        } catch (\Throwable $exception) {
            foreach ($creditCounted as $creditTracker) {
                $creditTracker->rollback();
            }

            throw $exception;
        }
    }
}
