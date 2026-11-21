<?php

namespace App\Contracts;

interface Purchasing
{
    public function purchase(int $userId, string $slug, int $quantity): array;
}
