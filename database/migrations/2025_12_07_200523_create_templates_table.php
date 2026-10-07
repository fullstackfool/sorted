<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('templates', static function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('points')->default(0);
            $table->dateTime('deadline')->nullable();

            // Recurrence settings
            $table->enum('recurrence_type', ['none', 'daily', 'weekly', 'biweekly', 'monthly', 'custom'])
                ->default('none');
            $table->json('recurrence_pattern')->nullable();
            $table->dateTime('next_due_date')->nullable();

            // Subtemplate relationship (renamed in later migration to parent_id)
            $table->foreignId('parent_template_id')->nullable()->constrained('templates')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
