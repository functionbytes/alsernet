<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('supplier_automation_settings')) {
            return;
        }

        $usersTableExists = Schema::hasTable('users');

        Schema::create('supplier_automation_settings', function (Blueprint $table) use ($usersTableExists) {
            $table->id();
            $table->string('key', 100)->unique()->comment('Configuration key');
            $table->text('value')->comment('Configuration value (can be JSON)');
            $table->enum('type', ['string', 'integer', 'boolean', 'json', 'encrypted'])->default('string');
            $table->string('category', 50)->comment('Category: connection, security, limits, defaults');
            $table->text('description')->nullable()->comment('Description for UI');
            $table->boolean('is_sensitive')->default(false)->comment('Hide in logs if sensitive');

            // Constrain to users only when that table is already migrated; the
            // Auth module owns the users table and may run after this one in
            // RefreshDatabase test scenarios. The FK is added after the fact
            // by the recovery migration further below if it was deferred.
            if ($usersTableExists) {
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            } else {
                $table->unsignedBigInteger('updated_by')->nullable();
            }

            $table->timestamp('updated_at')->nullable();

            $table->index('category');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_automation_settings');
    }
};
