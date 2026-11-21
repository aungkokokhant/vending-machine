<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProductsTest extends TestCase
{
    use RefreshDatabase;

    private function signIn(bool $admin = true): User
    {
        $user = $admin ? User::factory()->admin()->create() : User::factory()->create();
        $this->withSession(['user_id' => $user->id, 'role' => $user->role]);

        return $user;
    }

    public function test_admin_can_create_view_update_and_delete_a_product(): void
    {
        $this->signIn();
        $this->get('/admin/products/create')->assertOk();
        $this->post('/admin/products', ['name' => 'Tea', 'price' => '1.125', 'quantity_available' => 8])->assertRedirect();
        $product = Product::query()->sole();
        $this->get('/admin/products/'.$product->id)->assertOk()->assertSee('1.125');
        $this->get('/admin/products/'.$product->id.'/edit')->assertOk();
        $this->put('/admin/products/'.$product->id, ['name' => 'Green tea', 'price' => '2.225', 'quantity_available' => 0])->assertRedirect();
        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Green tea', 'quantity_available' => 0]);
        $this->get('/admin/products/'.$product->id.'/delete')->assertOk()->assertSee('Delete Green tea?');
        $this->delete('/admin/products/'.$product->id)->assertRedirect('/products');
        $this->assertSoftDeleted($product);
        $this->get('/admin/products/'.$product->id)->assertNotFound();
    }

    public function test_normal_users_cannot_access_any_admin_route(): void
    {
        $this->signIn(false);
        $product = Product::factory()->create();
        foreach (['/admin/products/create', '/admin/products/'.$product->id, '/admin/products/'.$product->id.'/edit', '/admin/products/'.$product->id.'/delete'] as $url) {
            $this->get($url)->assertForbidden();
        }
        $this->post('/admin/products', [])->assertForbidden();
        $this->put('/admin/products/'.$product->id, [])->assertForbidden();
        $this->delete('/admin/products/'.$product->id)->assertForbidden();
        $this->assertModelExists($product);
    }

    public static function invalidProducts(): array
    {
        return [
            'missing fields' => [[], ['name', 'price', 'quantity_available']],
            'zero price' => [['name' => 'Tea', 'price' => '0', 'quantity_available' => 1], ['price']],
            'negative price' => [['name' => 'Tea', 'price' => '-1', 'quantity_available' => 1], ['price']],
            'too much precision' => [['name' => 'Tea', 'price' => '1.1234', 'quantity_available' => 1], ['price']],
            'scientific notation' => [['name' => 'Tea', 'price' => '1e3', 'quantity_available' => 1], ['price']],
            'negative stock' => [['name' => 'Tea', 'price' => '1', 'quantity_available' => -1], ['quantity_available']],
            'fractional stock' => [['name' => 'Tea', 'price' => '1', 'quantity_available' => '1.5'], ['quantity_available']],
            'too large stock' => [['name' => 'Tea', 'price' => '1', 'quantity_available' => '4294967296'], ['quantity_available']],
        ];
    }

    #[DataProvider('invalidProducts')]
    public function test_invalid_product_input_is_rejected(array $input, array $errors): void
    {
        $this->signIn();
        $this->from('/admin/products/create')->post('/admin/products', $input)->assertRedirect('/admin/products/create')->assertSessionHasErrors($errors);
        $this->assertDatabaseCount('products', 0);
    }

    public function test_catalog_is_paginated_sorted_and_preserves_sort_query(): void
    {
        $this->signIn(false);
        Product::factory()->count(11)->create(['price' => '2.000']);
        $cheapest = Product::factory()->create(['price' => '0.500']);
        $response = $this->get('/products?sort=price&direction=asc');
        $response->assertOk()->assertViewHas('products', fn ($products): bool => $products->total() === 12 && $products->count() === 9 && $products->first()['id'] === $cheapest->id)->assertSee('sort=price', false)->assertSee('direction=asc', false);
        $this->get('/products?sort=price&direction=asc&page=2')->assertOk()->assertViewHas('products', fn ($products): bool => $products->count() === 3);
    }

    public function test_product_names_are_escaped_in_html(): void
    {
        $this->signIn();
        Product::factory()->create(['name' => '<script>alert(1)</script>']);
        $this->get('/products')->assertOk()->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }
}
