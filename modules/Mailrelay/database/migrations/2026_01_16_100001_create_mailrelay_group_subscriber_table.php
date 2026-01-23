<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mailrelay_group_subscriber', function (Blueprint $table) {
            $table->ulid('subscriber_id');
            $table->foreignId('mailrelay_group_id')->constrained('mailrelay_groups')->onDelete('cascade');
            $table->timestamps();

            $table->primary(['subscriber_id', 'mailrelay_group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mailrelay_group_subscriber');
    }
};
