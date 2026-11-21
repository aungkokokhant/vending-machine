<?php

namespace Tests\Feature;

use App\Contracts\Purchasing;
use App\Contracts\TransactionRepository;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class PurchaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_uses_server_price_and_exact_total_and_logs_history(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->pricedAt('6.885')->create(['quantity_available' => 3]);
        $this->withSession(['user_id' => $user->id, 'role' => 'user']);
        $this->get('/products/'.$product->slug.'/purchase')->assertOk()->assertSee('6.885');
        $this->post('/products/'.$product->slug.'/purchase', ['quantity' => 3, 'price' => '0.001', 'user_id' => 999])->assertRedirect('/transactions');
        $this->assertSame(0, $product->fresh()->quantity_available);
        $transaction = Transaction::query()->sole();
        $this->assertSame('20.655', $transaction->total_price);
        $this->assertSame('6.885', $transaction->unit_price);
        $this->assertSame($user->id, $transaction->user_id);
        $this->get('/transactions')->assertOk()->assertSee('20.655');
    }

    public static function invalidQuantities(): array
    {
        return ['missing' => [null], 'zero' => [0], 'negative' => [-1], 'fractional' => ['1.5'], 'excessive' => [4], 'text' => ['abc'], 'array' => [[1]], 'too large' => ['4294967296']];
    }

    #[DataProvider('invalidQuantities')]
    public function test_invalid_quantity_never_changes_stock_or_logs_a_transaction(mixed $quantity): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['quantity_available' => 3]);
        $this->withSession(['user_id' => $user->id])->post('/products/'.$product->slug.'/purchase', ['quantity' => $quantity])->assertSessionHasErrors('quantity');
        $this->assertSame(3, $product->fresh()->quantity_available);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_failure_to_insert_transaction_rolls_back_stock_change(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['quantity_available' => 3]);
        $mock = Mockery::mock(TransactionRepository::class);
        $mock->shouldReceive('create')->once()->andThrow(new RuntimeException('Insert failed'));
        $this->app->instance(TransactionRepository::class, $mock);
        try {
            app(Purchasing::class)->purchase($user->id, $product->slug, 2);
            $this->fail('Expected the insert to fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Insert failed', $exception->getMessage());
        }
        $this->assertSame(3, $product->fresh()->quantity_available);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_out_of_stock_missing_and_deleted_products_cannot_be_purchased(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->outOfStock()->create();
        $this->withSession(['user_id' => $user->id]);
        $this->post('/products/'.$product->slug.'/purchase', ['quantity' => 1])->assertSessionHasErrors('quantity');
        $this->get('/products/'.$product->slug.'/purchase')->assertOk()->assertSee('out of stock');
        $product->delete();
        $this->post('/products/'.$product->slug.'/purchase', ['quantity' => 1])->assertNotFound();
        $this->post('/products/missing/purchase', ['quantity' => 1])->assertNotFound();
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_purchase_history_is_private_to_the_current_user(): void
    {
        $user = User::factory()->create();
        Transaction::factory()->create(['total_price' => '876.543']);
        $this->withSession(['user_id' => $user->id])->get('/transactions')->assertOk()->assertDontSee('876.543');
    }

    public function test_a_second_purchase_cannot_consume_stock_already_sold(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['quantity_available' => 1]);
        $this->withSession(['user_id' => $user->id]);
        $this->post('/products/'.$product->slug.'/purchase', ['quantity' => 1])->assertRedirect('/transactions');
        $this->post('/products/'.$product->slug.'/purchase', ['quantity' => 1])->assertSessionHasErrors('quantity');
        $this->assertDatabaseCount('transactions', 1);
        $this->assertSame(0, $product->fresh()->quantity_available);
    }
}
