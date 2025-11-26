<?php
/* @noinspection ALL */
// @formatter:off
// phpcs:ignoreFile

/**
 * A helper file for Laravel, to provide autocomplete information to your IDE
 * Generated for Laravel 12.40.1.
 *
 * This file should not be included in your code, only analyzed by your IDE!
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 * @see https://github.com/barryvdh/laravel-ide-helper
 */
namespace Artesaos\SEOTools\Facades {
    /**
     * SEOMeta is a facade for the `MetaTags` implementation access.
     *
     * @see \Artesaos\SEOTools\Contracts\MetaTags
     */
    class SEOMeta {
        /**
         * Generates meta tags HTML./
         *
         * @param bool $minify
         * @return string
         * @static
         */
        public static function generate($minify = false)
        {
            /** @var \Artesaos\SEOTools\SEOMeta $instance */
            return $instance->generate($minify);
        }

        /**
         * Set the title.
         *
         * @param string $title
         * @param bool $appendDefault
         * @return static
         * @static
         */
        public static function setTitle($title, $appendDefault = true)
        {
            /** @var \Artesaos\SEOTools\SEOMeta $instance */
            return $instance->setTitle($title, $appendDefault);
        }

        /**
         * Sets the default title tag.
         *
         * @param string $default
         * @return static
         * @static
         */
        public static function setTitleDefault($default)
        {
            /** @var \Artesaos\SEOTools\SEOMeta $instance */
            return $instance->setTitleDefault($default);
        }

        /**
         * Set the title separator.
         *
         * @param string $separator
         * @return static
         * @static
         */
        public static function setTitleSeparator($separator)
        {
            /** @var \Artesaos\SEOTools\SEOMeta $instance */
            return $instance->setTitleSeparator($separator);
        }

        /**
         * Set the description.
         *
         * @param string $description
         * @return static
         * @static
         */
        public static function setDescription($description)
        {
            /** @var \Artesaos\SEOTools\SEOMeta $instance */
            return $instance->setDescription($description);
        }

        /**
         * Sets the list of keywords, you can send an array or string separated with commas
         * also clears the previously set keywords.
         *
         * @param string|array $keywords
         * @return static
         * @static
         */
        public static function setKeywords($keywords)
        {
            /** @var \Artesaos\SEOTools\SEOMeta $instance */
            return $instance->setKeywords($keywords);
        }

        /**
         * Add a keyword.
         *
         * @param string|array $keyword
         * @return static
         * @static
         */
        public static function addKeyword($keyword)
        {
            /** @var \Artesaos\SEOTools\SEOMeta $instance */
            return $instance->addKeyword($keyword);
        }

        /**
         * Remove a metatag.
         *
         * @param string $key
         * @return static
         * @static
         */
        public static function removeMeta($key)
        {
            /** @var \Artesaos\SEOTools\SEOMeta $instance */
            return $instance->removeMeta($key);
        }

        /**
         * Add a custom meta tag.
         *
         * @param string|array $meta
         * @param string|null $value
         * @param string $name
         * @return static
         * @static
         */
        public static function addMeta($meta, $value = null, $name = 'name')
        {
            /** @var \Artesaos\SEOTools\SEOMeta $instance */
            return $instance->addMeta($meta, $value, $name);
        }

        /**
         * Sets the canonical URL.
         *
         * @param string $url
         * @return static
         * @static
         */
        public static function setCanonical($url)
        {
            /** @var \Artesaos\SEOTools\SEOMeta $instance */
            return $instance->setCanonical($url);
        }

        /**
         * Sets the AMP html URL.
         *
         * @param string $url
         * @return \Artesaos\SEOTools\Contracts\MetaTags
         * @static
         */
        public static function setAmpHtml($url)
        {
            /** @var \Artesaos\SEOTools\SEOMeta $instance */
            return $instance->setAmpHtml($url);
        }

        /**
         * Sets the prev URL.
         *
         * @param string $url
         * @return static
         * @static
         */
        public static function setPrev($url)
        {
            /** @var \Artesaos\SEOTools\SEOMeta $instance */
            return $instance->setPrev($url);
        }

        /**
         * Sets the next URL.
         *
         * @param string $url
         * @return static
         * @static
         */
        public static function setNext($url)
        {
            /** @var \Artesaos\SEOTools\SEOMeta $instance */
            return $instance->setNext($url);
        }

        /**
         * Add an alternate language.
         *
         * @param string $lang language code in format ISO 639-1
         * @param string $url
         * @return static
         * @static
         */
        public static function addAlternateLanguage($lang, $url)
        {
            /** @var \Artesaos\SEOTools\SEOMeta $instance */
            return $instance->addAlternateLanguage($lang, $url);
        }

        /**
         * Add alternate languages.
         *
         * @param array $languages
         * @return static
         * @static
         */
        public static function addAlternateLanguages($languages)
        {
            /** @var \Artesaos\SEOTools\SEOMeta $instance */
            return $instance->addAlternateLanguages($languages);
        }

        /**
         * Set an alternate language.
         *
         * @param string $lang language code in format ISO 639-1
         * @param string $url
         * @return static
         * @static
         */
        public static function setAlternateLanguage($lang, $url)
        {
            /** @var \Artesaos\SEOTools\SEOMeta $instance */
            return $instance->setAlternateLanguage($lang, $url);
        }

        /**
         * Set alternate languages.
         *
         * @param array $languages
         * @return static
         * @static
         */
        public static function setAlternateLanguages($languages)
        {
            /** @var \Artesaos\SEOTools\SEOMeta $instance */
            return $instance->setAlternateLanguages($languages);
        }

        /**
         * Sets the meta robots.
         *
         * @param string $robots
         * @return \Artesaos\SEOTools\Contracts\MetaTags
         * @static
         */
        public static function setRobots($robots)
        {
            /** @var \Artesaos\SEOTools\SEOMeta $instance */
            return $instance->setRobots($robots);
        }

        /**
         * Get the title formatted for display.
         *
         * @return string
         * @static
         */
        public static function getTitle()
        {
            /** @var \Artesaos\SEOTools\SEOMeta $instance */
            return $instance->getTitle();
        }

        /**
         * Takes the default title.
         *
         * @return string
         * @static
         */
        public static function getDefaultTitle()
        {
            /** @var \Artesaos\SEOTools\SEOMeta $instance */
            return $instance->getDefaultTitle();
        }

        /**
         * Get the title that was set.
         *
         * @return string
         * @static
         */
        public static function getTitleSession()
        {
            /** @var \Artesaos\SEOTools\SEOMeta $instance */
            return $instance->getTitleSession();
        }

        /**
         * Get the title separator that was set.
         *
         * @return string
         * @static
         */
        public static function getTitleSeparator()
        {
            /** @var \Artesaos\SEOTools\SEOMeta $instance */
            return $instance->getTitleSeparator();
        }

        /**
         * Get the Meta keywords.
         *
         * @return array
         * @static
         */
        public static function getKeywords()
        {
            /** @var \Artesaos\SEOTools\SEOMeta $instance */
            return $instance->getKeywords();
        }

        /**
         * Get all metatags.
         *
         * @return array
         * @static
         */
        public static function getMetatags()
        {
            /** @var \Artesaos\SEOTools\SEOMeta $instance */
            return $instance->getMetatags();
        }

        /**
         * Get the Meta description.
         *
         * @return string|null
         * @static
         */
        public static function getDescription()
        {
            /** @var \Artesaos\SEOTools\SEOMeta $instance */
            return $instance->getDescription();
        }

        /**
         * Get the canonical URL.
         *
         * @return string
         * @static
         */
        public static function getCanonical()
        {
            /** @var \Artesaos\SEOTools\SEOMeta $instance */
            return $instance->getCanonical();
        }

        /**
         * Get the AMP html URL.
         *
         * @return string
         * @static
         */
        public static function getAmpHtml()
        {
            /** @var \Artesaos\SEOTools\SEOMeta $instance */
            return $instance->getAmpHtml();
        }

        /**
         * Get the prev URL.
         *
         * @return string
         * @static
         */
        public static function getPrev()
        {
            /** @var \Artesaos\SEOTools\SEOMeta $instance */
            return $instance->getPrev();
        }

        /**
         * Get the next URL.
         *
         * @return string
         * @static
         */
        public static function getNext()
        {
            /** @var \Artesaos\SEOTools\SEOMeta $instance */
            return $instance->getNext();
        }

        /**
         * Get alternate languages.
         *
         * @return array
         * @static
         */
        public static function getAlternateLanguages()
        {
            /** @var \Artesaos\SEOTools\SEOMeta $instance */
            return $instance->getAlternateLanguages();
        }

        /**
         * Get meta robots.
         *
         * @return string
         * @static
         */
        public static function getRobots()
        {
            /** @var \Artesaos\SEOTools\SEOMeta $instance */
            return $instance->getRobots();
        }

        /**
         * Reset all data.
         *
         * @return void
         * @static
         */
        public static function reset()
        {
            /** @var \Artesaos\SEOTools\SEOMeta $instance */
            $instance->reset();
        }

            }
    /**
     * OpenGraph is a facade for the `OpenGraph` implementation access.
     *
     * @see \Artesaos\SEOTools\Contracts\OpenGraph
     */
    class OpenGraph {
        /**
         * Generates open graph tags.
         *
         * @param bool $minify
         * @return string
         * @static
         */
        public static function generate($minify = false)
        {
            /** @var \Artesaos\SEOTools\OpenGraph $instance */
            return $instance->generate($minify);
        }

        /**
         * Add or update property.
         *
         * @param string $key
         * @param string|array $value
         * @return static
         * @static
         */
        public static function addProperty($key, $value)
        {
            /** @var \Artesaos\SEOTools\OpenGraph $instance */
            return $instance->addProperty($key, $value);
        }

        /**
         * Set Article properties.
         *
         * @param array $attributes
         * @return static
         * @static
         */
        public static function setArticle($attributes = [])
        {
            /** @var \Artesaos\SEOTools\OpenGraph $instance */
            return $instance->setArticle($attributes);
        }

        /**
         * Set Profile properties.
         *
         * @param array $attributes
         * @return static
         * @static
         */
        public static function setProfile($attributes = [])
        {
            /** @var \Artesaos\SEOTools\OpenGraph $instance */
            return $instance->setProfile($attributes);
        }

        /**
         * Set Book properties.
         *
         * @param array $attributes
         * @return static
         * @static
         */
        public static function setBook($attributes = [])
        {
            /** @var \Artesaos\SEOTools\OpenGraph $instance */
            return $instance->setBook($attributes);
        }

        /**
         * Set Music Song properties.
         *
         * @param array $attributes
         * @return static
         * @static
         */
        public static function setMusicSong($attributes = [])
        {
            /** @var \Artesaos\SEOTools\OpenGraph $instance */
            return $instance->setMusicSong($attributes);
        }

        /**
         * Set Music Album properties.
         *
         * @param array $attributes
         * @return static
         * @static
         */
        public static function setMusicAlbum($attributes = [])
        {
            /** @var \Artesaos\SEOTools\OpenGraph $instance */
            return $instance->setMusicAlbum($attributes);
        }

        /**
         * Set Music Playlist properties.
         *
         * @param array $attributes
         * @return static
         * @static
         */
        public static function setMusicPlaylist($attributes = [])
        {
            /** @var \Artesaos\SEOTools\OpenGraph $instance */
            return $instance->setMusicPlaylist($attributes);
        }

        /**
         * Set Music  RadioStation properties.
         *
         * @param array $attributes
         * @return static
         * @static
         */
        public static function setMusicRadioStation($attributes = [])
        {
            /** @var \Artesaos\SEOTools\OpenGraph $instance */
            return $instance->setMusicRadioStation($attributes);
        }

        /**
         * Set Video Movie properties.
         *
         * @param array $attributes
         * @return static
         * @static
         */
        public static function setVideoMovie($attributes = [])
        {
            /** @var \Artesaos\SEOTools\OpenGraph $instance */
            return $instance->setVideoMovie($attributes);
        }

        /**
         * Set Video Episode properties.
         *
         * @param array $attributes
         * @return static
         * @static
         */
        public static function setVideoEpisode($attributes = [])
        {
            /** @var \Artesaos\SEOTools\OpenGraph $instance */
            return $instance->setVideoEpisode($attributes);
        }

        /**
         * Set Video Episode properties.
         *
         * @param array $attributes
         * @return static
         * @static
         */
        public static function setVideoOther($attributes = [])
        {
            /** @var \Artesaos\SEOTools\OpenGraph $instance */
            return $instance->setVideoOther($attributes);
        }

        /**
         * Set Video Episode properties.
         *
         * @param array $attributes
         * @return static
         * @static
         */
        public static function setVideoTVShow($attributes = [])
        {
            /** @var \Artesaos\SEOTools\OpenGraph $instance */
            return $instance->setVideoTVShow($attributes);
        }

        /**
         * Add Video properties.
         *
         * @param string|null $source
         * @param array $attributes
         * @return static
         * @static
         */
        public static function addVideo($source = null, $attributes = [])
        {
            /** @var \Artesaos\SEOTools\OpenGraph $instance */
            return $instance->addVideo($source, $attributes);
        }

        /**
         * Add audio properties.
         *
         * @param string|null $source
         * @param array $attributes
         * @return static
         * @static
         */
        public static function addAudio($source = null, $attributes = [])
        {
            /** @var \Artesaos\SEOTools\OpenGraph $instance */
            return $instance->addAudio($source, $attributes);
        }

        /**
         * Set place properties.
         *
         * @param array $attributes opengraph place attributes
         * @return \Artesaos\SEOTools\Contracts\OpenGraph
         * @static
         */
        public static function setPlace($attributes = [])
        {
            /** @var \Artesaos\SEOTools\OpenGraph $instance */
            return $instance->setPlace($attributes);
        }

        /**
         * Set product properties.
         * 
         * Reference: https://developers.facebook.com/docs/marketing-api/catalog/reference/#example-feeds
         *
         * @param array $attributes opengraph product attributes
         * @return \Artesaos\SEOTools\Contracts\OpenGraph
         * @static
         */
        public static function setProduct($attributes = [])
        {
            /** @var \Artesaos\SEOTools\OpenGraph $instance */
            return $instance->setProduct($attributes);
        }

        /**
         * Remove property.
         *
         * @param string $key
         * @return static
         * @static
         */
        public static function removeProperty($key)
        {
            /** @var \Artesaos\SEOTools\OpenGraph $instance */
            return $instance->removeProperty($key);
        }

        /**
         * Add image to properties.
         *
         * @param string $url
         * @param array $attributes
         * @return static
         * @static
         */
        public static function addImage($source = null, $attributes = [])
        {
            /** @var \Artesaos\SEOTools\OpenGraph $instance */
            return $instance->addImage($source, $attributes);
        }

        /**
         * Add images to properties.
         *
         * @param array $urls
         * @return static
         * @static
         */
        public static function addImages($urls)
        {
            /** @var \Artesaos\SEOTools\OpenGraph $instance */
            return $instance->addImages($urls);
        }

        /**
         * Define type property.
         *
         * @param string|null $type set the opengraph type
         * @return static
         * @static
         */
        public static function setType($type = null)
        {
            /** @var \Artesaos\SEOTools\OpenGraph $instance */
            return $instance->setType($type);
        }

        /**
         * Define title property.
         *
         * @param string $title
         * @return static
         * @static
         */
        public static function setTitle($title = null)
        {
            /** @var \Artesaos\SEOTools\OpenGraph $instance */
            return $instance->setTitle($title);
        }

        /**
         * Define description property.
         *
         * @param string $description
         * @return static
         * @static
         */
        public static function setDescription($description = null)
        {
            /** @var \Artesaos\SEOTools\OpenGraph $instance */
            return $instance->setDescription($description);
        }

        /**
         * Define url property.
         *
         * @param string $url
         * @return static
         * @static
         */
        public static function setUrl($url)
        {
            /** @var \Artesaos\SEOTools\OpenGraph $instance */
            return $instance->setUrl($url);
        }

        /**
         * Define site_name property.
         *
         * @param string $name
         * @return static
         * @static
         */
        public static function setSiteName($name)
        {
            /** @var \Artesaos\SEOTools\OpenGraph $instance */
            return $instance->setSiteName($name);
        }

            }
    /**
     * TwitterCard is a facade for the `TwitterCards` implementation access.
     *
     * @see \Artesaos\SEOTools\Contracts\TwitterCards
     */
    class TwitterCard {
        /**
         * @param bool $minify
         * @return string
         * @static
         */
        public static function generate($minify = false)
        {
            /** @var \Artesaos\SEOTools\TwitterCards $instance */
            return $instance->generate($minify);
        }

        /**
         * @param string $key
         * @param string|array $value
         * @return static
         * @static
         */
        public static function addValue($key, $value)
        {
            /** @var \Artesaos\SEOTools\TwitterCards $instance */
            return $instance->addValue($key, $value);
        }

        /**
         * @param string $title
         * @return static
         * @static
         */
        public static function setTitle($title)
        {
            /** @var \Artesaos\SEOTools\TwitterCards $instance */
            return $instance->setTitle($title);
        }

        /**
         * @param string $type
         * @return static
         * @static
         */
        public static function setType($type)
        {
            /** @var \Artesaos\SEOTools\TwitterCards $instance */
            return $instance->setType($type);
        }

        /**
         * @param string $site
         * @return static
         * @static
         */
        public static function setSite($site)
        {
            /** @var \Artesaos\SEOTools\TwitterCards $instance */
            return $instance->setSite($site);
        }

        /**
         * @param string $description
         * @return static
         * @static
         */
        public static function setDescription($description)
        {
            /** @var \Artesaos\SEOTools\TwitterCards $instance */
            return $instance->setDescription($description);
        }

        /**
         * @param string $url
         * @return static
         * @static
         */
        public static function setUrl($url)
        {
            /** @var \Artesaos\SEOTools\TwitterCards $instance */
            return $instance->setUrl($url);
        }

        /**
         * @deprecated use setImage($image) instead
         * @param string|array $image
         * @return static
         * @static
         */
        public static function addImage($image)
        {
            /** @var \Artesaos\SEOTools\TwitterCards $instance */
            return $instance->addImage($image);
        }

        /**
         * @deprecated use setImage($image) instead
         * @param string|array $images
         * @return static
         * @static
         */
        public static function setImages($images)
        {
            /** @var \Artesaos\SEOTools\TwitterCards $instance */
            return $instance->setImages($images);
        }

        /**
         * @param $image
         * @return \Artesaos\SEOTools\Contracts\TwitterCards
         * @static
         */
        public static function setImage($image)
        {
            /** @var \Artesaos\SEOTools\TwitterCards $instance */
            return $instance->setImage($image);
        }

            }
    /**
     * JsonLd is a facade for the `JsonLd` implementation access.
     *
     * @see \Artesaos\SEOTools\Contracts\JsonLd
     */
    class JsonLd {
        /**
         * Check if all attribute are empty
         *
         * @return static
         * @static
         */
        public static function isEmpty()
        {
            /** @var \Artesaos\SEOTools\JsonLd $instance */
            return $instance->isEmpty();
        }

        /**
         * Generates linked data script tag.
         *
         * @param bool $minify
         * @return string
         * @static
         */
        public static function generate($minify = false)
        {
            /** @var \Artesaos\SEOTools\JsonLd $instance */
            return $instance->generate($minify);
        }

        /**
         * @return string[]|string[][]
         * @static
         */
        public static function convertToArray()
        {
            /** @var \Artesaos\SEOTools\JsonLd $instance */
            return $instance->convertToArray();
        }

        /**
         * @param string $key
         * @param string|array $value
         * @return static
         * @static
         */
        public static function addValue($key, $value)
        {
            /** @var \Artesaos\SEOTools\JsonLd $instance */
            return $instance->addValue($key, $value);
        }

        /**
         * @param array $values
         * @return static
         * @static
         */
        public static function addValues($values)
        {
            /** @var \Artesaos\SEOTools\JsonLd $instance */
            return $instance->addValues($values);
        }

