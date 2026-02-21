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
        Schema::create('persistent_logins', function (Blueprint $table) {
            $table->id();
            $table->string('user_type'); // 'client' or 'rider'
            $table->unsignedBigInteger('user_id');
            $table->string('token_hash', 64)->unique(); // Hashed token for storage
            $table->string('device_id', 128)->nullable(); // Device identifier
            $table->string('device_name', 255)->nullable(); // Device name
            $table->string('pin_hash', 255)->nullable(); // Hashed PIN (optional)
            $table->boolean('pin_enabled')->default(false);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            // Index for faster lookups
            $table->index(['user_type', 'user_id']);
            $table->index('token_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('persistent_logins');
    }
};
