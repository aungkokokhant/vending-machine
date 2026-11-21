<?php

namespace App\Services;

use App\Contracts\ProductRepository;
use App\Contracts\Purchasing;
use App\Contracts\TransactionRepository;
use App\Database\PdoConnection;
use Brick\Math\BigDecimal;
use Illuminate\Validation\ValidationException;

class PurchaseService implements Purchasing
{
    public function __construct(private PdoConnection $database, private ProductRepository $products, private TransactionRepository $transactions) {}

    public function purchase(int $userId, string $slug, int $quantity): array
    {
        if ($quantity < 1 || $quantity > 4294967295) {
            throw ValidationException::withMessages(['quantity' => 'Enter a positive whole number within the supported range.']);
        }

        return $this->database->transaction(function () use ($userId, $slug, $quantity): array {
            $product = $this->products->findBySlug($slug, lock: true);
            abort_unless($product, 404, 'Product not found.');
            if ($quantity > $product['quantity_available'] || ! $this->products->decrementStock($product['id'], $quantity)) {
                throw ValidationException::withMessages(['quantity' => 'There is not enough stock for this purchase.']);
            }

            return $this->transactions->create([
                'user_id' => $userId,
                'product_id' => $product['id'],
                'quantity' => $quantity,
                'unit_price' => $product['price'],
                'total_price' => (string) BigDecimal::of($product['price'])->multipliedBy($quantity)->toScale(3),
            ]);
        });
    }
}