        /**
         * @param string $type
         * @return static
         * @static
         */
        public static function setType($type)
        {
            /** @var \Artesaos\SEOTools\JsonLd $instance */
            return $instance->setType($type);
        }

        /**
         * @param string $title
         * @return static
         * @static
         */
        public static function setTitle($title)
        {
            /** @var \Artesaos\SEOTools\JsonLd $instance */
            return $instance->setTitle($title);
        }

        /**
         * @param string $site
         * @return static
         * @static
         */
        public static function setSite($site)
        {
            /** @var \Artesaos\SEOTools\JsonLd $instance */
            return $instance->setSite($site);
        }

        /**
         * @param string $description
         * @return static
         * @static
         */
        public static function setDescription($description)
        {
            /** @var \Artesaos\SEOTools\JsonLd $instance */
            return $instance->setDescription($description);
        }

        /**
         * @param string|null|bool $url
         * @return static
         * @static
         */
        public static function setUrl($url)
        {
            /** @var \Artesaos\SEOTools\JsonLd $instance */
            return $instance->setUrl($url);
        }

        /**
         * @param string|array $images
         * @return static
         * @static
         */
        public static function setImages($images)
        {
            /** @var \Artesaos\SEOTools\JsonLd $instance */
            return $instance->setImages($images);
        }

        /**
         * @param string|array $image
         * @return static
         * @static
         */
        public static function addImage($image)
        {
            /** @var \Artesaos\SEOTools\JsonLd $instance */
            return $instance->addImage($image);
        }

        /**
         * {@inheritdoc}
         *
         * @static
         */
        public static function setImage($image)
        {
            /** @var \Artesaos\SEOTools\JsonLd $instance */
            return $instance->setImage($image);
        }

            }
    /**
     * JsonLd is a facade for the `JsonLd` implementation access.
     *
     * @see \Artesaos\SEOTools\Contracts\JsonLdMulti
     */
    class JsonLdMulti {
        /**
         * Generates linked data script tag.
         *
         * @param bool $minify
         * @return string
         * @static
         */
        public static function generate($minify = false)
        {
            /** @var \Artesaos\SEOTools\JsonLdMulti $instance */
            return $instance->generate($minify);
        }

        /**
         * Create a new JsonLd group and increment the selector to target it
         *
         * @return static
         * @static
         */
        public static function newJsonLd()
        {
            /** @var \Artesaos\SEOTools\JsonLdMulti $instance */
            return $instance->newJsonLd();
        }

        /**
         * Check if the current JsonLd group is empty
         *
         * @return static
         * @static
         */
        public static function isEmpty()
        {
            /** @var \Artesaos\SEOTools\JsonLdMulti $instance */
            return $instance->isEmpty();
        }

        /**
         * Target a JsonLd group that will be edited in the next methods
         *
         * @param int $index
         * @return static
         * @static
         */
        public static function select($index)
        {
            /** @var \Artesaos\SEOTools\JsonLdMulti $instance */
            return $instance->select($index);
        }

        /**
         * @param string $key
         * @param string|array $value
         * @return static
         * @static
         */
        public static function addValue($key, $value)
        {
            /** @var \Artesaos\SEOTools\JsonLdMulti $instance */
            return $instance->addValue($key, $value);
        }

        /**
         * @param array $values
         * @return static
         * @static
         */
        public static function addValues($values)
        {
            /** @var \Artesaos\SEOTools\JsonLdMulti $instance */
            return $instance->addValues($values);
        }

        /**
         * @param string $type
         * @return static
         * @static
         */
        public static function setType($type)
        {
            /** @var \Artesaos\SEOTools\JsonLdMulti $instance */
            return $instance->setType($type);
        }

        /**
         * @param string $title
         * @return static
         * @static
         */
        public static function setTitle($title)
        {
            /** @var \Artesaos\SEOTools\JsonLdMulti $instance */
            return $instance->setTitle($title);
        }

        /**
         * @param string $site
         * @return static
         * @static
         */
        public static function setSite($site)
        {
            /** @var \Artesaos\SEOTools\JsonLdMulti $instance */
            return $instance->setSite($site);
        }

        /**
         * @param string $description
         * @return static
         * @static
         */
        public static function setDescription($description)
        {
            /** @var \Artesaos\SEOTools\JsonLdMulti $instance */
            return $instance->setDescription($description);
        }

        /**
         * @param string|null|bool $url
         * @return static
         * @static
         */
        public static function setUrl($url)
        {
            /** @var \Artesaos\SEOTools\JsonLdMulti $instance */
            return $instance->setUrl($url);
        }

        /**
         * @param string|array $images
         * @return static
         * @static
         */
        public static function setImages($images)
        {
            /** @var \Artesaos\SEOTools\JsonLdMulti $instance */
            return $instance->setImages($images);
        }

        /**
         * @param string|array $image
         * @return static
         * @static
         */
        public static function addImage($image)
        {
            /** @var \Artesaos\SEOTools\JsonLdMulti $instance */
            return $instance->addImage($image);
        }

        /**
         * {@inheritdoc}
         *
         * @static
         */
        public static function setImage($image)
        {
            /** @var \Artesaos\SEOTools\JsonLdMulti $instance */
            return $instance->setImage($image);
        }

            }
    /**
     * SEOTools is a facade for the `SEOTools` implementation access.
     *
     * @see \Artesaos\SEOTools\Contracts\SEOTools
     */
    class SEOTools {
        /**
         * @return \Artesaos\SEOTools\Contracts\MetaTags
         * @static
         */
        public static function metatags()
        {
            /** @var \Artesaos\SEOTools\SEOTools $instance */
            return $instance->metatags();
        }

        /**
         * @return \Artesaos\SEOTools\Contracts\OpenGraph
         * @static
         */
        public static function opengraph()
        {
            /** @var \Artesaos\SEOTools\SEOTools $instance */
            return $instance->opengraph();
        }

        /**
         * @return \Artesaos\SEOTools\Contracts\TwitterCards
         * @static
         */
        public static function twitter()
        {
            /** @var \Artesaos\SEOTools\SEOTools $instance */
            return $instance->twitter();
        }

        /**
         * @return \Artesaos\SEOTools\Contracts\JsonLd
         * @static
         */
        public static function jsonLd()
        {
            /** @var \Artesaos\SEOTools\SEOTools $instance */
            return $instance->jsonLd();
        }

        /**
         * @return \Artesaos\SEOTools\Contracts\JsonLdMulti
         * @static
         */
        public static function jsonLdMulti()
        {
            /** @var \Artesaos\SEOTools\SEOTools $instance */
            return $instance->jsonLdMulti();
        }

        /**
         * Setup title for all seo providers.
         *
         * @param string $title
         * @param bool $appendDefault
         * @return static
         * @static
         */
        public static function setTitle($title, $appendDefault = true)
        {
            /** @var \Artesaos\SEOTools\SEOTools $instance */
            return $instance->setTitle($title, $appendDefault);
        }

        /**
         * Setup description for all seo providers.
         *
         * @param string $description
         * @return static
         * @static
         */
        public static function setDescription($description)
        {
            /** @var \Artesaos\SEOTools\SEOTools $instance */
            return $instance->setDescription($description);
        }

        /**
         * Sets the canonical URL.
         *
         * @param string $url
         * @return static
         * @static
         */
        public static function setCanonical($url)
        {
            /** @var \Artesaos\SEOTools\SEOTools $instance */
            return $instance->setCanonical($url);
        }

        /**
         * Add one or more images urls.
         *
         * @param array|string $urls
         * @return static
         * @static
         */
        public static function addImages($urls)
        {
            /** @var \Artesaos\SEOTools\SEOTools $instance */
            return $instance->addImages($urls);
        }

        /**
         * Get current title from metatags.
         *
         * @param bool $session
         * @return string
         * @static
         */
        public static function getTitle($session = false)
        {
            /** @var \Artesaos\SEOTools\SEOTools $instance */
            return $instance->getTitle($session);
        }

        /**
         * Generate from all seo providers.
         *
         * @param bool $minify
         * @return string
         * @static
         */
        public static function generate($minify = false)
        {
            /** @var \Artesaos\SEOTools\SEOTools $instance */
            return $instance->generate($minify);
        }

        /**
         * Register a custom macro.
         *
         * @param string $name
         * @param object|callable $macro
         * @param-closure-this static  $macro
         * @return void
         * @static
         */
        public static function macro($name, $macro)
        {
            \Artesaos\SEOTools\SEOTools::macro($name, $macro);
        }

        /**
         * Mix another object into the class.
         *
         * @param object $mixin
         * @param bool $replace
         * @return void
         * @throws \ReflectionException
         * @static
         */
        public static function mixin($mixin, $replace = true)
        {
            \Artesaos\SEOTools\SEOTools::mixin($mixin, $replace);
        }

        /**
         * Checks if macro is registered.
         *
         * @param string $name
         * @return bool
         * @static
         */
        public static function hasMacro($name)
        {
            return \Artesaos\SEOTools\SEOTools::hasMacro($name);
        }

        /**
         * Flush the existing macros.
         *
         * @return void
         * @static
         */
        public static function flushMacros()
        {
            \Artesaos\SEOTools\SEOTools::flushMacros();
        }

            }
    }

namespace Illuminate\Support\Facades {
    /**
     * @see \Illuminate\Auth\AuthManager
     * @see \Illuminate\Auth\SessionGuard
     */
    class Auth {
        /**
         * Attempt to get the guard from the local cache.
         *
         * @param string|null $name
         * @return \Illuminate\Contracts\Auth\Guard|\Illuminate\Contracts\Auth\StatefulGuard
         * @static
         */
        public static function guard($name = null)
        {
            /** @var \Illuminate\Auth\AuthManager $instance */
            return $instance->guard($name);
        }

        /**
         * Create a session based authentication guard.
         *
         * @param string $name
         * @param array $config
         * @return \Illuminate\Auth\SessionGuard
         * @static
         */
        public static function createSessionDriver($name, $config)
        {
            /** @var \Illuminate\Auth\AuthManager $instance */
            return $instance->createSessionDriver($name, $config);
        }

        /**
         * Create a token based authentication guard.
         *
         * @param string $name
         * @param array $config
         * @return \Illuminate\Auth\TokenGuard
         * @static
         */
        public static function createTokenDriver($name, $config)
        {
            /** @var \Illuminate\Auth\AuthManager $instance */
            return $instance->createTokenDriver($name, $config);
        }

        /**
         * Get the default authentication driver name.
         *
         * @return string
         * @static
         */
        public static function getDefaultDriver()
        {
            /** @var \Illuminate\Auth\AuthManager $instance */
            return $instance->getDefaultDriver();
        }

        /**
         * Set the default guard driver the factory should serve.
         *
         * @param string $name
         * @return void
         * @static
         */
        public static function shouldUse($name)
        {
            /** @var \Illuminate\Auth\AuthManager $instance */
            $instance->shouldUse($name);
        }

        /**
         * Set the default authentication driver name.
         *
         * @param string $name
         * @return void
         * @static
         */
        public static function setDefaultDriver($name)
        {
            /** @var \Illuminate\Auth\AuthManager $instance */
            $instance->setDefaultDriver($name);
        }

        /**
         * Register a new callback based request guard.
         *
         * @param string $driver
         * @param callable $callback
         * @return \Illuminate\Auth\AuthManager
         * @static
         */
        public static function viaRequest($driver, $callback)
        {
            /** @var \Illuminate\Auth\AuthManager $instance */
            return $instance->viaRequest($driver, $callback);
        }

        /**
         * Get the user resolver callback.
         *
         * @return \Closure
         * @static
         */
        public static function userResolver()
        {
            /** @var \Illuminate\Auth\AuthManager $instance */
            return $instance->userResolver();
        }

        /**
         * Set the callback to be used to resolve users.
         *
         * @param \Closure $userResolver
         * @return \Illuminate\Auth\AuthManager
         * @static
         */
        public static function resolveUsersUsing($userResolver)
        {
            /** @var \Illuminate\Auth\AuthManager $instance */
            return $instance->resolveUsersUsing($userResolver);
        }

        /**
         * Register a custom driver creator Closure.
         *
         * @param string $driver
         * @param \Closure $callback
         * @return \Illuminate\Auth\AuthManager
         * @static
         */
        public static function extend($driver, $callback)
        {
            /** @var \Illuminate\Auth\AuthManager $instance */
            return $instance->extend($driver, $callback);
        }

        /**
         * Register a custom provider creator Closure.
         *
         * @param string $name
         * @param \Closure $callback
         * @return \Illuminate\Auth\AuthManager
         * @static
         */
        public static function provider($name, $callback)
        {
            /** @var \Illuminate\Auth\AuthManager $instance */
            return $instance->provider($name, $callback);
        }

        /**
         * Determines if any guards have already been resolved.
         *
         * @return bool
         * @static
         */
        public static function hasResolvedGuards()
        {
            /** @var \Illuminate\Auth\AuthManager $instance */
            return $instance->hasResolvedGuards();
        }

        /**
         * Forget all of the resolved guard instances.
         *
         * @return \Illuminate\Auth\AuthManager
         * @static
         */
        public static function forgetGuards()
        {
            /** @var \Illuminate\Auth\AuthManager $instance */
            return $instance->forgetGuards();
        }

        /**
         * Set the application instance used by the manager.
         *
         * @param \Illuminate\Contracts\Foundation\Application $app
         * @return \Illuminate\Auth\AuthManager
         * @static
         */
        public static function setApplication($app)
        {
            /** @var \Illuminate\Auth\AuthManager $instance */
            return $instance->setApplication($app);
        }

        /**
         * Create the user provider implementation for the driver.
         *
         * @param string|null $provider
         * @return \Illuminate\Contracts\Auth\UserProvider|null
         * @throws \InvalidArgumentException
         * @static
         */
        public static function createUserProvider($provider = null)
        {
            /** @var \Illuminate\Auth\AuthManager $instance */
            return $instance->createUserProvider($provider);
        }

        /**
         * Get the default user provider name.
         *
         * @return string
         * @static
         */
        public static function getDefaultUserProvider()
        {
            /** @var \Illuminate\Auth\AuthManager $instance */
            return $instance->getDefaultUserProvider();
        }

        /**
         * Get the currently authenticated user.
         *
         * @return \App\Models\User|null
         * @static
         */
        public static function user()
        {
            /** @var \Illuminate\Auth\SessionGuard $instance */
            return $instance->user();
        }

        /**
         * Get the ID for the currently authenticated user.
         *
         * @return int|string|null
         * @static
         */
        public static function id()
        {
            /** @var \Illuminate\Auth\SessionGuard $instance */
            return $instance->id();
        }

        /**
         * Log a user into the application without sessions or cookies.
         *
         * @param array $credentials
         * @return bool
         * @static
         */
        public static function once($credentials = [])
        {
            /** @var \Illuminate\Auth\SessionGuard $instance */
            return $instance->once($credentials);
        }

        /**
         * Log the given user ID into the application without sessions or cookies.
         *
         * @param mixed $id
         * @return \App\Models\User|false
         * @static
         */
        public static function onceUsingId($id)
        {
            /** @var \Illuminate\Auth\SessionGuard $instance */
            return $instance->onceUsingId($id);
        }

        /**
         * Validate a user's credentials.
         *
         * @param array $credentials
         * @return bool
         * @static
         */
        public static function validate($credentials = [])
        {
            /** @var \Illuminate\Auth\SessionGuard $instance */
            return $instance->validate($credentials);
        }

        /**
         * Attempt to authenticate using HTTP Basic Auth.
         *
         * @param string $field
         * @param array $extraConditions
         * @return \Symfony\Component\HttpFoundation\Response|null
         * @throws \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
         * @static
         */
        public static function basic($field = 'email', $extraConditions = [])
        {
            /** @var \Illuminate\Auth\SessionGuard $instance */
            return $instance->basic($field, $extraConditions);
        }

        /**
         * Perform a stateless HTTP Basic login attempt.
         *
         * @param string $field
         * @param array $extraConditions
         * @return \Symfony\Component\HttpFoundation\Response|null
         * @throws \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
         * @static
         */
        public static function onceBasic($field = 'email', $extraConditions = [])
        {
            /** @var \Illuminate\Auth\SessionGuard $instance */
            return $instance->onceBasic($field, $extraConditions);
        }

        /**
         * Attempt to authenticate a user using the given credentials.
         *
         * @param array $credentials
         * @param bool $remember
         * @return bool
         * @static
         */
        public static function attempt($credentials = [], $remember = false)
        {
            /** @var \Illuminate\Auth\SessionGuard $instance */
            return $instance->attempt($credentials, $remember);
        }

        /**
         * Attempt to authenticate a user with credentials and additional callbacks.
         *
         * @param array $credentials
         * @param array|callable|null $callbacks
         * @param bool $remember
         * @return bool
         * @static
         */
        public static function attemptWhen($credentials = [], $callbacks = null, $remember = false)
        {
            /** @var \Illuminate\Auth\SessionGuard $instance */
            return $instance->attemptWhen($credentials, $callbacks, $remember);
        }

        /**
         * Log the given user ID into the application.
         *
         * @param mixed $id
         * @param bool $remember
         * @return \App\Models\User|false
         * @static
         */
        public static function loginUsingId($id, $remember = false)
        {
            /** @var \Illuminate\Auth\SessionGuard $instance */
            return $instance->loginUsingId($id, $remember);
        }

        /**
         * Log a user into the application.
         *
         * @param \Illuminate\Contracts\Auth\Authenticatable $user
         * @param bool $remember
         * @return void
         * @static
         */
        public static function login($user, $remember = false)
        {
            /** @var \Illuminate\Auth\SessionGuard $instance */
            $instance->login($user, $remember);
        }

        /**
         * Log the user out of the application.
         *
         * @return void
         * @static
         */
        public static function logout()
        {
            /** @var \Illuminate\Auth\SessionGuard $instance */
            $instance->logout();
        }

        /**
         * Log the user out of the application on their current device only.
         * 
         * This method does not cycle the "remember" token.
         *
         * @return void
         * @static
         */
        public static function logoutCurrentDevice()
        {
            /** @var \Illuminate\Auth\SessionGuard $instance */
            $instance->logoutCurrentDevice();
        }

        /**
         * Invalidate other sessions for the current user.
         * 
         * The application must be using the AuthenticateSession middleware.
         *
         * @param string $password
         * @return \App\Models\User|null
         * @throws \Illuminate\Auth\AuthenticationException
         * @static
         */
        public static function logoutOtherDevices($password)
        {
            /** @var \Illuminate\Auth\SessionGuard $instance */
            return $instance->logoutOtherDevices($password);
        }

        /**
         * Register an authentication attempt event listener.
         *
         * @param mixed $callback
         * @return void
         * @static
         */
        public static function attempting($callback)
        {
            /** @var \Illuminate\Auth\SessionGuard $instance */
            $instance->attempting($callback);
        }

        /**
         * Get the last user we attempted to authenticate.
         *
         * @return \App\Models\User
         * @static
         */
        public static function getLastAttempted()
        {
            /** @var \Illuminate\Auth\SessionGuard $instance */
            return $instance->getLastAttempted();
        }

        /**
         * Get a unique identifier for the auth session value.
         *
         * @return string
         * @static
         */
        public static function getName()
        {
            /** @var \Illuminate\Auth\SessionGuard $instance */
            return $instance->getName();
        }

        /**
         * Get the name of the cookie used to store the "recaller".
         *
         * @return string
         * @static
         */
        public static function getRecallerName()
        {
            /** @var \Illuminate\Auth\SessionGuard $instance */
            return $instance->getRecallerName();
        }

        /**
         * Determine if the user was authenticated via "remember me" cookie.
         *
         * @return bool
         * @static
         */
        public static function viaRemember()
        {
            /** @var \Illuminate\Auth\SessionGuard $instance */
            return $instance->viaRemember();
        }

        /**
         * Set the number of minutes the remember me cookie should be valid for.
         *
         * @param int $minutes
         * @return \Illuminate\Auth\SessionGuard
         * @static
         */
        public static function setRememberDuration($minutes)
        {
            /** @var \Illuminate\Auth\SessionGuard $instance */
            return $instance->setRememberDuration($minutes);
        }

