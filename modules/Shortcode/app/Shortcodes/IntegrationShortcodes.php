<?php

namespace Modules\Shortcode\Shortcodes;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Modules\Blog\Models\BlogCategory;
use Modules\Blog\Models\BlogPost;
use Modules\Forms\Models\Form;
use Modules\Media\Models\MediaFile;
use Modules\Page\Models\Page;
use Modules\Shortcode\Compiler\ShortcodeCompiler;
use Modules\Template\Models\Menu;

/**
 * Shortcodes que integran el módulo Shortcode con otros módulos del CMS.
 *
 * Cada método register*() verifica class_exists() antes de registrar,
 * así este módulo sigue funcionando si las dependencias están deshabilitadas.
 */
class IntegrationShortcodes
{
    public function __construct(private readonly ShortcodeCompiler $compiler) {}

    public function registerAll(): void
    {
        $this->registerForm();
        $this->registerPage();
        $this->registerMenu();
        $this->registerBlog();
        $this->registerMediaShortcode();
    }

    // ---------------------------------------------------------------------
    // [form id="X"] / [form slug="contacto"]
    // ---------------------------------------------------------------------

    protected function registerForm(): void
    {
        if (! class_exists(Form::class)) {
            return;
        }

        $this->compiler->register('form', function (array $attrs): string {
            $id = $attrs['id'] ?? null;
            $slug = $attrs['slug'] ?? null;

            if (! $id && ! $slug) {
                return '';
            }

            try {
                $query = Form::query();
                $form = $id
                    ? $query->find((int) $id)
                    : $query->where('slug', $slug)->first();

                if (! $form || (isset($form->is_active) && ! $form->is_active)) {
                    return '';
                }

                // Usa la URL pública del form. Delegamos a ruta pública si existe.
                $url = Route::has('forms.public.show')
                    ? route('forms.public.show', $form->slug ?? $form->id)
                    : null;

                if ($url) {
                    return sprintf(
                        '<iframe src="%s" class="shortcode-form-iframe w-100 border-0" style="min-height: 400px;" loading="lazy" title="%s"></iframe>',
                        htmlspecialchars($url),
                        htmlspecialchars($form->name ?? 'Formulario')
                    );
                }

                // Fallback: link al form público.
                return sprintf(
                    '<a href="#form-%d" class="btn btn-primary">%s</a>',
                    (int) $form->id,
                    htmlspecialchars($form->name ?? 'Abrir formulario')
                );
            } catch (\Throwable $e) {
                Log::warning('form shortcode error: '.$e->getMessage());

                return '';
            }
        }, [
            'description' => 'Inserta un formulario del módulo Forms por id o slug.',
            'example' => '[form slug="contacto" /]',
            'attributes' => [
                'id' => 'ID numérico del formulario',
                'slug' => 'Slug del formulario (alternativa a id)',
            ],
            'cacheable' => false, // CSRF/estado del form cambia por sesión
        ]);
    }

    // ---------------------------------------------------------------------
    // [page slug="about"]
    // ---------------------------------------------------------------------

    protected function registerPage(): void
    {
        if (! class_exists(Page::class)) {
            return;
        }

        $this->compiler->register('page', function (array $attrs): string {
            $slug = $attrs['slug'] ?? null;
            $id = $attrs['id'] ?? null;

            if (! $slug && ! $id) {
                return '';
            }

            try {
                $query = Page::query();
                $page = $id
                    ? $query->find((int) $id)
                    : $query->where('slug', $slug)->first();

                if (! $page) {
                    return '';
                }

                // Usa scope published() si existe.
                if (method_exists($page, 'isPublished') && ! $page->isPublished()) {
                    return '';
                }

                $content = (string) ($page->content ?? '');

                // Evita recursión infinita: no re-compila si la página llama al mismo shortcode.
                $depth = (int) request()->attributes->get('shortcode.page.depth', 0);
                if ($depth >= 3) {
                    return '<!-- page shortcode: recursion limit -->';
                }
                request()->attributes->set('shortcode.page.depth', $depth + 1);

                try {
                    return app('shortcode')->compile($content);
                } finally {
                    request()->attributes->set('shortcode.page.depth', $depth);
                }
            } catch (\Throwable $e) {
                Log::warning('page shortcode error: '.$e->getMessage());

                return '';
            }
        }, [
            'description' => 'Inyecta el contenido de otra página del módulo Page.',
            'example' => '[page slug="about" /]',
            'attributes' => [
                'slug' => 'Slug de la página a inyectar',
                'id' => 'ID numérico (alternativa a slug)',
            ],
        ]);
    }

