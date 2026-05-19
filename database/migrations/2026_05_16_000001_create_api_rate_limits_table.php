<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('api_rate_limits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('endpoint', 255);
            $table->string('method', 10);
            $table->string('ip_address', 45)->nullable();
            $table->string('api_key_prefix', 50)->nullable();
            $table->unsignedInteger('requests_count')->default(0);
            $table->unsignedInteger('limit')->default(60);
            $table->unsignedInteger('window_seconds')->default(60);
            $table->boolean('rate_limited')->default(false);
            $table->timestamp('reset_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['endpoint', 'method']);
            $table->index(['user_id', 'created_at']);
            $table->index(['ip_address', 'created_at']);
            $table->index(['rate_limited', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_rate_limits');
    }
};
