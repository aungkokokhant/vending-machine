<?php

namespace App\Services;

use App\Contracts\UserRepository;
use App\Database\PdoConnection;
use PDO;

class TokenService
{
    public function __construct(private PdoConnection $database, private UserRepository $users) {}

    public function issue(int $userId): array
    {
        $token = bin2hex(random_bytes(32));
        $expiresAt = now()->addHour();
        $this->database->execute('INSERT INTO api_tokens (user_id, token_hash, expires_at, created_at) VALUES (?, ?, ?, ?)', [$userId, hash('sha256', $token), $expiresAt->toDateTimeString(), now()->toDateTimeString()]);

        return ['token' => $token, 'token_type' => 'Bearer', 'expires_at' => $expiresAt->toIso8601String()];
    }

    public function authenticate(string $token): ?array
    {
        if (! preg_match('/\A[a-f0-9]{64}\z/', $token)) {
            return null;
        }
        $row = $this->database->execute('SELECT user_id FROM api_tokens WHERE token_hash = ? AND expires_at > ?', [hash('sha256', $token), now()->toDateTimeString()])->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->users->find((int) $row['user_id']) : null;
    }

    public function revoke(string $token): void
    {
        $this->database->execute('DELETE FROM api_tokens WHERE token_hash = ?', [hash('sha256', $token)]);
    }
}
