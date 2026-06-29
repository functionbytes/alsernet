<?php

namespace Modules\Social\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class RssFeedParser
{
    /**
     * Parse RSS/Atom feed and return items
     */
    public function parse(string $feedUrl): array
    {
        $response = Http::timeout(30)->get($feedUrl);

        if (! $response->successful()) {
            throw new \Exception("Failed to fetch RSS feed: HTTP {$response->status()}");
        }

        $xml = $response->body();

        // Disable XML errors
        libxml_use_internal_errors(true);

        $feed = simplexml_load_string($xml);

        if ($feed === false) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            throw new \Exception('Failed to parse RSS feed: '.json_encode($errors));
        }

        // Detect feed type (RSS 2.0, RSS 1.0, or Atom)
        if (isset($feed->channel)) {
            return $this->parseRss($feed);
        } elseif (isset($feed->entry)) {
            return $this->parseAtom($feed);
        }

        throw new \Exception('Unknown feed format');
    }

    /**
     * Parse RSS 2.0 feed
     */
    protected function parseRss(\SimpleXMLElement $feed): array
    {
        $items = [];

        foreach ($feed->channel->item as $item) {
            $items[] = [
                'title' => (string) $item->title,
                'description' => (string) ($item->description ?? $item->summary ?? ''),
                'link' => (string) $item->link,
                'published_at' => $this->parseDate((string) ($item->pubDate ?? $item->published ?? '')),
            ];
        }

        return $items;
    }

    /**
     * Parse Atom feed
     */
    protected function parseAtom(\SimpleXMLElement $feed): array
    {
        $items = [];

        foreach ($feed->entry as $entry) {
            $link = '';
            if (isset($entry->link)) {
                if (isset($entry->link['href'])) {
                    $link = (string) $entry->link['href'];
                } else {
                    $link = (string) $entry->link;
                }
            }

            $items[] = [
                'title' => (string) $entry->title,
                'description' => (string) ($entry->summary ?? $entry->content ?? ''),
                'link' => $link,
                'published_at' => $this->parseDate((string) ($entry->published ?? $entry->updated ?? '')),
            ];
        }

        return $items;
    }

    /**
     * Parse various date formats
     */
    protected function parseDate(string $dateString): ?string
    {
        if (empty($dateString)) {
            return null;
        }

        try {
            return Carbon::parse($dateString)->toDateTimeString();
        } catch (\Exception $e) {
            return null;
        }
    }
}
