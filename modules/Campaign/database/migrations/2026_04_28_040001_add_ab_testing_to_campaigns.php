<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table): void {
            $table->enum('variant', ['A', 'B'])->nullable()->after('type')->comment('Para AB testing: A o B');
            $table->unsignedBigInteger('parent_campaign_id')->nullable()->after('variant');
            $table->foreign('parent_campaign_id')
                ->references('id')
                ->on('campaigns')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table): void {
            $table->dropForeign(['parent_campaign_id']);
            $table->dropColumn(['variant', 'parent_campaign_id']);
        });
    }
};
