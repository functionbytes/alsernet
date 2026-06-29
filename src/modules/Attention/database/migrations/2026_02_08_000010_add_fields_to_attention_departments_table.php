<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add new fields to attention_departments table
        Schema::table('attention_departments', function (Blueprint $table) {
            $table->string('responsible_name', 150)->nullable()->after('email');
            $table->string('responsible_email', 150)->nullable()->after('responsible_name');
            $table->string('phone', 20)->nullable()->after('responsible_email');
        });

        // Create pivot table for department-user relationship
        Schema::create('attention_department_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained('attention_departments')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['department_id', 'user_id']);
            $table->index('department_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attention_department_user');

        Schema::table('attention_departments', function (Blueprint $table) {
            $table->dropColumn(['responsible_name', 'responsible_email', 'phone']);
        });
    }
};
