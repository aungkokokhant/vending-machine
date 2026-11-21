<?php

namespace Tests\Feature\Models;

use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_purchase_snapshot_keeps_three_decimal_prices_after_a_product_price_change(): void
    {
        $product = Product::factory()->pricedAt('6.885')->create();
        $transaction = Transaction::factory()->for($product)->create(['quantity' => 3]);

        $product->update(['price' => '9.990']);

        $this->assertSame('6.885', $transaction->fresh()->unit_price);
        $this->assertSame('20.655', $transaction->fresh()->total_price);
        $this->assertSame(3, $transaction->fresh()->quantity);
    }

    public function test_purchase_history_remains_linked_after_a_product_and_user_are_soft_deleted(): void
    {
        $transaction = Transaction::factory()->create();
        $product = $transaction->product;
        $user = $transaction->user;

        $product->delete();
        $user->delete();

        $this->assertSoftDeleted($product);
        $this->assertSoftDeleted($user);
        $this->assertSame(0, Product::query()->count());
        $this->assertSame(0, User::query()->count());
        $this->assertModelExists($transaction);
        $this->assertSame($product->id, $transaction->fresh()->product->id);
        $this->assertSame($user->id, $transaction->fresh()->user->id);
        $this->assertSame($transaction->id, $product->transactions()->sole()->id);
        $this->assertSame($transaction->id, $user->transactions()->sole()->id);
    }

    public function test_rejects_permanent_product_deletion_when_purchase_history_exists(): void
    {
        $transaction = Transaction::factory()->create();

        $this->expectException(QueryException::class);

        $transaction->product->forceDelete();
    }

    public function test_rejects_permanent_user_deletion_when_purchase_history_exists(): void
    {
        $transaction = Transaction::factory()->create();

        $this->expectException(QueryException::class);

        $transaction->user->forceDelete();
    }
}
