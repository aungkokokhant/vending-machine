<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Services\TokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    private function token(User $user): string
    {
        return app(TokenService::class)->issue($user->id)['token'];
    }

    public function test_token_login_returns_only_safe_user_fields_and_a_hashed_expiring_token(): void
    {
        $user = User::factory()->create();
        $response = $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'password'])->assertOk()->assertJsonPath('token_type', 'Bearer')->assertJsonPath('user.role', 'user')->assertJsonMissingPath('user.password');
        $token = $response->json('token');
        $this->assertDatabaseHas('api_tokens', ['token_hash' => hash('sha256', $token)]);
        $this->assertDatabaseMissing('api_tokens', ['token_hash' => $token]);
        $this->withToken($token)->getJson('/api/v1/products')->assertOk();
        $this->postJson('/api/v1/logout')->assertOk();
        $this->getJson('/api/v1/products')->assertUnauthorized();
    }

    public function test_missing_invalid_and_expired_tokens_and_bad_credentials_are_rejected(): void
    {
        $this->freezeTime();
        $user = User::factory()->create();
        $this->get('/api/v1/products')->assertUnauthorized();
        $this->withToken('invalid')->getJson('/api/v1/products')->assertUnauthorized();
        $token = $this->token($user);
        $this->travel(61)->minutes();
        $this->withToken($token)->getJson('/api/v1/products')->assertUnauthorized();
        $this->travelBack();
        $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'wrong'])->assertUnauthorized();
    }

    public function test_admin_api_crud_and_user_authorization(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $adminToken = $this->token($admin);
        $this->withToken($adminToken)->postJson('/api/v1/products', ['name' => 'Tea', 'price' => '1.125', 'quantity_available' => 4])->assertCreated()->assertJsonPath('data.price', '1.125');
        $product = Product::query()->sole();
        $this->getJson('/api/v1/products/'.$product->id)->assertOk();
        $this->putJson('/api/v1/products/'.$product->id, ['name' => 'Tea 2', 'price' => '2.225', 'quantity_available' => 5])->assertOk()->assertJsonPath('data.price', '2.225');
        $this->withToken($this->token($user));
        $this->postJson('/api/v1/products', [])->assertForbidden();
        $this->putJson('/api/v1/products/'.$product->id, [])->assertForbidden();
        $this->deleteJson('/api/v1/products/'.$product->id)->assertForbidden();
        $this->withToken($adminToken)->deleteJson('/api/v1/products/'.$product->id)->assertNoContent();
        $this->getJson('/api/v1/products/'.$product->id)->assertNotFound();
    }

    public function test_api_purchase_uses_shared_logic_and_history_is_scoped_to_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $product = Product::factory()->pricedAt('6.885')->create(['quantity_available' => 3]);
        $this->withToken($this->token($user))->postJson('/api/v1/products/'.$product->slug.'/purchase', ['quantity' => 2, 'price' => '0.001'])->assertCreated()->assertJsonPath('data.total_price', '13.770')->assertJsonPath('data.user_id', $user->id);
        $this->postJson('/api/v1/products/'.$product->slug.'/purchase', ['quantity' => 2])->assertUnprocessable()->assertJsonValidationErrors('quantity');
        $this->getJson('/api/v1/transactions')->assertOk()->assertJsonPath('total', 1);
        $this->withToken($this->token($other))->getJson('/api/v1/transactions')->assertOk()->assertJsonPath('total', 0);
        $this->assertSame(1, $product->fresh()->quantity_available);
    }

    public function test_deleted_users_and_demoted_admins_lose_access_with_existing_tokens(): void
    {
        $admin = User::factory()->admin()->create();
        $token = $this->token($admin);
        $admin->role = 'user';
        $admin->save();
        $this->withToken($token)->postJson('/api/v1/products', [])->assertForbidden();
        $admin->delete();
        $this->getJson('/api/v1/products')->assertUnauthorized();
    }

    public function test_api_validates_inputs_and_does_not_accept_session_authentication(): void
    {
        $admin = User::factory()->admin()->create();
        $this->withSession(['user_id' => $admin->id, 'role' => 'admin'])->getJson('/api/v1/products')->assertUnauthorized();
        $this->withToken($this->token($admin))->postJson('/api/v1/products', ['name' => '', 'price' => '-1', 'quantity_available' => -1])->assertUnprocessable()->assertJsonValidationErrors(['name', 'price', 'quantity_available']);
        $this->assertDatabaseCount('products', 0);
    }

    public function test_malformed_query_and_login_values_return_safe_responses(): void
    {
        $user = User::factory()->create();
        $this->postJson('/api/v1/login', ['email' => ['bad'], 'password' => 'wrong'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->withToken($this->token($user))->getJson('/api/v1/products?sort[]=price&direction[]=desc&page[]=2')
            ->assertOk()->assertJsonPath('current_page', 1);
        $this->getJson('/api/v1/products?page=99999999999999999999999')->assertOk()->assertJsonPath('current_page', 1);
        $this->getJson('/api/v1/products?page=9223372036854775807')->assertOk()->assertJsonPath('current_page', 1000000);
    }
}
