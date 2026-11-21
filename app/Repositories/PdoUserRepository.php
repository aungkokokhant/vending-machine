<?php

namespace App\Repositories;

use App\Contracts\UserRepository;
use Illuminate\Support\Facades\Hash;
use PDO;

class PdoUserRepository extends PdoRepository implements UserRepository
{
    protected string $table = 'users';

    protected array $fields = ['name', 'email', 'password', 'role'];

    protected bool $softDeletes = true;

    protected function writable(array $attributes): array
    {
        $attributes = parent::writable($attributes);
        if (isset($attributes['password'])) {
            $attributes['password'] = Hash::make($attributes['password']);
        }

        return $attributes;
    }

    public function findByEmail(string $email): ?array
    {
        $row = $this->database->execute('SELECT * FROM users WHERE email = ? AND deleted_at IS NULL', [$email])->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->normalize($row) : null;
    }
}
