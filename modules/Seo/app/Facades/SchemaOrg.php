<?php

namespace Modules\Seo\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Seo\Services\SchemaOrgService;

/**
 * @method static array generateArticleSchema(object $model, array $options = [])
 * @method static array generateOrganizationSchema(array $options = [])
 * @method static array generateBreadcrumbSchema(array $items)
 * @method static array generateFAQSchema(array $questions)
 * @method static array generateWebPageSchema(Model|array $page, array $options = [])
 * @method static array generateProductSchema(object $model, array $options = [])
 * @method static array generateLocalBusinessSchema(array $options = [])
 * @method static array generateGraphSchema(array $schemas)
 * @method static string renderJsonLd(array $schema, bool $prettyPrint = false)
 * @method static string renderScriptTag(array $schema, bool $prettyPrint = false)
 *
 * @see SchemaOrgService
 */
class SchemaOrg extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SchemaOrgService::class;
    }
}
