<?php

namespace Modules\Seo\Services;

use Illuminate\Support\Collection;
use Modules\Seo\Models\SeoRedirect;

class RedirectChainDetector
{
    private const MAX_DEPTH = 10;

    /**
     * Detect a redirect chain starting from the given source path.
     * Returns the full chain array if a chain longer than 2 hops is found, or empty array otherwise.
     *
     * @param  Collection<string, SeoRedirect>|null  $allRedirects
     * @return array<string>
     */
    public function detect(string $from, ?Collection $allRedirects = null): array
    {
        $allRedirects ??= SeoRedirect::active()->get()->keyBy('source_path');

        $chain = [$from];
        $current = $from;
        $depth = self::MAX_DEPTH;

        while ($depth-- > 0) {
            $redirect = $allRedirects->get($current);

            if (! $redirect) {
                break;
            }

            $current = $redirect->target_path;

            // Prevent infinite loops
            if (in_array($current, $chain, true)) {
                $chain[] = $current;
                break;
            }

            $chain[] = $current;

            if (count($chain) > 2) {
                return $chain;
            }
        }

        return [];
    }

    /**
     * Detect all active redirects that participate in a chain.
     *
     * @return Collection<int, array{source: string, chain: array<string>}>
     */
    public function detectAll(): Collection
    {
        $allRedirects = SeoRedirect::active()->get()->keyBy('source_path');

        return $allRedirects
            ->map(function (SeoRedirect $redirect) use ($allRedirects): ?array {
                $chain = $this->detect($redirect->source_path, $allRedirects);

                if (empty($chain)) {
                    return null;
                }

                return [
                    'source' => $redirect->source_path,
                    'chain' => $chain,
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * Flatten all detected chains by pointing the first redirect in each chain
     * to the final destination. E.g. A→B→C becomes A→C (and we deactivate B).
     *
     * Returns the number of redirects updated.
     */
    public function resolveAll(): int
    {
        $chains = $this->detectAll();
        $updated = 0;

        foreach ($chains as $entry) {
            $source = $entry['source'];
            $chain = $entry['chain'];
            $final = end($chain);

            // Skip if chain loops back to itself
            if ($final === $source) {
                continue;
            }

            $redirect = SeoRedirect::where('source_path', $source)->first();
            if (! $redirect) {
                continue;
            }

            if ($redirect->target_path !== $final) {
                $redirect->update(['target_path' => $final]);
                $updated++;
            }
        }

        return $updated;
    }
}
