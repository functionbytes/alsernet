<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_ai_contents', function (Blueprint $table) {
            $table->foreignId('assigned_to')->nullable()->after('validated_by')
                ->constrained('users')->onDelete('set null');
            $table->timestamp('assigned_at')->nullable()->after('assigned_to');

            $table->index('assigned_to');
        });
    }

    public function down(): void
    {
        Schema::table('supplier_ai_contents', function (Blueprint $table) {
            $table->dropForeign(['assigned_to']);
            $table->dropIndex(['assigned_to']);
            $table->dropColumn(['assigned_to', 'assigned_at']);
        });
    }
};
