<?php

namespace Modules\Ecommerce\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Ecommerce\Models\NewsletterSubscriber;
use Tests\TestCase;

class NewsletterTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitor_can_subscribe_to_newsletter(): void
    {
        $response = $this->post(route('shop.newsletter.subscribe'), [
            'email' => 'subscriber@example.com',
            'name' => 'Juan',
        ]);

        $this->assertDatabaseHas('ecommerce_newsletter_subscribers', [
            'email' => 'subscriber@example.com',
            'status' => 'subscribed',
        ]);
    }

    public function test_subscribe_validates_email_required(): void
    {
        $response = $this->post(route('shop.newsletter.subscribe'), []);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_subscribe_validates_email_format(): void
    {
        $response = $this->post(route('shop.newsletter.subscribe'), [
            'email' => 'not-an-email',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_subscribe_validates_unique_email(): void
    {
        NewsletterSubscriber::factory()->create(['email' => 'existing@example.com']);

        $response = $this->post(route('shop.newsletter.subscribe'), [
            'email' => 'existing@example.com',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_subscriber_can_unsubscribe_via_token(): void
    {
        $subscriber = NewsletterSubscriber::factory()->create([
            'unsubscribe_token' => 'unique_token_123',
            'status' => 'subscribed',
        ]);

        $this->get(route('shop.newsletter.unsubscribe', 'unique_token_123'));

        $subscriber->refresh();
        $this->assertEquals('unsubscribed', $subscriber->status);
        $this->assertNotNull($subscriber->unsubscribed_at);
    }
}
