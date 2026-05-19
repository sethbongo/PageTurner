<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('backup_monitoring', function (Blueprint $table): void {
            $table->id();
            $table->string('backup_name', 255);
            $table->string('disk', 50);
            $table->string('path', 255);
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('type', 50)->nullable(); // full, incremental, etc
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->boolean('verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->string('health_status', 20)->default('unknown');
            $table->timestamp('next_backup_scheduled_at')->nullable();
            $table->timestamps();

            $table->index(['backup_name', 'completed_at']);
            $table->index(['status', 'created_at']);
            $table->index(['health_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_monitoring');
    }
};
