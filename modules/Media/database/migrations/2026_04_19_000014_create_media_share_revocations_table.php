<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_share_revocations', function (Blueprint $table): void {
            $table->id();
            $table->string('token_hash', 64);
            $table->foreignId('revoked_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->text('reason')->nullable();
            $table->timestamp('revoked_at')->useCurrent();
            $table->unique('token_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_share_revocations');
    }
};
