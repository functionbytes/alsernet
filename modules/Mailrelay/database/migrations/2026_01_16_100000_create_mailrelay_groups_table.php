<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mailrelay_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mailrelay_group_id')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('subscriber_count')->default(0);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index('mailrelay_group_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mailrelay_groups');
    }
};
