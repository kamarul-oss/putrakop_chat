<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name_en');
            $table->string('name_bm');
            $table->text('description_en')->nullable();
            $table->text('description_bm')->nullable();
            $table->string('color')->default('#1E40AF');
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0);
            $table->integer('max_queue_size')->default(50);
            $table->integer('max_agents')->default(10);
            $table->json('business_hours')->nullable();
            $table->json('ai_config')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
            $table->index('priority');
        });

        Schema::create('routing_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->enum('rule_type', ['round_robin', 'skill_based', 'least_loaded', 'random'])->default('round_robin');
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('config')->nullable();
            $table->json('conditions')->nullable();
            $table->timestamps();

            $table->index(['department_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routing_rules');
        Schema::dropIfExists('departments');
    }
};
