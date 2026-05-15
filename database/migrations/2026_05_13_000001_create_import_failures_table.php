<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('import_failures', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('import_export_log_id')
                ->constrained('import_export_logs')
                ->cascadeOnDelete();
            $table->unsignedInteger('row_number')->nullable();
            $table->string('attribute')->nullable();
            $table->json('errors')->nullable();
            $table->json('values')->nullable();
            $table->timestamps();

            $table->index(['import_export_log_id', 'row_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_failures');
    }
};