    // ---------------------------------------------------------------------
    // [menu location="header"]
    // ---------------------------------------------------------------------

    protected function registerMenu(): void
    {
        if (! class_exists(Menu::class)) {
            return;
        }

        $this->compiler->register('menu', function (array $attrs): string {
            $location = $attrs['location'] ?? null;
            $slug = $attrs['slug'] ?? null;
            $class = $attrs['class'] ?? 'nav';

            if (! $location && ! $slug) {
                return '';
            }

            try {
                $query = Menu::query()->with('items');
                $menu = $location
                    ? $query->where('location', $location)->first()
                    : $query->where('slug', $slug)->first();

                if (! $menu) {
                    return '';
                }

                $items = $menu->items ?? collect();

                // Ordena por 'order' si existe.
                if (method_exists($items, 'sortBy')) {
                    $items = $items->sortBy('order');
                }

                $html = sprintf('<ul class="%s">', htmlspecialchars($class));
                foreach ($items as $item) {
                    $label = $item->display_title ?? $item->title ?? '';
                    $url = $item->full_url ?? $item->url ?? '#';
                    $target = ! empty($item->target) ? sprintf(' target="%s"', htmlspecialchars($item->target)) : '';

                    $html .= sprintf(
                        '<li class="nav-item"><a class="nav-link" href="%s"%s>%s</a></li>',
                        htmlspecialchars($url),
                        $target,
                        htmlspecialchars($label)
                    );
                }
                $html .= '</ul>';

                return $html;
            } catch (\Throwable $e) {
                Log::warning('menu shortcode error: '.$e->getMessage());

                return '';
            }
        }, [
            'description' => 'Renderiza un menú del módulo Template por location o slug.',
            'example' => '[menu location="header" class="navbar-nav" /]',
            'attributes' => [
                'location' => 'Location del menú (header, footer, sidebar)',
                'slug' => 'Slug del menú (alternativa)',
                'class' => 'Clases CSS del <ul> contenedor',
            ],
        ]);
    }

    // ---------------------------------------------------------------------
    // [latest-posts count="5"] / [post id="X"]
    // ---------------------------------------------------------------------

