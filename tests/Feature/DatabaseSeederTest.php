<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_seeds_the_assignment_products_and_hashed_demo_accounts_without_fake_purchases(): void
    {
        $this->seed();

        $this->assertDatabaseCount('products', 3);
        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('transactions', 0);
        $this->assertSame([
            'coke' => '3.990',
            'pepsi' => '6.885',
            'water' => '0.500',
        ], Product::query()->orderBy('slug')->get()->pluck('price', 'slug')->all());
        $this->assertSame([20, 20, 20], Product::query()->orderBy('slug')->pluck('quantity_available')->all());

        $admin = User::query()->where('email', 'admin@vending.test')->firstOrFail();
        $user = User::query()->where('email', 'user@vending.test')->firstOrFail();

        $this->assertSame('admin', $admin->role);
        $this->assertSame('user', $user->role);
        $this->assertTrue(Hash::check('AdminDemo123!', $admin->password));
        $this->assertTrue(Hash::check('UserDemo123!', $user->password));
    }

    public function test_reseeding_preserves_changed_stock_prices_and_account_passwords(): void
    {
        $this->seed();
        $product = Product::query()->where('slug', 'coke')->firstOrFail();
        $admin = User::query()->where('email', 'admin@vending.test')->firstOrFail();
        $product->update(['quantity_available' => 7, 'price' => '4.250']);
        $admin->update(['password' => 'ChangedPassword123!']);

        $this->seed();

        $this->assertDatabaseCount('products', 3);
        $this->assertDatabaseCount('users', 2);
        $this->assertSame(7, $product->fresh()->quantity_available);
        $this->assertSame('4.250', $product->fresh()->price);
        $this->assertTrue(Hash::check('ChangedPassword123!', $admin->fresh()->password));
    }

    public function test_reseeding_does_not_restore_deleted_products_or_accounts(): void
    {
        $this->seed();
        $product = Product::query()->where('slug', 'water')->firstOrFail();
        $user = User::query()->where('email', 'user@vending.test')->firstOrFail();
        $product->delete();
        $user->delete();

        $this->seed();

        $this->assertSoftDeleted($product);
        $this->assertSoftDeleted($user);
        $this->assertDatabaseCount('products', 3);
        $this->assertDatabaseCount('users', 2);
    }
}
