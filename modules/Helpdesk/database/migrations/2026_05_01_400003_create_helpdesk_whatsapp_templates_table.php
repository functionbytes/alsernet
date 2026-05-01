<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('helpdesk_whatsapp_templates', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->unique(); // template name in Meta (e.g. "order_confirmation")
            $table->string('display_name');
            $table->string('language', 10)->default('es');
            $table->string('category'); // utility|marketing|authentication
            $table->string('status')->default('pending'); // approved|rejected|pending
            $table->text('body_template')->nullable(); // body with {{1}} placeholders
            $table->string('header_type')->nullable(); // text|image|document|video
            $table->text('header_value')->nullable();
            $table->text('footer_text')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('category');
            $table->index('language');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_whatsapp_templates');
    }
};
