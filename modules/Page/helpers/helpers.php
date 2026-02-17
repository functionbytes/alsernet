<?php

/**
 * Check if the given page is the homepage.
 *
 * @param  mixed  $pageId
 * @return bool
 */
if (! function_exists('is_homepage')) {
    function is_homepage($pageId = null): bool
    {
        if (! $pageId) {
            return false;
        }

        $homepageId = setting('homepage_id');

        return $pageId == $homepageId;
    }
}

/**
 * Get the homepage URL.
 *
 * @return string
 */
if (! function_exists('homepage_url')) {
    function homepage_url(): string
    {
        return url('/');
    }
}
