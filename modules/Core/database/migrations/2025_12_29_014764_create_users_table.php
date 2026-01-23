<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('uid')->unique()->nullable();
            $table->string('firstname')->nullable();
            $table->string('lastname')->nullable();
            $table->string('identification')->nullable();
            $table->string('cellphone')->nullable();
            $table->string('email')->unique();
            $table->timestamp('mail_verified_at')->nullable();
            $table->string('password');
            $table->string('address')->nullable();
            $table->boolean('available')->default(1);
            $table->boolean('verified')->default(0);
            $table->boolean('terms')->default(0);
            $table->string('validation')->nullable();
            $table->string('page')->nullable();
            $table->json('setting')->nullable();
            $table->string('role')->nullable();
            $table->string('company')->nullable();
            $table->json('detail')->nullable();
            $table->string('user_img')->nullable();
            $table->unsignedBigInteger('citie_id')->nullable();
            $table->foreignId('enterprise_id')->nullable();
            $table->string('timezone')->nullable();
            $table->boolean('voilated')->default(0);
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip')->nullable();
            $table->timestamp('last_logins_at')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->index('email');
            $table->index('available');
            $table->index('uid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
