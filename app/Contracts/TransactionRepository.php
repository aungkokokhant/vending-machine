<?php

namespace App\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;

interface TransactionRepository extends CrudRepository
{
    public function forUser(int $userId, int $page = 1): LengthAwarePaginator;
}
