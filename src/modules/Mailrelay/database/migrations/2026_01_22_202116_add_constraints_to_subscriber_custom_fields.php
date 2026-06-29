<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Constraints already defined in create_subscriber_custom_fields_table migration.
    }

    public function down(): void
    {
        // Nothing to reverse.
    }
};
