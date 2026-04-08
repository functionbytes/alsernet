<?php

namespace Modules\Blog\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogSettingsController extends Controller
{
    public function index(): View
    {
        $this->authorize('blog.settings');

        $settings = [
            'posts_per_page' => setting('blog.posts_per_page', config('blog.posts_per_page', 12)),
            'allow_comments' => setting('blog.allow_comments', config('blog.allow_comments', false)),
            'default_status' => setting('blog.default_status', config('blog.default_status', 'draft')),
            'blog_title' => setting('blog.blog_title', config('blog.blog_title', '')),
            'blog_description' => setting('blog.blog_description', config('blog.blog_description', '')),
        ];

        return view('blog::settings.index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('blog.settings');

        $data = $request->validate([
            'posts_per_page' => ['required', 'integer', 'min:1', 'max:100'],
            'allow_comments' => ['boolean'],
            'default_status' => ['required', 'in:draft,published,pending'],
            'blog_title' => ['nullable', 'string', 'max:255'],
            'blog_description' => ['nullable', 'string', 'max:1000'],
        ]);

        updateSettings([
            'blog.posts_per_page' => $data['posts_per_page'],
            'blog.allow_comments' => $data['allow_comments'] ?? false,
            'blog.default_status' => $data['default_status'],
            'blog.blog_title' => $data['blog_title'] ?? '',
            'blog.blog_description' => $data['blog_description'] ?? '',
        ]);

        return redirect()
            ->route('settings.blog.index')
            ->with('success', __('blog::messages.settings_updated'));
    }
}
