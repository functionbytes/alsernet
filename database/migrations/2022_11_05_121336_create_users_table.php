<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateUsersTable.
 */
class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('slack')->nullable();
            $table->string('firstname')->nullable();
            $table->string('lastname')->nullable();
            $table->string('cellphone')->nullable();
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->string('role')->nullable();
            $table->string('detail')->nullable();
            $table->timestamp('password_changed_at')->nullable();
            $table->tinyInteger('available')->default(0)->unsigned();
            $table->tinyInteger('verified')->default(0)->unsigned();
            $table->tinyInteger('terms')->default(0)->unsigned();
            $table->tinyInteger('validation')->default(0)->unsigned();
            $table->tinyInteger('page')->default(0)->unsigned();
            $table->tinyInteger('setting')->default(0)->unsigned();
            $table->string('confirmation_code')->nullable();
            $table->boolean('confirmed')->default(0)->unsigned();
            $table->string('timezone')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip')->nullable();
            $table->string('session')->nullable();
            $table->unsignedBigInteger('shop_id')->nullable();
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade')->onUpdate('cascade');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
