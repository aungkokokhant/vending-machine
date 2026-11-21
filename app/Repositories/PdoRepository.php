<?php

namespace App\Repositories;

use App\Contracts\CrudRepository;
use App\Database\PdoConnection;
use Brick\Math\BigDecimal;
use PDO;

abstract class PdoRepository implements CrudRepository
{
    protected string $table;

    protected array $fields;

    protected bool $softDeletes = false;

    public function __construct(protected PdoConnection $database) {}

    protected function activeCondition(): string
    {
        return $this->softDeletes ? 'deleted_at IS NULL' : '1 = 1';
    }

    protected function normalize(array $row): array
    {
        foreach (['id', 'user_id', 'product_id', 'quantity', 'quantity_available'] as $key) {
            if (isset($row[$key])) {
                $row[$key] = (int) $row[$key];
            }
        }
        foreach (['price', 'unit_price', 'total_price'] as $key) {
            if (isset($row[$key])) {
                $row[$key] = (string) BigDecimal::of((string) $row[$key])->toScale(3);
            }
        }

        return $row;
    }

    public function find(int $id): ?array
    {
        $row = $this->database->execute("SELECT * FROM {$this->table} WHERE id = ? AND {$this->activeCondition()}", [$id])->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->normalize($row) : null;
    }

    public function all(): array
    {
        return array_map($this->normalize(...), $this->database->execute("SELECT * FROM {$this->table} WHERE {$this->activeCondition()} ORDER BY id")->fetchAll(PDO::FETCH_ASSOC));
    }

    protected function writable(array $attributes): array
    {
        return array_intersect_key($attributes, array_flip($this->fields));
    }

    public function create(array $attributes): array
    {
        $attributes = $this->writable($attributes);
        $attributes['created_at'] = $attributes['updated_at'] = now()->toDateTimeString();
        $columns = implode(', ', array_keys($attributes));
        $placeholders = implode(', ', array_fill(0, count($attributes), '?'));
        $this->database->execute("INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})", array_values($attributes));

        return $this->find((int) $this->database->pdo()->lastInsertId());
    }

    public function update(int $id, array $attributes): ?array
    {
        $attributes = $this->writable($attributes);
        $attributes['updated_at'] = now()->toDateTimeString();
        $assignments = implode(', ', array_map(fn (string $key): string => "$key = ?", array_keys($attributes)));
        $this->database->execute("UPDATE {$this->table} SET {$assignments} WHERE id = ? AND {$this->activeCondition()}", [...array_values($attributes), $id]);

        return $this->find($id);
    }

    public function delete(int $id): bool
    {
        if ($this->softDeletes) {
            return $this->database->execute("UPDATE {$this->table} SET deleted_at = ?, updated_at = ? WHERE id = ? AND deleted_at IS NULL", [now()->toDateTimeString(), now()->toDateTimeString(), $id])->rowCount() === 1;
        }

        return $this->database->execute("DELETE FROM {$this->table} WHERE id = ?", [$id])->rowCount() === 1;
    }
}
