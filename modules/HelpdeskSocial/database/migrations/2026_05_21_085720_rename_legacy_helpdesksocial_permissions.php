<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $map = [
            'helpdesksocial.manage-accounts' => 'helpdesksocial.accounts.manage',
            'helpdesksocial.manage-rules' => 'helpdesksocial.rules.manage',
            'helpdesksocial.manage-templates' => 'helpdesksocial.templates.manage',
            'helpdesksocial.view-analytics' => 'helpdesksocial.analytics.view',
        ];

        foreach ($map as $old => $new) {
            DB::table('permissions')
                ->where('name', $old)
                ->where('guard_name', 'web')
                ->update(['name' => $new]);
        }
    }

    public function down(): void
    {
        $map = [
            'helpdesksocial.accounts.manage' => 'helpdesksocial.manage-accounts',
            'helpdesksocial.rules.manage' => 'helpdesksocial.manage-rules',
            'helpdesksocial.templates.manage' => 'helpdesksocial.manage-templates',
            'helpdesksocial.analytics.view' => 'helpdesksocial.view-analytics',
        ];

        foreach ($map as $old => $new) {
            DB::table('permissions')
                ->where('name', $old)
                ->where('guard_name', 'web')
                ->update(['name' => $new]);
        }
    }
};
