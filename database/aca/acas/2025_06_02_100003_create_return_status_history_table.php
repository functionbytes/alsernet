<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReturnStatusHistoryTable extends Migration
{
    public function up()
    {
        Schema::create('return_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_id')->constrained('returns')->onDelete('cascade');
            $table->string('previous_status')->nullable();
            $table->string('new_status');
            $table->string('changed_by')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['return_id', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('return_status_history');
    }
}