        /**
         * Get the cookie creator instance used by the guard.
         *
         * @return \Illuminate\Contracts\Cookie\QueueingFactory
         * @throws \RuntimeException
         * @static
         */
        public static function getCookieJar()
        {
            /** @var \Illuminate\Auth\SessionGuard $instance */
            return $instance->getCookieJar();
        }

        /**
         * Set the cookie creator instance used by the guard.
         *
         * @param \Illuminate\Contracts\Cookie\QueueingFactory $cookie
         * @return void
         * @static
         */
        public static function setCookieJar($cookie)
        {
            /** @var \Illuminate\Auth\SessionGuard $instance */
            $instance->setCookieJar($cookie);
        }

        /**
         * Get the event dispatcher instance.
         *
         * @return \Illuminate\Contracts\Events\Dispatcher
         * @static
         */
        public static function getDispatcher()
        {
            /** @var \Illuminate\Auth\SessionGuard $instance */
            return $instance->getDispatcher();
        }

        /**
         * Set the event dispatcher instance.
         *
         * @param \Illuminate\Contracts\Events\Dispatcher $events
         * @return void
         * @static
         */
        public static function setDispatcher($events)
        {
            /** @var \Illuminate\Auth\SessionGuard $instance */
            $instance->setDispatcher($events);
        }

        /**
         * Get the session store used by the guard.
         *
         * @return \Illuminate\Contracts\Session\Session
         * @static
         */
        public static function getSession()
        {
            /** @var \Illuminate\Auth\SessionGuard $instance */
            return $instance->getSession();
        }

        /**
         * Return the currently cached user.
         *
         * @return \App\Models\User|null
         * @static
         */
        public static function getUser()
        {
            /** @var \Illuminate\Auth\SessionGuard $instance */
            return $instance->getUser();
        }

        /**
         * Set the current user.
         *
         * @param \Illuminate\Contracts\Auth\Authenticatable $user
         * @return \Illuminate\Auth\SessionGuard
         * @static
         */
        public static function setUser($user)
        {
            /** @var \Illuminate\Auth\SessionGuard $instance */
            return $instance->setUser($user);
        }

        /**
         * Get the current request instance.
         *
         * @return \Symfony\Component\HttpFoundation\Request
         * @static
         */
        public static function getRequest()
        {
            /** @var \Illuminate\Auth\SessionGuard $instance */
            return $instance->getRequest();
        }

        /**
         * Set the current request instance.
         *
         * @param \Symfony\Component\HttpFoundation\Request $request
         * @return \Illuminate\Auth\SessionGuard
         * @static
         */
        public static function setRequest($request)
        {
            /** @var \Illuminate\Auth\SessionGuard $instance */
            return $instance->setRequest($request);
        }

        /**
         * Get the timebox instance used by the guard.
         *
         * @return \Illuminate\Support\Timebox
         * @static
         */
        public static function getTimebox()
        {
            /** @var \Illuminate\Auth\SessionGuard $instance */
            return $instance->getTimebox();
        }

        /**
         * Determine if the current user is authenticated. If not, throw an exception.
         *
         * @return \App\Models\User
         * @throws \Illuminate\Auth\AuthenticationException
         * @static
         */
        public static function authenticate()
        {
            /** @var \Illuminate\Auth\SessionGuard $instance */
            return $instance->authenticate();
        }

        /**
         * Determine if the guard has a user instance.
         *
         * @return bool
         * @static
         */
        public static function hasUser()
        {
            /** @var \Illuminate\Auth\SessionGuard $instance */
            return $instance->hasUser();
        }

        /**
         * Determine if the current user is authenticated.
         *
         * @return bool
         * @static
         */
        public static function check()
        {
            /** @var \Illuminate\Auth\SessionGuard $instance */
            return $instance->check();
        }

        /**
         * Determine if the current user is a guest.
         *
         * @return bool
         * @static
         */
        public static function guest()
        {
            /** @var \Illuminate\Auth\SessionGuard $instance */
            return $instance->guest();
        }

        /**
         * Forget the current user.
         *
         * @return \Illuminate\Auth\SessionGuard
         * @static
         */
        public static function forgetUser()
        {
            /** @var \Illuminate\Auth\SessionGuard $instance */
            return $instance->forgetUser();
        }

        /**
         * Get the user provider used by the guard.
         *
         * @return \Illuminate\Contracts\Auth\UserProvider
         * @static
         */
        public static function getProvider()
        {
            /** @var \Illuminate\Auth\SessionGuard $instance */
            return $instance->getProvider();
        }

        /**
         * Set the user provider used by the guard.
         *
         * @param \Illuminate\Contracts\Auth\UserProvider $provider
         * @return void
         * @static
         */
        public static function setProvider($provider)
        {
            /** @var \Illuminate\Auth\SessionGuard $instance */
            $instance->setProvider($provider);
        }

        /**
         * Register a custom macro.
         *
         * @param string $name
         * @param object|callable $macro
         * @param-closure-this static  $macro
         * @return void
         * @static
         */
        public static function macro($name, $macro)
        {
            \Illuminate\Auth\SessionGuard::macro($name, $macro);
        }

        /**
         * Mix another object into the class.
         *
         * @param object $mixin
         * @param bool $replace
         * @return void
         * @throws \ReflectionException
         * @static
         */
        public static function mixin($mixin, $replace = true)
        {
            \Illuminate\Auth\SessionGuard::mixin($mixin, $replace);
        }

        /**
         * Checks if macro is registered.
         *
         * @param string $name
         * @return bool
         * @static
         */
        public static function hasMacro($name)
        {
            return \Illuminate\Auth\SessionGuard::hasMacro($name);
        }

        /**
         * Flush the existing macros.
         *
         * @return void
         * @static
         */
        public static function flushMacros()
        {
            \Illuminate\Auth\SessionGuard::flushMacros();
        }

            }
    /**
     * @method static \Illuminate\Routing\RouteRegistrar attribute(string $key, mixed $value)
     * @method static \Illuminate\Routing\RouteRegistrar whereAlpha(array|string $parameters)
     * @method static \Illuminate\Routing\RouteRegistrar whereAlphaNumeric(array|string $parameters)
     * @method static \Illuminate\Routing\RouteRegistrar whereNumber(array|string $parameters)
     * @method static \Illuminate\Routing\RouteRegistrar whereUlid(array|string $parameters)
     * @method static \Illuminate\Routing\RouteRegistrar whereUuid(array|string $parameters)
     * @method static \Illuminate\Routing\RouteRegistrar whereIn(array|string $parameters, array $values)
     * @method static \Illuminate\Routing\RouteRegistrar as(string $value)
     * @method static \Illuminate\Routing\RouteRegistrar can(\UnitEnum|string $ability, array|string $models = [])
     * @method static \Illuminate\Routing\RouteRegistrar controller(string $controller)
     * @method static \Illuminate\Routing\RouteRegistrar domain(\BackedEnum|string $value)
     * @method static \Illuminate\Routing\RouteRegistrar middleware(array|string|null $middleware)
     * @method static \Illuminate\Routing\RouteRegistrar missing(\Closure $missing)
     * @method static \Illuminate\Routing\RouteRegistrar name(\BackedEnum|string $value)
     * @method static \Illuminate\Routing\RouteRegistrar namespace(string|null $value)
     * @method static \Illuminate\Routing\RouteRegistrar prefix(string $prefix)
     * @method static \Illuminate\Routing\RouteRegistrar scopeBindings()
     * @method static \Illuminate\Routing\RouteRegistrar where(array $where)
     * @method static \Illuminate\Routing\RouteRegistrar withoutMiddleware(array|string $middleware)
     * @method static \Illuminate\Routing\RouteRegistrar withoutScopedBindings()
     * @see \Illuminate\Routing\Router
     */
    class Route {
        /**
         * Register a new GET route with the router.
         *
         * @param string $uri
         * @param array|string|callable|null $action
         * @return \Illuminate\Routing\Route
         * @static
         */
        public static function get($uri, $action = null)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->get($uri, $action);
        }

        /**
         * Register a new POST route with the router.
         *
         * @param string $uri
         * @param array|string|callable|null $action
         * @return \Illuminate\Routing\Route
         * @static
         */
        public static function post($uri, $action = null)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->post($uri, $action);
        }

        /**
         * Register a new PUT route with the router.
         *
         * @param string $uri
         * @param array|string|callable|null $action
         * @return \Illuminate\Routing\Route
         * @static
         */
        public static function put($uri, $action = null)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->put($uri, $action);
        }

        /**
         * Register a new PATCH route with the router.
         *
         * @param string $uri
         * @param array|string|callable|null $action
         * @return \Illuminate\Routing\Route
         * @static
         */
        public static function patch($uri, $action = null)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->patch($uri, $action);
        }

        /**
         * Register a new DELETE route with the router.
         *
         * @param string $uri
         * @param array|string|callable|null $action
         * @return \Illuminate\Routing\Route
         * @static
         */
        public static function delete($uri, $action = null)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->delete($uri, $action);
        }

        /**
         * Register a new OPTIONS route with the router.
         *
         * @param string $uri
         * @param array|string|callable|null $action
         * @return \Illuminate\Routing\Route
         * @static
         */
        public static function options($uri, $action = null)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->options($uri, $action);
        }

        /**
         * Register a new route responding to all verbs.
         *
         * @param string $uri
         * @param array|string|callable|null $action
         * @return \Illuminate\Routing\Route
         * @static
         */
        public static function any($uri, $action = null)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->any($uri, $action);
        }

        /**
         * Register a new fallback route with the router.
         *
         * @param array|string|callable|null $action
         * @return \Illuminate\Routing\Route
         * @static
         */
        public static function fallback($action)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->fallback($action);
        }

        /**
         * Create a redirect from one URI to another.
         *
         * @param string $uri
         * @param string $destination
         * @param int $status
         * @return \Illuminate\Routing\Route
         * @static
         */
        public static function redirect($uri, $destination, $status = 302)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->redirect($uri, $destination, $status);
        }

        /**
         * Create a permanent redirect from one URI to another.
         *
         * @param string $uri
         * @param string $destination
         * @return \Illuminate\Routing\Route
         * @static
         */
        public static function permanentRedirect($uri, $destination)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->permanentRedirect($uri, $destination);
        }

        /**
         * Register a new route that returns a view.
         *
         * @param string $uri
         * @param string $view
         * @param array $data
         * @param int|array $status
         * @param array $headers
         * @return \Illuminate\Routing\Route
         * @static
         */
        public static function view($uri, $view, $data = [], $status = 200, $headers = [])
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->view($uri, $view, $data, $status, $headers);
        }

        /**
         * Register a new route with the given verbs.
         *
         * @param array|string $methods
         * @param string $uri
         * @param array|string|callable|null $action
         * @return \Illuminate\Routing\Route
         * @static
         */
        public static function match($methods, $uri, $action = null)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->match($methods, $uri, $action);
        }

        /**
         * Register an array of resource controllers.
         *
         * @param array $resources
         * @param array $options
         * @return void
         * @static
         */
        public static function resources($resources, $options = [])
        {
            /** @var \Illuminate\Routing\Router $instance */
            $instance->resources($resources, $options);
        }

        /**
         * Register an array of resource controllers that can be soft deleted.
         *
         * @param array $resources
         * @param array $options
         * @return void
         * @static
         */
        public static function softDeletableResources($resources, $options = [])
        {
            /** @var \Illuminate\Routing\Router $instance */
            $instance->softDeletableResources($resources, $options);
        }

        /**
         * Route a resource to a controller.
         *
         * @param string $name
         * @param string $controller
         * @param array $options
         * @return \Illuminate\Routing\PendingResourceRegistration
         * @static
         */
        public static function resource($name, $controller, $options = [])
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->resource($name, $controller, $options);
        }

        /**
         * Register an array of API resource controllers.
         *
         * @param array $resources
         * @param array $options
         * @return void
         * @static
         */
        public static function apiResources($resources, $options = [])
        {
            /** @var \Illuminate\Routing\Router $instance */
            $instance->apiResources($resources, $options);
        }

        /**
         * Route an API resource to a controller.
         *
         * @param string $name
         * @param string $controller
         * @param array $options
         * @return \Illuminate\Routing\PendingResourceRegistration
         * @static
         */
        public static function apiResource($name, $controller, $options = [])
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->apiResource($name, $controller, $options);
        }

        /**
         * Register an array of singleton resource controllers.
         *
         * @param array $singletons
         * @param array $options
         * @return void
         * @static
         */
        public static function singletons($singletons, $options = [])
        {
            /** @var \Illuminate\Routing\Router $instance */
            $instance->singletons($singletons, $options);
        }

        /**
         * Route a singleton resource to a controller.
         *
         * @param string $name
         * @param string $controller
         * @param array $options
         * @return \Illuminate\Routing\PendingSingletonResourceRegistration
         * @static
         */
        public static function singleton($name, $controller, $options = [])
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->singleton($name, $controller, $options);
        }

        /**
         * Register an array of API singleton resource controllers.
         *
         * @param array $singletons
         * @param array $options
         * @return void
         * @static
         */
        public static function apiSingletons($singletons, $options = [])
        {
            /** @var \Illuminate\Routing\Router $instance */
            $instance->apiSingletons($singletons, $options);
        }

        /**
         * Route an API singleton resource to a controller.
         *
         * @param string $name
         * @param string $controller
         * @param array $options
         * @return \Illuminate\Routing\PendingSingletonResourceRegistration
         * @static
         */
        public static function apiSingleton($name, $controller, $options = [])
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->apiSingleton($name, $controller, $options);
        }

        /**
         * Create a route group with shared attributes.
         *
         * @param array $attributes
         * @param \Closure|array|string $routes
         * @return \Illuminate\Routing\Router
         * @static
         */
        public static function group($attributes, $routes)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->group($attributes, $routes);
        }

        /**
         * Merge the given array with the last group stack.
         *
         * @param array $new
         * @param bool $prependExistingPrefix
         * @return array
         * @static
         */
        public static function mergeWithLastGroup($new, $prependExistingPrefix = true)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->mergeWithLastGroup($new, $prependExistingPrefix);
        }

        /**
         * Get the prefix from the last group on the stack.
         *
         * @return string
         * @static
         */
        public static function getLastGroupPrefix()
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->getLastGroupPrefix();
        }

        /**
         * Add a route to the underlying route collection.
         *
         * @param array|string $methods
         * @param string $uri
         * @param array|string|callable|null $action
         * @return \Illuminate\Routing\Route
         * @static
         */
        public static function addRoute($methods, $uri, $action)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->addRoute($methods, $uri, $action);
        }

        /**
         * Create a new Route object.
         *
         * @param array|string $methods
         * @param string $uri
         * @param mixed $action
         * @return \Illuminate\Routing\Route
         * @static
         */
        public static function newRoute($methods, $uri, $action)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->newRoute($methods, $uri, $action);
        }

        /**
         * Return the response returned by the given route.
         *
         * @param string $name
         * @return \Symfony\Component\HttpFoundation\Response
         * @static
         */
        public static function respondWithRoute($name)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->respondWithRoute($name);
        }

        /**
         * Dispatch the request to the application.
         *
         * @param \Illuminate\Http\Request $request
         * @return \Symfony\Component\HttpFoundation\Response
         * @static
         */
        public static function dispatch($request)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->dispatch($request);
        }

        /**
         * Dispatch the request to a route and return the response.
         *
         * @param \Illuminate\Http\Request $request
         * @return \Symfony\Component\HttpFoundation\Response
         * @static
         */
        public static function dispatchToRoute($request)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->dispatchToRoute($request);
        }

        /**
         * Gather the middleware for the given route with resolved class names.
         *
         * @param \Illuminate\Routing\Route $route
         * @return array
         * @static
         */
        public static function gatherRouteMiddleware($route)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->gatherRouteMiddleware($route);
        }

        /**
         * Resolve a flat array of middleware classes from the provided array.
         *
         * @param array $middleware
         * @param array $excluded
         * @return array
         * @static
         */
        public static function resolveMiddleware($middleware, $excluded = [])
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->resolveMiddleware($middleware, $excluded);
        }

        /**
         * Create a response instance from the given value.
         *
         * @param \Symfony\Component\HttpFoundation\Request $request
         * @param mixed $response
         * @return \Symfony\Component\HttpFoundation\Response
         * @static
         */
        public static function prepareResponse($request, $response)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->prepareResponse($request, $response);
        }

        /**
         * Static version of prepareResponse.
         *
         * @param \Symfony\Component\HttpFoundation\Request $request
         * @param mixed $response
         * @return \Symfony\Component\HttpFoundation\Response
         * @static
         */
        public static function toResponse($request, $response)
        {
            return \Illuminate\Routing\Router::toResponse($request, $response);
        }

        /**
         * Substitute the route bindings onto the route.
         *
         * @param \Illuminate\Routing\Route $route
         * @return \Illuminate\Routing\Route
         * @throws \Illuminate\Database\Eloquent\ModelNotFoundException<\Illuminate\Database\Eloquent\Model>
         * @throws \Illuminate\Routing\Exceptions\BackedEnumCaseNotFoundException
         * @static
         */
        public static function substituteBindings($route)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->substituteBindings($route);
        }

        /**
         * Substitute the implicit route bindings for the given route.
         *
         * @param \Illuminate\Routing\Route $route
         * @return void
         * @throws \Illuminate\Database\Eloquent\ModelNotFoundException<\Illuminate\Database\Eloquent\Model>
         * @throws \Illuminate\Routing\Exceptions\BackedEnumCaseNotFoundException
         * @static
         */
        public static function substituteImplicitBindings($route)
        {
            /** @var \Illuminate\Routing\Router $instance */
            $instance->substituteImplicitBindings($route);
        }

        /**
         * Register a callback to run after implicit bindings are substituted.
         *
         * @param callable $callback
         * @return \Illuminate\Routing\Router
         * @static
         */
        public static function substituteImplicitBindingsUsing($callback)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->substituteImplicitBindingsUsing($callback);
        }

        /**
         * Register a route matched event listener.
         *
         * @param string|callable $callback
         * @return void
         * @static
         */
        public static function matched($callback)
        {
            /** @var \Illuminate\Routing\Router $instance */
            $instance->matched($callback);
        }

        /**
         * Get all of the defined middleware short-hand names.
         *
         * @return array
         * @static
         */
        public static function getMiddleware()
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->getMiddleware();
        }

        /**
         * Register a short-hand name for a middleware.
         *
         * @param string $name
         * @param string $class
         * @return \Illuminate\Routing\Router
         * @static
         */
        public static function aliasMiddleware($name, $class)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->aliasMiddleware($name, $class);
        }

        /**
         * Check if a middlewareGroup with the given name exists.
         *
         * @param string $name
         * @return bool
         * @static
         */
        public static function hasMiddlewareGroup($name)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->hasMiddlewareGroup($name);
        }

        /**
         * Get all of the defined middleware groups.
         *
         * @return array
         * @static
         */
        public static function getMiddlewareGroups()
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->getMiddlewareGroups();
        }

        /**
         * Register a group of middleware.
         *
         * @param string $name
         * @param array $middleware
         * @return \Illuminate\Routing\Router
         * @static
         */
        public static function middlewareGroup($name, $middleware)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->middlewareGroup($name, $middleware);
        }

        /**
         * Add a middleware to the beginning of a middleware group.
         * 
         * If the middleware is already in the group, it will not be added again.
         *
         * @param string $group
         * @param string $middleware
         * @return \Illuminate\Routing\Router
         * @static
         */
        public static function prependMiddlewareToGroup($group, $middleware)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->prependMiddlewareToGroup($group, $middleware);
        }

        /**
         * Add a middleware to the end of a middleware group.
         * 
         * If the middleware is already in the group, it will not be added again.
         *
         * @param string $group
         * @param string $middleware
         * @return \Illuminate\Routing\Router
         * @static
         */
        public static function pushMiddlewareToGroup($group, $middleware)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->pushMiddlewareToGroup($group, $middleware);
        }

        /**
         * Remove the given middleware from the specified group.
         *
         * @param string $group
         * @param string $middleware
         * @return \Illuminate\Routing\Router
         * @static
         */
        public static function removeMiddlewareFromGroup($group, $middleware)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->removeMiddlewareFromGroup($group, $middleware);
        }

        /**
         * Flush the router's middleware groups.
         *
         * @return \Illuminate\Routing\Router
         * @static
         */
        public static function flushMiddlewareGroups()
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->flushMiddlewareGroups();
        }

        /**
         * Add a new route parameter binder.
         *
         * @param string $key
         * @param string|callable $binder
         * @return void
         * @static
         */
        public static function bind($key, $binder)
        {
            /** @var \Illuminate\Routing\Router $instance */
            $instance->bind($key, $binder);
        }

        /**
         * Register a model binder for a wildcard.
         *
         * @param string $key
         * @param string $class
         * @param \Closure|null $callback
         * @return void
         * @static
         */
        public static function model($key, $class, $callback = null)
        {
            /** @var \Illuminate\Routing\Router $instance */
            $instance->model($key, $class, $callback);
        }

        /**
         * Get the binding callback for a given binding.
         *
         * @param string $key
         * @return \Closure|null
         * @static
         */
        public static function getBindingCallback($key)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->getBindingCallback($key);
        }

        /**
         * Get the global "where" patterns.
         *
         * @return array
         * @static
         */
        public static function getPatterns()
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->getPatterns();
        }

        /**
         * Set a global where pattern on all routes.
         *
         * @param string $key
         * @param string $pattern
         * @return void
         * @static
         */
        public static function pattern($key, $pattern)
        {
            /** @var \Illuminate\Routing\Router $instance */
            $instance->pattern($key, $pattern);
        }

        /**
         * Set a group of global where patterns on all routes.
         *
         * @param array $patterns
         * @return void
         * @static
         */
        public static function patterns($patterns)
        {
            /** @var \Illuminate\Routing\Router $instance */
            $instance->patterns($patterns);
        }

        /**
         * Determine if the router currently has a group stack.
         *
         * @return bool
         * @static
         */
        public static function hasGroupStack()
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->hasGroupStack();
        }

        /**
         * Get the current group stack for the router.
         *
         * @return array
         * @static
         */
        public static function getGroupStack()
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->getGroupStack();
        }

        /**
         * Get a route parameter for the current route.
         *
         * @param string $key
         * @param string|null $default
         * @return mixed
         * @static
         */
        public static function input($key, $default = null)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->input($key, $default);
        }

        /**
         * Get the request currently being dispatched.
         *
         * @return \Illuminate\Http\Request
         * @static
         */
        public static function getCurrentRequest()
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->getCurrentRequest();
        }

        /**
         * Get the currently dispatched route instance.
         *
         * @return \Illuminate\Routing\Route|null
         * @static
         */
        public static function getCurrentRoute()
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->getCurrentRoute();
        }

        /**
         * Get the currently dispatched route instance.
         *
         * @return \Illuminate\Routing\Route|null
         * @static
         */
        public static function current()
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->current();
        }

        /**
         * Check if a route with the given name exists.
         *
         * @param string|array $name
         * @return bool
         * @static
         */
        public static function has($name)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->has($name);
        }

        /**
         * Get the current route name.
         *
         * @return string|null
         * @static
         */
        public static function currentRouteName()
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->currentRouteName();
        }

        /**
         * Alias for the "currentRouteNamed" method.
         *
         * @param mixed $patterns
         * @return bool
         * @static
         */
        public static function is(...$patterns)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->is(...$patterns);
        }

        /**
         * Determine if the current route matches a pattern.
         *
         * @param mixed $patterns
         * @return bool
         * @static
         */
        public static function currentRouteNamed(...$patterns)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->currentRouteNamed(...$patterns);
        }

        /**
         * Get the current route action.
         *
         * @return string|null
         * @static
         */
        public static function currentRouteAction()
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->currentRouteAction();
        }

        /**
         * Alias for the "currentRouteUses" method.
         *
         * @param array|string $patterns
         * @return bool
         * @static
         */
        public static function uses(...$patterns)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->uses(...$patterns);
        }

        /**
         * Determine if the current route action matches a given action.
         *
         * @param string $action
         * @return bool
         * @static
         */
        public static function currentRouteUses($action)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->currentRouteUses($action);
        }

        /**
         * Set the unmapped global resource parameters to singular.
         *
         * @param bool $singular
         * @return void
         * @static
         */
        public static function singularResourceParameters($singular = true)
        {
            /** @var \Illuminate\Routing\Router $instance */
            $instance->singularResourceParameters($singular);
        }

        /**
         * Set the global resource parameter mapping.
         *
         * @param array $parameters
         * @return void
         * @static
         */
        public static function resourceParameters($parameters = [])
        {
            /** @var \Illuminate\Routing\Router $instance */
            $instance->resourceParameters($parameters);
        }

        /**
         * Get or set the verbs used in the resource URIs.
         *
         * @param array $verbs
         * @return array|null
         * @static
         */
        public static function resourceVerbs($verbs = [])
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->resourceVerbs($verbs);
        }

        /**
         * Get the underlying route collection.
         *
         * @return \Illuminate\Routing\RouteCollectionInterface
         * @static
         */
        public static function getRoutes()
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->getRoutes();
        }

        /**
         * Set the route collection instance.
         *
         * @param \Illuminate\Routing\RouteCollection $routes
         * @return void
         * @static
         */
        public static function setRoutes($routes)
        {
            /** @var \Illuminate\Routing\Router $instance */
            $instance->setRoutes($routes);
        }

        /**
         * Set the compiled route collection instance.
         *
         * @param array $routes
         * @return void
         * @static
         */
        public static function setCompiledRoutes($routes)
        {
            /** @var \Illuminate\Routing\Router $instance */
            $instance->setCompiledRoutes($routes);
        }

        /**
         * Remove any duplicate middleware from the given array.
         *
         * @param array $middleware
         * @return array
         * @static
         */
        public static function uniqueMiddleware($middleware)
        {
            return \Illuminate\Routing\Router::uniqueMiddleware($middleware);
        }

        /**
         * Set the container instance used by the router.
         *
         * @param \Illuminate\Container\Container $container
         * @return \Illuminate\Routing\Router
         * @static
         */
        public static function setContainer($container)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->setContainer($container);
        }

        /**
         * Register a custom macro.
         *
         * @param string $name
         * @param object|callable $macro
         * @param-closure-this static  $macro
         * @return void
         * @static
         */
        public static function macro($name, $macro)
        {
            \Illuminate\Routing\Router::macro($name, $macro);
        }

        /**
         * Mix another object into the class.
         *
         * @param object $mixin
         * @param bool $replace
         * @return void
         * @throws \ReflectionException
         * @static
         */
        public static function mixin($mixin, $replace = true)
        {
            \Illuminate\Routing\Router::mixin($mixin, $replace);
        }

        /**
         * Checks if macro is registered.
         *
         * @param string $name
         * @return bool
         * @static
         */
        public static function hasMacro($name)
        {
            return \Illuminate\Routing\Router::hasMacro($name);
        }

        /**
         * Flush the existing macros.
         *
         * @return void
         * @static
         */
        public static function flushMacros()
        {
            \Illuminate\Routing\Router::flushMacros();
        }

        /**
         * Dynamically handle calls to the class.
         *
         * @param string $method
         * @param array $parameters
         * @return mixed
         * @throws \BadMethodCallException
         * @static
         */
        public static function macroCall($method, $parameters)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->macroCall($method, $parameters);
        }

        /**
         * Call the given Closure with this instance then return the instance.
         *
         * @param (callable($this): mixed)|null $callback
         * @return ($callback is null ? \Illuminate\Support\HigherOrderTapProxy : $this)
         * @static
         */
        public static function tap($callback = null)
        {
            /** @var \Illuminate\Routing\Router $instance */
            return $instance->tap($callback);
        }

        /**
         * @see \Laravel\Ui\AuthRouteMethods::auth()
         * @param mixed $options
         * @static
         */
        public static function auth($options = [])
        {
            return \Illuminate\Routing\Router::auth($options);
        }

        /**
         * @see \Laravel\Ui\AuthRouteMethods::resetPassword()
         * @static
         */
        public static function resetPassword()
        {
            return \Illuminate\Routing\Router::resetPassword();
        }

        /**
         * @see \Laravel\Ui\AuthRouteMethods::confirmPassword()
         * @static
         */
        public static function confirmPassword()
        {
            return \Illuminate\Routing\Router::confirmPassword();
        }

        /**
         * @see \Laravel\Ui\AuthRouteMethods::emailVerification()
         * @static
         */
        public static function emailVerification()
        {
            return \Illuminate\Routing\Router::emailVerification();
        }

            }
    }

