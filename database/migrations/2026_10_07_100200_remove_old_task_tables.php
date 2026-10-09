<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('label_template');
        Schema::dropIfExists('template_user');
        Schema::dropIfExists('templates');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nothing to restore: chores and completions now hold the data.
    }
};
