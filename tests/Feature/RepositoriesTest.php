<?php

namespace Tests\Feature;

use App\Contracts\ProductRepository;
use App\Contracts\TransactionRepository;
use App\Contracts\UserRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RepositoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_pdo_crud_for_products_users_and_transactions(): void
    {
        $users = app(UserRepository::class);
        $products = app(ProductRepository::class);
        $transactions = app(TransactionRepository::class);
        $user = $users->create(['name' => 'Buyer', 'email' => 'buyer@example.com', 'password' => 'Password123!', 'role' => 'user', 'id' => 999]);
        $this->assertNotSame(999, $user['id']);
        $this->assertTrue(Hash::check('Password123!', $user['password']));
        $this->assertSame($user['id'], $users->findByEmail('buyer@example.com')['id']);
        $this->assertSame('New name', $users->update($user['id'], ['name' => 'New name'])['name']);
        $product = $products->create(['name' => "O'Reilly cola", 'price' => '6.885', 'quantity_available' => 4]);
        $this->assertSame("O'Reilly cola", $products->find($product['id'])['name']);
        $this->assertSame('6.885', $products->findBySlug($product['slug'])['price']);
        $this->assertSame(3, $products->update($product['id'], ['quantity_available' => 3])['quantity_available']);
        $transaction = $transactions->create(['user_id' => $user['id'], 'product_id' => $product['id'], 'quantity' => 1, 'unit_price' => '6.885', 'total_price' => '6.885']);
        $this->assertSame('6.885', $transactions->find($transaction['id'])['total_price']);
        $this->assertSame(2, $transactions->update($transaction['id'], ['quantity' => 2, 'total_price' => '13.770'])['quantity']);
        $this->assertCount(1, $transactions->all());
        $this->assertTrue($transactions->delete($transaction['id']));
        $this->assertNull($transactions->find($transaction['id']));
        $this->assertTrue($products->delete($product['id']));
        $this->assertNull($products->find($product['id']));
        $this->assertNull($products->findBySlug($product['slug']));
        $this->assertTrue($users->delete($user['id']));
        $this->assertNull($users->findByEmail('buyer@example.com'));
        $this->assertSoftDeleted('products', ['id' => $product['id']]);
        $this->assertSoftDeleted('users', ['id' => $user['id']]);
    }

    public function test_sql_values_and_sort_columns_cannot_inject_queries(): void
    {
        $products = app(ProductRepository::class);
        $products->create(['name' => "'); DROP TABLE products; --", 'price' => '0.500', 'quantity_available' => 3]);
        $this->assertNull($products->findBySlug("' OR 1=1 --"));
        $this->assertCount(1, $products->paginate('name; DROP TABLE products', 'desc; DROP TABLE users'));
        $this->assertDatabaseCount('products', 1);
    }

    public function test_missing_records_return_null_and_slugs_remain_unique_and_stable(): void
    {
        $products = app(ProductRepository::class);
        $this->assertNull($products->update(999, ['name' => 'Missing']));
        $this->assertFalse($products->delete(999));
        $attributes = ['name' => 'Coke', 'price' => '3.990', 'quantity_available' => 20];
        $first = $products->create($attributes);
        $second = $products->create($attributes);
        $this->assertNotSame($first['slug'], $second['slug']);
        $this->assertSame($first['slug'], $products->update($first['id'], ['name' => 'New name', 'slug' => 'bad'])['slug']);
    }
}
