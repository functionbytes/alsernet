<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaign_sending_servers', function (Blueprint $t): void {
            $t->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('campaign_sending_servers', function (Blueprint $t): void {
            $t->dropSoftDeletes();
        });
    }
};
