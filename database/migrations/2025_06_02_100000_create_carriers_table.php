<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCarriersTable extends Migration
{
    public function up()
    {
        Schema::create('carriers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->enum('type', ['agency', 'pickup', 'store', 'inpost']);
            $table->string('logo_path')->nullable();
            $table->string('tracking_url')->nullable();
            $table->boolean('is_active')->default(true);

            $table->string('api_endpoint')->nullable();
            $table->string('api_key')->nullable();
            $table->string('api_secret')->nullable();
            $table->string('api_username')->nullable();
            $table->json('api_config')->nullable();

            $table->json('services')->nullable();
            $table->json('zones')->nullable();
            $table->decimal('max_weight', 8, 2)->nullable();
            $table->json('working_hours')->nullable();

            $table->decimal('base_cost', 8, 2)->default(0);
            $table->json('cost_rules')->nullable();

            $table->timestamps();
            $table->index('code');
            $table->index(['type', 'is_active']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('carriers');
    }
}
