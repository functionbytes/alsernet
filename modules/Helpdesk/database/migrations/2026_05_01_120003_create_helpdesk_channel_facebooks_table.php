<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection('helpdesk')->create('helpdesk_channel_facebooks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('account_id')->nullable()->index('hd_channel_facebooks_account_id_index');
            $table->string('page_id')->index('hd_channel_facebooks_page_id_index');
            $table->string('page_name');
            $table->text('page_access_token');
            $table->text('user_access_token')->nullable();
            $table->string('instagram_id')->nullable();
            $table->json('additional_attributes')->nullable();
            $table->timestamps();

            $table->unique(['page_id'], 'hd_channel_facebooks_page_id_unique');
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk')->dropIfExists('helpdesk_channel_facebooks');
    }
};
