<?php

namespace Modules\HelpdeskHelpcenter\Concerns;

/**
 * Shared helper to turn a plain search string into a FULLTEXT boolean-mode term.
 *
 * Used by widget, public and manager searches so they all benefit from the
 * `helpdesk_helpcenter_articles_fulltext` index on (title, body) instead of
 * non-sargable `LIKE '%term%'` full table scans.
 */
trait BuildsFulltextSearch
{
    /**
     * Builds a FULLTEXT boolean mode term from a plain search string.
     * Returns null when the term is too short for FULLTEXT (< 3 chars or all tokens < 3 chars).
     */
    protected function buildBooleanTerm(string $term): ?string
    {
        $tokens = array_filter(
            explode(' ', $term),
            fn (string $t) => strlen($t) >= 3
        );

        if (empty($tokens)) {
            return null;
        }

        return implode(' ', array_map(fn (string $t) => '+'.$t.'*', $tokens));
    }
}
