<?php

namespace App\Repositories;

use App\Contracts\TransactionRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use PDO;

class PdoTransactionRepository extends PdoRepository implements TransactionRepository
{
    protected string $table = 'transactions';

    protected array $fields = ['user_id', 'product_id', 'quantity', 'unit_price', 'total_price'];

    public function forUser(int $userId, int $page = 1): LengthAwarePaginator
    {
        $page = max(1, min($page, 1_000_000));
        $total = (int) $this->database->execute('SELECT COUNT(*) FROM transactions WHERE user_id = ?', [$userId])->fetchColumn();
        $rows = $this->database->execute('SELECT t.*, p.name AS product_name FROM transactions t JOIN products p ON p.id = t.product_id WHERE t.user_id = ? ORDER BY t.id DESC LIMIT ? OFFSET ?', [$userId, 10, ($page - 1) * 10])->fetchAll(PDO::FETCH_ASSOC);

        return new LengthAwarePaginator(array_map($this->normalize(...), $rows), $total, 10, $page, ['path' => LengthAwarePaginator::resolveCurrentPath()]);
    }
}
