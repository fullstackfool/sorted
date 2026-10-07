<?php

namespace Database\Seeders;

use App\Models\Label;
use App\Models\Template;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Create initial task instance for a template (and its subtemplates)
     */
    private function createInitialInstance(Template $template): void
    {
        // Skip subtemplates - they're handled by their parent
        if ($template->parent_id !== null) {
            return;
        }

        // Skip if template is flexible (no due date)
        $pattern = $template->recurrence_pattern ?? [];
        $isFlexible = $pattern['flexible'] ?? false;

        if ($isFlexible) {
            return;
        }

        $taskDate = null;

        // For one-time templates with deadline, use the deadline
        if ($template->recurrence_type === 'none' && $template->deadline) {
            $taskDate = $template->deadline;
        }
        // For one-time templates without deadline, skip (they're not scheduled)
        elseif ($template->recurrence_type === 'none') {
            return;
        }
        // For recurring templates, calculate first instance date
        else {
            $taskDate = $template->calculateNextOccurrence(now()->subDay());
        }

        if (!$taskDate) {
            return;
        }

        // Create the parent task
        $task = Task::create([
            'template_id' => $template->id,
            'date'        => $taskDate,
            'status'      => 'todo',
        ]);

        // Create subtasks for any subtemplates
        foreach ($template->subtemplates as $subtemplate) {
            Task::create([
                'template_id' => $subtemplate->id,
                'parent_id'   => $task->id,
                'date'        => $taskDate,
                'status'      => 'todo',
            ]);
        }
    }

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create family members
        $karl = User::create([
            'name'     => 'Karl',
            'email'    => 'karl@example.com',
            'password' => Hash::make('password'),
        ]);

        $lisa = User::create([
            'name'     => 'Lisa',
            'email'    => 'lisa@example.com',
            'password' => Hash::make('password'),
        ]);

        $leo = User::create([
            'name'     => 'Leo',
            'email'    => 'leo@example.com',
            'password' => Hash::make('password'),
        ]);

        $kai = User::create([
            'name'     => 'Kai',
            'email'    => 'kai@example.com',
            'password' => Hash::make('password'),
        ]);

        // Create labels
        $cleaning = Label::create(['name' => 'Cleaning', 'color' => '#3B82F6']);
        $kitchen  = Label::create(['name' => 'Kitchen', 'color' => '#10B981']);
        $outdoor  = Label::create(['name' => 'Outdoor', 'color' => '#F59E0B']);
        $laundry  = Label::create(['name' => 'Laundry', 'color' => '#EC4899']);
        $pets     = Label::create(['name' => 'Pets', 'color' => '#8B5CF6']);
        $bathroom = Label::create(['name' => 'Bathroom', 'color' => '#06B6D4']);

        // DAILY TASKS

        // Dishes
        $dishes                = Template::create([
            'title'           => 'Do the dishes',
            'description'     => 'Wash, dry and put away all dishes',
            'points'          => 10,
            'recurrence_type' => 'daily',
        ]);
        $dishes->next_due_date = $dishes->calculateNextDueDate();
        $dishes->save();
        $dishes->labels()->attach([$kitchen->id]);
        $dishes->users()->attach([$leo->id, $kai->id]);
        $this->createInitialInstance($dishes);

        // Tidy living room
        $tidy                = Template::create([
            'title'           => 'Tidy living room',
            'description'     => 'Pick up toys, straighten cushions, put things away',
            'points'          => 5,
            'recurrence_type' => 'daily',
        ]);
        $tidy->next_due_date = $tidy->calculateNextDueDate();
        $tidy->save();
        $tidy->labels()->attach([$cleaning->id]);
        $tidy->users()->attach([$leo->id, $kai->id]);
        $this->createInitialInstance($tidy);

        // Feed pets
        $feedPets                = Template::create([
            'title'           => 'Feed the pets',
            'description'     => 'Morning and evening meals',
            'points'          => 5,
            'recurrence_type' => 'daily',
        ]);
        $feedPets->next_due_date = $feedPets->calculateNextDueDate();
        $feedPets->save();
        $feedPets->labels()->attach([$pets->id]);
        $feedPets->users()->attach([$leo->id, $kai->id]);
        $this->createInitialInstance($feedPets);

        // Make beds
        $beds                = Template::create([
            'title'           => 'Make all beds',
            'description'     => 'Make beds in all bedrooms',
            'points'          => 5,
            'recurrence_type' => 'daily',
        ]);
        $beds->next_due_date = $beds->calculateNextDueDate();
        $beds->save();
        $beds->labels()->attach([$cleaning->id]);
        $beds->users()->attach([$leo->id, $kai->id]);
        $this->createInitialInstance($beds);

        // WEEKLY TASKS

        // Laundry (flexible - any day)
        $laundryTask                = Template::create([
            'title'              => 'Do laundry',
            'description'        => 'Wash, dry, fold and put away clothes',
            'points'             => 15,
            'recurrence_type'    => 'weekly',
            'recurrence_pattern' => ['flexible' => true],
        ]);
        $laundryTask->next_due_date = $laundryTask->calculateNextDueDate();
        $laundryTask->save();
        $laundryTask->labels()->attach([$laundry->id]);
        $laundryTask->users()->attach([$karl->id, $lisa->id]);

        // Vacuum (Saturdays)
        $vacuum                = Template::create([
            'title'              => 'Vacuum all floors',
            'description'        => 'Vacuum all carpets and rugs throughout the house',
            'points'             => 15,
            'recurrence_type'    => 'weekly',
            'recurrence_pattern' => ['days_of_week' => [6]], // Saturday
        ]);
        $vacuum->next_due_date = $vacuum->calculateNextDueDate();
        $vacuum->save();
        $vacuum->labels()->attach([$cleaning->id]);
        $vacuum->users()->attach([$leo->id, $kai->id]);
        $this->createInitialInstance($vacuum);

        // Trash (Wednesdays)
        $trash                = Template::create([
            'title'              => 'Take out trash and recycling',
            'description'        => 'Empty all bins and take to curb',
            'points'             => 10,
            'recurrence_type'    => 'weekly',
            'recurrence_pattern' => ['days_of_week' => [3]], // Wednesday
        ]);
        $trash->next_due_date = $trash->calculateNextDueDate();
        $trash->save();
        $trash->labels()->attach([$outdoor->id]);
        $trash->users()->attach([$leo->id]);
        $this->createInitialInstance($trash);

        // Mop floors (Fridays)
        $mop                = Template::create([
            'title'              => 'Mop kitchen and bathroom',
            'description'        => 'Mop all hard floors',
            'points'             => 15,
            'recurrence_type'    => 'weekly',
            'recurrence_pattern' => ['days_of_week' => [5]], // Friday
        ]);
        $mop->next_due_date = $mop->calculateNextDueDate();
        $mop->save();
        $mop->labels()->attach([$cleaning->id, $kitchen->id, $bathroom->id]);
        $mop->users()->attach([$kai->id]);
        $this->createInitialInstance($mop);

        // Clean bathrooms (Sundays)
        $bathroomClean                = Template::create([
            'title'              => 'Clean bathrooms',
            'description'        => 'Clean toilets, sinks, mirrors, and counters',
            'points'             => 20,
            'recurrence_type'    => 'weekly',
            'recurrence_pattern' => ['days_of_week' => [0]], // Sunday
        ]);
        $bathroomClean->next_due_date = $bathroomClean->calculateNextDueDate();
        $bathroomClean->save();
        $bathroomClean->labels()->attach([$bathroom->id, $cleaning->id]);
        $bathroomClean->users()->attach([$karl->id, $lisa->id]);
        $this->createInitialInstance($bathroomClean);

        // Grocery shopping (Saturdays)
        $groceries                = Template::create([
            'title'              => 'Grocery shopping',
            'description'        => 'Weekly grocery shop',
            'points'             => 10,
            'recurrence_type'    => 'weekly',
            'recurrence_pattern' => ['days_of_week' => [6]], // Saturday
        ]);
        $groceries->next_due_date = $groceries->calculateNextDueDate();
        $groceries->save();
        $groceries->labels()->attach([$kitchen->id]);
        $groceries->users()->attach([$karl->id, $lisa->id]);
        $this->createInitialInstance($groceries);

        // Meal prep (Sundays)
        $mealPrep                = Template::create([
            'title'              => 'Meal prep for the week',
            'description'        => 'Prepare meals and snacks for the week ahead',
            'points'             => 25,
            'recurrence_type'    => 'weekly',
            'recurrence_pattern' => ['days_of_week' => [0]], // Sunday
        ]);
        $mealPrep->next_due_date = $mealPrep->calculateNextDueDate();
        $mealPrep->save();
        $mealPrep->labels()->attach([$kitchen->id]);
        $mealPrep->users()->attach([$karl->id, $lisa->id]);
        $this->createInitialInstance($mealPrep);

        // MONTHLY TASKS

        // Deep clean refrigerator (flexible - any day this month)
        $fridge                = Template::create([
            'title'              => 'Deep clean refrigerator',
            'description'        => 'Remove all items, wipe shelves, throw out expired food',
            'points'             => 30,
            'recurrence_type'    => 'monthly',
            'recurrence_pattern' => ['flexible' => true],
        ]);
        $fridge->next_due_date = $fridge->calculateNextDueDate();
        $fridge->save();
        $fridge->labels()->attach([$kitchen->id, $cleaning->id]);
        $fridge->users()->attach([$karl->id, $lisa->id]);

        // Wash windows (1st of each month)
        $windows                = Template::create([
            'title'              => 'Wash all windows',
            'description'        => 'Clean inside and outside of all windows',
            'points'             => 30,
            'recurrence_type'    => 'monthly',
            'recurrence_pattern' => ['days_of_month' => [1]],
        ]);
        $windows->next_due_date = $windows->calculateNextDueDate();
        $windows->save();
        $windows->labels()->attach([$cleaning->id, $outdoor->id]);
        $windows->users()->attach([$karl->id]);
        $this->createInitialInstance($windows);

        // Change bed linens (15th of each month)
        $linens                = Template::create([
            'title'              => 'Change all bed linens',
            'description'        => 'Wash and change sheets on all beds',
            'points'             => 20,
            'recurrence_type'    => 'monthly',
            'recurrence_pattern' => ['days_of_month' => [15]],
        ]);
        $linens->next_due_date = $linens->calculateNextDueDate();
        $linens->save();
        $linens->labels()->attach([$laundry->id]);
        $linens->users()->attach([$lisa->id]);
        $this->createInitialInstance($linens);

        // ONE-TIME / NO RECURRENCE TASKS

        // Garden work with subtemplates
        $garden = Template::create([
            'title'           => 'Garden maintenance',
            'description'     => 'Complete garden overhaul',
            'points'          => 50,
            'recurrence_type' => 'none',
            'deadline'        => now()->addDays(10),
        ]);
        $garden->labels()->attach([$outdoor->id]);
        $garden->users()->attach([$karl->id, $leo->id]);

        // Garden subtemplates (must be created before calling createInitialInstance)
        Template::create([
            'title'     => 'Mow the lawn',
            'points'    => 15,
            'parent_id' => $garden->id,
        ]);

        Template::create([
            'title'     => 'Trim hedges',
            'points'    => 15,
            'parent_id' => $garden->id,
        ]);

        Template::create([
            'title'     => 'Weed flower beds',
            'points'    => 20,
            'parent_id' => $garden->id,
        ]);

        // Reload to get subtemplates, then create initial task with subtasks
        $garden->load('subtemplates');
        $this->createInitialInstance($garden);

        // Organize garage
        $garage = Template::create([
            'title'       => 'Organize garage',
            'description' => 'Sort, organize and clean the garage',
            'points'      => 40,
            'recurrence_type' => 'none',
            'deadline'    => now()->addDays(14),
        ]);
        $garage->labels()->attach([$outdoor->id, $cleaning->id]);
        $garage->users()->attach([$karl->id]);
        $this->createInitialInstance($garage);

        // Sort toy closet
        $toys = Template::create([
            'title'       => 'Sort and organize toy closet',
            'description' => 'Donate unused toys, organize remaining',
            'points'      => 20,
            'recurrence_type' => 'none',
            'deadline'    => now()->addDays(7),
        ]);
        $toys->labels()->attach([$cleaning->id]);
        $toys->users()->attach([$leo->id, $kai->id]);
        $this->createInitialInstance($toys);
    }
}