    protected function registerBlog(): void
    {
        if (! class_exists(BlogPost::class)) {
            return;
        }

        // [latest-posts count="5" category="news"]
        $this->compiler->register('latest-posts', function (array $attrs): string {
            $count = max(1, min(20, (int) ($attrs['count'] ?? 5)));
            $categorySlug = $attrs['category'] ?? null;

            try {
                $query = BlogPost::query();

                if (method_exists(BlogPost::class, 'scopePublished')
                    || method_exists($query->getModel(), 'scopePublished')) {
                    $query->published();
                }

                if ($categorySlug && class_exists(BlogCategory::class)) {
                    $query->whereHas('categories', fn ($q) => $q->where('slug', $categorySlug));
                }

                $posts = $query->orderByDesc('published_at')->take($count)->get();

                if ($posts->isEmpty()) {
                    return '<p class="text-muted small">No hay publicaciones.</p>';
                }

                $html = '<ul class="latest-posts-list list-unstyled">';
                foreach ($posts as $post) {
                    $url = $post->url ?? '#';
                    $title = $post->title ?? '';
                    $excerpt = $post->excerpt ?? $post->description ?? '';
                    $excerpt = Str::limit(strip_tags($excerpt), 150);

                    $html .= sprintf(
                        '<li class="mb-3"><a href="%s" class="fw-semibold">%s</a><p class="small text-muted mb-0">%s</p></li>',
                        htmlspecialchars($url),
                        htmlspecialchars($title),
                        htmlspecialchars($excerpt)
                    );
                }
                $html .= '</ul>';

                return $html;
            } catch (\Throwable $e) {
                Log::warning('latest-posts shortcode error: '.$e->getMessage());

                return '';
            }
        }, [
            'description' => 'Lista las últimas publicaciones del blog.',
            'example' => '[latest-posts count="5" category="news" /]',
            'attributes' => [
                'count' => 'Cantidad a mostrar (1-20)',
                'category' => 'Filtrar por slug de categoría',
            ],
        ]);

        // [post id="X"] o [post slug="X"]
        $this->compiler->register('post', function (array $attrs): string {
            $id = $attrs['id'] ?? null;
            $slug = $attrs['slug'] ?? null;

            if (! $id && ! $slug) {
                return '';
            }

            try {
                $query = BlogPost::query();
                $post = $id
                    ? $query->find((int) $id)
                    : $query->where('slug', $slug)->first();

                if (! $post) {
                    return '';
                }

                if (method_exists($post, 'isPublished') && ! $post->isPublished()) {
                    return '';
                }

                $url = $post->url ?? '#';
                $title = htmlspecialchars($post->title ?? '');
                $excerpt = htmlspecialchars(Str::limit(
                    strip_tags($post->excerpt ?? $post->description ?? ''),
                    200
                ));

                return sprintf(
                    '<article class="post-shortcode card"><div class="card-body"><h5 class="card-title"><a href="%s">%s</a></h5><p class="card-text text-muted">%s</p></div></article>',
                    htmlspecialchars($url),
                    $title,
                    $excerpt
                );
            } catch (\Throwable $e) {
                Log::warning('post shortcode error: '.$e->getMessage());

                return '';
            }
        }, [
            'description' => 'Renderiza una tarjeta resumen de una publicación del blog.',
            'example' => '[post slug="mi-articulo" /]',
            'attributes' => [
                'id' => 'ID numérico del post',
                'slug' => 'Slug del post',
            ],
        ]);
    }

    // ---------------------------------------------------------------------
    // [media id="X" variant="thumb"]
    // ---------------------------------------------------------------------

    protected function registerMediaShortcode(): void
    {
        if (! class_exists(MediaFile::class)) {
            return;
        }

        $this->compiler->register('media', function (array $attrs): string {
            $id = $attrs['id'] ?? null;
            $variant = $attrs['variant'] ?? null;
            $alt = $attrs['alt'] ?? '';
            $class = $attrs['class'] ?? 'img-fluid';

            if (! ctype_digit((string) $id) || (int) $id <= 0) {
                return '';
            }

            try {
                $file = MediaFile::find((int) $id);
                if (! $file) {
                    return '';
                }

                $url = function_exists('media') ? media((int) $id, $variant) : ($file->url ?? '#');
                $alt = $alt !== '' ? $alt : ($file->name ?? '');
                $type = $file->type ?? 'image';

                if ($type === 'video') {
                    return sprintf(
                        '<video src="%s" class="%s" controls preload="metadata"></video>',
                        htmlspecialchars($url),
                        htmlspecialchars($class)
                    );
                }

                if ($type === 'audio') {
                    return sprintf(
                        '<audio src="%s" class="%s" controls preload="metadata"></audio>',
                        htmlspecialchars($url),
                        htmlspecialchars($class)
                    );
                }

                return sprintf(
                    '<img src="%s" class="%s" alt="%s" loading="lazy">',
                    htmlspecialchars($url),
                    htmlspecialchars($class),
                    htmlspecialchars($alt)
                );
            } catch (\Throwable $e) {
                Log::warning('media shortcode error: '.$e->getMessage());

                return '';
            }
        }, [
            'description' => 'Inserta media (imagen/video/audio) del módulo Media por id.',
            'example' => '[media id="42" variant="thumb" alt="Logo" /]',
            'attributes' => [
                'id' => 'ID numérico del archivo',
                'variant' => 'Tamaño de thumbnail (thumb, medium, large)',
                'alt' => 'Texto alternativo',
                'class' => 'Clases CSS',
            ],
        ]);
    }
}