namespace Maatwebsite\Excel\Facades {
    /**
     */
    class Excel {
        /**
         * @param object $export
         * @param string|null $fileName
         * @param string $writerType
         * @param array $headers
         * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
         * @throws \PhpOffice\PhpSpreadsheet\Exception
         * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
         * @static
         */
        public static function download($export, $fileName, $writerType = null, $headers = [])
        {
            /** @var \Maatwebsite\Excel\Excel $instance */
            return $instance->download($export, $fileName, $writerType, $headers);
        }

        /**
         * @param string|null $disk Fallback for usage with named properties
         * @param object $export
         * @param string $filePath
         * @param string|null $diskName
         * @param string $writerType
         * @param mixed $diskOptions
         * @return bool
         * @throws \PhpOffice\PhpSpreadsheet\Exception
         * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
         * @static
         */
        public static function store($export, $filePath, $diskName = null, $writerType = null, $diskOptions = [], $disk = null)
        {
            /** @var \Maatwebsite\Excel\Excel $instance */
            return $instance->store($export, $filePath, $diskName, $writerType, $diskOptions, $disk);
        }

        /**
         * @param object $export
         * @param string $filePath
         * @param string|null $disk
         * @param string $writerType
         * @param mixed $diskOptions
         * @return \Illuminate\Foundation\Bus\PendingDispatch
         * @static
         */
        public static function queue($export, $filePath, $disk = null, $writerType = null, $diskOptions = [])
        {
            /** @var \Maatwebsite\Excel\Excel $instance */
            return $instance->queue($export, $filePath, $disk, $writerType, $diskOptions);
        }

        /**
         * @param object $export
         * @param string $writerType
         * @return string
         * @static
         */
        public static function raw($export, $writerType)
        {
            /** @var \Maatwebsite\Excel\Excel $instance */
            return $instance->raw($export, $writerType);
        }

        /**
         * @param object $import
         * @param string|\Symfony\Component\HttpFoundation\File\UploadedFile $filePath
         * @param string|null $disk
         * @param string|null $readerType
         * @return \Maatwebsite\Excel\Reader|\Illuminate\Foundation\Bus\PendingDispatch
         * @static
         */
        public static function import($import, $filePath, $disk = null, $readerType = null)
        {
            /** @var \Maatwebsite\Excel\Excel $instance */
            return $instance->import($import, $filePath, $disk, $readerType);
        }

        /**
         * @param object $import
         * @param string|\Symfony\Component\HttpFoundation\File\UploadedFile $filePath
         * @param string|null $disk
         * @param string|null $readerType
         * @return array
         * @static
         */
        public static function toArray($import, $filePath, $disk = null, $readerType = null)
        {
            /** @var \Maatwebsite\Excel\Excel $instance */
            return $instance->toArray($import, $filePath, $disk, $readerType);
        }

        /**
         * @param object $import
         * @param string|\Symfony\Component\HttpFoundation\File\UploadedFile $filePath
         * @param string|null $disk
         * @param string|null $readerType
         * @return \Illuminate\Support\Collection
         * @static
         */
        public static function toCollection($import, $filePath, $disk = null, $readerType = null)
        {
            /** @var \Maatwebsite\Excel\Excel $instance */
            return $instance->toCollection($import, $filePath, $disk, $readerType);
        }

        /**
         * @param \Illuminate\Contracts\Queue\ShouldQueue $import
         * @param string|\Symfony\Component\HttpFoundation\File\UploadedFile $filePath
         * @param string|null $disk
         * @param string $readerType
         * @return \Illuminate\Foundation\Bus\PendingDispatch
         * @static
         */
        public static function queueImport($import, $filePath, $disk = null, $readerType = null)
        {
            /** @var \Maatwebsite\Excel\Excel $instance */
            return $instance->queueImport($import, $filePath, $disk, $readerType);
        }

        /**
         * Register a custom macro.
         *
         * @param string $name
         * @param object|callable $macro
         * @param-closure-this static  $macro
         * @return void
         * @static
         */
        public static function macro($name, $macro)
        {
            \Maatwebsite\Excel\Excel::macro($name, $macro);
        }

        /**
         * Mix another object into the class.
         *
         * @param object $mixin
         * @param bool $replace
         * @return void
         * @throws \ReflectionException
         * @static
         */
        public static function mixin($mixin, $replace = true)
        {
            \Maatwebsite\Excel\Excel::mixin($mixin, $replace);
        }

        /**
         * Checks if macro is registered.
         *
         * @param string $name
         * @return bool
         * @static
         */
        public static function hasMacro($name)
        {
            return \Maatwebsite\Excel\Excel::hasMacro($name);
        }

        /**
         * Flush the existing macros.
         *
         * @return void
         * @static
         */
        public static function flushMacros()
        {
            \Maatwebsite\Excel\Excel::flushMacros();
        }

        /**
         * @param string $concern
         * @param callable $handler
         * @param string $event
         * @static
         */
        public static function extend($concern, $handler, $event = 'Maatwebsite\\Excel\\Events\\BeforeWriting')
        {
            return \Maatwebsite\Excel\Excel::extend($concern, $handler, $event);
        }

        /**
         * When asserting downloaded, stored, queued or imported, use regular expression
         * to look for a matching file path.
         *
         * @return void
         * @static
         */
        public static function matchByRegex()
        {
            /** @var \Maatwebsite\Excel\Fakes\ExcelFake $instance */
            $instance->matchByRegex();
        }

        /**
         * When asserting downloaded, stored, queued or imported, use regular string
         * comparison for matching file path.
         *
         * @return void
         * @static
         */
        public static function doNotMatchByRegex()
        {
            /** @var \Maatwebsite\Excel\Fakes\ExcelFake $instance */
            $instance->doNotMatchByRegex();
        }

        /**
         * @param string $fileName
         * @param callable|null $callback
         * @static
         */
        public static function assertDownloaded($fileName, $callback = null)
        {
            /** @var \Maatwebsite\Excel\Fakes\ExcelFake $instance */
            return $instance->assertDownloaded($fileName, $callback);
        }

        /**
         * @param string $filePath
         * @param string|callable|null $disk
         * @param callable|null $callback
         * @static
         */
        public static function assertStored($filePath, $disk = null, $callback = null)
        {
            /** @var \Maatwebsite\Excel\Fakes\ExcelFake $instance */
            return $instance->assertStored($filePath, $disk, $callback);
        }

        /**
         * @param string $filePath
         * @param string|callable|null $disk
         * @param callable|null $callback
         * @static
         */
        public static function assertQueued($filePath, $disk = null, $callback = null)
        {
            /** @var \Maatwebsite\Excel\Fakes\ExcelFake $instance */
            return $instance->assertQueued($filePath, $disk, $callback);
        }

        /**
         * @static
         */
        public static function assertQueuedWithChain($chain)
        {
            /** @var \Maatwebsite\Excel\Fakes\ExcelFake $instance */
            return $instance->assertQueuedWithChain($chain);
        }

        /**
         * @param string $classname
         * @param callable|null $callback
         * @static
         */
        public static function assertExportedInRaw($classname, $callback = null)
        {
            /** @var \Maatwebsite\Excel\Fakes\ExcelFake $instance */
            return $instance->assertExportedInRaw($classname, $callback);
        }

        /**
         * @param string $filePath
         * @param string|callable|null $disk
         * @param callable|null $callback
         * @static
         */
        public static function assertImported($filePath, $disk = null, $callback = null)
        {
            /** @var \Maatwebsite\Excel\Fakes\ExcelFake $instance */
            return $instance->assertImported($filePath, $disk, $callback);
        }

            }
    }

namespace Barryvdh\DomPDF\Facade {
    /**
     * @method static BasePDF setBaseHost(string $baseHost)
     * @method static BasePDF setBasePath(string $basePath)
     * @method static BasePDF setCanvas(\Dompdf\Canvas $canvas)
     * @method static BasePDF setCallbacks(array<string, mixed> $callbacks)
     * @method static BasePDF setCss(\Dompdf\Css\Stylesheet $css)
     * @method static BasePDF setDefaultView(string $defaultView, array<string, mixed> $options)
     * @method static BasePDF setDom(\DOMDocument $dom)
     * @method static BasePDF setFontMetrics(\Dompdf\FontMetrics $fontMetrics)
     * @method static BasePDF setHttpContext(resource|array<string, mixed> $httpContext)
     * @method static BasePDF setPaper(string|float[] $paper, string $orientation = 'portrait')
     * @method static BasePDF setProtocol(string $protocol)
     * @method static BasePDF setTree(\Dompdf\Frame\FrameTree $tree)
     */
    class Pdf {
        /**
         * Get the DomPDF instance
         *
         * @static
         */
        public static function getDomPDF()
        {
            /** @var \Barryvdh\DomPDF\PDF $instance */
            return $instance->getDomPDF();
        }

        /**
         * Show or hide warnings
         *
         * @static
         */
        public static function setWarnings($warnings)
        {
            /** @var \Barryvdh\DomPDF\PDF $instance */
            return $instance->setWarnings($warnings);
        }

        /**
         * Load a HTML string
         *
         * @param string|null $encoding Not used yet
         * @static
         */
        public static function loadHTML($string, $encoding = null)
        {
            /** @var \Barryvdh\DomPDF\PDF $instance */
            return $instance->loadHTML($string, $encoding);
        }

        /**
         * Load a HTML file
         *
         * @static
         */
        public static function loadFile($file)
        {
            /** @var \Barryvdh\DomPDF\PDF $instance */
            return $instance->loadFile($file);
        }

        /**
         * Add metadata info
         *
         * @param array<string, string> $info
         * @static
         */
        public static function addInfo($info)
        {
            /** @var \Barryvdh\DomPDF\PDF $instance */
            return $instance->addInfo($info);
        }

        /**
         * Load a View and convert to HTML
         *
         * @param array<string, mixed> $data
         * @param array<string, mixed> $mergeData
         * @param string|null $encoding Not used yet
         * @static
         */
        public static function loadView($view, $data = [], $mergeData = [], $encoding = null)
        {
            /** @var \Barryvdh\DomPDF\PDF $instance */
            return $instance->loadView($view, $data, $mergeData, $encoding);
        }

        /**
         * Set/Change an option (or array of options) in Dompdf
         *
         * @param array<string, mixed>|string $attribute
         * @param null|mixed $value
         * @static
         */
        public static function setOption($attribute, $value = null)
        {
            /** @var \Barryvdh\DomPDF\PDF $instance */
            return $instance->setOption($attribute, $value);
        }

        /**
         * Replace all the Options from DomPDF
         *
         * @param array<string, mixed> $options
         * @static
         */
        public static function setOptions($options, $mergeWithDefaults = false)
        {
            /** @var \Barryvdh\DomPDF\PDF $instance */
            return $instance->setOptions($options, $mergeWithDefaults);
        }

        /**
         * Output the PDF as a string.
         * 
         * The options parameter controls the output. Accepted options are:
         * 
         * 'compress' = > 1 or 0 - apply content stream compression, this is
         *    on (1) by default
         *
         * @param array<string, int> $options
         * @return string The rendered PDF as string
         * @static
         */
        public static function output($options = [])
        {
            /** @var \Barryvdh\DomPDF\PDF $instance */
            return $instance->output($options);
        }

