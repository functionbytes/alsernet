<?php

// Inject DB check into the test via a temporary inline test

namespace Modules\System\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Modules\System\Tests\TestCase;

class RoleDbCheckTest extends TestCase
{
    public function test_db_has_role_record_after_assign(): void
    {
        $user = $this->createUserWithRole('administrative');

        $count = DB::table('model_has_roles')
            ->where('model_id', $user->getKey())
            ->count();

        $this->assertGreaterThan(0, $count,
            sprintf('No model_has_roles record. User key: %s, Roles: %s',
                $user->getKey(),
                $user->getRoleNames()->implode(', ')
            )
        );
    }
}
