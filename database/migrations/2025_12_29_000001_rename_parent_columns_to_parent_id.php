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
        // Remove status column from templates if it exists (templates don't have status, only tasks do)
        if (Schema::hasColumn('templates', 'status')) {
            Schema::table('templates', static function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }

        // Rename parent_template_id to parent_id in templates table
        Schema::table('templates', static function (Blueprint $table) {
            $table->dropForeign(['parent_template_id']);
            $table->renameColumn('parent_template_id', 'parent_id');
        });

        Schema::table('templates', static function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('templates')->onDelete('cascade');
        });

        // Add parent_id to tasks table for subtasks
        Schema::table('tasks', static function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('template_id')->constrained('tasks')->onDelete('cascade');
        });

        // Add status column to tasks if it doesn't exist (in case the old buggy migration dropped it)
        if (!Schema::hasColumn('tasks', 'status')) {
            Schema::table('tasks', static function (Blueprint $table) {
                $table->enum('status', ['todo', 'done', 'skipped'])->default('todo')->after('date');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove parent_id from tasks table
        Schema::table('tasks', static function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
        });

        // Rename parent_id back to parent_template_id in templates table
        Schema::table('templates', static function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->renameColumn('parent_id', 'parent_template_id');
        });

        Schema::table('templates', static function (Blueprint $table) {
            $table->foreign('parent_template_id')->references('id')->on('templates')->onDelete('cascade');
        });
    }
};