        /**
         * Save the PDF to a file
         *
         * @static
         */
        public static function save($filename, $disk = null)
        {
            /** @var \Barryvdh\DomPDF\PDF $instance */
            return $instance->save($filename, $disk);
        }

        /**
         * Make the PDF downloadable by the user
         *
         * @static
         */
        public static function download($filename = 'document.pdf')
        {
            /** @var \Barryvdh\DomPDF\PDF $instance */
            return $instance->download($filename);
        }

        /**
         * Return a response with the PDF to show in the browser
         *
         * @static
         */
        public static function stream($filename = 'document.pdf')
        {
            /** @var \Barryvdh\DomPDF\PDF $instance */
            return $instance->stream($filename);
        }

        /**
         * Render the PDF
         *
         * @static
         */
        public static function render()
        {
            /** @var \Barryvdh\DomPDF\PDF $instance */
            return $instance->render();
        }

        /**
         * @param array<string> $pc
         * @static
         */
        public static function setEncryption($password, $ownerpassword = '', $pc = [])
        {
            /** @var \Barryvdh\DomPDF\PDF $instance */
            return $instance->setEncryption($password, $ownerpassword, $pc);
        }

            }
    /**
     * @method static BasePDF setBaseHost(string $baseHost)
     * @method static BasePDF setBasePath(string $basePath)
     * @method static BasePDF setCanvas(\Dompdf\Canvas $canvas)
     * @method static BasePDF setCallbacks(array<string, mixed> $callbacks)
     * @method static BasePDF setCss(\Dompdf\Css\Stylesheet $css)
     * @method static BasePDF setDefaultView(string $defaultView, array<string, mixed> $options)
     * @method static BasePDF setDom(\DOMDocument $dom)
     * @method static BasePDF setFontMetrics(\Dompdf\FontMetrics $fontMetrics)
     * @method static BasePDF setHttpContext(resource|array<string, mixed> $httpContext)
     * @method static BasePDF setPaper(string|float[] $paper, string $orientation = 'portrait')
     * @method static BasePDF setProtocol(string $protocol)
     * @method static BasePDF setTree(\Dompdf\Frame\FrameTree $tree)
     */
    class Pdf {
        /**
         * Get the DomPDF instance
         *
         * @static
         */
        public static function getDomPDF()
        {
            /** @var \Barryvdh\DomPDF\PDF $instance */
            return $instance->getDomPDF();
        }

        /**
         * Show or hide warnings
         *
         * @static
         */
        public static function setWarnings($warnings)
        {
            /** @var \Barryvdh\DomPDF\PDF $instance */
            return $instance->setWarnings($warnings);
        }

        /**
         * Load a HTML string
         *
         * @param string|null $encoding Not used yet
         * @static
         */
        public static function loadHTML($string, $encoding = null)
        {
            /** @var \Barryvdh\DomPDF\PDF $instance */
            return $instance->loadHTML($string, $encoding);
        }

        /**
         * Load a HTML file
         *
         * @static
         */
        public static function loadFile($file)
        {
            /** @var \Barryvdh\DomPDF\PDF $instance */
            return $instance->loadFile($file);
        }

        /**
         * Add metadata info
         *
         * @param array<string, string> $info
         * @static
         */
        public static function addInfo($info)
        {
            /** @var \Barryvdh\DomPDF\PDF $instance */
            return $instance->addInfo($info);
        }

        /**
         * Load a View and convert to HTML
         *
         * @param array<string, mixed> $data
         * @param array<string, mixed> $mergeData
         * @param string|null $encoding Not used yet
         * @static
         */
        public static function loadView($view, $data = [], $mergeData = [], $encoding = null)
        {
            /** @var \Barryvdh\DomPDF\PDF $instance */
            return $instance->loadView($view, $data, $mergeData, $encoding);
        }

        /**
         * Set/Change an option (or array of options) in Dompdf
         *
         * @param array<string, mixed>|string $attribute
         * @param null|mixed $value
         * @static
         */
        public static function setOption($attribute, $value = null)
        {
            /** @var \Barryvdh\DomPDF\PDF $instance */
            return $instance->setOption($attribute, $value);
        }

        /**
         * Replace all the Options from DomPDF
         *
         * @param array<string, mixed> $options
         * @static
         */
        public static function setOptions($options, $mergeWithDefaults = false)
        {
            /** @var \Barryvdh\DomPDF\PDF $instance */
            return $instance->setOptions($options, $mergeWithDefaults);
        }

        /**
         * Output the PDF as a string.
         * 
         * The options parameter controls the output. Accepted options are:
         * 
         * 'compress' = > 1 or 0 - apply content stream compression, this is
         *    on (1) by default
         *
         * @param array<string, int> $options
         * @return string The rendered PDF as string
         * @static
         */
        public static function output($options = [])
        {
            /** @var \Barryvdh\DomPDF\PDF $instance */
            return $instance->output($options);
        }

        /**
         * Save the PDF to a file
         *
         * @static
         */
        public static function save($filename, $disk = null)
        {
            /** @var \Barryvdh\DomPDF\PDF $instance */
            return $instance->save($filename, $disk);
        }

        /**
         * Make the PDF downloadable by the user
         *
         * @static
         */
        public static function download($filename = 'document.pdf')
        {
            /** @var \Barryvdh\DomPDF\PDF $instance */
            return $instance->download($filename);
        }

        /**
         * Return a response with the PDF to show in the browser
         *
         * @static
         */
        public static function stream($filename = 'document.pdf')
        {
            /** @var \Barryvdh\DomPDF\PDF $instance */
            return $instance->stream($filename);
        }

        /**
         * Render the PDF
         *
         * @static
         */
        public static function render()
        {
            /** @var \Barryvdh\DomPDF\PDF $instance */
            return $instance->render();
        }

        /**
         * @param array<string> $pc
         * @static
         */
        public static function setEncryption($password, $ownerpassword = '', $pc = [])
        {
            /** @var \Barryvdh\DomPDF\PDF $instance */
            return $instance->setEncryption($password, $ownerpassword, $pc);
        }

            }
    }

namespace BotMan\BotMan\Facades {
    /**
     */
    class BotMan {
        /**
         * Set a fallback message to use if no listener matches.
         *
         * @param callable $callback
         * @static
         */
        public static function fallback($callback)
        {
            /** @var \BotMan\BotMan\BotMan $instance */
            return $instance->fallback($callback);
        }

        /**
         * @param string $name The Driver name or class
         * @static
         */
        public static function loadDriver($name)
        {
            /** @var \BotMan\BotMan\BotMan $instance */
            return $instance->loadDriver($name);
        }

        /**
         * @param \BotMan\BotMan\Interfaces\DriverInterface $driver
         * @static
         */
        public static function setDriver($driver)
        {
            /** @var \BotMan\BotMan\BotMan $instance */
            return $instance->setDriver($driver);
        }

        /**
         * @return \BotMan\BotMan\Interfaces\DriverInterface
         * @static
         */
        public static function getDriver()
        {
            /** @var \BotMan\BotMan\BotMan $instance */
            return $instance->getDriver();
        }

        /**
         * @param \Psr\Container\ContainerInterface $container
         * @static
         */
        public static function setContainer($container)
        {
            /** @var \BotMan\BotMan\BotMan $instance */
            return $instance->setContainer($container);
        }

        /**
         * Retrieve the chat message.
         *
         * @return array
         * @static
         */
        public static function getMessages()
        {
            /** @var \BotMan\BotMan\BotMan $instance */
            return $instance->getMessages();
        }

        /**
         * Retrieve the chat message that are sent from bots.
         *
         * @return array
         * @static
         */
        public static function getBotMessages()
        {
            /** @var \BotMan\BotMan\BotMan $instance */
            return $instance->getBotMessages();
        }

        /**
         * @return \BotMan\BotMan\Messages\Incoming\Answer
         * @static
         */
        public static function getConversationAnswer()
        {
            /** @var \BotMan\BotMan\BotMan $instance */
            return $instance->getConversationAnswer();
        }

        /**
         * @param bool $running
         * @return bool
         * @static
         */
        public static function runsOnSocket($running = null)
        {
            /** @var \BotMan\BotMan\BotMan $instance */
            return $instance->runsOnSocket($running);
        }

        /**
         * @return \BotMan\BotMan\Interfaces\UserInterface
         * @static
         */
        public static function getUser()
        {
            /** @var \BotMan\BotMan\BotMan $instance */
            return $instance->getUser();
        }

        /**
         * @param array|string $pattern the pattern to listen for
         * @param \Closure|string $callback the callback to execute. Either a closure or a Class@method notation
         * @param string $in the channel type to listen to (either direct message or public channel)
         * @return \BotMan\BotMan\Commands\Command
         * @static
         */
        public static function hears($pattern, $callback, $in = null)
        {
            /** @var \BotMan\BotMan\BotMan $instance */
            return $instance->hears($pattern, $callback, $in);
        }

        /**
         * Listen for messaging service events.
         *
         * @param array|string $names
         * @param \Closure|string $callback
         * @static
         */
        public static function on($names, $callback)
        {
            /** @var \BotMan\BotMan\BotMan $instance */
            return $instance->on($names, $callback);
        }

        /**
         * Listening for image files.
         *
         * @param $callback
         * @return \BotMan\BotMan\Commands\Command
         * @static
         */
        public static function receivesImages($callback)
        {
            /** @var \BotMan\BotMan\BotMan $instance */
            return $instance->receivesImages($callback);
        }

        /**
         * Listening for video files.
         *
         * @param $callback
         * @return \BotMan\BotMan\Commands\Command
         * @static
         */
        public static function receivesVideos($callback)
        {
            /** @var \BotMan\BotMan\BotMan $instance */
            return $instance->receivesVideos($callback);
        }

        /**
         * Listening for audio files.
         *
         * @param $callback
         * @return \BotMan\BotMan\Commands\Command
         * @static
         */
        public static function receivesAudio($callback)
        {
            /** @var \BotMan\BotMan\BotMan $instance */
            return $instance->receivesAudio($callback);
        }

        /**
         * Listening for location attachment.
         *
         * @param $callback
         * @return \BotMan\BotMan\Commands\Command
         * @static
         */
        public static function receivesLocation($callback)
        {
            /** @var \BotMan\BotMan\BotMan $instance */
            return $instance->receivesLocation($callback);
        }

        /**
         * Listening for contact attachment.
         *
         * @param $callback
         * @return \BotMan\BotMan\Commands\Command
         * @static
         */
        public static function receivesContact($callback)
        {
            /** @var \BotMan\BotMan\BotMan $instance */
            return $instance->receivesContact($callback);
        }

        /**
         * Listening for files attachment.
         *
         * @param $callback
         * @return \BotMan\BotMan\Commands\Command
         * @static
         */
        public static function receivesFiles($callback)
        {
            /** @var \BotMan\BotMan\BotMan $instance */
            return $instance->receivesFiles($callback);
        }

        /**
         * Create a command group with shared attributes.
         *
         * @param array $attributes
         * @param \Closure $callback
         * @static
         */
        public static function group($attributes, $callback)
        {
            /** @var \BotMan\BotMan\BotMan $instance */
            return $instance->group($attributes, $callback);
        }

        /**
         * Try to match messages with the ones we should
         * listen to.
         *
         * @static
         */
        public static function listen()
        {
            /** @var \BotMan\BotMan\BotMan $instance */
            return $instance->listen();
        }

        /**
         * @param string|\BotMan\BotMan\Messages\Outgoing\Question|\BotMan\BotMan\Messages\Outgoing\OutgoingMessage $message
         * @param string|array $recipients
         * @param string|\BotMan\BotMan\Interfaces\DriverInterface|null $driver
         * @param array $additionalParameters
         * @return \Symfony\Component\HttpFoundation\Response
         * @throws BotManException
         * @static
         */
        public static function say($message, $recipients, $driver = null, $additionalParameters = [])
        {
            /** @var \BotMan\BotMan\BotMan $instance */
            return $instance->say($message, $recipients, $driver, $additionalParameters);
        }

        /**
         * @param string|\BotMan\BotMan\Messages\Outgoing\Question $question
         * @param array|\Closure $next
         * @param array $additionalParameters
         * @param null|string $recipient
         * @param null|string $driver
         * @return \Symfony\Component\HttpFoundation\Response
         * @static
         */
        public static function ask($question, $next, $additionalParameters = [], $recipient = null, $driver = null)
        {
            /** @var \BotMan\BotMan\BotMan $instance */
            return $instance->ask($question, $next, $additionalParameters, $recipient, $driver);
        }

        /**
         * @return \BotMan\BotMan\BotMan
         * @static
         */
        public static function types()
        {
            /** @var \BotMan\BotMan\BotMan $instance */
            return $instance->types();
        }

        /**
         * @param float $seconds Number of seconds to wait
         * @return \BotMan\BotMan\BotMan
         * @static
         */
        public static function typesAndWaits($seconds)
        {
            /** @var \BotMan\BotMan\BotMan $instance */
            return $instance->typesAndWaits($seconds);
        }

        /**
         * Low-level method to perform driver specific API requests.
         *
         * @param string $endpoint
         * @param array $additionalParameters
         * @return \BotMan\BotMan\BotMan
         * @throws BadMethodCallException
         * @static
         */
        public static function sendRequest($endpoint, $additionalParameters = [])
        {
            /** @var \BotMan\BotMan\BotMan $instance */
            return $instance->sendRequest($endpoint, $additionalParameters);
        }

        /**
         * @param string|\BotMan\BotMan\Messages\Outgoing\OutgoingMessage|\BotMan\BotMan\Messages\Outgoing\Question $message
         * @param array $additionalParameters
         * @return mixed
         * @static
         */
        public static function reply($message, $additionalParameters = [])
        {
            /** @var \BotMan\BotMan\BotMan $instance */
            return $instance->reply($message, $additionalParameters);
        }

        /**
         * @param $payload
         * @return mixed
         * @static
         */
        public static function sendPayload($payload)
        {
            /** @var \BotMan\BotMan\BotMan $instance */
            return $instance->sendPayload($payload);
        }

        /**
         * Return a random message.
         *
         * @param array $messages
         * @return \BotMan\BotMan\BotMan
         * @static
         */
        public static function randomReply($messages)
        {
            /** @var \BotMan\BotMan\BotMan $instance */
            return $instance->randomReply($messages);
        }

        /**
         * @return array
         * @static
         */
        public static function getMatches()
        {
            /** @var \BotMan\BotMan\BotMan $instance */
            return $instance->getMatches();
        }

        /**
         * @return \BotMan\BotMan\Messages\Incoming\IncomingMessage
         * @static
         */
        public static function getMessage()
        {
            /** @var \BotMan\BotMan\BotMan $instance */
            return $instance->getMessage();
        }

        /**
         * @return \BotMan\BotMan\Messages\Outgoing\OutgoingMessage|\BotMan\BotMan\Messages\Outgoing\Question
         * @static
         */
        public static function getOutgoingMessage()
        {
            /** @var \BotMan\BotMan\BotMan $instance */
            return $instance->getOutgoingMessage();
        }

        /**
         * @return \BotMan\BotMan\Storage
         * @static
         */
        public static function userStorage()
        {
            /** @var \BotMan\BotMan\BotMan $instance */
            return $instance->userStorage();
        }

        /**
         * @return \BotMan\BotMan\Storage
         * @static
         */
        public static function channelStorage()
        {
            /** @var \BotMan\BotMan\BotMan $instance */
            return $instance->channelStorage();
        }

        /**
         * @return \BotMan\BotMan\Storage
         * @static
         */
        public static function driverStorage()
        {
            /** @var \BotMan\BotMan\BotMan $instance */
            return $instance->driverStorage();
        }

        /**
         * @param \BotMan\BotMan\Messages\Conversations\Conversation $instance
         * @param null|string $recipient
         * @param null|string $driver
         * @static
         */
        public static function startConversation($instance, $recipient = null, $driver = null)
        {
            /** @var \BotMan\BotMan\BotMan $instance */
            return $instance->startConversation($instance, $recipient, $driver);
        }

        /**
         * @param \BotMan\BotMan\Messages\Conversations\Conversation $instance
         * @param array|\Closure $next
         * @param string|\BotMan\BotMan\Messages\Outgoing\Question $question
         * @param array $additionalParameters
         * @static
         */
        public static function storeConversation($instance, $next, $question = null, $additionalParameters = [])
        {
            /** @var \BotMan\BotMan\BotMan $instance */
            return $instance->storeConversation($instance, $next, $question, $additionalParameters);
        }

        /**
         * Get a stored conversation array from the cache for a given message.
         *
         * @param null|\BotMan\BotMan\Messages\Incoming\IncomingMessage $message
         * @return array
         * @static
         */
        public static function getStoredConversation($message = null)
        {
            /** @var \BotMan\BotMan\BotMan $instance */
            return $instance->getStoredConversation($message);
        }

        /**
         * Touch and update the current conversation.
         *
         * @return void
         * @static
         */
        public static function touchCurrentConversation()
        {
            /** @var \BotMan\BotMan\BotMan $instance */
            $instance->touchCurrentConversation();
        }

        /**
         * Get the question that was asked in the currently stored conversation
         * for a given message.
         *
         * @param null|\BotMan\BotMan\Messages\Incoming\IncomingMessage $message
         * @return string|\BotMan\BotMan\Messages\Outgoing\Question
         * @static
         */
        public static function getStoredConversationQuestion($message = null)
        {
            /** @var \BotMan\BotMan\BotMan $instance */
            return $instance->getStoredConversationQuestion($message);
        }

        /**
         * Remove a stored conversation array from the cache for a given message.
         *
         * @param null|\BotMan\BotMan\Messages\Incoming\IncomingMessage $message
         * @static
         */
        public static function removeStoredConversation($message = null)
        {
            /** @var \BotMan\BotMan\BotMan $instance */
            return $instance->removeStoredConversation($message);
        }

        /**
         * @param \Closure $closure
         * @return string
         * @static
         */
        public static function serializeClosure($closure)
        {
            /** @var \BotMan\BotMan\BotMan $instance */
            return $instance->serializeClosure($closure);
        }

        /**
         * Look for active conversations and clear the payload
         * if a conversation is found.
         *
         * @static
         */
        public static function loadActiveConversation()
        {
            /** @var \BotMan\BotMan\BotMan $instance */
            return $instance->loadActiveConversation();
        }

        /**
         * Register a custom exception handler.
         *
         * @param string $exception
         * @param callable $closure
         * @static
         */
        public static function exception($exception, $closure)
        {
            /** @var \BotMan\BotMan\BotMan $instance */
            return $instance->exception($exception, $closure);
        }

        /**
         * @param \BotMan\BotMan\Interfaces\ExceptionHandlerInterface $exceptionHandler
         * @static
         */
        public static function setExceptionHandler($exceptionHandler)
        {
            /** @var \BotMan\BotMan\BotMan $instance */
            return $instance->setExceptionHandler($exceptionHandler);
        }

            }
    }

namespace Laravel\Pulse\Facades {
    /**
     * @method static void store(\Illuminate\Support\Collection $items)
     * @method static void trim()
     * @method static void purge(array $types = null)
     * @method static \Illuminate\Support\Collection values(string $type, array $keys = null)
     * @method static \Illuminate\Support\Collection graph(array $types, string $aggregate, \Carbon\CarbonInterval $interval)
     * @method static \Illuminate\Support\Collection aggregate(string $type, string|array $aggregates, \Carbon\CarbonInterval $interval, string|null $orderBy = null, string $direction = 'desc', int $limit = 101)
     * @method static \Illuminate\Support\Collection aggregateTypes(string|array $types, string $aggregate, \Carbon\CarbonInterval $interval, string|null $orderBy = null, string $direction = 'desc', int $limit = 101)
     * @method static float|\Illuminate\Support\Collection aggregateTotal(string|array $types, string $aggregate, \Carbon\CarbonInterval $interval)
     * @see \Laravel\Pulse\Pulse
     */
    class Pulse {
        /**
         * Register a recorder.
         *
         * @param array<class-string, array<mixed>|bool> $recorders
         * @static
         */
        public static function register($recorders)
        {
            /** @var \Laravel\Pulse\Pulse $instance */
            return $instance->register($recorders);
        }

