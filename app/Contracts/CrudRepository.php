<?php

namespace App\Contracts;

interface CrudRepository
{
    public function find(int $id): ?array;

    public function all(): array;

    public function create(array $attributes): array;

    public function update(int $id, array $attributes): ?array;

    public function delete(int $id): bool;
}
