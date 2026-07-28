<?php

use App\Providers\AppServiceProvider;
use App\Providers\SentryServiceProvider;
use App\Providers\TelescopeServiceProvider;
use Modules\Activity\Providers\ActivityServiceProvider;
use Modules\Ads\Providers\AdsServiceProvider;
use Modules\Analytics\Providers\AnalyticsServiceProvider;
use Modules\Attention\Providers\AttentionServiceProvider;
use Modules\Auth\Providers\AuthServiceProvider;
use Modules\Backup\Providers\BackupServiceProvider;
use Modules\Blog\Providers\BlogServiceProvider;
use Modules\Cache\Providers\CacheServiceProvider;
use Modules\Captcha\Providers\CaptchaServiceProvider;
use Modules\Cookie\Providers\CookieServiceProvider;
use Modules\Core\Providers\CoreServiceProvider;
use Modules\Database\Providers\DatabaseServiceProvider;
use Modules\Document\Providers\DocumentsServiceProvider;
use Modules\Ecommerce\Providers\EcommerceServiceProvider;
use Modules\EcommercePayment\Providers\EcommercePaymentServiceProvider;
use Modules\Engagement\Providers\EngagementServiceProvider;
use Modules\Faqs\Providers\FaqsServiceProvider;
use Modules\Forms\Providers\FormsServiceProvider;
use Modules\Health\Providers\HealthServiceProvider;
use Modules\Helpdesk\Providers\HelpdeskServiceProvider;
use Modules\HelpdeskAgents\Providers\HelpdeskAgentsServiceProvider;
use Modules\HelpdeskAnalytics\Providers\HelpdeskAnalyticsServiceProvider;
use Modules\HelpdeskCampaigns\Providers\HelpdeskCampaignsServiceProvider;
use Modules\HelpdeskCompliance\Providers\HelpdeskComplianceServiceProvider;
use Modules\HelpdeskContacts\Providers\HelpdeskContactsServiceProvider;
use Modules\HelpdeskDocument\Providers\HelpdeskDocumentServiceProvider;
use Modules\HelpdeskErp\Providers\HelpdeskErpServiceProvider;
use Modules\HelpdeskIntegration\Providers\HelpdeskIntegrationServiceProvider;
use Modules\HelpdeskLivechat\Providers\HelpdeskLivechatServiceProvider;
use Modules\HelpdeskPrestashop\Providers\HelpdeskPrestashopServiceProvider;
use Modules\HelpdeskSla\Providers\HelpdeskSlaServiceProvider;
use Modules\HelpdeskTickets\Providers\HelpdeskTicketsServiceProvider;
use Modules\Locales\Providers\LocalesServiceProvider;
use Modules\Locations\Providers\LocationsServiceProvider;
use Modules\Mailer\Providers\MailerServiceProvider;
use Modules\MailsSettings\Providers\MailsSettingsServiceProvider;
use Modules\Media\Providers\MediaServiceProvider;
use Modules\Modules\Providers\EventServiceProvider;
use Modules\Newsletter\Providers\NewsletterServiceProvider;
use Modules\Notification\Providers\NotificationServiceProvider;
use Modules\Optimize\Providers\OptimizeServiceProvider;
use Modules\Page\Providers\PageServiceProvider;
use Modules\PriceLabels\Providers\PriceLabelsServiceProvider;
use Modules\Queue\Providers\QueueServiceProvider;
use Modules\Remarketing\Providers\RemarketingServiceProvider;
use Modules\Reverb\Providers\ReverServiceProvider;
use Modules\Reviews\Providers\ReviewsServiceProvider;
use Modules\Role\Providers\RoleServiceProvider;
use Modules\Seo\Providers\SeoServiceProvider;
use Modules\Shortcode\Providers\ShortcodeServiceProvider;
use Modules\SimpleSlider\Providers\SimpleSliderServiceProvider;
use Modules\Sitemap\Providers\SitemapServiceProvider;
use Modules\Storage\Providers\StorageServiceProvider;
use Modules\System\Providers\SystemServiceProvider;
use Modules\Template\Providers\TemplateServiceProvider;
use Modules\Theme\Providers\ThemeServiceProvider;
use Modules\User\Providers\UserServiceProvider;

return [
    AppServiceProvider::class,
    SentryServiceProvider::class,
    TelescopeServiceProvider::class,
    ActivityServiceProvider::class,
    AdsServiceProvider::class,
    AnalyticsServiceProvider::class,
    AttentionServiceProvider::class,
    AuthServiceProvider::class,
    BackupServiceProvider::class,
    BlogServiceProvider::class,
    CacheServiceProvider::class,
    CaptchaServiceProvider::class,
    CookieServiceProvider::class,
    CoreServiceProvider::class,
    DatabaseServiceProvider::class,
    DocumentsServiceProvider::class,
    EcommerceServiceProvider::class,
    EcommercePaymentServiceProvider::class,
    EngagementServiceProvider::class,
    FaqsServiceProvider::class,
    FormsServiceProvider::class,
    HealthServiceProvider::class,
    HelpdeskAgentsServiceProvider::class,
    HelpdeskAnalyticsServiceProvider::class,
    HelpdeskCampaignsServiceProvider::class,
    HelpdeskComplianceServiceProvider::class,
    HelpdeskDocumentServiceProvider::class,
    HelpdeskContactsServiceProvider::class,
    HelpdeskErpServiceProvider::class,
    HelpdeskIntegrationServiceProvider::class,
    HelpdeskLivechatServiceProvider::class,
    HelpdeskPrestashopServiceProvider::class,
    HelpdeskSlaServiceProvider::class,
    HelpdeskTicketsServiceProvider::class,
    HelpdeskServiceProvider::class,
    LocalesServiceProvider::class,
    LocationsServiceProvider::class,
    MailerServiceProvider::class,
    MailsSettingsServiceProvider::class,
    MediaServiceProvider::class,
    EventServiceProvider::class,
    NewsletterServiceProvider::class,
    NotificationServiceProvider::class,
    OptimizeServiceProvider::class,
    PageServiceProvider::class,
    PriceLabelsServiceProvider::class,
    QueueServiceProvider::class,
    RemarketingServiceProvider::class,
    ReverServiceProvider::class,
    ReviewsServiceProvider::class,
    RoleServiceProvider::class,
    SeoServiceProvider::class,
    ShortcodeServiceProvider::class,
    SimpleSliderServiceProvider::class,
    SitemapServiceProvider::class,
    StorageServiceProvider::class,
    SystemServiceProvider::class,
    TemplateServiceProvider::class,
    ThemeServiceProvider::class,
    UserServiceProvider::class,
];