        /**
         * Record an entry.
         *
         * @static
         */
        public static function record($type, $key, $value = null, $timestamp = null)
        {
            /** @var \Laravel\Pulse\Pulse $instance */
            return $instance->record($type, $key, $value, $timestamp);
        }

        /**
         * Record a value.
         *
         * @static
         */
        public static function set($type, $key, $value, $timestamp = null)
        {
            /** @var \Laravel\Pulse\Pulse $instance */
            return $instance->set($type, $key, $value, $timestamp);
        }

        /**
         * Lazily capture items.
         *
         * @static
         */
        public static function lazy($closure)
        {
            /** @var \Laravel\Pulse\Pulse $instance */
            return $instance->lazy($closure);
        }

        /**
         * Report the throwable exception to Pulse.
         *
         * @static
         */
        public static function report($e)
        {
            /** @var \Laravel\Pulse\Pulse $instance */
            return $instance->report($e);
        }

        /**
         * Start recording.
         *
         * @static
         */
        public static function startRecording()
        {
            /** @var \Laravel\Pulse\Pulse $instance */
            return $instance->startRecording();
        }

        /**
         * Stop recording.
         *
         * @static
         */
        public static function stopRecording()
        {
            /** @var \Laravel\Pulse\Pulse $instance */
            return $instance->stopRecording();
        }

        /**
         * Execute the given callback without recording.
         *
         * @template TReturn
         * @param (callable(): TReturn) $callback
         * @return TReturn
         * @static
         */
        public static function ignore($callback)
        {
            /** @var \Laravel\Pulse\Pulse $instance */
            return $instance->ignore($callback);
        }

        /**
         * Flush the queue.
         *
         * @static
         */
        public static function flush()
        {
            /** @var \Laravel\Pulse\Pulse $instance */
            return $instance->flush();
        }

        /**
         * Filter items before storage using the provided filter.
         *
         * @param (callable(\Laravel\Pulse\Entry|\Laravel\Pulse\Value): bool) $filter
         * @static
         */
        public static function filter($filter)
        {
            /** @var \Laravel\Pulse\Pulse $instance */
            return $instance->filter($filter);
        }

        /**
         * Ingest the entries.
         *
         * @static
         */
        public static function ingest()
        {
            /** @var \Laravel\Pulse\Pulse $instance */
            return $instance->ingest();
        }

        /**
         * Digest the entries.
         *
         * @static
         */
        public static function digest()
        {
            /** @var \Laravel\Pulse\Pulse $instance */
            return $instance->digest();
        }

        /**
         * Determine if Pulse wants to ingest entries.
         *
         * @static
         */
        public static function wantsIngesting()
        {
            /** @var \Laravel\Pulse\Pulse $instance */
            return $instance->wantsIngesting();
        }

        /**
         * Get the registered recorders.
         *
         * @return \Illuminate\Support\Collection<int, object>
         * @static
         */
        public static function recorders()
        {
            /** @var \Laravel\Pulse\Pulse $instance */
            return $instance->recorders();
        }

        /**
         * Resolve the user details for the given user IDs.
         *
         * @param \Illuminate\Support\Collection<int, string> $keys
         * @static
         */
        public static function resolveUsers($keys)
        {
            /** @var \Laravel\Pulse\Pulse $instance */
            return $instance->resolveUsers($keys);
        }

        /**
         * Resolve the users' details using the given closure.
         *
         * @deprecated
         * @param callable(\Illuminate\Support\Collection<int, mixed>):  ?iterable<int|string, array{name: string, email?: ?string, avatar?: ?string, extra?: ?string}>  $callback
         * @static
         */
        public static function users($callback)
        {
            /** @var \Laravel\Pulse\Pulse $instance */
            return $instance->users($callback);
        }

        /**
         * Resolve the user's details using the given closure.
         *
         * @param callable(\Illuminate\Contracts\Auth\Authenticatable):  array{name: string, email?: ?string, avatar?: ?string, extra?: ?string}  $callback
         * @static
         */
        public static function user($callback)
        {
            /** @var \Laravel\Pulse\Pulse $instance */
            return $instance->user($callback);
        }

        /**
         * Get the authenticated user ID resolver.
         *
         * @return callable(): (int|string|null)
         * @static
         */
        public static function authenticatedUserIdResolver()
        {
            /** @var \Laravel\Pulse\Pulse $instance */
            return $instance->authenticatedUserIdResolver();
        }

        /**
         * Resolve the authenticated user id.
         *
         * @static
         */
        public static function resolveAuthenticatedUserId()
        {
            /** @var \Laravel\Pulse\Pulse $instance */
            return $instance->resolveAuthenticatedUserId();
        }

        /**
         * Remember the authenticated user's ID.
         *
         * @static
         */
        public static function rememberUser($user)
        {
            /** @var \Laravel\Pulse\Pulse $instance */
            return $instance->rememberUser($user);
        }

        /**
         * Register or return CSS for the Pulse dashboard.
         *
         * @param string|\Illuminate\Contracts\Support\Htmlable|list<string|Htmlable>|null $css
         * @static
         */
        public static function css($css = null)
        {
            /** @var \Laravel\Pulse\Pulse $instance */
            return $instance->css($css);
        }

        /**
         * Return the compiled JavaScript from the vendor directory.
         *
         * @static
         */
        public static function js()
        {
            /** @var \Laravel\Pulse\Pulse $instance */
            return $instance->js();
        }

        /**
         * The default "vendor" cache keys that should be ignored by Pulse.
         *
         * @return list<string>
         * @static
         */
        public static function defaultVendorCacheKeys()
        {
            return \Laravel\Pulse\Pulse::defaultVendorCacheKeys();
        }

        /**
         * Determine if Pulse may register routes.
         *
         * @static
         */
        public static function registersRoutes()
        {
            /** @var \Laravel\Pulse\Pulse $instance */
            return $instance->registersRoutes();
        }

        /**
         * Configure Pulse to not register its routes.
         *
         * @static
         */
        public static function ignoreRoutes()
        {
            /** @var \Laravel\Pulse\Pulse $instance */
            return $instance->ignoreRoutes();
        }

        /**
         * Handle exceptions using the given callback.
         *
         * @param (callable(\Throwable): mixed) $callback
         * @static
         */
        public static function handleExceptionsUsing($callback)
        {
            /** @var \Laravel\Pulse\Pulse $instance */
            return $instance->handleExceptionsUsing($callback);
        }

        /**
         * Execute the given callback handling any exceptions.
         *
         * @template TReturn
         * @param (callable(): TReturn) $callback
         * @return TReturn|null
         * @static
         */
        public static function rescue($callback)
        {
            /** @var \Laravel\Pulse\Pulse $instance */
            return $instance->rescue($callback);
        }

        /**
         * Set the container instance.
         *
         * @param \Illuminate\Contracts\Foundation\Application $container
         * @return \Laravel\Pulse\Pulse
         * @static
         */
        public static function setContainer($container)
        {
            /** @var \Laravel\Pulse\Pulse $instance */
            return $instance->setContainer($container);
        }

        /**
         * Configure the class after resolving.
         *
         * @static
         */
        public static function afterResolving($app, $class, $callback)
        {
            /** @var \Laravel\Pulse\Pulse $instance */
            return $instance->afterResolving($app, $class, $callback);
        }

            }
    }

namespace Livewire {
    /**
     * @see \Livewire\LivewireManager
     */
    class Livewire {
        /**
         * @static
         */
        public static function setProvider($provider)
        {
            /** @var \Livewire\LivewireManager $instance */
            return $instance->setProvider($provider);
        }

        /**
         * @static
         */
        public static function provide($callback)
        {
            /** @var \Livewire\LivewireManager $instance */
            return $instance->provide($callback);
        }

        /**
         * @static
         */
        public static function component($name, $class = null)
        {
            /** @var \Livewire\LivewireManager $instance */
            return $instance->component($name, $class);
        }

        /**
         * @static
         */
        public static function componentHook($hook)
        {
            /** @var \Livewire\LivewireManager $instance */
            return $instance->componentHook($hook);
        }

        /**
         * @static
         */
        public static function propertySynthesizer($synth)
        {
            /** @var \Livewire\LivewireManager $instance */
            return $instance->propertySynthesizer($synth);
        }

        /**
         * @static
         */
        public static function directive($name, $callback)
        {
            /** @var \Livewire\LivewireManager $instance */
            return $instance->directive($name, $callback);
        }

        /**
         * @static
         */
        public static function precompiler($callback)
        {
            /** @var \Livewire\LivewireManager $instance */
            return $instance->precompiler($callback);
        }

        /**
         * @static
         */
        public static function new($name, $id = null)
        {
            /** @var \Livewire\LivewireManager $instance */
            return $instance->new($name, $id);
        }

        /**
         * @static
         */
        public static function isDiscoverable($componentNameOrClass)
        {
            /** @var \Livewire\LivewireManager $instance */
            return $instance->isDiscoverable($componentNameOrClass);
        }

        /**
         * @static
         */
        public static function resolveMissingComponent($resolver)
        {
            /** @var \Livewire\LivewireManager $instance */
            return $instance->resolveMissingComponent($resolver);
        }

        /**
         * @static
         */
        public static function mount($name, $params = [], $key = null)
        {
            /** @var \Livewire\LivewireManager $instance */
            return $instance->mount($name, $params, $key);
        }

        /**
         * @static
         */
        public static function snapshot($component, $context = null)
        {
            /** @var \Livewire\LivewireManager $instance */
            return $instance->snapshot($component, $context);
        }

        /**
         * @static
         */
        public static function fromSnapshot($snapshot)
        {
            /** @var \Livewire\LivewireManager $instance */
            return $instance->fromSnapshot($snapshot);
        }

        /**
         * @static
         */
        public static function listen($eventName, $callback)
        {
            /** @var \Livewire\LivewireManager $instance */
            return $instance->listen($eventName, $callback);
        }

        /**
         * @static
         */
        public static function current()
        {
            /** @var \Livewire\LivewireManager $instance */
            return $instance->current();
        }

        /**
         * @static
         */
        public static function findSynth($keyOrTarget, $component)
        {
            /** @var \Livewire\LivewireManager $instance */
            return $instance->findSynth($keyOrTarget, $component);
        }

        /**
         * @static
         */
        public static function update($snapshot, $diff, $calls)
        {
            /** @var \Livewire\LivewireManager $instance */
            return $instance->update($snapshot, $diff, $calls);
        }

        /**
         * @static
         */
        public static function updateProperty($component, $path, $value)
        {
            /** @var \Livewire\LivewireManager $instance */
            return $instance->updateProperty($component, $path, $value);
        }

        /**
         * @static
         */
        public static function isLivewireRequest()
        {
            /** @var \Livewire\LivewireManager $instance */
            return $instance->isLivewireRequest();
        }

        /**
         * @static
         */
        public static function componentHasBeenRendered()
        {
            /** @var \Livewire\LivewireManager $instance */
            return $instance->componentHasBeenRendered();
        }

        /**
         * @static
         */
        public static function forceAssetInjection()
        {
            /** @var \Livewire\LivewireManager $instance */
            return $instance->forceAssetInjection();
        }

        /**
         * @static
         */
        public static function setUpdateRoute($callback)
        {
            /** @var \Livewire\LivewireManager $instance */
            return $instance->setUpdateRoute($callback);
        }

        /**
         * @static
         */
        public static function getUpdateUri()
        {
            /** @var \Livewire\LivewireManager $instance */
            return $instance->getUpdateUri();
        }

        /**
         * @static
         */
        public static function setScriptRoute($callback)
        {
            /** @var \Livewire\LivewireManager $instance */
            return $instance->setScriptRoute($callback);
        }

        /**
         * @static
         */
        public static function useScriptTagAttributes($attributes)
        {
            /** @var \Livewire\LivewireManager $instance */
            return $instance->useScriptTagAttributes($attributes);
        }

        /**
         * @static
         */
        public static function withUrlParams($params)
        {
            /** @var \Livewire\LivewireManager $instance */
            return $instance->withUrlParams($params);
        }

        /**
         * @static
         */
        public static function withQueryParams($params)
        {
            /** @var \Livewire\LivewireManager $instance */
            return $instance->withQueryParams($params);
        }

        /**
         * @static
         */
        public static function withCookie($name, $value)
        {
            /** @var \Livewire\LivewireManager $instance */
            return $instance->withCookie($name, $value);
        }

        /**
         * @static
         */
        public static function withCookies($cookies)
        {
            /** @var \Livewire\LivewireManager $instance */
            return $instance->withCookies($cookies);
        }

        /**
         * @static
         */
        public static function withHeaders($headers)
        {
            /** @var \Livewire\LivewireManager $instance */
            return $instance->withHeaders($headers);
        }

        /**
         * @static
         */
        public static function withoutLazyLoading()
        {
            /** @var \Livewire\LivewireManager $instance */
            return $instance->withoutLazyLoading();
        }

        /**
         * @static
         */
        public static function test($name, $params = [])
        {
            /** @var \Livewire\LivewireManager $instance */
            return $instance->test($name, $params);
        }

        /**
         * @static
         */
        public static function visit($name)
        {
            /** @var \Livewire\LivewireManager $instance */
            return $instance->visit($name);
        }

        /**
         * @static
         */
        public static function actingAs($user, $driver = null)
        {
            /** @var \Livewire\LivewireManager $instance */
            return $instance->actingAs($user, $driver);
        }

        /**
         * @static
         */
        public static function isRunningServerless()
        {
            /** @var \Livewire\LivewireManager $instance */
            return $instance->isRunningServerless();
        }

        /**
         * @static
         */
        public static function addPersistentMiddleware($middleware)
        {
            /** @var \Livewire\LivewireManager $instance */
            return $instance->addPersistentMiddleware($middleware);
        }

        /**
         * @static
         */
        public static function setPersistentMiddleware($middleware)
        {
            /** @var \Livewire\LivewireManager $instance */
            return $instance->setPersistentMiddleware($middleware);
        }

        /**
         * @static
         */
        public static function getPersistentMiddleware()
        {
            /** @var \Livewire\LivewireManager $instance */
            return $instance->getPersistentMiddleware();
        }

        /**
         * @static
         */
        public static function flushState()
        {
            /** @var \Livewire\LivewireManager $instance */
            return $instance->flushState();
        }

        /**
         * @static
         */
        public static function originalUrl()
        {
            /** @var \Livewire\LivewireManager $instance */
            return $instance->originalUrl();
        }

        /**
         * @static
         */
        public static function originalPath()
        {
            /** @var \Livewire\LivewireManager $instance */
            return $instance->originalPath();
        }

        /**
         * @static
         */
        public static function originalMethod()
        {
            /** @var \Livewire\LivewireManager $instance */
            return $instance->originalMethod();
        }

            }
    }

namespace Milon\Barcode\Facades {
    /**
     * @method static string|false getBarcodePNGPath(string $code, string $type, int|float $w = 2, int|float $h = 30, array $color = [0, 0, 0], bool $showCode = false)
     * @method static \Illuminate\Contracts\Routing\UrlGenerator|string getBarcodePNGUri(string $code, string $type, int|float $w = 2, int|float $h = 30, array $color = [0, 0, 0])
     */
    class DNS1DFacade {
        /**
         * Return a SVG string representation of barcode.
         *
         * @param $code (string) code to print
         * @param $type (string) type of barcode: <ul><li>C39 : CODE 39 - ANSI MH10.8M-1983 - USD-3 - 3 of 9.</li><li>C39+ : CODE 39 with checksum</li><li>C39E : CODE 39 EXTENDED</li><li>C39E+ : CODE 39 EXTENDED + CHECKSUM</li><li>C93 : CODE 93 - USS-93</li><li>S25 : Standard 2 of 5</li><li>S25+ : Standard 2 of 5 + CHECKSUM</li><li>I25 : Interleaved 2 of 5</li><li>I25+ : Interleaved 2 of 5 + CHECKSUM</li><li>C128 : CODE 128</li><li>C128A : CODE 128 A</li><li>C128B : CODE 128 B</li><li>C128C : CODE 128 C</li><li>EAN2 : 2-Digits UPC-Based Extention</li><li>EAN5 : 5-Digits UPC-Based Extention</li><li>EAN8 : EAN 8</li><li>EAN13 : EAN 13</li><li>UPCA : UPC-A</li><li>UPCE : UPC-E</li><li>MSI : MSI (Variation of Plessey code)</li><li>MSI+ : MSI + CHECKSUM (modulo 11)</li><li>POSTNET : POSTNET</li><li>PLANET : PLANET</li><li>RMS4CC : RMS4CC (Royal Mail 4-state Customer Code) - CBC (Customer Bar Code)</li><li>KIX : KIX (Klant index - Customer index)</li><li>IMB: Intelligent Mail Barcode - Onecode - USPS-B-3200</li><li>CODABAR : CODABAR</li><li>CODE11 : CODE 11</li><li>PHARMA : PHARMACODE</li><li>PHARMA2T : PHARMACODE TWO-TRACKS</li></ul>
         * @param $w (int) Minimum width of a single bar in user units.
         * @param $h (int) Height of barcode in user units.
         * @param $color (string) Foreground color (in SVG format) for bar elements (background is transparent).
         * @return string SVG code.
         * @protected
         * @static
         */
        public static function getBarcodeSVG($code, $type, $w = 2, $h = 30, $color = 'black', $showCode = true, $inline = false)
        {
            /** @var \Milon\Barcode\DNS1D $instance */
            return $instance->getBarcodeSVG($code, $type, $w, $h, $color, $showCode, $inline);
        }

        /**
         * Return an HTML representation of barcode.
         *
         * @param $code (string) code to print
         * @param $type (string) type of barcode: <ul><li>C39 : CODE 39 - ANSI MH10.8M-1983 - USD-3 - 3 of 9.</li><li>C39+ : CODE 39 with checksum</li><li>C39E : CODE 39 EXTENDED</li><li>C39E+ : CODE 39 EXTENDED + CHECKSUM</li><li>C93 : CODE 93 - USS-93</li><li>S25 : Standard 2 of 5</li><li>S25+ : Standard 2 of 5 + CHECKSUM</li><li>I25 : Interleaved 2 of 5</li><li>I25+ : Interleaved 2 of 5 + CHECKSUM</li><li>C128 : CODE 128</li><li>C128A : CODE 128 A</li><li>C128B : CODE 128 B</li><li>C128C : CODE 128 C</li><li>EAN2 : 2-Digits UPC-Based Extention</li><li>EAN5 : 5-Digits UPC-Based Extention</li><li>EAN8 : EAN 8</li><li>EAN13 : EAN 13</li><li>UPCA : UPC-A</li><li>UPCE : UPC-E</li><li>MSI : MSI (Variation of Plessey code)</li><li>MSI+ : MSI + CHECKSUM (modulo 11)</li><li>POSTNET : POSTNET</li><li>PLANET : PLANET</li><li>RMS4CC : RMS4CC (Royal Mail 4-state Customer Code) - CBC (Customer Bar Code)</li><li>KIX : KIX (Klant index - Customer index)</li><li>IMB: Intelligent Mail Barcode - Onecode - USPS-B-3200</li><li>CODABAR : CODABAR</li><li>CODE11 : CODE 11</li><li>PHARMA : PHARMACODE</li><li>PHARMA2T : PHARMACODE TWO-TRACKS</li></ul>
         * @param $w (int) Width of a single bar element in pixels.
         * @param $h (int) Height of a single bar element in pixels.
         * @param $color (string) Foreground color for bar elements (background is transparent).
         * @param $showcode (int) font size of the shown code, default 0.
         * @return string HTML code.
         * @protected
         * @static
         */
        public static function getBarcodeHTML($code, $type, $w = 2, $h = 30, $color = 'black', $showCode = 0)
        {
            /** @var \Milon\Barcode\DNS1D $instance */
            return $instance->getBarcodeHTML($code, $type, $w, $h, $color, $showCode);
        }

