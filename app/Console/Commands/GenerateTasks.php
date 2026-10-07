<?php

namespace App\Console\Commands;

use App\Models\Template;
use App\Support\SyncStamp;
use Illuminate\Console\Command;

class GenerateTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate pending tasks for all templates that need them';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Generating pending tasks for templates...');

        // Get all parent templates (not subtemplates)
        $templates = Template::primary()->get();

        $generated = 0;
        $skipped = 0;

        foreach ($templates as $template) {
            // Check if a pending task already exists
            $hasPendingTask = $template->tasks()->where('status', 'todo')->exists();

            if ($hasPendingTask) {
                $skipped++;
                continue;
            }

            $template->ensurePendingTaskExists();
            $generated++;

            $this->line("  Created task for: {$template->title}");
        }

        // Runs at midnight: reload every open page so the new day shows without a tap.
        SyncStamp::bump();

        $this->info("Done! Generated {$generated} tasks, skipped {$skipped} templates with existing pending tasks.");

        return Command::SUCCESS;
    }
}

