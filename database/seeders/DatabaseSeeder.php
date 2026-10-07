<?php

namespace Database\Seeders;

use App\Models\Chore;
use App\Models\Completion;
use App\Models\Label;
use App\Models\User;
use App\Support\Schedule;
use Carbon\CarbonImmutable;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Create a chore with its people and labels, due when its schedule first falls unless a due date is given.
     *
     * @param array<string, mixed> $attributes
     * @param array<User>          $people
     * @param array<Label>         $labels
     */
    private function chore(array $attributes, array $people, array $labels): Chore
    {
        $chore              = new Chore($attributes);
        $chore->next_due_on = $attributes['next_due_on'] ?? Schedule::of($chore)->firstDueOn(CarbonImmutable::today());
        $chore->save();

        $chore->users()->attach(array_map(fn (User $user) => $user->id, $people));
        $chore->labels()->attach(array_map(fn (Label $label) => $label->id, $labels));

        return $chore;
    }

    /**
     * Record the chore as done or skipped at $hour on the day it was due, and move it on to its next due date.
     */
    private function complete(Chore $chore, User $user, string $status, CarbonImmutable $dueOn, int $hour): void
    {
        $completedAt = $dueOn->setTime($hour, 0);

        $chore->completions()->create([
            'user_id'      => $user->id,
            'status'       => $status,
            'due_on'       => $dueOn,
            'points'       => $status === Completion::DONE ? $chore->points : 0,
            'completed_at' => $completedAt,
        ]);

        $chore->update(['next_due_on' => Schedule::of($chore)->nextDueOn($dueOn, $completedAt->startOfDay())]);
    }

    /**
     * Seed a fictional sample family with their chores, labels and a few past completions.
     */
    public function run(): void
    {
        $today = CarbonImmutable::today();

        // Add a fictional family so we can assign chores to them.
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

        // DAILY CHORES

        // Dishes (skipped three days ago, so overdue since the day after)
        $dishes = $this->chore([
            'title'       => 'Do the dishes',
            'description' => 'Wash, dry and put away all dishes',
            'points'      => 10,
            'schedule'    => Chore::ON,
            'rule'        => Schedule::dailyRule(),
            'starts_on'   => $today->subDays(3),
        ], [$leo, $kai], [$kitchen]);
        $this->complete($dishes, $kai, Completion::SKIPPED, $today->subDays(3), 18);

        // Tidy living room (not done yesterday, so overdue)
        $this->chore([
            'title'       => 'Tidy living room',
            'description' => 'Pick up toys, straighten cushions, put things away',
            'points'      => 5,
            'schedule'    => Chore::ON,
            'rule'        => Schedule::dailyRule(),
            'starts_on'   => $today->subDay(),
            'next_due_on' => $today->subDay(),
        ], [$leo, $kai], [$cleaning]);

        // Feed pets
        $feedPets = $this->chore([
            'title'       => 'Feed the pets',
            'description' => 'Morning and evening meals',
            'points'      => 5,
            'schedule'    => Chore::ON,
            'rule'        => Schedule::dailyRule(),
            'starts_on'   => $today->subDay(),
        ], [$leo, $kai], [$pets]);
        $this->complete($feedPets, $leo, Completion::DONE, $today->subDay(), 8);

        // Make beds
        $beds = $this->chore([
            'title'       => 'Make all beds',
            'description' => 'Make beds in all bedrooms',
            'points'      => 5,
            'schedule'    => Chore::ON,
            'rule'        => Schedule::dailyRule(),
            'starts_on'   => $today->subDay(),
        ], [$leo, $kai], [$cleaning]);
        $this->complete($beds, $kai, Completion::DONE, $today->subDay(), 9);

        // WEEKLY CHORES

        // Laundry (a week after it's done)
        $laundryChore = $this->chore([
            'title'       => 'Do laundry',
            'description' => 'Wash, dry, fold and put away clothes',
            'points'      => 15,
            'schedule'    => Chore::AFTER,
            'every'       => 1,
            'unit'        => 'week',
        ], [$karl, $lisa], [$laundry]);
        $this->complete($laundryChore, $lisa, Completion::DONE, $today->subDays(3), 17);

        // Vacuum (Saturdays)
        $this->chore([
            'title'       => 'Vacuum all floors',
            'description' => 'Vacuum all carpets and rugs throughout the house',
            'points'      => 15,
            'schedule'    => Chore::ON,
            'rule'        => Schedule::weekdaysRule([6]),
            'starts_on'   => $today,
        ], [$leo, $kai], [$cleaning]);

        // Trash (Wednesdays)
        $this->chore([
            'title'       => 'Take out trash and recycling',
            'description' => 'Empty all bins and take to curb',
            'points'      => 10,
            'schedule'    => Chore::ON,
            'rule'        => Schedule::weekdaysRule([3]),
            'starts_on'   => $today,
        ], [$leo], [$outdoor]);

        // Mop floors (Fridays)
        $this->chore([
            'title'       => 'Mop kitchen and bathroom',
            'description' => 'Mop all hard floors',
            'points'      => 15,
            'schedule'    => Chore::ON,
            'rule'        => Schedule::weekdaysRule([5]),
            'starts_on'   => $today,
        ], [$kai], [$cleaning, $kitchen, $bathroom]);

        // Clean bathrooms (Sundays)
        $this->chore([
            'title'       => 'Clean bathrooms',
            'description' => 'Clean toilets, sinks, mirrors, and counters',
            'points'      => 20,
            'schedule'    => Chore::ON,
            'rule'        => Schedule::weekdaysRule([0]),
            'starts_on'   => $today,
        ], [$karl, $lisa], [$bathroom, $cleaning]);

        // Grocery shopping (Saturdays)
        $this->chore([
            'title'       => 'Grocery shopping',
            'description' => 'Weekly grocery shop',
            'points'      => 10,
            'schedule'    => Chore::ON,
            'rule'        => Schedule::weekdaysRule([6]),
            'starts_on'   => $today,
        ], [$karl, $lisa], [$kitchen]);

        // Meal prep (Sundays)
        $this->chore([
            'title'       => 'Meal prep for the week',
            'description' => 'Prepare meals and snacks for the week ahead',
            'points'      => 25,
            'schedule'    => Chore::ON,
            'rule'        => Schedule::weekdaysRule([0]),
            'starts_on'   => $today,
        ], [$karl, $lisa], [$kitchen]);

        // MONTHLY CHORES

        // Deep clean refrigerator (a month after it's done)
        $fridge = $this->chore([
            'title'       => 'Deep clean refrigerator',
            'description' => 'Remove all items, wipe shelves, throw out expired food',
            'points'      => 30,
            'schedule'    => Chore::AFTER,
            'every'       => 1,
            'unit'        => 'month',
        ], [$karl, $lisa], [$kitchen, $cleaning]);
        $this->complete($fridge, $karl, Completion::DONE, $today->subDays(10), 11);

        // Wash windows (1st of each month)
        $this->chore([
            'title'       => 'Wash all windows',
            'description' => 'Clean inside and outside of all windows',
            'points'      => 30,
            'schedule'    => Chore::ON,
            'rule'        => Schedule::monthDaysRule([1]),
            'starts_on'   => $today,
        ], [$karl], [$cleaning, $outdoor]);

        // Change bed linens (15th of each month)
        $this->chore([
            'title'       => 'Change all bed linens',
            'description' => 'Wash and change sheets on all beds',
            'points'      => 20,
            'schedule'    => Chore::ON,
            'rule'        => Schedule::monthDaysRule([15]),
            'starts_on'   => $today,
        ], [$lisa], [$laundry]);

        // ONE-OFF CHORES

        // Garden work, with its steps in the description
        $this->chore([
            'title'       => 'Garden maintenance',
            'description' => "Complete garden overhaul\n\nSteps:\n- Mow the lawn\n- Trim hedges\n- Weed flower beds",
            'points'      => 50,
            'schedule'    => Chore::ONCE,
            'next_due_on' => $today->addDays(10),
        ], [$karl, $leo], [$outdoor]);

        // Organize garage
        $this->chore([
            'title'       => 'Organize garage',
            'description' => 'Sort, organize and clean the garage',
            'points'      => 40,
            'schedule'    => Chore::ONCE,
            'next_due_on' => $today->addDays(14),
        ], [$karl], [$outdoor, $cleaning]);

        // Sort toy closet
        $this->chore([
            'title'       => 'Sort and organize toy closet',
            'description' => 'Donate unused toys, organize remaining',
            'points'      => 20,
            'schedule'    => Chore::ONCE,
            'next_due_on' => $today->addDays(7),
        ], [$leo, $kai], [$cleaning]);
    }
}
