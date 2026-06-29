<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('review_google_locations', function (Blueprint $table) {
            if (! Schema::hasColumn('review_google_locations', 'place_id')) {
                $table->string('place_id')->nullable()->after('google_account_id');
            }

            if (! Schema::hasColumn('review_google_locations', 'sync_strategy')) {
                $table->enum('sync_strategy', ['oauth', 'places_api', 'serpapi', 'manual'])
                    ->default('oauth')
                    ->after('place_id');
            }

            $table->dropForeign(['connection_id']);
            $table->unsignedBigInteger('connection_id')->nullable()->change();
            $table->foreign('connection_id')
                ->references('id')
                ->on('review_google_connections')
                ->cascadeOnDelete();
        });

        $existingIndexNames = collect(Schema::getIndexes('review_google_locations'))
            ->pluck('name')
            ->all();

        Schema::table('review_google_locations', function (Blueprint $table) use ($existingIndexNames) {
            if (! in_array('review_google_locations_place_id_index', $existingIndexNames)) {
                $table->index('place_id');
            }

            if (! in_array('review_google_locations_sync_strategy_index', $existingIndexNames)) {
                $table->index('sync_strategy');
            }
        });
    }

    public function down(): void
    {
        Schema::table('review_google_locations', function (Blueprint $table) {
            $table->dropIndex(['place_id']);
            $table->dropIndex(['sync_strategy']);
            $table->dropColumn(['place_id', 'sync_strategy']);

            $table->dropForeign(['connection_id']);
            $table->unsignedBigInteger('connection_id')->nullable(false)->change();
            $table->foreign('connection_id')
                ->references('id')
                ->on('review_google_connections')
                ->cascadeOnDelete();
        });
    }
};
