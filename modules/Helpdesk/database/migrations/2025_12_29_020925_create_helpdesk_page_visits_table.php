<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('helpdesk_page_visits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('page_url');
            $table->string('referrer')->nullable();
            $table->integer('duration_seconds')->default(0);
            $table->string('device_type')->nullable();
            $table->string('browser')->nullable();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('helpdesk_customers')->onDelete('set null');

            $table->index('customer_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('helpdesk_page_visits');
    }
};
