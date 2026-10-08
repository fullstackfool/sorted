<?php

namespace Tests\Feature;

use App\Models\Chore;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RetiredEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_after_migrating_the_old_task_tables_are_gone_and_the_chores_tables_exist(): void
    {
        foreach (['templates', 'tasks', 'template_user', 'label_template'] as $table) {
            $this->assertFalse(Schema::hasTable($table), "The old {$table} table still exists.");
        }

        foreach (['chores', 'completions', 'chore_user', 'chore_label'] as $table) {
            $this->assertTrue(Schema::hasTable($table), "The {$table} table is missing.");
        }
    }

    public function test_the_task_generator_is_no_longer_a_command(): void
    {
        $this->assertArrayNotHasKey('tasks:generate', Artisan::all());
    }

    public function test_deleting_a_person_removes_them_and_their_chore_assignments(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $chore = Chore::factory()->create();
        $chore->users()->attach([$user->id, $other->id]);

        $this->delete(route('users.destroy', $user))
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success');

        $this->assertModelMissing($user);
        $this->assertSame([$other->id], DB::table('chore_user')->where('chore_id', $chore->id)->pluck('user_id')->all());
    }
}
