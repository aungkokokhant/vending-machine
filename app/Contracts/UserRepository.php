<?php

namespace App\Contracts;

interface UserRepository extends CrudRepository
{
    public function findByEmail(string $email): ?array;
}
