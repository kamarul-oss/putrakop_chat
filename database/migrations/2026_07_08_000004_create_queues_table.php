<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->enum('status', ['waiting', 'assigned', 'cancelled'])->default('waiting');
            $table->integer('position');
            $table->integer('priority_score')->default(0);
            $table->integer('estimated_wait_seconds')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();

            $table->index(['department_id', 'status', 'position']);
            $table->unique('conversation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queues');
    }
};
