<?php

namespace App\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;

interface ProductRepository extends CrudRepository
{
    public function paginate(string $sort = 'name', string $direction = 'asc', int $page = 1): LengthAwarePaginator;

    public function findBySlug(string $slug, bool $lock = false): ?array;

    public function decrementStock(int $id, int $quantity): bool;
}
