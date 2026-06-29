<?php

namespace Modules\Seo\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Seo\Services\SeoService;

/**
 * @method static \Modules\Seo\Services\SeoService setTitle(string $title, bool $appendSuffix = true)
 * @method static \Modules\Seo\Services\SeoService setDescription(string $description)
 * @method static \Modules\Seo\Services\SeoService setKeywords(array|string $keywords)
 * @method static \Modules\Seo\Services\SeoService setOgTitle(string $title)
 * @method static \Modules\Seo\Services\SeoService setOgDescription(string $description)
 * @method static \Modules\Seo\Services\SeoService setOgImage(string $url)
 * @method static \Modules\Seo\Services\SeoService setOgType(string $type)
 * @method static \Modules\Seo\Services\SeoService setTwitterCard(string $type = 'summary')
 * @method static \Modules\Seo\Services\SeoService setTwitterTitle(string $title)
 * @method static \Modules\Seo\Services\SeoService setTwitterDescription(string $description)
 * @method static \Modules\Seo\Services\SeoService setTwitterImage(string $url)
 * @method static \Modules\Seo\Services\SeoService setCanonical(string $url)
 * @method static \Modules\Seo\Services\SeoService setRobots(string $robots = 'index,follow')
 * @method static \Modules\Seo\Services\SeoService loadFromModel(\Illuminate\Database\Eloquent\Model $model)
 * @method static \Modules\Seo\Models\SeoMeta saveMeta(\Illuminate\Database\Eloquent\Model $model, array $data)
 * @method static array getData()
 * @method static mixed get(string $key, mixed $default = null)
 * @method static string render()
 * @method static array generatePreview()
 *
 * @see SeoService
 */
class Seo extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'seo';
    }
}
