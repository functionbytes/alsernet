<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('helpdesk_drip_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('helpdesk_drip_campaigns')->cascadeOnDelete();
            $table->unsignedInteger('step_order')->default(1);
            $table->unsignedInteger('delay_minutes')->default(0);
            $table->string('channel')->nullable(); // override, uses campaign's default if null
            $table->string('template_type')->default('text'); // text|hsm
            $table->text('body')->nullable();
            $table->string('template_id')->nullable(); // HSM template name
            $table->json('template_params')->nullable();
            $table->timestamps();

            $table->index(['campaign_id', 'step_order']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_drip_steps');
    }
};
