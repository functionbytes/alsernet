<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('helpdesk_drip_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('trigger_type'); // tag_added|conversation_closed|csat_low|manual
            $table->string('trigger_value')->nullable(); // e.g. tag slug for tag_added
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->index('trigger_type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_drip_campaigns');
    }
};
