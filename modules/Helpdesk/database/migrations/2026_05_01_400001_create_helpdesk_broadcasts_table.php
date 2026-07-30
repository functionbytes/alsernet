<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('helpdesk_broadcasts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('channel'); // whatsapp|facebook|instagram|email|widget
            $table->string('template_type')->default('text'); // text|hsm
            $table->text('body')->nullable();
            $table->string('template_id')->nullable(); // HSM template name
            $table->json('template_params')->nullable();
            $table->json('filters')->nullable(); // segmentation query filters
            $table->string('status')->default('draft'); // draft|scheduled|sending|sent|failed
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->unsignedInteger('recipients_count')->default(0);
            $table->unsignedInteger('delivered_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->index('status');
            $table->index('channel');
            $table->index('created_by');
            $table->index('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_broadcasts');
    }
};
