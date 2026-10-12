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
        Schema::create('notices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('title');
            $table->text('body')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('todos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->date('due_on')->nullable();
            $table->timestamp('done_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'done_at']);
        });

        Schema::create('recurring_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('frequency', 16);
            $table->unsignedTinyInteger('weekday')->nullable();
            $table->unsignedTinyInteger('day_of_month')->nullable();
            $table->time('time_of_day')->nullable();
            $table->date('starts_on');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('recurring_task_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recurring_task_id')->constrained()->cascadeOnDelete();
            $table->date('due_on');
            $table->timestamp('completed_at');

            $table->unique(['recurring_task_id', 'due_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_task_completions');
        Schema::dropIfExists('recurring_tasks');
        Schema::dropIfExists('todos');
        Schema::dropIfExists('notices');
    }
};
