<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop and recreate the foreign key for template_id to properly allow NULL values
     */
    public function up(): void
    {
        // FK already points to mailer_templates from the create migration — no-op
    }

    public function down(): void
    {
        // no-op
    }
};
