<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_users', function (Blueprint $table) {
            $table->unsignedInteger('id');
            $table->char('uid', 36)->unique();
            $table->string('api_token');
            $table->unsignedInteger('creator_id')->nullable();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('remember_token', 100)->nullable();
            $table->string('status')->nullable();
            $table->longText('quota')->nullable();
            $table->boolean('activated')->default(0);
            $table->string('one_time_api_token')->nullable();
            $table->unsignedInteger('customer_id')->nullable();
            $table->text('first_name')->nullable();
            $table->text('last_name')->nullable();
            $table->string('google_id')->nullable();
            $table->text('google_token')->nullable();
            $table->text('google_refresh_token')->nullable();
            $table->string('facebook_id')->nullable();
            $table->text('facebook_token')->nullable();
            $table->text('facebook_refresh_token')->nullable();
            $table->longText('onscreen_intros')->nullable();
            $table->string('lang', 45)->nullable();
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->foreign('creator_id')->references('id')->on('mailing_users')->onDelete('set null');
            $table->foreign('customer_id')->references('id')->on('mailing_customers')->onDelete('set null');

            $table->index('creator_id');
            $table->index('customer_id');

            // Foreign Keys
            $table->foreign('creator_id')
                ->references('id')
                ->on('mailing_users')
                ->onDelete('set null');
            $table->foreign('customer_id')
                ->references('id')
                ->on('mailing_customers')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_users');
    }
};
