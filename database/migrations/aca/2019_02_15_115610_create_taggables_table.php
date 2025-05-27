<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTaggablesTable extends Migration {


    public function up()
    {
        Schema::create('taggables', function(Blueprint $table)
        {
            $table->integer('tag_id');
            $table->integer('taggable_id');
            $table->string('taggable_type');
            $table->timestamps();
            $table->softDeletes();
        });

    }

    public function down()
    {
        Schema::drop('taggables');
    }

}
