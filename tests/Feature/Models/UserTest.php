<?php

namespace Tests\Feature\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_new_accounts_default_to_user_even_when_mass_assignment_contains_an_admin_role(): void
    {
        $attributes = User::factory()->raw(['role' => 'admin']);

        $user = User::query()->create($attributes);

        $this->assertSame('user', $user->fresh()->role);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'role' => 'user']);
    }
}