        /**
         * Return a PNG image representation of barcode (requires GD or Imagick library).
         *
         * @param $code (string) code to print
         * @param $type (string) type of barcode: <ul><li>C39 : CODE 39 - ANSI MH10.8M-1983 - USD-3 - 3 of 9.</li><li>C39+ : CODE 39 with checksum</li><li>C39E : CODE 39 EXTENDED</li><li>C39E+ : CODE 39 EXTENDED + CHECKSUM</li><li>C93 : CODE 93 - USS-93</li><li>S25 : Standard 2 of 5</li><li>S25+ : Standard 2 of 5 + CHECKSUM</li><li>I25 : Interleaved 2 of 5</li><li>I25+ : Interleaved 2 of 5 + CHECKSUM</li><li>C128 : CODE 128</li><li>C128A : CODE 128 A</li><li>C128B : CODE 128 B</li><li>C128C : CODE 128 C</li><li>EAN2 : 2-Digits UPC-Based Extention</li><li>EAN5 : 5-Digits UPC-Based Extention</li><li>EAN8 : EAN 8</li><li>EAN13 : EAN 13</li><li>UPCA : UPC-A</li><li>UPCE : UPC-E</li><li>MSI : MSI (Variation of Plessey code)</li><li>MSI+ : MSI + CHECKSUM (modulo 11)</li><li>POSTNET : POSTNET</li><li>PLANET : PLANET</li><li>RMS4CC : RMS4CC (Royal Mail 4-state Customer Code) - CBC (Customer Bar Code)</li><li>KIX : KIX (Klant index - Customer index)</li><li>IMB: Intelligent Mail Barcode - Onecode - USPS-B-3200</li><li>CODABAR : CODABAR</li><li>CODE11 : CODE 11</li><li>PHARMA : PHARMACODE</li><li>PHARMA2T : PHARMACODE TWO-TRACKS</li></ul>
         * @param $w (int) Width of a single bar element in pixels.
         * @param $h (int) Height of a single bar element in pixels.
         * @param $color (array) RGB (0-255) foreground color for bar elements (background is transparent).
         * @return string|false in case of error.
         * @protected
         * @static
         */
        public static function getBarcodePNG($code, $type, $w = 2, $h = 30, $color = [], $showCode = false)
        {
            /** @var \Milon\Barcode\DNS1D $instance */
            return $instance->getBarcodePNG($code, $type, $w, $h, $color, $showCode);
        }

        /**
         * @static
         */
        public static function setStorPath($path)
        {
            /** @var \Milon\Barcode\DNS1D $instance */
            return $instance->setStorPath($path);
        }

        /**
         * Return a JPG image representation of barcode (requires GD or Imagick library).
         *
         * @param $code (string) code to print
         * @param $type (string) type of barcode: <ul><li>C39 : CODE 39 - ANSI MH10.8M-1983 - USD-3 - 3 of 9.</li><li>C39+ : CODE 39 with checksum</li><li>C39E : CODE 39 EXTENDED</li><li>C39E+ : CODE 39 EXTENDED + CHECKSUM</li><li>C93 : CODE 93 - USS-93</li><li>S25 : Standard 2 of 5</li><li>S25+ : Standard 2 of 5 + CHECKSUM</li><li>I25 : Interleaved 2 of 5</li><li>I25+ : Interleaved 2 of 5 + CHECKSUM</li><li>C128 : CODE 128</li><li>C128A : CODE 128 A</li><li>C128B : CODE 128 B</li><li>C128C : CODE 128 C</li><li>EAN2 : 2-Digits UPC-Based Extention</li><li>EAN5 : 5-Digits UPC-Based Extention</li><li>EAN8 : EAN 8</li><li>EAN13 : EAN 13</li><li>UPCA : UPC-A</li><li>UPCE : UPC-E</li><li>MSI : MSI (Variation of Plessey code)</li><li>MSI+ : MSI + CHECKSUM (modulo 11)</li><li>POSTNET : POSTNET</li><li>PLANET : PLANET</li><li>RMS4CC : RMS4CC (Royal Mail 4-state Customer Code) - CBC (Customer Bar Code)</li><li>KIX : KIX (Klant index - Customer index)</li><li>IMB: Intelligent Mail Barcode - Onecode - USPS-B-3200</li><li>CODABAR : CODABAR</li><li>CODE11 : CODE 11</li><li>PHARMA : PHARMACODE</li><li>PHARMA2T : PHARMACODE TWO-TRACKS</li></ul>
         * @param $w (int) Width of a single bar element in pixels.
         * @param $h (int) Height of a single bar element in pixels.
         * @param $color (array) RGB (0-255) foreground color for bar elements (background is transparent).
         * @return string|false in case of error.
         * @protected
         * @static
         */
        public static function getBarcodeJPG($code, $type, $w = 2, $h = 30, $color = [], $showCode = false)
        {
            /** @var \Milon\Barcode\DNS1D $instance */
            return $instance->getBarcodeJPG($code, $type, $w, $h, $color, $showCode);
        }

            }
    /**
     * @method static string|false getBarcodePNGPath(string $code, string $type, int $w = 2, int $h = 30, array $color = [0, 0, 0])
     */
    class DNS2DFacade {
        /**
         * Return a SVG string representation of barcode.
         * 
         * <li>$arrcode['code'] code to be printed on text label</li>
         * <li>$arrcode['num_rows'] required number of rows</li>
         * <li>$arrcode['num_cols'] required number of columns</li>
         * <li>$arrcode['bcode'][$r][$c] value of the cell is $r row and $c column (0 = transparent, 1 = black)</li></ul>
         *
         * @param $code (string) code to print
         * @param $type (string) type of barcode: <ul><li>DATAMATRIX : Datamatrix (ISO/IEC 16022)</li><li>PDF417 : PDF417 (ISO/IEC 15438:2006)</li><li>PDF417,a,e,t,s,f,o0,o1,o2,o3,o4,o5,o6 : PDF417 with parameters: a = aspect ratio (width/height); e = error correction level (0-8); t = total number of macro segments; s = macro segment index (0-99998); f = file ID; o0 = File Name (text); o1 = Segment Count (numeric); o2 = Time Stamp (numeric); o3 = Sender (text); o4 = Addressee (text); o5 = File Size (numeric); o6 = Checksum (numeric). NOTES: Parameters t, s and f are required for a Macro Control Block, all other parametrs are optional. To use a comma character ',' on text options, replace it with the character 255: "\xff".</li><li>QRCODE : QRcode Low error correction</li><li>QRCODE,L : QRcode Low error correction</li><li>QRCODE,M : QRcode Medium error correction</li><li>QRCODE,Q : QRcode Better error correction</li><li>QRCODE,H : QR-CODE Best error correction</li><li>RAW: raw mode - comma-separad list of array rows</li><li>RAW2: raw mode - array rows are surrounded by square parenthesis.</li><li>TEST : Test matrix</li></ul>
         * @param $w (int) Width of a single rectangle element in user units.
         * @param $h (int) Height of a single rectangle element in user units.
         * @param $color (string) Foreground color (in SVG format) for bar elements (background is transparent).
         * @return string SVG code.
         * @protected
         * @static
         */
        public static function getBarcodeSVG($code, $type, $w = 3, $h = 3, $color = 'black')
        {
            /** @var \Milon\Barcode\DNS2D $instance */
            return $instance->getBarcodeSVG($code, $type, $w, $h, $color);
        }

        /**
         * Return an HTML representation of barcode.
         * 
         * <li>$arrcode['code'] code to be printed on text label</li>
         * <li>$arrcode['num_rows'] required number of rows</li>
         * <li>$arrcode['num_cols'] required number of columns</li>
         * <li>$arrcode['bcode'][$r][$c] value of the cell is $r row and $c column (0 = transparent, 1 = black)</li></ul>
         *
         * @param $code (string) code to print
         * @param $type (string) type of barcode: <ul><li>DATAMATRIX : Datamatrix (ISO/IEC 16022)</li><li>PDF417 : PDF417 (ISO/IEC 15438:2006)</li><li>PDF417,a,e,t,s,f,o0,o1,o2,o3,o4,o5,o6 : PDF417 with parameters: a = aspect ratio (width/height); e = error correction level (0-8); t = total number of macro segments; s = macro segment index (0-99998); f = file ID; o0 = File Name (text); o1 = Segment Count (numeric); o2 = Time Stamp (numeric); o3 = Sender (text); o4 = Addressee (text); o5 = File Size (numeric); o6 = Checksum (numeric). NOTES: Parameters t, s and f are required for a Macro Control Block, all other parametrs are optional. To use a comma character ',' on text options, replace it with the character 255: "\xff".</li><li>QRCODE : QRcode Low error correction</li><li>QRCODE,L : QRcode Low error correction</li><li>QRCODE,M : QRcode Medium error correction</li><li>QRCODE,Q : QRcode Better error correction</li><li>QRCODE,H : QR-CODE Best error correction</li><li>RAW: raw mode - comma-separad list of array rows</li><li>RAW2: raw mode - array rows are surrounded by square parenthesis.</li><li>TEST : Test matrix</li></ul>
         * @param $w (int) Width of a single rectangle element in pixels.
         * @param $h (int) Height of a single rectangle element in pixels.
         * @param $color (string) Foreground color for bar elements (background is transparent).
         * @return string HTML code.
         * @protected
         * @static
         */
        public static function getBarcodeHTML($code, $type, $w = 10, $h = 10, $color = 'black')
        {
            /** @var \Milon\Barcode\DNS2D $instance */
            return $instance->getBarcodeHTML($code, $type, $w, $h, $color);
        }

        /**
         * Return a PNG image representation of barcode (requires GD or Imagick library).
         * 
         * <li>$arrcode['code'] code to be printed on text label</li>
         * <li>$arrcode['num_rows'] required number of rows</li>
         * <li>$arrcode['num_cols'] required number of columns</li>
         * <li>$arrcode['bcode'][$r][$c] value of the cell is $r row and $c column (0 = transparent, 1 = black)</li></ul>
         *
         * @param $code (string) code to print
         * @param $type (string) type of barcode: <ul><li>DATAMATRIX : Datamatrix (ISO/IEC 16022)</li><li>PDF417 : PDF417 (ISO/IEC 15438:2006)</li><li>PDF417,a,e,t,s,f,o0,o1,o2,o3,o4,o5,o6 : PDF417 with parameters: a = aspect ratio (width/height); e = error correction level (0-8); t = total number of macro segments; s = macro segment index (0-99998); f = file ID; o0 = File Name (text); o1 = Segment Count (numeric); o2 = Time Stamp (numeric); o3 = Sender (text); o4 = Addressee (text); o5 = File Size (numeric); o6 = Checksum (numeric). NOTES: Parameters t, s and f are required for a Macro Control Block, all other parametrs are optional. To use a comma character ',' on text options, replace it with the character 255: "\xff".</li><li>QRCODE : QRcode Low error correction</li><li>QRCODE,L : QRcode Low error correction</li><li>QRCODE,M : QRcode Medium error correction</li><li>QRCODE,Q : QRcode Better error correction</li><li>QRCODE,H : QR-CODE Best error correction</li><li>RAW: raw mode - comma-separad list of array rows</li><li>RAW2: raw mode - array rows are surrounded by square parenthesis.</li><li>TEST : Test matrix</li></ul>
         * @param $w (int) Width of a single rectangle element in pixels.
         * @param $h (int) Height of a single rectangle element in pixels.
         * @param $color (array) RGB (0-255) foreground color for bar elements (background is transparent).
         * @return string|false path or false in case of error.
         * @protected
         * @static
         */
        public static function getBarcodePNG($code, $type, $w = 3, $h = 3, $color = [])
        {
            /** @var \Milon\Barcode\DNS2D $instance */
            return $instance->getBarcodePNG($code, $type, $w, $h, $color);
        }

        /**
         * @static
         */
        public static function setStorPath($path)
        {
            /** @var \Milon\Barcode\DNS2D $instance */
            return $instance->setStorPath($path);
        }

            }
    }

namespace SimpleSoftwareIO\QrCode\Facades {
    /**
     */
    class QrCode {
        /**
         * Generates the QrCode.
         *
         * @param string $text
         * @param string|null $filename
         * @return void|\Illuminate\Support\HtmlString|string
         * @throws WriterException
         * @throws InvalidArgumentException
         * @static
         */
        public static function generate($text, $filename = null)
        {
            /** @var \SimpleSoftwareIO\QrCode\Generator $instance */
            return $instance->generate($text, $filename);
        }

        /**
         * Merges an image over the QrCode.
         *
         * @param string $filepath
         * @param float $percentage
         * @param \SimpleSoftwareIO\QrCode\SimpleSoftwareIO\QrCode\boolean|bool $absolute
         * @return \Generator
         * @static
         */
        public static function merge($filepath, $percentage = 0.2, $absolute = false)
        {
            /** @var \SimpleSoftwareIO\QrCode\Generator $instance */
            return $instance->merge($filepath, $percentage, $absolute);
        }

        /**
         * Merges an image string with the center of the QrCode.
         *
         * @param string $content
         * @param float $percentage
         * @return \Generator
         * @static
         */
        public static function mergeString($content, $percentage = 0.2)
        {
            /** @var \SimpleSoftwareIO\QrCode\Generator $instance */
            return $instance->mergeString($content, $percentage);
        }

        /**
         * Sets the size of the QrCode.
         *
         * @param int $pixels
         * @return \Generator
         * @static
         */
        public static function size($pixels)
        {
            /** @var \SimpleSoftwareIO\QrCode\Generator $instance */
            return $instance->size($pixels);
        }

        /**
         * Sets the format of the QrCode.
         *
         * @param string $format
         * @return \Generator
         * @throws InvalidArgumentException
         * @static
         */
        public static function format($format)
        {
            /** @var \SimpleSoftwareIO\QrCode\Generator $instance */
            return $instance->format($format);
        }

        /**
         * Sets the foreground color of the QrCode.
         *
         * @param int $red
         * @param int $green
         * @param int $blue
         * @param null|int $alpha
         * @return \Generator
         * @static
         */
        public static function color($red, $green, $blue, $alpha = null)
        {
            /** @var \SimpleSoftwareIO\QrCode\Generator $instance */
            return $instance->color($red, $green, $blue, $alpha);
        }

        /**
         * Sets the background color of the QrCode.
         *
         * @param int $red
         * @param int $green
         * @param int $blue
         * @param null|int $alpha
         * @return \Generator
         * @static
         */
        public static function backgroundColor($red, $green, $blue, $alpha = null)
        {
            /** @var \SimpleSoftwareIO\QrCode\Generator $instance */
            return $instance->backgroundColor($red, $green, $blue, $alpha);
        }

        /**
         * Sets the eye color for the provided eye index.
         *
         * @param int $eyeNumber
         * @param int $innerRed
         * @param int $innerGreen
         * @param int $innerBlue
         * @param int $outterRed
         * @param int $outterGreen
         * @param int $outterBlue
         * @return \Generator
         * @throws InvalidArgumentException
         * @static
         */
        public static function eyeColor($eyeNumber, $innerRed, $innerGreen, $innerBlue, $outterRed = 0, $outterGreen = 0, $outterBlue = 0)
        {
            /** @var \SimpleSoftwareIO\QrCode\Generator $instance */
            return $instance->eyeColor($eyeNumber, $innerRed, $innerGreen, $innerBlue, $outterRed, $outterGreen, $outterBlue);
        }

        /**
         * @static
         */
        public static function gradient($startRed, $startGreen, $startBlue, $endRed, $endGreen, $endBlue, $type)
        {
            /** @var \SimpleSoftwareIO\QrCode\Generator $instance */
            return $instance->gradient($startRed, $startGreen, $startBlue, $endRed, $endGreen, $endBlue, $type);
        }

        /**
         * Sets the eye style.
         *
         * @param string $style
         * @return \Generator
         * @throws InvalidArgumentException
         * @static
         */
        public static function eye($style)
        {
            /** @var \SimpleSoftwareIO\QrCode\Generator $instance */
            return $instance->eye($style);
        }

        /**
         * Sets the style of the blocks for the QrCode.
         *
         * @param string $style
         * @param float $size
         * @return \Generator
         * @throws InvalidArgumentException
         * @static
         */
        public static function style($style, $size = 0.5)
        {
            /** @var \SimpleSoftwareIO\QrCode\Generator $instance */
            return $instance->style($style, $size);
        }

        /**
         * Sets the encoding for the QrCode.
         * 
         * Possible values are
         * ISO-8859-2, ISO-8859-3, ISO-8859-4, ISO-8859-5, ISO-8859-6,
         * ISO-8859-7, ISO-8859-8, ISO-8859-9, ISO-8859-10, ISO-8859-11,
         * ISO-8859-12, ISO-8859-13, ISO-8859-14, ISO-8859-15, ISO-8859-16,
         * SHIFT-JIS, WINDOWS-1250, WINDOWS-1251, WINDOWS-1252, WINDOWS-1256,
         * UTF-16BE, UTF-8, ASCII, GBK, EUC-KR.
         *
         * @param string $encoding
         * @return \Generator
         * @static
         */
        public static function encoding($encoding)
        {
            /** @var \SimpleSoftwareIO\QrCode\Generator $instance */
            return $instance->encoding($encoding);
        }

        /**
         * Sets the error correction for the QrCode.
         * 
         * L: 7% loss.
         * M: 15% loss.
         * Q: 25% loss.
         * H: 30% loss.
         *
         * @param string $errorCorrection
         * @return \Generator
         * @static
         */
        public static function errorCorrection($errorCorrection)
        {
            /** @var \SimpleSoftwareIO\QrCode\Generator $instance */
            return $instance->errorCorrection($errorCorrection);
        }

        /**
         * Sets the margin of the QrCode.
         *
         * @param int $margin
         * @return \Generator
         * @static
         */
        public static function margin($margin)
        {
            /** @var \SimpleSoftwareIO\QrCode\Generator $instance */
            return $instance->margin($margin);
        }

        /**
         * Fetches the Writer.
         *
         * @param \BaconQrCode\Renderer\ImageRenderer $renderer
         * @return \BaconQrCode\Writer
         * @static
         */
        public static function getWriter($renderer)
        {
            /** @var \SimpleSoftwareIO\QrCode\Generator $instance */
            return $instance->getWriter($renderer);
        }

        /**
         * Fetches the Image Renderer.
         *
         * @return \BaconQrCode\Renderer\ImageRenderer
         * @static
         */
        public static function getRenderer()
        {
            /** @var \SimpleSoftwareIO\QrCode\Generator $instance */
            return $instance->getRenderer();
        }

        /**
         * Returns the Renderer Style.
         *
         * @return \BaconQrCode\Renderer\RendererStyle\RendererStyle
         * @static
         */
        public static function getRendererStyle()
        {
            /** @var \SimpleSoftwareIO\QrCode\Generator $instance */
            return $instance->getRendererStyle();
        }

        /**
         * Fetches the formatter.
         *
         * @return \BaconQrCode\Renderer\Image\ImageBackEndInterface
         * @static
         */
        public static function getFormatter()
        {
            /** @var \SimpleSoftwareIO\QrCode\Generator $instance */
            return $instance->getFormatter();
        }

        /**
         * Fetches the module.
         *
         * @return \BaconQrCode\Renderer\Module\ModuleInterface
         * @static
         */
        public static function getModule()
        {
            /** @var \SimpleSoftwareIO\QrCode\Generator $instance */
            return $instance->getModule();
        }

        /**
         * Fetches the eye style.
         *
         * @return \BaconQrCode\Renderer\Eye\EyeInterface
         * @static
         */
        public static function getEye()
        {
            /** @var \SimpleSoftwareIO\QrCode\Generator $instance */
            return $instance->getEye();
        }

        /**
         * Fetches the color fill.
         *
         * @return \BaconQrCode\Renderer\RendererStyle\Fill
         * @static
         */
        public static function getFill()
        {
            /** @var \SimpleSoftwareIO\QrCode\Generator $instance */
            return $instance->getFill();
        }

