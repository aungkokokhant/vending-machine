<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_and_login_page_renders(): void
    {
        $this->get('/products')->assertRedirect('/login');
        $this->get('/admin/products/create')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('Sign in to your account');
    }

    public function test_login_regenerates_session_stores_role_and_logout_clears_it(): void
    {
        $user = User::factory()->admin()->create();
        $this->get('/login');
        $before = session()->getId();
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/products')->assertSessionHas('user_id', $user->id)->assertSessionHas('role', 'admin');
        $this->assertNotSame($before, session()->getId());
        $this->get('/admin/products/create')->assertOk();
        $this->post('/logout')->assertRedirect('/login')->assertSessionMissing('user_id')->assertSessionMissing('role');
        $this->get('/admin/products/create')->assertRedirect('/login');
    }

    public function test_wrong_password_and_deleted_user_cannot_login(): void
    {
        $user = User::factory()->create();
        $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email')->assertSessionMissing('user_id');
        $user->delete();
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email')->assertSessionMissing('user_id');
    }

    public function test_session_role_is_refreshed_from_the_database(): void
    {
        $user = User::factory()->create();
        $this->withSession(['user_id' => $user->id, 'role' => 'admin'])->get('/admin/products/create')->assertForbidden();
        $this->assertSame('user', session('role'));
        $user->delete();
        $this->get('/products')->assertRedirect('/login')->assertSessionMissing('user_id');
    }

    public function test_login_is_rate_limited(): void
    {
        $email = 'ratelimit@example.com';
        RateLimiter::clear($email.'|127.0.0.1');
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/login', ['email' => $email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        }
        $this->post('/login', ['email' => $email, 'password' => 'wrong'])->assertTooManyRequests();
    }
}
