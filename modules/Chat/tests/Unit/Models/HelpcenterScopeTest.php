<?php

namespace Modules\Chat\Tests\Unit\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Chat\Models\Helpcenters\HelpcenterArticle;
use Modules\Chat\Models\Helpcenters\HelpcenterCategory;
use Tests\TestCase;

class HelpcenterScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_scope_search(): void
    {
        $category1 = HelpcenterCategory::create([
            'name' => 'Getting Started',
            'description' => 'Learn the basics',
            'is_section' => false,
        ]);

        $category2 = HelpcenterCategory::create([
            'name' => 'Advanced Topics',
            'description' => 'Deep dive into features',
            'is_section' => false,
        ]);

        $category3 = HelpcenterCategory::create([
            'name' => 'Troubleshooting',
            'description' => 'Fix common issues',
            'is_section' => false,
        ]);

        $results = HelpcenterCategory::search('Getting')->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($category1));
        $this->assertFalse($results->contains($category2));
        $this->assertFalse($results->contains($category3));
    }

    public function test_category_scope_search_partial_match(): void
    {
        $category1 = HelpcenterCategory::create([
            'name' => 'Account Settings',
            'is_section' => false,
        ]);

        $category2 = HelpcenterCategory::create([
            'name' => 'Billing and Payments',
            'is_section' => false,
        ]);

        $results = HelpcenterCategory::search('settings')->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($category1));
    }

    public function test_category_scope_ordered(): void
    {
        $category1 = HelpcenterCategory::create([
            'name' => 'Third',
            'position' => 3,
            'is_section' => false,
        ]);

        $category2 = HelpcenterCategory::create([
            'name' => 'First',
            'position' => 1,
            'is_section' => false,
        ]);

        $category3 = HelpcenterCategory::create([
            'name' => 'Second',
            'position' => 2,
            'is_section' => false,
        ]);

        $results = HelpcenterCategory::ordered()->get();

        $this->assertCount(3, $results);
        $this->assertEquals('First', $results[0]->name);
        $this->assertEquals('Second', $results[1]->name);
        $this->assertEquals('Third', $results[2]->name);
    }

    public function test_category_scope_categories(): void
    {
        $category = HelpcenterCategory::create([
            'name' => 'Main Category',
            'is_section' => false,
            'parent_id' => null,
        ]);

        $section = HelpcenterCategory::create([
            'name' => 'Section',
            'is_section' => true,
            'parent_id' => $category->id,
        ]);

        $results = HelpcenterCategory::categories()->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($category));
        $this->assertFalse($results->contains($section));
    }

    public function test_category_scope_sections(): void
    {
        $category = HelpcenterCategory::create([
            'name' => 'Main Category',
            'is_section' => false,
            'parent_id' => null,
        ]);

        $section = HelpcenterCategory::create([
            'name' => 'Section',
            'is_section' => true,
            'parent_id' => $category->id,
        ]);

        $results = HelpcenterCategory::query()->sections()->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($section));
        $this->assertFalse($results->contains($category));
    }

    public function test_article_scope_published(): void
    {
        $user = User::create([
            'name' => 'Author',
            'email' => 'author@test.com',
            'password' => bcrypt('password'),
        ]);

        $publishedArticle = HelpcenterArticle::create([
            'title' => 'Published Article',
            'body' => 'Content',
            'draft' => false,
            'author_id' => $user->id,
        ]);

        $draftArticle = HelpcenterArticle::create([
            'title' => 'Draft Article',
            'body' => 'Content',
            'draft' => true,
            'author_id' => $user->id,
        ]);

        $results = HelpcenterArticle::published()->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($publishedArticle));
        $this->assertFalse($results->contains($draftArticle));
    }

    public function test_article_scope_drafts(): void
    {
        $user = User::create([
            'name' => 'Author',
            'email' => 'author@test.com',
            'password' => bcrypt('password'),
        ]);

        $draftArticle = HelpcenterArticle::create([
            'title' => 'Draft Article',
            'body' => 'Content',
            'draft' => true,
            'author_id' => $user->id,
        ]);

        $publishedArticle = HelpcenterArticle::create([
            'title' => 'Published Article',
            'body' => 'Content',
            'draft' => false,
            'author_id' => $user->id,
        ]);

        $results = HelpcenterArticle::drafts()->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($draftArticle));
        $this->assertFalse($results->contains($publishedArticle));
    }

    public function test_article_scope_search(): void
    {
        $user = User::create([
            'name' => 'Author',
            'email' => 'author@test.com',
            'password' => bcrypt('password'),
        ]);

        $article1 = HelpcenterArticle::create([
            'title' => 'How to Reset Your Password',
            'body' => 'Step by step guide',
            'draft' => false,
            'author_id' => $user->id,
        ]);

        $article2 = HelpcenterArticle::create([
            'title' => 'Account Setup Guide',
            'body' => 'Getting started',
            'draft' => false,
            'author_id' => $user->id,
        ]);

        $article3 = HelpcenterArticle::create([
            'title' => 'Billing Information',
            'body' => 'Payment methods',
            'draft' => false,
            'author_id' => $user->id,
        ]);

        $results = HelpcenterArticle::search('Password')->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($article1));
        $this->assertFalse($results->contains($article2));
        $this->assertFalse($results->contains($article3));
    }

    public function test_article_scope_search_partial_match(): void
    {
        $user = User::create([
            'name' => 'Author',
            'email' => 'author@test.com',
            'password' => bcrypt('password'),
        ]);

        $article1 = HelpcenterArticle::create([
            'title' => 'Understanding Analytics',
            'body' => 'Content',
            'draft' => false,
            'author_id' => $user->id,
        ]);

        $article2 = HelpcenterArticle::create([
            'title' => 'Setting up Notifications',
            'body' => 'Content',
            'draft' => false,
            'author_id' => $user->id,
        ]);

        $results = HelpcenterArticle::search('analytic')->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($article1));
    }

    public function test_article_scope_draft_status(): void
    {
        $user = User::create([
            'name' => 'Author',
            'email' => 'author@test.com',
            'password' => bcrypt('password'),
        ]);

        $draft = HelpcenterArticle::create([
            'title' => 'Draft Article',
            'body' => 'Content',
            'draft' => true,
            'author_id' => $user->id,
        ]);

        $published = HelpcenterArticle::create([
            'title' => 'Published Article',
            'body' => 'Content',
            'draft' => false,
            'author_id' => $user->id,
        ]);

        $draftResults = HelpcenterArticle::draftStatus(true)->get();
        $this->assertCount(1, $draftResults);
        $this->assertTrue($draftResults->contains($draft));

        $publishedResults = HelpcenterArticle::draftStatus(false)->get();
        $this->assertCount(1, $publishedResults);
        $this->assertTrue($publishedResults->contains($published));
    }
}
