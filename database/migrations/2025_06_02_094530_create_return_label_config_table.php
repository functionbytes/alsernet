<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReturnLabelConfigTable extends Migration
{
    public function up()
    {
        Schema::create('return_label_config', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->enum('label_size', ['A4', 'A5', '10x15', '4x6'])->default('A4');
            $table->enum('label_format', ['PDF', 'ZPL', 'EPL'])->default('PDF');
            $table->json('warehouse_address');
            $table->json('return_instructions');
            $table->string('logo_path')->nullable();
            $table->boolean('include_barcode')->default(true);
            $table->boolean('include_qr')->default(false);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('return_label_config');
    }
}
