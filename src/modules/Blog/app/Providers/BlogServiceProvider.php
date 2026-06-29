<?php

namespace Modules\Blog\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Blog\Events\BlogCommentCreated;
use Modules\Blog\Events\BlogPostPublished;
use Modules\Blog\Events\CommentApproved;
use Modules\Blog\Jobs\PublishScheduledPostsJob;
use Modules\Blog\Listeners\NotifyAdminOnNewComment;
use Modules\Blog\Listeners\NotifyAuthorOnNewComment;
use Modules\Blog\Listeners\NotifyCommenterOnApproval;
use Modules\Blog\Listeners\SendNewsletterOnPublish;
use Modules\Blog\Models\BlogCategory;
use Modules\Blog\Models\BlogPost;
use Modules\Blog\Models\BlogPostComment;
use Modules\Blog\Models\BlogTag;
use Modules\Blog\Observers\BlogPostObserver;
use Modules\Blog\Policies\BlogCategoryPolicy;
use Modules\Blog\Policies\BlogCommentPolicy;
use Modules\Blog\Policies\BlogPostPolicy;
use Modules\Blog\Policies\BlogTagPolicy;
use Modules\Blog\Services\BlogPostService;
use Modules\Blog\Services\CommentService;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Facades\Module;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class BlogServiceProvider extends ServiceProvider
{
    protected string $name = 'Blog';

    protected string $nameLower = 'blog';

    public function boot(): void
    {
        if (Module::find('Blog')?->isDisabled()) {
            return;
        }

        $this->registerConfig();
        $this->registerViews();
        $this->loadTranslationsFrom(module_path($this->name, 'lang'), $this->nameLower);
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
        $this->registerPolicies();
        $this->registerMenus();
        $this->registerSchedule();
        $this->registerEventListeners();
        BlogPost::observe(BlogPostObserver::class);
    }

    public function register(): void
    {
        if (Module::find('Blog')?->isDisabled()) {
            return;
        }

        $this->app->register(RouteServiceProvider::class);
        $this->app->singleton(BlogPostService::class);
        $this->app->singleton(CommentService::class);

        $helperFile = __DIR__.'/../Helpers/BlogHelper.php';
        if (file_exists($helperFile)) {
            require_once $helperFile;
        }
    }

    protected function registerConfig(): void
    {
        $configPath = module_path($this->name, config('modules.paths.generator.config.path'));

        if (! is_dir($configPath)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath));

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $config = str_replace($configPath.DIRECTORY_SEPARATOR, '', $file->getPathname());
            $configKey = str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $config);
            $segments = explode('.', $this->nameLower.'.'.$configKey);

            $normalized = [];
            foreach ($segments as $segment) {
                if (end($normalized) !== $segment) {
                    $normalized[] = $segment;
                }
            }

            $key = ($config === 'config.php') ? $this->nameLower : implode('.', $normalized);

            $this->publishes([$file->getPathname() => config_path($config)], 'config');
            $this->mergeConfigFrom($file->getPathname(), $key);
        }
    }

    protected function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);
    }

    protected function registerPolicies(): void
    {
        Gate::policy(BlogPost::class, BlogPostPolicy::class);
        Gate::policy(BlogPostComment::class, BlogCommentPolicy::class);
        Gate::policy(BlogCategory::class, BlogCategoryPolicy::class);
        Gate::policy(BlogTag::class, BlogTagPolicy::class);
    }

    protected function registerSchedule(): void
    {
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->job(PublishScheduledPostsJob::class)
                ->everyMinute()
                ->withoutOverlapping()
                ->onOneServer();
        });
    }

    protected function registerEventListeners(): void
    {
        Event::listen(BlogCommentCreated::class, NotifyAdminOnNewComment::class);
        Event::listen(BlogCommentCreated::class, NotifyAuthorOnNewComment::class);
        Event::listen(BlogPostPublished::class, SendNewsletterOnPublish::class);
        Event::listen(CommentApproved::class, NotifyCommenterOnApproval::class);
    }

    protected function registerMenus(): void
    {
        NavService::registerMiniItem('blog', [
            'icon' => 'fa-duotone fa-thin fa-square-rss',
            'tooltip' => 'Blog',
            'sidebar_id' => 'blog',
            'order' => 55,
        ]);

        NavService::registerSidebar('blog', [
            'title' => 'Blog',
            'items' => [
                ['label' => 'Publicaciones', 'route' => 'blog.posts.index'],
                ['label' => 'Categorias', 'route' => 'blog.categories.index'],
                ['label' => 'Etiquetas', 'route' => 'blog.tags.index'],
                ['label' => 'Comentarios', 'route' => 'blog.comments.index'],
            ],
        ]);

        NavService::addItemsToSection('settings', 'Configuraciones', [
            ['label' => 'Configuracion del blog', 'route' => 'settings.blog.index'],
        ]);

    }

    public function provides(): array
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->nameLower)) {
                $paths[] = $path.'/modules/'.$this->nameLower;
            }
        }

        return $paths;
    }
}