        /**
         * Creates a RGB or Alpha channel color.
         *
         * @param int $red
         * @param int $green
         * @param int $blue
         * @param null|int $alpha
         * @return \BaconQrCode\Renderer\Color\ColorInterface
         * @static
         */
        public static function createColor($red, $green, $blue, $alpha = null)
        {
            /** @var \SimpleSoftwareIO\QrCode\Generator $instance */
            return $instance->createColor($red, $green, $blue, $alpha);
        }

            }
    }

namespace Spatie\LaravelIgnition\Facades {
    /**
     * @see \Spatie\FlareClient\Flare
     */
    class Flare {
        /**
         * @static
         */
        public static function make($apiKey = null, $contextDetector = null)
        {
            return \Spatie\FlareClient\Flare::make($apiKey, $contextDetector);
        }

        /**
         * @static
         */
        public static function setApiToken($apiToken)
        {
            /** @var \Spatie\FlareClient\Flare $instance */
            return $instance->setApiToken($apiToken);
        }

        /**
         * @static
         */
        public static function apiTokenSet()
        {
            /** @var \Spatie\FlareClient\Flare $instance */
            return $instance->apiTokenSet();
        }

        /**
         * @static
         */
        public static function setBaseUrl($baseUrl)
        {
            /** @var \Spatie\FlareClient\Flare $instance */
            return $instance->setBaseUrl($baseUrl);
        }

        /**
         * @static
         */
        public static function setStage($stage)
        {
            /** @var \Spatie\FlareClient\Flare $instance */
            return $instance->setStage($stage);
        }

        /**
         * @static
         */
        public static function sendReportsImmediately()
        {
            /** @var \Spatie\FlareClient\Flare $instance */
            return $instance->sendReportsImmediately();
        }

        /**
         * @static
         */
        public static function determineVersionUsing($determineVersionCallable)
        {
            /** @var \Spatie\FlareClient\Flare $instance */
            return $instance->determineVersionUsing($determineVersionCallable);
        }

        /**
         * @static
         */
        public static function reportErrorLevels($reportErrorLevels)
        {
            /** @var \Spatie\FlareClient\Flare $instance */
            return $instance->reportErrorLevels($reportErrorLevels);
        }

        /**
         * @static
         */
        public static function filterExceptionsUsing($filterExceptionsCallable)
        {
            /** @var \Spatie\FlareClient\Flare $instance */
            return $instance->filterExceptionsUsing($filterExceptionsCallable);
        }

        /**
         * @static
         */
        public static function filterReportsUsing($filterReportsCallable)
        {
            /** @var \Spatie\FlareClient\Flare $instance */
            return $instance->filterReportsUsing($filterReportsCallable);
        }

        /**
         * @param array<class-string<ArgumentReducer>|ArgumentReducer>|\Spatie\Backtrace\Arguments\ArgumentReducers|null $argumentReducers
         * @static
         */
        public static function argumentReducers($argumentReducers)
        {
            /** @var \Spatie\FlareClient\Flare $instance */
            return $instance->argumentReducers($argumentReducers);
        }

        /**
         * @static
         */
        public static function withStackFrameArguments($withStackFrameArguments = true, $forcePHPIniSetting = false)
        {
            /** @var \Spatie\FlareClient\Flare $instance */
            return $instance->withStackFrameArguments($withStackFrameArguments, $forcePHPIniSetting);
        }

        /**
         * @param class-string $exceptionClass
         * @static
         */
        public static function overrideGrouping($exceptionClass, $type = 'exception_message_and_class')
        {
            /** @var \Spatie\FlareClient\Flare $instance */
            return $instance->overrideGrouping($exceptionClass, $type);
        }

        /**
         * @static
         */
        public static function version()
        {
            /** @var \Spatie\FlareClient\Flare $instance */
            return $instance->version();
        }

        /**
         * @return array<int, FlareMiddleware|class-string<FlareMiddleware>>
         * @static
         */
        public static function getMiddleware()
        {
            /** @var \Spatie\FlareClient\Flare $instance */
            return $instance->getMiddleware();
        }

        /**
         * @static
         */
        public static function setContextProviderDetector($contextDetector)
        {
            /** @var \Spatie\FlareClient\Flare $instance */
            return $instance->setContextProviderDetector($contextDetector);
        }

        /**
         * @static
         */
        public static function setContainer($container)
        {
            /** @var \Spatie\FlareClient\Flare $instance */
            return $instance->setContainer($container);
        }

        /**
         * @static
         */
        public static function registerFlareHandlers()
        {
            /** @var \Spatie\FlareClient\Flare $instance */
            return $instance->registerFlareHandlers();
        }

        /**
         * @static
         */
        public static function registerExceptionHandler()
        {
            /** @var \Spatie\FlareClient\Flare $instance */
            return $instance->registerExceptionHandler();
        }

        /**
         * @static
         */
        public static function registerErrorHandler($errorLevels = null)
        {
            /** @var \Spatie\FlareClient\Flare $instance */
            return $instance->registerErrorHandler($errorLevels);
        }

        /**
         * @param \Spatie\FlareClient\FlareMiddleware\FlareMiddleware|array<FlareMiddleware>|class-string<FlareMiddleware>|callable $middleware
         * @return \Spatie\FlareClient\Flare
         * @static
         */
        public static function registerMiddleware($middleware)
        {
            /** @var \Spatie\FlareClient\Flare $instance */
            return $instance->registerMiddleware($middleware);
        }

        /**
         * @return array<int,FlareMiddleware|class-string<FlareMiddleware>>
         * @static
         */
        public static function getMiddlewares()
        {
            /** @var \Spatie\FlareClient\Flare $instance */
            return $instance->getMiddlewares();
        }

        /**
         * @param string $name
         * @param string $messageLevel
         * @param array<int, mixed> $metaData
         * @return \Spatie\FlareClient\Flare
         * @static
         */
        public static function glow($name, $messageLevel = 'info', $metaData = [])
        {
            /** @var \Spatie\FlareClient\Flare $instance */
            return $instance->glow($name, $messageLevel, $metaData);
        }

        /**
         * @static
         */
        public static function handleException($throwable)
        {
            /** @var \Spatie\FlareClient\Flare $instance */
            return $instance->handleException($throwable);
        }

        /**
         * @return mixed
         * @static
         */
        public static function handleError($code, $message, $file = '', $line = 0)
        {
            /** @var \Spatie\FlareClient\Flare $instance */
            return $instance->handleError($code, $message, $file, $line);
        }

        /**
         * @static
         */
        public static function applicationPath($applicationPath)
        {
            /** @var \Spatie\FlareClient\Flare $instance */
            return $instance->applicationPath($applicationPath);
        }

        /**
         * @static
         */
        public static function report($throwable, $callback = null, $report = null, $handled = null)
        {
            /** @var \Spatie\FlareClient\Flare $instance */
            return $instance->report($throwable, $callback, $report, $handled);
        }

        /**
         * @static
         */
        public static function reportHandled($throwable)
        {
            /** @var \Spatie\FlareClient\Flare $instance */
            return $instance->reportHandled($throwable);
        }

        /**
         * @static
         */
        public static function reportMessage($message, $logLevel, $callback = null)
        {
            /** @var \Spatie\FlareClient\Flare $instance */
            return $instance->reportMessage($message, $logLevel, $callback);
        }

        /**
         * @static
         */
        public static function sendTestReport($throwable)
        {
            /** @var \Spatie\FlareClient\Flare $instance */
            return $instance->sendTestReport($throwable);
        }

        /**
         * @static
         */
        public static function reset()
        {
            /** @var \Spatie\FlareClient\Flare $instance */
            return $instance->reset();
        }

        /**
         * @static
         */
        public static function anonymizeIp()
        {
            /** @var \Spatie\FlareClient\Flare $instance */
            return $instance->anonymizeIp();
        }

        /**
         * @param array<int, string> $fieldNames
         * @return \Spatie\FlareClient\Flare
         * @static
         */
        public static function censorRequestBodyFields($fieldNames)
        {
            /** @var \Spatie\FlareClient\Flare $instance */
            return $instance->censorRequestBodyFields($fieldNames);
        }

        /**
         * @static
         */
        public static function createReport($throwable)
        {
            /** @var \Spatie\FlareClient\Flare $instance */
            return $instance->createReport($throwable);
        }

        /**
         * @static
         */
        public static function createReportFromMessage($message, $logLevel)
        {
            /** @var \Spatie\FlareClient\Flare $instance */
            return $instance->createReportFromMessage($message, $logLevel);
        }

        /**
         * @static
         */
        public static function stage($stage)
        {
            /** @var \Spatie\FlareClient\Flare $instance */
            return $instance->stage($stage);
        }

        /**
         * @static
         */
        public static function messageLevel($messageLevel)
        {
            /** @var \Spatie\FlareClient\Flare $instance */
            return $instance->messageLevel($messageLevel);
        }

        /**
         * @param string $groupName
         * @param mixed $default
         * @return array<int, mixed>
         * @static
         */
        public static function getGroup($groupName = 'context', $default = [])
        {
            /** @var \Spatie\FlareClient\Flare $instance */
            return $instance->getGroup($groupName, $default);
        }

        /**
         * @static
         */
        public static function context($key, $value)
        {
            /** @var \Spatie\FlareClient\Flare $instance */
            return $instance->context($key, $value);
        }

        /**
         * @param string $groupName
         * @param array<string, mixed> $properties
         * @return \Spatie\FlareClient\Flare
         * @static
         */
        public static function group($groupName, $properties)
        {
            /** @var \Spatie\FlareClient\Flare $instance */
            return $instance->group($groupName, $properties);
        }

            }
    }

namespace Webklex\IMAP\Facades {
    /**
     * Class Client
     *
     * @package Webklex\IMAP\Facades
     */
    class Client {
        /**
         * Safely create a new client instance which is not listed in accounts
         *
         * @param array $config
         * @return \Client
         * @throws Exceptions\MaskNotFoundException
         * @static
         */
        public static function make($config)
        {
            /** @var \Webklex\PHPIMAP\ClientManager $instance */
            return $instance->make($config);
        }

        /**
         * Resolve a account instance.
         *
         * @param string|null $name
         * @return \Client
         * @throws Exceptions\MaskNotFoundException
         * @static
         */
        public static function account($name = null)
        {
            /** @var \Webklex\PHPIMAP\ClientManager $instance */
            return $instance->account($name);
        }

        /**
         * Merge the vendor settings with the local config
         * 
         * The default account identifier will be used as default for any missing account parameters.
         * If however the default account is missing a parameter the package default account parameter will be used.
         * This can be disabled by setting imap.default in your config file to 'false'
         *
         * @param array|string|\Webklex\PHPIMAP\Config $config
         * @return \Webklex\PHPIMAP\ClientManager
         * @static
         */
        public static function setConfig($config)
        {
            /** @var \Webklex\PHPIMAP\ClientManager $instance */
            return $instance->setConfig($config);
        }

        /**
         * Get the config instance
         *
         * @return \Webklex\PHPIMAP\Config
         * @static
         */
        public static function getConfig()
        {
            /** @var \Webklex\PHPIMAP\ClientManager $instance */
            return $instance->getConfig();
        }

            }
    }

namespace Illuminate\Support {
    /**
     * @template TKey of array-key
     * @template-covariant TValue
     * @implements \ArrayAccess<TKey, TValue>
     * @implements \Illuminate\Support\Enumerable<TKey, TValue>
     */
    class Collection {
        /**
         * @see \Maatwebsite\Excel\Mixins\DownloadCollectionMixin::downloadExcel()
         * @param string $fileName
         * @param string|null $writerType
         * @param mixed $withHeadings
         * @param array $responseHeaders
         * @static
         */
        public static function downloadExcel($fileName, $writerType = null, $withHeadings = false, $responseHeaders = [])
        {
            return \Illuminate\Support\Collection::downloadExcel($fileName, $writerType, $withHeadings, $responseHeaders);
        }

        /**
         * @see \Maatwebsite\Excel\Mixins\StoreCollectionMixin::storeExcel()
         * @param string $filePath
         * @param string|null $disk
         * @param string|null $writerType
         * @param mixed $withHeadings
         * @static
         */
        public static function storeExcel($filePath, $disk = null, $writerType = null, $withHeadings = false)
        {
            return \Illuminate\Support\Collection::storeExcel($filePath, $disk, $writerType, $withHeadings);
        }

            }
    }

namespace Illuminate\Http {
    /**
     */
    class Request extends \Symfony\Component\HttpFoundation\Request {
        /**
         * @see \Illuminate\Foundation\Providers\FoundationServiceProvider::registerRequestValidation()
         * @param array $rules
         * @param mixed $params
         * @static
         */
        public static function validate($rules, ...$params)
        {
            return \Illuminate\Http\Request::validate($rules, ...$params);
        }

        /**
         * @see \Illuminate\Foundation\Providers\FoundationServiceProvider::registerRequestValidation()
         * @param string $errorBag
         * @param array $rules
         * @param mixed $params
         * @static
         */
        public static function validateWithBag($errorBag, $rules, ...$params)
        {
            return \Illuminate\Http\Request::validateWithBag($errorBag, $rules, ...$params);
        }

        /**
         * @see \Illuminate\Foundation\Providers\FoundationServiceProvider::registerRequestSignatureValidation()
         * @param mixed $absolute
         * @static
         */
        public static function hasValidSignature($absolute = true)
        {
            return \Illuminate\Http\Request::hasValidSignature($absolute);
        }

        /**
         * @see \Illuminate\Foundation\Providers\FoundationServiceProvider::registerRequestSignatureValidation()
         * @static
         */
        public static function hasValidRelativeSignature()
        {
            return \Illuminate\Http\Request::hasValidRelativeSignature();
        }

        /**
         * @see \Illuminate\Foundation\Providers\FoundationServiceProvider::registerRequestSignatureValidation()
         * @param mixed $ignoreQuery
         * @param mixed $absolute
         * @static
         */
        public static function hasValidSignatureWhileIgnoring($ignoreQuery = [], $absolute = true)
        {
            return \Illuminate\Http\Request::hasValidSignatureWhileIgnoring($ignoreQuery, $absolute);
        }

        /**
         * @see \Illuminate\Foundation\Providers\FoundationServiceProvider::registerRequestSignatureValidation()
         * @param mixed $ignoreQuery
         * @static
         */
        public static function hasValidRelativeSignatureWhileIgnoring($ignoreQuery = [])
        {
            return \Illuminate\Http\Request::hasValidRelativeSignatureWhileIgnoring($ignoreQuery);
        }

            }
    }

namespace Illuminate\Routing {
    /**
     * @mixin \Illuminate\Routing\RouteRegistrar
     */
    class Router {
        /**
         * @see \Laravel\Ui\AuthRouteMethods::auth()
         * @param mixed $options
         * @static
         */
        public static function auth($options = [])
        {
            return \Illuminate\Routing\Router::auth($options);
        }

        /**
         * @see \Laravel\Ui\AuthRouteMethods::resetPassword()
         * @static
         */
        public static function resetPassword()
        {
            return \Illuminate\Routing\Router::resetPassword();
        }

        /**
         * @see \Laravel\Ui\AuthRouteMethods::confirmPassword()
         * @static
         */
        public static function confirmPassword()
        {
            return \Illuminate\Routing\Router::confirmPassword();
        }

        /**
         * @see \Laravel\Ui\AuthRouteMethods::emailVerification()
         * @static
         */
        public static function emailVerification()
        {
            return \Illuminate\Routing\Router::emailVerification();
        }

            }
    /**
     */
    class Route {
        /**
         * @see \Livewire\Features\SupportLazyLoading\SupportLazyLoading::registerRouteMacro()
         * @param mixed $enabled
         * @static
         */
        public static function lazy($enabled = true)
        {
            return \Illuminate\Routing\Route::lazy($enabled);
        }

        /**
         * @see \Spatie\Permission\PermissionServiceProvider::registerMacroHelpers()
         * @param mixed $roles
         * @static
         */
        public static function role($roles = [])
        {
            return \Illuminate\Routing\Route::role($roles);
        }

        /**
         * @see \Spatie\Permission\PermissionServiceProvider::registerMacroHelpers()
         * @param mixed $permissions
         * @static
         */
        public static function permission($permissions = [])
        {
            return \Illuminate\Routing\Route::permission($permissions);
        }

            }
    }

namespace Illuminate\View {
    /**
     */
    class ComponentAttributeBag {
        /**
         * @see \Livewire\Features\SupportBladeAttributes\SupportBladeAttributes::provide()
         * @param mixed $name
         * @static
         */
        public static function wire($name)
        {
            return \Illuminate\View\ComponentAttributeBag::wire($name);
        }

            }
    /**
     */
    class View {
        /**
         * @see \Livewire\Features\SupportPageComponents\SupportPageComponents::registerLayoutViewMacros()
         * @param mixed $data
         * @static
         */
        public static function layoutData($data = [])
        {
            return \Illuminate\View\View::layoutData($data);
        }

        /**
         * @see \Livewire\Features\SupportPageComponents\SupportPageComponents::registerLayoutViewMacros()
         * @param mixed $section
         * @static
         */
        public static function section($section)
        {
            return \Illuminate\View\View::section($section);
        }

        /**
         * @see \Livewire\Features\SupportPageComponents\SupportPageComponents::registerLayoutViewMacros()
         * @param mixed $title
         * @static
         */
        public static function title($title)
        {
            return \Illuminate\View\View::title($title);
        }

        /**
         * @see \Livewire\Features\SupportPageComponents\SupportPageComponents::registerLayoutViewMacros()
         * @param mixed $slot
         * @static
         */
        public static function slot($slot)
        {
            return \Illuminate\View\View::slot($slot);
        }

        /**
         * @see \Livewire\Features\SupportPageComponents\SupportPageComponents::registerLayoutViewMacros()
         * @param mixed $view
         * @param mixed $params
         * @static
         */
        public static function extends($view, $params = [])
        {
            return \Illuminate\View\View::extends($view, $params);
        }

        /**
         * @see \Livewire\Features\SupportPageComponents\SupportPageComponents::registerLayoutViewMacros()
         * @param mixed $view
         * @param mixed $params
         * @static
         */
        public static function layout($view, $params = [])
        {
            return \Illuminate\View\View::layout($view, $params);
        }

        /**
         * @see \Livewire\Features\SupportPageComponents\SupportPageComponents::registerLayoutViewMacros()
         * @param callable $callback
         * @static
         */
        public static function response($callback)
        {
            return \Illuminate\View\View::response($callback);
        }

            }
    }


namespace  {
    class SEOMeta extends \Artesaos\SEOTools\Facades\SEOMeta {}
    class OpenGraph extends \Artesaos\SEOTools\Facades\OpenGraph {}
    class Twitter extends \Artesaos\SEOTools\Facades\TwitterCard {}
    class JsonLd extends \Artesaos\SEOTools\Facades\JsonLd {}
    class JsonLdMulti extends \Artesaos\SEOTools\Facades\JsonLdMulti {}
    class SEO extends \Artesaos\SEOTools\Facades\SEOTools {}
    class Str extends \Illuminate\Support\Str {}
    class Auth extends \Illuminate\Support\Facades\Auth {}
    class Route extends \Illuminate\Support\Facades\Route {}
    class Excel extends \Maatwebsite\Excel\Facades\Excel {}
    class PDF extends \Barryvdh\DomPDF\Facade\Pdf {}
    class Pdf extends \Barryvdh\DomPDF\Facade\Pdf {}
    class BotMan extends \BotMan\BotMan\Facades\BotMan {}
    class Pulse extends \Laravel\Pulse\Facades\Pulse {}
    class Livewire extends \Livewire\Livewire {}
    class DNS1D extends \Milon\Barcode\Facades\DNS1DFacade {}
    class DNS2D extends \Milon\Barcode\Facades\DNS2DFacade {}
    class QrCode extends \SimpleSoftwareIO\QrCode\Facades\QrCode {}
    class Flare extends \Spatie\LaravelIgnition\Facades\Flare {}
    class Client extends \Webklex\IMAP\Facades\Client {}
}





