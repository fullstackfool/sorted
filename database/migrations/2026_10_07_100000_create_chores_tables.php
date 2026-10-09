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
        Schema::create('chores', static function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('points')->default(0);
            $table->string('schedule');
            $table->unsignedSmallInteger('every')->nullable();
            $table->string('unit')->nullable();
            $table->string('rule')->nullable();
            $table->date('starts_on')->nullable();
            $table->date('next_due_on')->nullable()->index();
            $table->dateTime('finished_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('completions', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('chore_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status');
            $table->date('due_on')->nullable();
            $table->unsignedInteger('points')->default(0);
            $table->dateTime('completed_at');
            $table->timestamps();

            $table->index(['user_id', 'completed_at']);
            $table->index(['chore_id', 'completed_at']);
        });

        Schema::create('chore_user', static function (Blueprint $table) {
            $table->foreignId('chore_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['chore_id', 'user_id']);
        });

        Schema::create('chore_label', static function (Blueprint $table) {
            $table->foreignId('chore_id')->constrained()->cascadeOnDelete();
            $table->foreignId('label_id')->constrained()->cascadeOnDelete();
            $table->primary(['chore_id', 'label_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chore_label');
        Schema::dropIfExists('chore_user');
        Schema::dropIfExists('completions');
        Schema::dropIfExists('chores');
    }
};
