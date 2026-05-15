<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('archived_audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('event')->index();
            $table->string('auditable_type')->nullable()->index();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->longText('old_values')->nullable(); // longText for archived data
            $table->longText('new_values')->nullable();
            $table->json('metadata')->nullable();
            $table->string('checksum')->unique()->index();
            $table->timestamps();

            $table->index(['auditable_type', 'auditable_id']);
            $table->index('created_at');
            $table->index('event');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archived_audit_logs');
    }
};
