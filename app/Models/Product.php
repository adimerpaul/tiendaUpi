<?php

declare(strict_types=1);

namespace App\Models;

use App\Database\Database;
use PDO;

class Product
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        $stmt = $this->db->query(
            'SELECT id, nombre, precio, cantidad, marca, imagen, created_at, updated_at, deleted_at
             FROM products
             WHERE deleted_at IS NULL
             ORDER BY id ASC'
        );
        return $stmt->fetchAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, nombre, precio, cantidad, marca, imagen, created_at, updated_at, deleted_at
             FROM products
             WHERE id = :id AND deleted_at IS NULL'
        );
        $stmt->execute(['id' => $id]);
        $product = $stmt->fetch();
        return $product === false ? null : $product;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO products (nombre, precio, cantidad, marca, imagen)
             VALUES (:nombre, :precio, :cantidad, :marca, :imagen)'
        );
        $stmt->execute([
            'nombre' => $data['nombre'],
            'precio' => $data['precio'],
            'cantidad' => $data['cantidad'],
            'marca' => $data['marca'],
            'imagen' => $data['imagen'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE products
             SET nombre = :nombre, precio = :precio, cantidad = :cantidad, marca = :marca, imagen = :imagen, updated_at = CURRENT_TIMESTAMP
             WHERE id = :id AND deleted_at IS NULL'
        );

        $stmt->execute([
            'id' => $id,
            'nombre' => $data['nombre'],
            'precio' => $data['precio'],
            'cantidad' => $data['cantidad'],
            'marca' => $data['marca'],
            'imagen' => $data['imagen'] ?? null,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE products
             SET deleted_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
             WHERE id = :id AND deleted_at IS NULL'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }
}
