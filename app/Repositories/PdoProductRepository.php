<?php

namespace App\Repositories;

use App\Contracts\ProductRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use PDO;

class PdoProductRepository extends PdoRepository implements ProductRepository
{
    protected string $table = 'products';

    protected array $fields = ['name', 'slug', 'price', 'quantity_available'];

    protected bool $softDeletes = true;

    public function create(array $attributes): array
    {
        $attributes['slug'] = (Str::slug($attributes['name']) ?: 'product').'-'.Str::lower((string) Str::ulid());

        return parent::create($attributes);
    }

    public function update(int $id, array $attributes): ?array
    {
        unset($attributes['slug']);

        return parent::update($id, $attributes);
    }

    public function paginate(string $sort = 'name', string $direction = 'asc', int $page = 1): LengthAwarePaginator
    {
        $sort = in_array($sort, ['name', 'price', 'quantity_available', 'id'], true) ? $sort : 'name';
        $direction = strtolower($direction) === 'desc' ? 'DESC' : 'ASC';
        $page = max(1, min($page, 1_000_000));
        $total = (int) $this->database->execute('SELECT COUNT(*) FROM products WHERE deleted_at IS NULL')->fetchColumn();
        $rows = $this->database->execute("SELECT * FROM products WHERE deleted_at IS NULL ORDER BY {$sort} {$direction}, id ASC LIMIT ? OFFSET ?", [9, ($page - 1) * 9])->fetchAll(PDO::FETCH_ASSOC);

        return new LengthAwarePaginator(array_map($this->normalize(...), $rows), $total, 9, $page, ['path' => LengthAwarePaginator::resolveCurrentPath()]);
    }

    public function findBySlug(string $slug, bool $lock = false): ?array
    {
        $lockSql = $lock && $this->database->supportsRowLocks() ? ' FOR UPDATE' : '';
        $row = $this->database->execute('SELECT * FROM products WHERE slug = ? AND deleted_at IS NULL'.$lockSql, [$slug])->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->normalize($row) : null;
    }

    public function decrementStock(int $id, int $quantity): bool
    {
        return $this->database->execute('UPDATE products SET quantity_available = quantity_available - ?, updated_at = ? WHERE id = ? AND deleted_at IS NULL AND quantity_available >= ?', [$quantity, now()->toDateTimeString(), $id, $quantity])->rowCount() === 1;
    }
}
