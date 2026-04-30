<?php

namespace Modules\Chat\Tests\Unit\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Chat\Models\Accounts\Account;
use Modules\Chat\Models\Customers\Customer;
use Modules\Chat\Models\Customers\CustomerSegment;
use Tests\TestCase;

class CustomerScopeTest extends TestCase
{
    use RefreshDatabase;

    private Account $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->account = Account::create([
            'name' => 'Test Account',
            'default_locale' => 'en',
        ]);
    }

    public function test_scope_for_account(): void
    {
        $otherAccount = Account::create([
            'name' => 'Other Account',
            'default_locale' => 'en',
        ]);

        $customer1 = Customer::create([
            'account_id' => $this->account->id,
            'name' => 'Customer 1',
            'email' => 'customer1@test.com',
        ]);

        $customer2 = Customer::create([
            'account_id' => $otherAccount->id,
            'name' => 'Customer 2',
            'email' => 'customer2@test.com',
        ]);

        $results = Customer::forAccount($this->account->id)->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($customer1));
        $this->assertFalse($results->contains($customer2));
    }

    public function test_scope_active_filters_recent_activity(): void
    {
        $activeCustomer = Customer::create([
            'account_id' => $this->account->id,
            'name' => 'Active Customer',
            'email' => 'active@test.com',
            'last_activity_at' => now()->subDays(5),
        ]);

        $inactiveCustomer = Customer::create([
            'account_id' => $this->account->id,
            'name' => 'Inactive Customer',
            'email' => 'inactive@test.com',
            'last_activity_at' => now()->subDays(60),
        ]);

        $noActivityCustomer = Customer::create([
            'account_id' => $this->account->id,
            'name' => 'No Activity Customer',
            'email' => 'noactivity@test.com',
            'last_activity_at' => null,
        ]);

        $results = Customer::active(30)->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($activeCustomer));
        $this->assertFalse($results->contains($inactiveCustomer));
        $this->assertFalse($results->contains($noActivityCustomer));
    }

    public function test_scope_active_with_custom_days(): void
    {
        $recentCustomer = Customer::create([
            'account_id' => $this->account->id,
            'name' => 'Recent Customer',
            'email' => 'recent@test.com',
            'last_activity_at' => now()->subDays(5),
        ]);

        $olderCustomer = Customer::create([
            'account_id' => $this->account->id,
            'name' => 'Older Customer',
            'email' => 'older@test.com',
            'last_activity_at' => now()->subDays(20),
        ]);

        $results = Customer::active(10)->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($recentCustomer));
        $this->assertFalse($results->contains($olderCustomer));
    }

    public function test_scope_search_matches_name_email_phone(): void
    {
        $customer1 = Customer::create([
            'account_id' => $this->account->id,
            'name' => 'John Smith',
            'email' => 'john@test.com',
            'phone_number' => '123456789',
        ]);

        $customer2 = Customer::create([
            'account_id' => $this->account->id,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone_number' => '987654321',
        ]);

        $customer3 = Customer::create([
            'account_id' => $this->account->id,
            'name' => 'Bob Johnson',
            'email' => 'bob@test.com',
            'phone_number' => '555555555',
        ]);

        $nameResults = Customer::search('John')->get();
        $this->assertCount(2, $nameResults);
        $this->assertTrue($nameResults->contains($customer1));
        $this->assertTrue($nameResults->contains($customer3));

        $emailResults = Customer::search('jane@example')->get();
        $this->assertCount(1, $emailResults);
        $this->assertTrue($emailResults->contains($customer2));

        $phoneResults = Customer::search('987654')->get();
        $this->assertCount(1, $phoneResults);
        $this->assertTrue($phoneResults->contains($customer2));
    }

    public function test_scope_created_this_month(): void
    {
        Customer::query()->delete();

        $thisMonthDate = now()->startOfMonth()->addDays(5);
        $thisMonthCustomer = new Customer([
            'account_id' => $this->account->id,
            'name' => 'This Month Customer',
            'email' => 'thismonth@test.com',
        ]);
        $thisMonthCustomer->created_at = $thisMonthDate;
        $thisMonthCustomer->updated_at = $thisMonthDate;
        $thisMonthCustomer->save();

        $lastMonthDate = now()->subMonths(2)->startOfMonth();
        $lastMonthCustomer = new Customer([
            'account_id' => $this->account->id,
            'name' => 'Last Month Customer',
            'email' => 'lastmonth@test.com',
        ]);
        $lastMonthCustomer->created_at = $lastMonthDate;
        $lastMonthCustomer->updated_at = $lastMonthDate;
        $lastMonthCustomer->save();

        $results = Customer::createdThisMonth()->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($thisMonthCustomer));
        $this->assertFalse($results->contains($lastMonthCustomer));
    }

    public function test_scope_duplicate_contact(): void
    {
        $customer1 = Customer::create([
            'account_id' => $this->account->id,
            'name' => 'Customer 1',
            'email' => 'duplicate@test.com',
            'phone_number' => '123456789',
        ]);

        $customer2 = Customer::create([
            'account_id' => $this->account->id,
            'name' => 'Customer 2',
            'email' => 'unique@test.com',
            'phone_number' => '987654321',
        ]);

        $emailResults = Customer::duplicateContact('duplicate@test.com', null)->get();
        $this->assertCount(1, $emailResults);
        $this->assertTrue($emailResults->contains($customer1));

        $phoneResults = Customer::duplicateContact(null, '123456789')->get();
        $this->assertCount(1, $phoneResults);
        $this->assertTrue($phoneResults->contains($customer1));

        $combinedResults = Customer::duplicateContact('unique@test.com', '987654321')->get();
        $this->assertCount(1, $combinedResults);
        $this->assertTrue($combinedResults->contains($customer2));

        $noResults = Customer::duplicateContact('notfound@test.com', null)->get();
        $this->assertCount(0, $noResults);
    }

    public function test_scope_in_segment(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'testuser@test.com',
            'password' => bcrypt('password'),
        ]);

        $segment1 = CustomerSegment::create([
            'account_id' => $this->account->id,
            'user_id' => $user->id,
            'name' => 'VIP Customers',
            'description' => 'High value customers',
        ]);

        $segment2 = CustomerSegment::create([
            'account_id' => $this->account->id,
            'user_id' => $user->id,
            'name' => 'New Customers',
            'description' => 'Recently joined customers',
        ]);

        $customer1 = Customer::create([
            'account_id' => $this->account->id,
            'name' => 'VIP Customer',
            'email' => 'vip@test.com',
        ]);

        $customer2 = Customer::create([
            'account_id' => $this->account->id,
            'name' => 'New Customer',
            'email' => 'new@test.com',
        ]);

        $customer3 = Customer::create([
            'account_id' => $this->account->id,
            'name' => 'Regular Customer',
            'email' => 'regular@test.com',
        ]);

        $customer1->segments()->attach($segment1->id);
        $customer2->segments()->attach($segment2->id);

        $vipResults = Customer::inSegment($segment1->id)->get();
        $this->assertCount(1, $vipResults);
        $this->assertTrue($vipResults->contains($customer1));

        $newResults = Customer::inSegment($segment2->id)->get();
        $this->assertCount(1, $newResults);
        $this->assertTrue($newResults->contains($customer2));
    }
}
