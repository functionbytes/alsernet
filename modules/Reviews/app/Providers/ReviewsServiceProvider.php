<?php

namespace Modules\Reviews\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Modules\Reviews\Console\CleanExpiredReportsCommand;
use Modules\Reviews\Console\Commands\CheckReviewSlaCommand;
use Modules\Reviews\Console\Commands\CleanupExpiredConnectionsCommand;
use Modules\Reviews\Console\Commands\CleanupExportFilesCommand;
use Modules\Reviews\Console\Commands\DetectReviewAnomaliesCommand;
use Modules\Reviews\Console\Commands\ExportUserReviewDataCommand;
use Modules\Reviews\Console\Commands\GenerateMonthlyReportCommand;
use Modules\Reviews\Console\Commands\GenerateReportCommand;
use Modules\Reviews\Console\Commands\ImportJsonReviewsCommand;
use Modules\Reviews\Console\Commands\InstallReviewsCommand;
use Modules\Reviews\Console\Commands\ProcessScheduledRepliesCommand;
use Modules\Reviews\Console\Commands\PruneOldReviewsCommand;
use Modules\Reviews\Console\Commands\SendDailyDigestCommand;
use Modules\Reviews\Console\Commands\SendWeeklyDigestCommand;
use Modules\Reviews\Console\Commands\SyncGoogleReviewsCommand;
use Modules\Reviews\Console\Commands\SyncReviewsCommand;
use Modules\Reviews\Console\Commands\TrackCompetitorsCommand;
use Modules\Reviews\Console\Commands\TranslateAllReviewsCommand;
use Modules\Reviews\Events\ConnectionRevoked;
use Modules\Reviews\Events\ReplyFailed;
use Modules\Reviews\Events\ReplyPublished;
use Modules\Reviews\Events\ReviewAnomalyDetected;
use Modules\Reviews\Events\ReviewSynced;
use Modules\Reviews\Jobs\SyncGoogleReviewsJob;
use Modules\Reviews\Listeners\HandleConnectionRevoked;
use Modules\Reviews\Listeners\HandleReplyFailed;
use Modules\Reviews\Listeners\LogReplyPublished;
use Modules\Reviews\Listeners\LogReviewSync;
use Modules\Reviews\Listeners\NotifyOnNewReview;
use Modules\Reviews\Listeners\ProcessAutoReplies;
use Modules\Reviews\Listeners\SendUrgentReviewNotification;
use Modules\Reviews\Models\GeneratedReport;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Models\ReviewAutoSuggestion;
use Modules\Reviews\Models\ReviewGoogleConnection;
use Modules\Reviews\Models\ReviewGoogleLocation;
use Modules\Reviews\Models\ReviewReply;
use Modules\Reviews\Models\ReviewReplyTemplate;
use Modules\Reviews\Models\ReviewSavedFilter;
use Modules\Reviews\Platforms\GoogleReviewPlatform;
use Modules\Reviews\Policies\GeneratedReportPolicy;
use Modules\Reviews\Policies\ReviewAutoSuggestionPolicy;
use Modules\Reviews\Policies\ReviewGoogleConnectionPolicy;
use Modules\Reviews\Policies\ReviewPolicy;
use Modules\Reviews\Policies\ReviewReplyPolicy;
use Modules\Reviews\Policies\ReviewReplyTemplatePolicy;
use Modules\Reviews\Policies\ReviewSavedFilterPolicy;
use Modules\Reviews\Services\Fetchers\PlacesApiReviewFetcher;
use Modules\Reviews\Services\Fetchers\SerpApiReviewFetcher;
use Modules\Reviews\Services\GoogleApiClient;
use Modules\Reviews\Services\GoogleWebhookService;
use Modules\Reviews\Services\NotificationService;
use Modules\Reviews\Services\OutboundWebhookService;
use Modules\Reviews\Services\PlatformRegistry;
use Modules\Reviews\Services\ReviewFetchOrchestrator;
use Modules\Reviews\Services\ReviewReportService;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Facades\Module;

class ReviewsServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Reviews';

    protected string $moduleNameLower = 'reviews';

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);

        $this->app->singleton(GoogleApiClient::class);
        $this->app->singleton(NotificationService::class);
        $this->app->singleton(ReviewReportService::class);
        $this->app->singleton(GoogleWebhookService::class);
        $this->app->singleton(OutboundWebhookService::class);

        $this->app->singleton(PlatformRegistry::class, function ($app) {
            $registry = new PlatformRegistry;
            $registry->register($app->make(GoogleReviewPlatform::class));

            return $registry;
        });

        $this->app->singleton(ReviewFetchOrchestrator::class, function ($app) {
            return new ReviewFetchOrchestrator([
                $app->make(PlacesApiReviewFetcher::class),
                $app->make(SerpApiReviewFetcher::class),
            ]);
        });
    }

    public function boot(): void
    {
        if (Module::find('Reviews')?->isDisabled()) {
            return;
        }

        // Load configs in boot instead of register to avoid cache resolution issues
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'config/general.php'),
            'reviews.general'
        );

        $this->mergeConfigFrom(
            module_path($this->moduleName, 'config/google.php'),
            'reviews.google'
        );

        $this->mergeConfigFrom(
            module_path($this->moduleName, 'config/permissions.php'),
            'reviews.permissions'
        );

        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));

        $this->loadViewsFrom(module_path($this->moduleName, 'resources/views'), 'reviews');

        $this->publishes([
            module_path($this->moduleName, 'config/general.php') => config_path('reviews/general.php'),
            module_path($this->moduleName, 'config/google.php') => config_path('reviews/google.php'),
        ], 'reviews-config');

        $this->registerPolicies();
        $this->registerScheduledTasks();
        $this->registerCommands();
        $this->registerEventListeners();
        $this->registerMenus();
        $this->registerRateLimiters();
        $this->registerShortcodes();
        $this->registerViewComposers();
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallReviewsCommand::class,
                SyncReviewsCommand::class,
                CleanupExpiredConnectionsCommand::class,
                GenerateReportCommand::class,
                PruneOldReviewsCommand::class,
                SendDailyDigestCommand::class,
                CleanExpiredReportsCommand::class,
                SyncGoogleReviewsCommand::class,
                CleanupExportFilesCommand::class,
                ProcessScheduledRepliesCommand::class,
                DetectReviewAnomaliesCommand::class,
                ExportUserReviewDataCommand::class,
                CheckReviewSlaCommand::class,
                SendWeeklyDigestCommand::class,
                GenerateMonthlyReportCommand::class,
                TrackCompetitorsCommand::class,
                ImportJsonReviewsCommand::class,
                TranslateAllReviewsCommand::class,
            ]);
        }
    }

    protected function registerPolicies(): void
    {
        Gate::policy(Review::class, ReviewPolicy::class);
        Gate::policy(ReviewReply::class, ReviewReplyPolicy::class);
        Gate::policy(ReviewGoogleConnection::class, ReviewGoogleConnectionPolicy::class);
        Gate::policy(ReviewReplyTemplate::class, ReviewReplyTemplatePolicy::class);
        Gate::policy(ReviewAutoSuggestion::class, ReviewAutoSuggestionPolicy::class);
        Gate::policy(ReviewSavedFilter::class, ReviewSavedFilterPolicy::class);
        Gate::policy(GeneratedReport::class, GeneratedReportPolicy::class);
    }

    protected function registerScheduledTasks(): void
    {
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->call(function () {
                ReviewGoogleLocation::query()
                    ->active()
                    ->needingSync()
                    ->each(function (ReviewGoogleLocation $location) {
                        SyncGoogleReviewsJob::dispatch($location);
                    });
            })
                ->everyFifteenMinutes()
                ->name('reviews:sync-google-reviews')
                ->withoutOverlapping()
                ->onOneServer();

            $schedule->command('reviews:sync-google')
                ->hourly()
                ->name('reviews:sync-google')
                ->withoutOverlapping()
                ->onOneServer();

            $schedule->command('reviews:send-daily-digest')
                ->dailyAt('08:00')
                ->name('reviews:send-daily-digest')
                ->withoutOverlapping()
                ->onOneServer();

            $schedule->command('reviews:cleanup-exports')
                ->daily()
                ->name('reviews:cleanup-exports')
                ->withoutOverlapping()
                ->onOneServer();

            $schedule->command('reviews:process-scheduled')
                ->everyFiveMinutes()
                ->name('reviews:process-scheduled')
                ->withoutOverlapping()
                ->onOneServer();

            $schedule->command('reviews:detect-anomalies')
                ->hourly()
                ->name('reviews:detect-anomalies')
                ->withoutOverlapping()
                ->onOneServer();

            $schedule->command('reviews:send-weekly-digest')
                ->weeklyOn(1, '08:00')
                ->name('reviews:send-weekly-digest')
                ->withoutOverlapping()
                ->onOneServer();

            $schedule->command('reviews:generate-monthly-report')
                ->monthlyOn(1, '06:00')
                ->name('reviews:generate-monthly-report')
                ->withoutOverlapping()
                ->onOneServer();

            $schedule->command('reviews:check-sla')
                ->hourly()
                ->name('reviews:check-sla')
                ->withoutOverlapping()
                ->onOneServer();

            $schedule->command('reviews:track-competitors')
                ->dailyAt('02:00')
                ->name('reviews:track-competitors')
                ->withoutOverlapping()
                ->onOneServer();
        });
    }

    protected function registerEventListeners(): void
    {
        Event::listen(ReviewSynced::class, NotifyOnNewReview::class);
        Event::listen(ReviewSynced::class, SendUrgentReviewNotification::class);
        Event::listen(ReviewSynced::class, LogReviewSync::class);
        Event::listen(ReviewSynced::class, ProcessAutoReplies::class);
        Event::listen(ReplyPublished::class, LogReplyPublished::class);
        Event::listen(ConnectionRevoked::class, HandleConnectionRevoked::class);
        Event::listen(ReplyFailed::class, HandleReplyFailed::class);

        // Outbound webhooks (Zapier integration)
        Event::listen(ReviewSynced::class, function (ReviewSynced $event): void {
            app(OutboundWebhookService::class)->dispatch('review.created', [
                'event' => 'review.created',
                'review_id' => $event->review->id,
                'location_id' => $event->review->location_id,
                'reviewer_name' => $event->review->reviewer_name,
                'star_rating' => $event->review->star_rating->value(),
                'comment' => $event->review->comment,
                'review_time' => $event->review->review_time?->toIso8601String(),
            ]);
        });

        Event::listen(ReplyPublished::class, function (ReplyPublished $event): void {
            app(OutboundWebhookService::class)->dispatch('reply.published', [
                'event' => 'reply.published',
                'reply_id' => $event->reply->id,
                'review_id' => $event->reply->review_id,
                'body' => $event->reply->body,
                'published_at' => now()->toIso8601String(),
            ]);
        });

        Event::listen(ReviewAnomalyDetected::class, function (ReviewAnomalyDetected $event): void {
            app(OutboundWebhookService::class)->dispatch('review.anomaly', [
                'event' => 'review.anomaly',
                'location_id' => $event->anomaly->locationId,
                'location_name' => $event->anomaly->locationName,
                'current_count' => $event->anomaly->currentCount,
                'historical_average' => $event->anomaly->historicalAverage,
                'multiplier' => $event->anomaly->multiplier,
                'detected_at' => $event->anomaly->detectedAt->toIso8601String(),
            ]);
        });
    }

    protected function registerMenus(): void
    {
        NavService::registerMiniItem('reviews', [
            'icon' => 'fas fa-star',
            'tooltip' => 'Reseñas',
            'sidebar_id' => 'reviews',
            'order' => 50,
        ]);

        NavService::registerSidebar('reviews', [
            'title' => 'Gestion de reseñas',
            'items' => [
                ['label' => 'Dashboard', 'route' => 'reviews.dashboard', 'permission' => 'reviews.reviews.view'],
                ['label' => 'Todas las reseñas', 'route' => 'reviews.index', 'permission' => 'reviews.reviews.view'],
                ['label' => 'Campañas', 'route' => 'reviews.campaigns.index', 'permission' => 'reviews.reviews.view'],
                ['label' => 'Reglas auto-respuesta',  'route' => 'reviews.autoreply.index', 'permission' => 'reviews.settings.manage'],
                ['label' => 'Competidores',  'route' => 'reviews.competitors.index', 'permission' => 'reviews.reviews.view'],
                ['label' => 'Insignias',  'route' => 'reviews.badges.index', 'permission' => 'reviews.reviews.view'],
                ['label' => 'Filtros guardados',  'route' => 'reviews.saved-filters.index', 'permission' => 'reviews.reviews.view'],
                ['label' => 'Respuestas programadas', 'route' => 'reviews.replies.scheduled', 'permission' => 'reviews.reviews.view'],
                ['label' => 'Historial exportaciones',  'route' => 'reviews.exports.history', 'permission' => 'reviews.reviews.view'],
                ['label' => 'Webhooks', 'route' => 'reviews.webhook-subscriptions.index', 'permission' => 'reviews.settings.manage'],
            ],
        ]);

        NavService::registerSidebar('settings', [
            'title' => 'Reseñas',
            'items' => [
                ['label' => 'Configuracion general', 'route' => 'settings.reviews.config.index', 'permission' => 'reviews.settings.manage'],
                ['label' => 'Conexiones Google', 'route' => 'settings.reviews.connections.index', 'permission' => 'reviews.google.manage'],
                ['label' => 'Ubicaciones', 'route' => 'settings.reviews.locations.index', 'permission' => 'reviews.google.manage'],
                ['label' => 'Plantillas de respuesta', 'route' => 'settings.reviews.templates.index', 'permission' => 'reviews.templates.view'],
                ['label' => 'Preferencias de notificacion', 'route' => 'settings.reviews.notifications.index', 'permission' => 'reviews.settings.manage'],
                ['label' => 'Auto-respuesta con IA', 'route' => 'settings.reviews.ai.index', 'permission' => 'reviews.settings.manage'],
                ['label' => 'Widget de reseñas', 'route' => 'settings.reviews.widget.index', 'permission' => 'reviews.settings.manage'],
            ],
        ]);
    }

    protected function registerRateLimiters(): void
    {
        // Admin users: 1000 requests per hour
        RateLimiter::for('reviews:admin', function (Request $request) {
            if ($request->user()?->hasRole('super-admin|administrative|manager')) {
                return Limit::perHour(1000)->by($request->user()->id);
            }

            return Limit::none();
        });

        // Regular authenticated users: 100 requests per hour
        RateLimiter::for('reviews:user', function (Request $request) {
            if ($request->user()) {
                return Limit::perHour(100)->by($request->user()->id);
            }

            return Limit::none();
        });

        // Guest/unauthenticated users: 20 requests per hour by IP
        RateLimiter::for('reviews:guest', function (Request $request) {
            return Limit::perHour(20)->by($request->ip());
        });

        // Combined rate limiter that checks role-based limits
        RateLimiter::for('reviews:api', function (Request $request) {
            if (! $request->user()) {
                return Limit::perHour(20)->by($request->ip());
            }

            if ($request->user()->hasAnyRole(['super-admin', 'administrative', 'manager'])) {
                return Limit::perHour(1000)->by($request->user()->id);
            }

            return Limit::perHour(100)->by($request->user()->id);
        });

        // Google webhook endpoints: 60 requests per minute per IP
        RateLimiter::for('reviews:webhooks', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });
    }

    protected function registerViewComposers(): void
    {
        // View composers moved to shortcode approach — no composer needed
    }

    protected function registerShortcodes(): void
    {
        $this->app->booted(function () {
            if (! $this->app->bound('shortcode')) {
                return;
            }

            app('shortcode')->register('reviews-page', function (array $attrs) {
                $locationId = isset($attrs['location_id']) ? (int) $attrs['location_id'] : null;
                static $instanceCounter = 0;
                $instanceId = ++$instanceCounter;

                $locale = app()->getLocale();
                $localeCode = strtoupper($locale);

                // Scope: specific location or all active locations
                $activeIds = $locationId
                    ? [$locationId]
                    : ReviewGoogleLocation::where('is_active', true)->pluck('id')->all();

                $visible = fn ($q) => $q
                    ->whereHas('moderation', fn ($m) => $m->where('is_visible', true))
                    ->orWhereDoesntHave('moderation');

                $reviews = Review::query()
                    ->where($visible)
                    ->whereIn('location_id', $activeIds)
                    ->with(['moderation', 'translations'])
                    ->orderByDesc('star_rating')
                    ->orderByDesc('review_time')
                    ->get();

                $row = Review::query()
                    ->where($visible)
                    ->whereIn('location_id', $activeIds)
                    ->selectRaw("COUNT(*) as total, AVG(CASE star_rating
                        WHEN 'ONE'   THEN 1
                        WHEN 'TWO'   THEN 2
                        WHEN 'THREE' THEN 3
                        WHEN 'FOUR'  THEN 4
                        WHEN 'FIVE'  THEN 5
                        ELSE NULL END) as avg_rating")
                    ->first();

                $tagCounts = $reviews
                    ->flatMap(fn ($r) => $r->moderation?->tags ?? [])
                    ->countBy()
                    ->sortByDesc(fn ($count) => $count)
                    ->all();

                // Merge available_tags from all scoped locations, deduplicate by slug
                $availableTags = ReviewGoogleLocation::whereIn('id', $activeIds)
                    ->get()
                    ->flatMap(fn ($l) => $l->available_tags ?? [])
                    ->keyBy('slug')
                    ->values()
                    ->all();

                return view('reviews::shortcodes.testimonios', [
                    'reviews' => $reviews,
                    'avgRating' => (float) ($row->avg_rating ?? 0),
                    'totalCount' => (int) ($row->total ?? 0),
                    'tagCounts' => $tagCounts,
                    'instanceId' => $instanceId,
                    'localeCode' => $localeCode,
                    'availableTags' => $availableTags,
                ])->render();
            }, ['cacheable' => false]);

            app('shortcode')->register('reviews-home', function (array $attrs) {
                $visible = fn ($q) => $q
                    ->whereHas('moderation', fn ($m) => $m->where('is_visible', true))
                    ->orWhereDoesntHave('moderation');

                $row = Review::query()
                    ->where($visible)
                    ->selectRaw("COUNT(*) as total, AVG(CASE star_rating
                        WHEN 'ONE'   THEN 1 WHEN 'TWO'   THEN 2 WHEN 'THREE' THEN 3
                        WHEN 'FOUR'  THEN 4 WHEN 'FIVE'  THEN 5 ELSE NULL END) as avg_rating")
                    ->first();

                return view('reviews::shortcodes.home-testimonials', [
                    'avgRating' => (float) ($row->avg_rating ?? 0),
                    'totalCount' => (int) ($row->total ?? 0),
                ])->render();
            }, ['cacheable' => false]);

            app('shortcode')->register('reviews-about', function (array $attrs) {
                return view('reviews::shortcodes.about-reviews')->render();
            }, ['cacheable' => false]);

            app('shortcode')->register('reviews-service', function (array $attrs) {
                $tag = $attrs['tag'] ?? null;
                $limit = (int) ($attrs['limit'] ?? 6);
                $minRating = (int) ($attrs['min_rating'] ?? 4);

                $visible = fn ($q) => $q
                    ->whereHas('moderation', fn ($m) => $m->where('is_visible', true))
                    ->orWhereDoesntHave('moderation');

                $statsQuery = Review::query()->where($visible);
                if ($tag) {
                    $statsQuery->whereHas('moderation', fn ($m) => $m->whereJsonContains('tags', $tag));
                }

                $row = $statsQuery->selectRaw("COUNT(*) as total, AVG(CASE star_rating
                    WHEN 'ONE'   THEN 1 WHEN 'TWO'   THEN 2 WHEN 'THREE' THEN 3
                    WHEN 'FOUR'  THEN 4 WHEN 'FIVE'  THEN 5 ELSE NULL END) as avg_rating")
                    ->first();

                return view('reviews::shortcodes.service-reviews', [
                    'tag' => $tag,
                    'limit' => $limit,
                    'minRating' => $minRating,
                    'avgRating' => (float) ($row->avg_rating ?? 0),
                    'totalCount' => (int) ($row->total ?? 0),
                ])->render();
            }, [
                'description' => 'Muestra reseñas aleatorias filtradas por tag de servicio/producto.',
                'example' => '[reviews-service tag="mosquiteras" limit="3" /]',
                'cacheable' => false,
                'attributes' => [
                    'tag' => 'Slug del tag de producto (ej: mosquiteras, ventanas-pvc)',
                    'limit' => 'Número de reseñas a mostrar (default: 6)',
                    'min_rating' => 'Rating mínimo 1-5 (default: 4)',
                ],
            ]);

            app('shortcode')->register('reviews', function (array $attrs) {
                $limit = (int) ($attrs['limit'] ?? 6);
                $title = $attrs['title'] ?? null;
                $minRating = (int) ($attrs['min_rating'] ?? 4);
                $featured = filter_var($attrs['featured'] ?? false, FILTER_VALIDATE_BOOLEAN);

                $ratingMap = ['ONE' => 1, 'TWO' => 2, 'THREE' => 3, 'FOUR' => 4, 'FIVE' => 5];

                $reviews = Review::query()
                    ->where(function ($q) {
                        $q->whereHas('moderation', fn ($m) => $m->where('is_visible', true))
                            ->orWhereDoesntHave('moderation');
                    })
                    ->when($featured, fn ($q) => $q->whereHas('moderation', fn ($m) => $m->where('is_featured', true)))
                    ->when($minRating > 1, function ($q) use ($minRating, $ratingMap) {
                        $allowedRatings = array_keys(array_filter($ratingMap, fn ($v) => $v >= $minRating));
                        $q->whereIn('star_rating', $allowedRatings);
                    })
                    ->with('moderation')
                    ->orderByDesc('star_rating')
                    ->orderByDesc('review_time')
                    ->limit($limit)
                    ->get();

                if ($reviews->isEmpty()) {
                    return '';
                }

                return view('template::partials.shortcodes.reviews', [
                    'reviews_override' => $reviews,
                    'limit' => $limit,
                    'show_rating' => true,
                    'reviews_section_title' => $title,
                ])->render();
            });
        });
    }
}
